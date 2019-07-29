<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 16/03/2016 - Ultima atualizacao: 28/06/2017
 * Descricao: Rotinas para acesso a banco de dados MS-SQL
 */
require_once( 'db_conn.php' );

class MsSqlConnection extends DbConnection {
	const TIL_STR_READ_UNCOMMITTED	= 1;
	const TIL_STR_READ_COMMITTED	= 2;
	const TIL_STR_REPEATABLE_READ	= 3;
	const TIL_STR_SERIALIZABLE		= 4;

	protected $lastStmt;

	public function __construct( $server_name, string $db_name = null, string $user_name = null, string $password = null, int $db_port = 1433 ) {
		parent::__construct( $server_name, $db_name, $user_name, $password, $db_port );
		$this->lastStmt = null;
	}

	public function connect(): bool {
		$connectionInfo = array( 'database' => $this->db_name, 'UID' => $this->user_name, 'PWD' => $this->password );
		$conn = sqlsrv_connect( $this->server_name, $connectionInfo );

		if( $conn === false ) {
			return false;
		}

		$this->connection = $conn;
		$sql = 'SELECT transaction_isolation_level FROM sys.dm_exec_sessions WHERE session_id = @@SPID';
		$til = $this->getField( $sql );

		switch( $til ) {
			case MsSqlConnection::TIL_STR_REPEATABLE_READ:
				$this->til = DbConnection::TIL_REPEATABLE_READ;
				break;

			case MsSqlConnection::TIL_STR_READ_COMMITTED:
				$this->til = DbConnection::TIL_READ_COMMITTED;
				break;

			case MsSqlConnection::TIL_STR_READ_UNCOMMITTED:
				$this->til = DbConnection::TIL_READ_UNCOMMITTED;
				break;

			case MsSqlConnection::TIL_STR_SERIALIZABLE:
				$this->til = DbConnection::TIL_SERIALIZABLE;
				break;

			default:
				$this->til = DbConnection::TIL_READ_COMMITTED;
				break;
		}

		return true;
	}

	public function disconnect(): bool {
		sqlsrv_close( $this->connection );
		parent::disconnect();
	}

	public function beginTrans(): bool {
		parent::beginTrans();
		return sqlsrv_begin_transaction( $this->connection );
	}

	public function commit(): bool {
		parent::commit();
		return sqlsrv_commit( $this->connection );
	}

	public function rollback(): bool {
		parent::rollback();
		return sqlsrv_rollback( $this->connection );
	}

	public function escapeStr( string $str ): string {
		return str_replace( "'", "''", $str );
	}

	public function quotedStr( string $str ): string {
		return "'" . $str . "'";
	}

	public function execSQL( string $sql ): bool {
		$this->lastStmt = sqlsrv_query( $this->connection, $sql );

		if( is_bool( $this->lastStmt ) ) {
			return $this->lastStmt;
		}

		return true;
	}

	public function getCursor( string $sql, int $fetch_type = DbCursor::FETCH_ASSOC, bool $fetch_all = false ) {
		$cur = new MsSqlCursor( $this->connection, $sql, $fetch_type, $fetch_all );

		if( ! isset( $cur ) || is_bool( $cur ) ) {
			return null;
		}

		if( $cur->_hasQueryError() ) {
			$cur->close();
			return null;
		}

		return $cur;
	}

	public function getLastError(): string {
		return sqlsrv_errors();
	}

	public function getLastKey(): int {
		return $this->getField( 'SELECT SCOPE_IDENTITY()' );
	}

	public function getNextKey( string $generator, int $increment = 1, string $field = null ): int {
		$sql = 'SELECT isnull( max( ' . $field . ' ), 0 ) + ' . $increment . ' AS key FROM ' . $generator;
		return $this->getField( $sql );
	}

	private function _getMerge( string $table, $keys, $fields, &$select_values, bool $ignoreInsert = false, string $tipo_merge = 'V', string $onUpdate = null ): string {
		if( is_string( $fields ) ) {
			$tFields = explode( ',', $fields );
		} else {
			$tFields = $fields;
			$fields = implode( ',', $fields );
		}

		if( is_string( $keys ) ) {
			$keys = explode( ',', $keys );
		}

		$cond = '';

		for( $i = 0; $i < count( $keys ); $i++ ) {
			$keys[$i] = trim( $keys[$i] );
			$cond .= 't.' . $keys[$i] . ' = s.' . $keys[$i] . ' AND ';
		}

		if( $cond != '' ) {
			$cond = substr( $cond, 0, strlen( $cond ) - 5 );
		}

		$fi = '';
		$fu = '';

		foreach( $tFields as $k => $f ) {
			$f = trim( $f );
			$fi .= 's.' . $f . ',';

			if( $onUpdate == null && ! in_array( $f, $keys ) ) {
				$fu .= $f . ' = s.' . $f . ',';
			}
		}

		if( $fi != '' ) {
			$fi = substr( $fi, 0, strlen( $fi ) - 1 );
		}

		if( $onUpdate != null ) {
			$fu = $onUpdate;
		} else {
			if( $fu != '' ) {
				$fu = substr( $fu, 0, strlen( $fu ) - 1 );
			}
		}

		if( $tipo_merge == 'S' ) {
			$sel_val = $select_values;
		} else {
			if( is_array( $select_values ) ) {
				$sel_val = 'VALUES ' . implode( ',', $select_values );
			} else {
				$sel_val = trim( $select_values );

				if( substr( $sel_val, 0, 1 ) != '(' ) {
					$sel_val = 'VALUES (' . $sel_val . ')';
				} else {
					$sel_val = 'VALUES ' . $sel_val;
				}
			}
		}

		return "MERGE INTO $table t USING( $sel_val ) AS s( $fields ) ON( $cond ) WHEN MATCHED THEN UPDATE SET $fu" .
				($ignoreInsert ? '' : " WHEN NOT MATCHED THEN INSERT( $fields ) VALUES( $fi )") . ';';
	}

	public function getMergeSelect( string $table, $keys, $fields, $select, bool $ignoreInsert = false, string $onUpdate = null ): string {
		return $this->_getMerge( $table, $keys, $fields, $select, $ignoreInsert, 'S', $onUpdate );
	}

	public function getMergeValues( string $table, $keys, $fields, $values, bool $ignoreInsert = false, string $onUpdate = null ): string {
		return $this->_getMerge( $table, $keys, $fields, $values, $ignoreInsert, 'V', $onUpdate );
	}

	public function getRowsAffected(): int {
		if( is_bool( $this->lastStmt ) ) {
			return 0;
		}

		return sqlsrv_rows_affected( $this->lastStmt );
	}

	public function limit( string $sql, int $limit, int $offset = null ): string {
		if( $offset != null ) {
			return $sql . ' OFFSET ' . $offset . ' ROWS FETCH NEXT ' . $limit . ' ROWS ONLY';
		} else {
			$pos = stripos( $sql, 'SELECT' );

			if( $pos === false ) {
				return $sql;
			}

			return substr( $sql, 0, 6 ) . ' TOP ' . $limit . substr( $sql, $pos + 6 );
		}
	}

	public function prepare( string $sql ): DbStatement {
		return new MsSqlStatement( $this->connection, $sql );
	}

	public function getFunctions(): DbFunctions {
		if( ! isset( $this->funcs ) ) {
			$this->funcs = new MsSqlFunctions();
		}

		return $this->funcs;
	}

	public function setTransactionIsolation( int $til ): DbConnection {
		$sql = 'SET TRANSACTION ISOLATION LEVEL ';

		switch( $til ) {
			case DbConnection::TIL_REPEATABLE_READ:
				$sql .= 'REPEATABLE READ';
				break;

			case DbConnection::TIL_READ_COMMITED:
				$sql .= 'READ COMMITTED';
				break;

			case DbConnection::TIL_READ_UNCOMMITED:
				$sql .= 'READ UNCOMMITTED';
				break;

			case DbConnection::TIL_SERIALIZABLE:
				$sql .= 'SERIALIZABLE';
				break;
		}

		$res = $this->execSQL( $sql );

		if( $res ) {
			parent::setTransactionIsolation( $til );
		}

		return $res;
	}
}

class MsSqlCursor extends DbCursor {
	public function __construct( &$conn, string $sql, int $fetch_type = DbCursor::FETCH_ASSOC, bool $fetch_all = false ) {
		parent::__construct( $conn, $sql, $fetch_type, $fetch_all );
		$p = array( 'Scrollable' => SQLSRV_CURSOR_STATIC );
		$this->cur = sqlsrv_query( $conn, $sql, null, $p );

		if( is_bool( $this->cur ) ) {
			$this->cur = null;
		}
	}

	public function close(): bool {
		if( $this->cur != null ) {
			sqlsrv_free_stmt( $this->cur );
		}

		parent::close();
	}

	public function next( int $fetch_type = DbCursor::FETCH_DEFAULT_OR_ASSOC ) {
		if( $this->cur == null ) {
			return null;
		}

		return sqlsrv_fetch_array( $this->cur, $this->getDBFetchType( $fetch_type ) );
	}

	public function getNumRows(): int {
		return ( $this->cur == null ? 0 : sqlsrv_num_rows( $this->cur ) );
	}

	public function getNumFields(): int {
		return ( $this->cur == null ? 0 : sqlsrv_num_fields( $this->cur ) );
	}

	protected function getDBFetchType( int $fetch_type ): int {
		$fetch_type = parent::getDBFetchType( $fetch_type );

		if( $fetch_type == DbCursor::FETCH_BOTH )
			return SQLSRV_FETCH_BOTH;
		elseif( $fetch_type == DbCursor::FETCH_NUMERIC )
			return SQLSRV_FETCH_NUMERIC;
		else
			return SQLSRV_FETCH_ASSOC;
	}

	public function getFieldsInfo(): array {
		$infos = array();
		$metas = sqlsrv_field_metadata( $this->cur );

		foreach( $metas as $meta ) {
			$infos[ $meta['Name'] ] = [ 'type' => $meta['Type'], 'size' => $meta['Size'], 'precision' => $meta['Precision'], 'scale' => $meta['Scale'],
										'scale' => $meta['Scale'], 'nullable' => $meta['Nullable'], 'default' => null ];
		}

		return $infos;
	}
}

class MsSqlStatement extends DbStatement {
	protected $params;
	protected $references;

	public function __construct( DbConnection &$conn, string $sql ) {
		$param_count = substr_count( '?', $sql );
		$this->params = array();

		for( $i = 0; $i < $this->param_count; $i++ ){
			$this->params[$i] = null;
		}

		$this->references = array();

		for( $i = 0; $i < $this->param_count; $i++ ) {
			$this->references[$i] = &$this->params[$i];
		}

		$this->stmt = sqlsrv_prepare( $conn, $sql, $this->references );
	}

	public function __destruct() {
		sqlsrv_free_stmt( $this->stmt );
	}

	public function execute( array $params ): bool {
		foreach( $params as $i => $v ) {
			$this->params[$i] = $v;
		}

		return sqlsrv_execute( $this->stmt );
	}
}

class MsSqlFunctions extends DbFunctions {
	private function getCastType( int $ct, int $size = null, int $prec = null ): string {
		switch( $ct ) {
			case DbFunctions::ctBinary:
				return 'BINARY' . ($size == null ? '' : '(' . $size . ')');

			case DbFunctions::ctChar:
				return 'VARCHAR' . ($size == null ? '' : '(' . $size . ')');

			case DbFunctions::ctInteger:
				return 'INT';

			case DbFunctions::ctUnsigned:
				return 'INT';

			case DbFunctions::ctDecimal:
				return 'DECIMAL' . ($size == null ? '' : '(' . $size . ($prec == null ? '' : ', ' . $prec) . ')');

			case DbFunctions::ctDatetime:
				return 'DATETIME';

			case DbFunctions::ctDate:
				return 'DATE';

			case DbFunctions::ctTime:
				return 'TIME';
		}
	}

	public function avg( string $s ): string {
		return "avg( $s )";
	}

	public function cast( string $v, int $t, int $s = null, int $p = null ): string {
		return "cast( $v AS " . $this->getCastType( $t, $s, $p ) . ' )';
	}

	public function ceil( string $v ): string {
		return "ceiling( $v )";
	}

	public function coalesce( $values ): string {
		if( ! is_array( $values ) ) {
			$values = func_get_args();
		}

		return 'coalesce( ' . implode( ', ', $values ) . ' )';
	}

	public function concat( $args ): string {
		if( ! is_array( $args ) ) {
			$args = func_get_args();
		}

		return implode( '+', $args );
	}

	public function count( string $s, bool $distinct = false ): string {
		if( $distinct ) {
			return "count( DISTINCT $s )";
		}

		return "count( $s )";
	}

	public function date(): string {
		return 'convert( date, sysDateTime() )';
	}

	private function getDatePart( int $part ): string {
		switch( $part ) {
		case DbFunctions::deYear:
			return 'yyyy';

		case DbFunctions::deMonth:
			return 'mm';

		case DbFunctions::deDay:
			return 'dd';

		case DbFunctions::deHour:
			return 'hh';

		case DbFunctions::deMinute:
			return 'mi';

		case DbFunctions::deSecond:
			return 'ss';

		case DbFunctions::deMicroSec:
			return 'mcs';
		}

		return '';
	}

	public function dateAdd( string $date, int $part, $number ): string {
		$p = $this->getDatePart( $part );
		return "dateadd( $p, $number, $date )";
	}

	public function dateExtract( string $field, int $part ): string {
		switch( $part ) {
		case DbFunctions::deYear:
			return "datepart( yyyy, $field )";

		case DbFunctions::deMonth:
			return "datepart( mm, $field )";

		case DbFunctions::deDay:
			return "datepart( dd, $field )";

		case DbFunctions::deHour:
			return "datepart( hh, $field )";

		case DbFunctions::deMinute:
			return "datepart( mi, $field )";

		case DbFunctions::deSecond:
			return "datepart( ss, $field )";

		case DbFunctions::deMicroSec:
			return "datepart( mcs, $field )";

		case DbFunctions::deDayOfWeek:
			return "datepart( dw, $field )";

		case DbFunctions::deDayOfYear:
			return "datepart( dy, $field )";

		case DbFunctions::deWeekOfYear:
			return "datepart( ww, $field )";
		}

		return "$field";
	}

	public function floor( string $v ): string {
		return "floor( $v )";
	}

	private function _iifnest( array &$values, string $oper, int $start = 0, int $end = 1 ): string {
		$r = 'iif(' . $values[$start] . $oper . $values[$end] . ', ';

		if( $start == count( $values ) - 2 && $end == count( $values ) - 1 ) {
			$r .= $values[$start] . ', ' . $values[$end];

		} elseif( $start == count( $values ) - 2 ) {
			$r .= _iifnest( $values, $oper, $start, $end + 1 ) . ', ' . $values[$end];

		} elseif( $end == count( $values ) - 1 ) {
			$r .= $values[$start] . ', ' . $values[$end];

		} else {
			$r .= _iifnest( $values, $oper, $start, $end + 1 ) . ', ' . _iifnest( $values, $oper, $end, $end + 1 );
		}

		return $r . ')';
	}

	public function greatest( $values ): string {
		if( ! is_array( $values ) ) {
			$values = func_get_args();
		}

		return _iifnest( $values, ' > ' );
	}

	public function iif( string $x, string $t, string $f ): string {
		return "iif( $x, $t, $f )";
	}

	public function ifnull( string $x, string $f ): string {
		return "isnull( $x, $f )";
	}

	public function isnull( string $x ): string {
		return "(iif( ($x) IS NULL, 0, 1 ) = 0)";
	}

	public function least( $values ): string {
		if( ! is_array( $values ) ) {
			$values = func_get_args();
		}

		return _iifnest( $values, ' < ' );
	}

	public function lower( string $s ): string {
		return "lower( $s )";
	}

	public function ltrim( string $s ): string {
		return "ltrim( $s )";
	}

	public function max( string $s ): string {
		return "max( $s )";
	}

	public function min( string $s ): string {
		return "min( $s )";
	}

	public function now( int $prec = 0 ): string {
		return 'getDate()';
	}

	public function nullSafeEqual( string $op1, string $op2 ): string {
		return "($op1 = $op2 OR ($op1 IS NULL AND $op2 IS NULL))";
	}

	public function replace( string $s, string $f, string $t ): string {
		return "replace( $s, $f, $t )";
	}

	public function round( string $v, int $p ): string {
		return "round( $v, $p, 0 )";
	}

	public function rtrim( string $s ): string {
		return "rtrim( $s )";
	}

	public function sum( string $s ): string {
		return "sum( $s )";
	}

	public function substr( string $s, int $i, int $t ): string {
		return "substring( $s, $i, $t )";
	}

	public function time( int $prec = 0 ): string {
		return 'convert( time, sysDateTime() )';
	}

	public function trim( string $s ): string {
		return "ltrim( rtrim( $s ) )";
	}

	public function trunc( string $v, int $p ): string {
		return "round( $v, $p, 1 )";
	}

	public function upper( string $s ): string {
		return "upper( $s )";
	}

	public function value( string $v ): string {
		return "s.$v";
	}
}

//~ $c = new MssqlConnection( '', '', '', '' );
//~ echo( $c->getMergeSQL( 'number_route', 'tn', 'tn, route, rn1, recipienteot, cnl, linetype, broadcastStartTimestamp, activationTimestamp, lnptype, tn55', "valores dos campos" ) );
?>

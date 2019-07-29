<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 16/03/2016 - Ultima atualizacao: 10/10/2017
 * Descricao: Rotinas para acesso a banco de dados MySQL
 */
require_once( 'db_conn.php' );

class MySqlConnection extends DbConnection {
	const TIL_STR_READ_COMMITTED	= 'READ-COMMITTED';
	const TIL_STR_READ_UNCOMMITTED	= 'READ-UNCOMMITTED';
	const TIL_STR_REPEATABLE_READ	= 'REPEATABLE-READ';
	const TIL_STR_SERIALIZABLE		= 'SERIALIZABLE';

	public function __construct( $server_name, string $db_name = null, string $user_name = null, string $password = null, int $db_port = 3306 ) {
		parent::__construct( $server_name, $db_name, $user_name, $password, $db_port );
	}

	public function connect(): bool {
		$host = $this->server_name . (! isset( $this->db_port ) || $this->db_port == 3306 ? '' : ':' . $this->db_port);
		$conn = mysqli_connect( $host, $this->user_name, $this->password, $this->db_name );

		if( $conn === false ) {
			return false;
		}

		$this->connection = $conn;
		$sql = 'SELECT @@tx_isolation';
		$til = $this->getField( $sql );

		switch( $til ) {
			case MySqlConnection::TIL_STR_REPEATABLE_READ:
				$this->til = DbConnection::TIL_REPEATABLE_READ;
				break;

			case MySqlConnection::TIL_STR_READ_COMMITTED:
				$this->til = DbConnection::TIL_READ_COMMITTED;
				break;

			case MySqlConnection::TIL_STR_READ_UNCOMMITTED:
				$this->til = DbConnection::TIL_READ_UNCOMMITTED;
				break;

			case MySqlConnection::TIL_STR_SERIALIZABLE:
				$this->til = DbConnection::TIL_SERIALIZABLE;
				break;

			default:
				$this->til = DbConnection::TIL_REPEATABLE_READ;
				break;
		}

		return true;
	}

	public function disconnect(): bool {
		$res = mysqli_close( $this->connection );
		parent::disconnect();
		return $res;
	}

	public function beginTrans(): bool {
		parent::beginTrans();
		return mysqli_begin_transaction( $this->connection );
	}

	public function commit(): bool {
		parent::commit();
		return mysqli_commit( $this->connection );
	}

	public function rollback(): bool {
		parent::rollback();
		return mysqli_rollback( $this->connection );
	}

	public function escapeStr( string $str ): string {
		return mysqli_real_escape_string( $this->connection, $str );
	}

	public function quotedStr( string $str ): string {
		return "'" . $str . "'";
	}

	public function execSQL( string $sql ): bool {
		$res = mysqli_query( $this->connection, $sql );

		if( is_bool( $res ) ) {
			return $res;
		}

		return true;
	}

	public function getCursor( string $sql, int $fetch_type = DbCursor::FETCH_ASSOC, bool $fetch_all = false ) {
		$cur = new MySqlCursor( $this->connection, $sql, $fetch_type, $fetch_all );

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
		return mysqli_error( $this->connection );
	}

	public function getLastKey(): int {
		return mysqli_insert_id( $this->connection );
	}

	public function getNextKey( string $generator, int $increment = 1, string $field = null ): int {
		$sql = 'SELECT ifnull( max( ' . $field . ' ), 0 ) + ' . $increment . ' AS key FROM ' . $generator;
		return (int) $this->getField( $sql );
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

		for( $i = 0; $i < count( $keys ); $i++ ) {
			$keys[$i] = trim( $keys[$i] );
		}

		if( $onUpdate != null ) {
			$fu = $onUpdate;
		} else {
			$fu = '';

			foreach( $tFields as $k => $f ) {
				$f = trim( $f );

				if( ! in_array( $f, $keys ) ) {
					$fu .= $f . ' = Values(' . $f . '),';
				}
			}

			if( $fu != '' ) {
				$fu = substr( $fu, 0, strlen( $fu ) - 1 );
			}
		}

		if( $tipo_merge == 'S' ) {
			$sel_val = $select_values;
		} elseif( is_array( $select_values ) ) {
			$sel_val = 'VALUES' . implode( ',', $select_values );
		} else {
			$sel_val = trim( $select_values );

			if( substr( $sel_val, 0, 1 ) != '(' ) {
				$sel_val = 'VALUES(' . $sel_val . ')';
			} else {
				$sel_val = 'VALUES' . $sel_val;
			}
		}

		return 'INSERT ' . ($ignoreInsert ? 'IGNORE ' : '') . "INTO $table( $fields ) $sel_val ON DUPLICATE KEY UPDATE $fu;";
	}

	public function getMergeSelect( string $table, $keys, $fields, $select, bool $ignoreInsert = false, string $onUpdate = null ): string {
		return $this->_getMerge( $table, $keys, $fields, $select, $ignoreInsert, 'S', $onUpdate );
	}

	public function getMergeValues( string $table, $keys, $fields, $values, bool $ignoreInsert = false, string $onUpdate = null ): string {
		return $this->_getMerge( $table, $keys, $fields, $values, $ignoreInsert, 'V', $onUpdate );
	}

	public function getRowsAffected(): int {
		return mysqli_affected_rows( $this->connection );
	}

	public function limit( string $sql, int $limit, int $offset = null ): string {
		return $sql . ' LIMIT ' . $limit . ($offset == null ? '' : ' OFFSET ' . $offset );
	}

	public function prepare( string $sql ): DbStatement {
		return new MySqlStatement( $this->connection, $sql );
	}

	public function getFunctions(): DbFunctions {
		if( ! isset( $this->funcs ) ) {
			$this->funcs = new MySqlFunctions();
		}

		return $this->funcs;
	}

	public function setTransactionIsolation( int $til ): DbConnection {
		$sql = 'SET SESSION TRANSACTION ISOLATION LEVEL ';

		switch( $til ) {
			case DbConnection::TIL_REPEATABLE_READ:
				$sql .= 'REPEATABLE READ';
				break;

			case DbConnection::TIL_READ_COMMITTED:
				$sql .= 'READ COMMITTED';
				break;

			case DbConnection::TIL_READ_UNCOMMITTED:
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

class MySqlCursor extends DbCursor {
	public function __construct( &$conn, string $sql, int $fetch_type = DbCursor::FETCH_ASSOC, bool $fetch_all = false ) {
		parent::__construct( $conn, $sql, $fetch_type, $fetch_all );

		if( $fetch_all ) {
			$this->cur = mysqli_query( $conn, $sql );
		} else {
			$this->cur = mysqli_query( $conn, $sql, MYSQLI_USE_RESULT );
		}

		if( is_bool( $this->cur ) ) {
			$this->cur = null;
		}
	}

	public function close(): bool {
		if( $this->cur != null ) {
			mysqli_free_result( $this->cur );
		}

		parent::close();
		return true;
	}

	public function next( int $fetch_type = DbCursor::FETCH_DEFAULT_OR_ASSOC ) {
		if( $this->cur == null ) {
			return null;
		}

		return mysqli_fetch_array( $this->cur, $this->getDBFetchType( $fetch_type ) );
	}

	public function getNumRows(): int {
		return ( $this->cur == null ? 0 : mysqli_num_rows( $this->cur ) );
	}

	public function getNumFields(): int {
		return ( $this->cur == null ? 0 : mysqli_num_fields( $this->cur ) );
	}

	protected function getDBFetchType( int $fetch_type ): int {
		$fetch_type = parent::getDBFetchType( $fetch_type );

		if( $fetch_type == DbCursor::FETCH_BOTH )
			return MYSQLI_BOTH;
		elseif( $fetch_type == DbCursor::FETCH_NUMERIC )
			return MYSQLI_NUM;
		else
			return MYSQLI_ASSOC;
	}

	public function getFieldsInfo(): array {
		$infos = array();
		$metas = mysqli_fetch_fields( $this->cur );

		foreach( $metas as $meta ) {
			$infos[ $meta->name ] = [ 'type' => $meta->type, 'size' => $meta->length, 'precision' => $meta->decimals,
										'scale' => null, 'nullable' => ($meta->flags & 1), 'default' => $meta->def ];
		}

		return $infos;
	}
}

class MySqlStatement extends DbStatement {
	protected $param_types;

	public function __construct( DbConnection &$conn, string $sql ) {
		$this->stmt = mysqli_prepare( $conn, $sql );
	}

	public function __destruct() {
		mysqli_stmt_close( $this->stmt );
	}

	public function execute( array $params ): bool {
		$param_types = '';
		$param_str = '';

		foreach( $params as $i => $v ) {
			switch( gettype( $v ) ) {
				case 'integer':
					$param_types .= 'i';
					$param_str .= ', ' . $v;
					break;

				case 'double':
					$param_types .= 'd';
					$param_str .= ', ' . $v;
					break;

				default:
					$param_types .= 's';
					$param_str .= ", '" . $v . "'";
					break;
			}
		}

		$command = '$res = mysqli_stmt_bind_param( $this->stmt, "' . $param_types . '"' . $param_str . ' );';
		eval( $command );

		if( $res ) {
			return mysqli_stmt_execute( $this->stmt );
		}

		return false;
	}
}

class MySqlFunctions extends DbFunctions {
	private function getCastType( int $ct, int $size = null, int $prec = null ): string {
		switch( $ct ) {
			case DbFunctions::ctBinary:
				return 'BINARY' . ($size == null ? '' : '(' . $size . ')');

			case DbFunctions::ctChar:
				return 'CHAR' . ($size == null ? '' : '(' . $size . ')');

			case DbFunctions::ctInteger:
				return 'SIGNED INTEGER';

			case DbFunctions::ctUnsigned:
				return 'UNSIGNED INTEGER';

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
		return "ceil( $v )";
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

		return 'concat( ' . implode( ',', $args ) . ' )';
	}

	public function count( string $s, bool $distinct = false ): string {
		if( $distinct ) {
			return "count( DISTINCT $s )";
		}

		return "count( $s )";
	}

	public function date(): string {
		return 'curdate()';
	}

	private function getDatePart( int $part ): string {
		switch( $part ) {
		case DbFunctions::deYear:
			return 'YEAR';

		case DbFunctions::deMonth:
			return 'MONTH';

		case DbFunctions::deDay:
			return 'DAY';

		case DbFunctions::deHour:
			return 'HOUR';

		case DbFunctions::deMinute:
			return 'MINUTE';

		case DbFunctions::deSecond:
			return 'SECOND';

		case DbFunctions::deMicroSec:
			return 'MICROSECOND';
		}

		return '';
	}

	public function dateAdd( string $date, int $part, $number ): string {
		$p = $this->getDatePart( $part );

		if( is_numeric( $number ) && $number < 0 ) {
			$number *= -1;
			return "date_sub( $date, INTERVAL $number $p )";
		}

		return "date_add( $date, INTERVAL $number $p )";
	}

	public function dateExtract( string $field, int $part ): string {
		switch( $part ) {
		case DbFunctions::deYear:
			return "year( $field )";

		case DbFunctions::deMonth:
			return "month( $field )";

		case DbFunctions::deDay:
			return "day( $field )";

		case DbFunctions::deHour:
			return "hour( $field )";

		case DbFunctions::deMinute:
			return "minute( $field )";

		case DbFunctions::deSecond:
			return "second( $field )";

		case DbFunctions::deMicroSec:
			return "microsecond( $field )";

		case DbFunctions::deDayOfWeek:
			return "dayofweek( $field )";

		case DbFunctions::deDayOfYear:
			return "dayofyear( $field )";

		case DbFunctions::deWeekOfYear:
			return "week( $field, 6 )";
		}

		return "$field";
	}

	public function floor( string $v ): string {
		return "floor( $v )";
	}

	public function greatest( $values ): string {
		if( ! is_array( $values ) ) {
			$values = func_get_args();
		}

		return 'greatest( ' . implode( ', ', $values ) . ' )';
	}

	public function iif( string $x, string $t, string $f ): string {
		return "if( $x, $t, $f )";
	}

	public function ifnull( string $x, string $f ): string {
		return "ifnull( $x, $f )";
	}

	public function isnull( string $x ): string {
		return "isnull( $x )";
	}

	public function least( $values ): string {
		if( ! is_array( $values ) ) {
			$values = func_get_args();
		}

		return 'least( ' . implode( ', ', $values ) . ' )';
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
		return "now( $prec )";
	}

	public function nullSafeEqual( string $op1, string $op2 ): string {
		return "($op1 <=> $op2)";
	}

	public function replace( string $s, string $f, string $t ): string {
		return "replace( $s, $f, $t )";
	}

	public function round( string $v, int $p ): string {
		return "round( $v, $p )";
	}

	public function rtrim( string $s ): string {
		return "rtrim( $s )";
	}

	public function substr( string $s, int $i, int $t ): string {
		return "substr( $s, $i, $t )";
	}

	public function sum( string $s ): string {
		return "sum( $s )";
	}

	public function time( int $prec = 0 ): string {
		return "curtime( $prec )";
	}

	public function trim( string $s ): string {
		return "trim( $s )";
	}

	public function trunc( string $v, int $p ): string {
		return "truncate( $v, $p )";
	}

	public function upper( string $s ): string {
		return "upper( $s )";
	}

	public function value( string $v ): string {
		return "Values( $v )";
	}
}

//~ $c = new MysqlConnection( '', '', '', '' );
//~ echo( $c->getMergeSQL( 'number_route', 'tn', 'tn, route, rn1, recipienteot, cnl, linetype, broadcastStartTimestamp, activationTimestamp, lnptype, tn55', "valores dos campos" ) );
?>

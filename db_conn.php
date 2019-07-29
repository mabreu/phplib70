<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 16/03/2016 - Ultima atualizacao: 15/02/2019
 * Descricao: Rotinas para acesso a banco de dados
 */

abstract class DbConnection {
	const TIL_NONE				= 0;
	const TIL_READ_UNCOMMITTED	= 1;
	const TIL_READ_COMMITTED	= 2;
	const TIL_REPEATABLE_READ	= 3;
	const TIL_SERIALIZABLE		= 4;

	const TEM_DEFAULT	= 0;
	const TEM_COMMIT	= 1;
	const TEM_ROLLBACK	= 2;

	private $inConnection;
	private $lastErrorMsg;
	private $end_method;

	protected $funcs;
	protected $connection;
	protected $db_name;
	protected $db_port;
	protected $password;
	protected $server_name;
	protected $user_name;
	protected $tFields;
	protected $tKeys;
	protected $til;

	protected function __construct( $server_name, string $db_name = null, string $user_name = null, string $password = null, int $db_port = null ) {
		if( is_array( $server_name ) ) {
			$this->server_name = $server_name['host'];
			$this->db_name = $server_name['database'];
			$this->user_name = ($server_name['user'] ?? null);
			$this->password = ($server_name['password'] ?? null);
			$this->db_port = ($server_name['port'] ?? null);
		} else {
			$this->server_name = $server_name;
			$this->db_name = $db_name;
			$this->user_name = $user_name;
			$this->password = $password;
			$this->db_port = $db_port;
		}

		$this->end_method = DbConnection::TEM_DEFAULT;
		$this->inConnection = false;
	}

	abstract protected function connect(): bool;
	abstract protected function escapeStr( string $str ): string;
	abstract protected function quotedStr( string $str ): string;
	abstract protected function execSQL( string $sql ): bool;
	abstract protected function limit( string $sql, int $limit, int $offset = null ): string;
	abstract protected function prepare( string $sql ): DbStatement;
	abstract protected function getCursor( string $sql, int $fetch_type = DbCursor::FETCH_ASSOC, bool $fetch_all = false );
	abstract protected function getLastError(): string;
	abstract protected function getLastKey(): int;
	abstract protected function getNextKey( string $generator, int $increment = 1, string $field = null ): int;
	abstract protected function getMergeSelect( string $table, $keys, $fields, $select, bool $ignoreInsert = false, string $onUpdate = null ): string;
	abstract protected function getMergeValues( string $table, $keys, $fields, $values, bool $ignoreInsert = false, string $onUpdate = null ): string;
	abstract protected function getRowsAffected(): int;
	abstract protected function getFunctions(): DbFunctions;

	protected function disconnect(): bool {
		if( $this->inTransaction() ) {
			if( $this->getEndMethod() == DbConnection::TEM_COMMIT ) {
				$this->commit();
 			} elseif( $this->getEndMethod() == DbConnection::TEM_ROLLBACK ) {
				$this->rollback();
			}
		}

		$this->connection = null;
		return true;
	}

	protected function beginTrans(): bool {
		$this->inConnection = true;
		return true;
	}

	protected function commit(): bool {
		$this->inConnection = false;
		return true;
	}

	protected function rollback(): bool {
		$this->inConnection = false;
		return true;
	}

	public function inTransaction(): bool {
		return $this->inConnection;
	}

	public function getTransactionIsolation(): int {
		return $this->til;
	}

	public function setTransactionIsolation( int $til ): DbConnection {
		$this->til = $til;
		return $this;
	}

	public function setEndMethod( int $end_method ): DbConnection {
		$this->end_method = $end_method;
		return $this;
	}

	public function getEndMethod(): int {
		return $this->end_method;
	}

	public function format( $value, bool $nullIfEmpty = false, string $fmt = null ): string {
		if( $value === null || ( $nullIfEmpty && empty( $value ) ) ) {
			return 'null';
		}

		$t = getType( $value );

		if( $t == 'string' ) {
			if( $fmt == null ) {
				return $this->quotedStr( $this->escapeStr( $value ) );
			} else {
				return $this->quotedStr( $this->escapeStr( sprintf( $fmt, $value ) ) );
			}

		} elseif( $t == 'integer' || $t == 'double' ) {
			if( $fmt == null ) {
				return '' . $value;
			} else {
				return sprintf( $fmt, $value );
			}

		} elseif( $t == 'boolean' ) {
			return $this->quotedStr( $value ? 'S' : 'N' );

		} elseif( $t == 'object' && $value instanceOf DateTime ) {
			if( $fmt == null ) {
				return $this->quotedStr( $value->format( 'Y-m-d H:i:s' ) );
			} else {
				return $this->quotedStr( $value->format( $fmt ) );
			}

		} else {
			if( $fmt == null ) {
				return $this->quotedStr( $this->escapeStr( '' . $value ) );
			} else {
				return $this->quotedStr( $this->escapeStr( sprintf( $fmt, $value ) ) );
			}
		}

		return 'null';
	}

	public function formatAll( $values ): string {
		if( ! is_array( $values ) ) {
			$values = func_get_args();
		}

		foreach( $values as $k => $v ) {
			$values[$k] = $this->format( $v );
		}

		return implode( ',', $values );
	}

	public function formatCond( $value, string $oper = '=', bool $nullIfEmpty = false, $fmt = null ): string {
		if( $nullIfEmpty && empty( $value ) ) {
			$value = null;
		}

		if( $value === null ) {
			if( $oper == '=' ) {
				return ' IS NULL';
			} else {
				return ' IS NOT NULL';
			}
		} else {
			return ' ' . $oper . ' ' . $this->format( $value, $nullIfEmpty, $fmt );
		}
	}

	public function getDataPacket( string $sql, bool $assoc = true ) {
		$cur = $this->getCursor( $sql );

		if( $cur == null ) {
			return null;
		}

		$res = array();
		$assoc = ($assoc ? DbCursor::FETCH_ASSOC : DbCursor::FETCH_NUMERIC);

		while( $row = $cur->next( $assoc ) ) {
			array_push( $res, $row );
		}

		$cur->close();
		unset( $cur );
		return $res;
	}

	public function getDataRow( string $sql, bool $assoc = true ) {
		$cur = $this->getCursor( $sql );

		if( $cur == null ) {
			return null;
		}

		$row = $cur->next( ($assoc ? DbCursor::FETCH_ASSOC : DbCursor::FETCH_NUMERIC) );
		$cur->close();
		unset( $cur );
		return $row;
	}

	public function getField( string $sql, $field = null ) {
		if( $field == null ) {
			$row = $this->getDataRow( $sql, false );
			return $row[0];
		} else {
			$row = $this->getDataRow( $sql );
			return $row[$field];
		}
	}

	public function getLastErrorMessage(): string {
		return $this->lastErrorMsg;
	}

	public function getValue( $chave ) {
		return $this->getField( 'SELECT valor FROM variaveis WHERE chave = ' . $this->format( $chave ) );
	}

	public function setValue( $chave, $valor ): bool {
		if( $valor === null ) {
			$sql = 'DELETE FROM variaveis WHERE chave = ' . $this->format( $chave );
		} else {
			$sql = $this->getMergeValues( 'variaveis', 'chave', 'chave,valor', $this->formatAll( $chave, $valor ) );
		}

		return $this->execSQL( $sql );
	}
}

abstract class DbCursor {
	const FETCH_NONE	= 0;
	const FETCH_NUMERIC	= 1;
	const FETCH_ASSOC	= 2;
	const FETCH_BOTH	= 3;
	const FETCH_DEFAULT				= 90;
	const FETCH_DEFAULT_OR_NUMERIC	= 91;
	const FETCH_DEFAULT_OR_ASSOC	= 92;
	const FETCH_DEFAULT_OR_BOTH		= 93;

	protected $cur;
	protected $def_fetch_type;

	protected function __construct( &$conn, string $sql, int $fetch_type = self::FETCH_ASSOC, bool $fetch_all = false ) {
		$this->def_fetch_type = ($fetch_type == null ? self::FETCH_ASSOC : $fetch_type);
	}

	abstract protected function next( int $fetch_type = self::FETCH_DEFAULT_OR_ASSOC );
	abstract protected function getNumRows(): int;
	abstract protected function getNumFields(): int;
	abstract protected function getFieldsInfo(): array;

	protected function getDBFetchType( int $fetch_type ): int {
		if( $fetch_type >= self::FETCH_DEFAULT || $fetch_type == null ) {
			if( $this->def_fetch_type == self::FETCH_DEFAULT || $this->def_fetch_type == self::FETCH_NONE ) {
				if( $fetch_type == self::FETCH_DEFAULT_OR_BOTH )
					return self::FETCH_BOTH;
				elseif( $this->def_fetch_type == self::FETCH_DEFAULT_OR_NUMERIC )
					return self::FETCH_NUMERIC;
				else
					return self::FETCH_ASSOC;
			}

			return $this->def_fetch_type;
		}

		return $fetch_type;
	}

	public function _hasQueryError(): bool {
		return ! isset( $this->cur );
	}

	protected function close() {
		$this->cur = null;
	}
}

abstract class DbStatement {
	protected $stmt;

	abstract protected function __construct( DbConnection &$conn, string $sql );
	abstract protected function __destruct();
	abstract protected function execute( array $params ): bool;
}

abstract class DbFunctions {
	const ctBinary		= 1;
	const ctChar		= 2;
	const ctInteger		= 3;
	const ctUnsigned	= 4;
	const ctDecimal		= 5;
	const ctDatetime	= 6;
	const ctDate		= 7;
	const ctTime		= 8;
	const ctJson		= 9;

	const deYear		= 1;
	const deMonth		= 2;
	const deDay			= 3;
	const deHour		= 4;
	const deMinute		= 5;
	const deSecond		= 6;
	const deMicroSec	= 7;
	const deDayOfWeek	= 8;
	const deDayOfYear	= 9;
	const deWeekOfYear	= 10;

	abstract protected function avg( string $s ): string;
	abstract protected function cast( string $v, int $t, int $s = null, int $p = null ): string;
	abstract protected function ceil( string $v ): string;
	abstract protected function coalesce( $values ): string;
	abstract protected function concat( $args ): string;
	abstract protected function count( string $s, bool $distinct = false ): string;
	abstract protected function date(): string;
	abstract protected function dateAdd( string $date, int $part, $number ): string;
	abstract protected function dateExtract( string $field, int $part ): string;
	abstract protected function floor( string $v ): string;
	abstract protected function greatest( $values ): string;
	abstract protected function iif( string $x, string $t, string $f ): string;
	abstract protected function ifnull( string $x, string $f ): string;
	abstract protected function isnull( string $x ): string;
	abstract protected function least( $values ): string;
	abstract protected function lower( string $s ): string;
	abstract protected function ltrim( string $s ): string;
	abstract protected function max( string $s ): string;
	abstract protected function min( string $s ): string;
	abstract protected function now( int $prec = 0 ): string;
	abstract protected function nullSafeEqual( string $op1, string $op2 ): string;
	abstract protected function time( int $prec = 0 ): string;
	abstract protected function replace( string $s, string $f, string $t ): string;
	abstract protected function round( string $v, int $p ): string;
	abstract protected function rtrim( string $s ): string;
	abstract protected function substr( string $s, int $i, int $t ): string;
	abstract protected function sum( string $s ): string;
	abstract protected function trim( string $s ): string;
	abstract protected function trunc( string $v, int $p ): string;
	abstract protected function upper( string $s ): string;
	abstract protected function value( string $v ): string;
}

/*
$a = array();
for( $i = 0; $i < 5; $i++ ) {
	$a[$i] = $i;
}
$b = array();
for( $i = 0; $i < 5; $i++ ) {
	$b[$i] = &$a[$i];
}
echo( $b[1] );
$a[1] = -3;
echo( $b[1] );
*/
/*
$a = array();
$a['id'] = 1;
$a['nome'] = null;

echo( isset( $a['id'] ) . ' - ' . array_key_exists( 'id', $a ) . "\n" );
echo( isset( $a['nome'] ) . ' - ' . array_key_exists( 'nome', $a ) . "\n" );
echo( isset( $a['idade'] ) . ' - ' . array_key_exists( 'idade', $a ) . "\n" );

foreach( $a as $k => $v ) {
	echo( $k . ' => ' . $v . "\n" );
}

$d = date_create_from_format( 'Ymd', '20160705' );
echo( $d->format( 'Y-m-d H:i:s' ) );
*/
/*
function _iifnest( array &$values, string $oper, int $start = 0, int $end = 1 ): string {
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


function greatest( $values ): string {
	if( ! is_array( $values ) ) {
		$values = func_get_args();
	}

	return _iifnest( $values, ' > ' );
}

echo greatest( 2, 4, 5, 3, 1 );
*/
?>

<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 05/05/2016 - Ultima atualizacao: 05/05/2016
 * Descricao: Classe Cache, em substituição ao conjunto de procedures do arquivo cache.php.
 */
require_once( 'db_conn.php' );
require_once( 'debuglog.php' );

class Cache {
	private $cache;

	public function __construct() {
		$this->clear();
	}

	public function clear() {
		$this->cache = array();
	}

	public function put( $key, $value ) {
		$this->cache[ $key ] = $value;
	}

	public function get( $key ) {
//~ 		$t = gettype( $key );

//~ 		if( $t != 'string' && $t != 'integer' ) {
//~ 			DebugLog::log( 1, 'Cache::get: Valor de tipo desconhecido: ' . $key . '(' . $t . ')' );
//~ 			debug_print_backtrace();
//~ 		}

		if( ! isset( $key ) || ! isset( $this->cache ) || ( ! is_string( $key ) && ! is_int( $key ) && ! is_float( $key ) ) ||
				! array_key_exists( $key, $this->cache ) ) {
			return null;
		}

		return $this->cache[ $key ];
	}

	public function getAsArray(): array {
		return $this->cache;
	}

	public function key_exists( $key ): bool {
		return array_key_exists( $key, $this->cache );
	}

	public function put_range( $key, $start, $end, $value ) {
		//~ 	[ { numero = [ { start = '20150101', end = '20150630', value = '001' },
		//~ 				{ start = '20150701', end = '20151231', value = '002' } ] }, ... ]
		$ranges = $this->get( $key );

		if( $ranges == null ) {
			$ranges = array();
		}

		array_push( $ranges, array( 'start' => $start, 'end' => $end, 'value' => $value ) );
		$this->put( $key, $ranges );
	}

	public function get_range( $key, $middle ) {
		$ranges = $this->get( $key );

		if( $ranges != null ) {
			foreach( $ranges as $k => $r ) {
				if( ( ! isset( $r['start'] ) || $r['start'] <= $middle ) && ( ! isset( $r['end'] ) || $r['end'] >= $middle ) ) {
					return $r['value'];
				}
			}
		}

		return null;
	}

	public function load( DbConnection &$conn, string $sql, $key_field = 0, $value_field = 1 ): bool {
		$cur = $conn->getCursor( $sql, (is_string( $key_field ) ? DbCursor::FETCH_ASSOC : DbCursor::FETCH_NUMERIC) );

		if( $cur == null ) {
			DebugLog::log( 1, 'Cache::load: Query inválida: ' . $sql );
			return false;
		}

		while( $row = $cur->next() ) {
			$this->put( $row[ $key_field ], $row[ $value_field ] );
		}

		$cur->close();
		return true;
	}

	public function load_range( DbConnection &$conn, string $sql, $key_field = 0, $start_field = 1, $end_field = 2,  $value_field = 3 ): bool {
		$cur = $conn->getCursor( $sql, (is_string( $key_field ) ? DbCursor::FETCH_ASSOC : DbCursor::FETCH_NUMERIC) );

		if( $cur == null ) {
			DebugLog::log( 1, 'Cache::load_range: Query inválida: ' . $sql );
			return false;
		}

		while( $row = $cur->next() ) {
			$this->put_range( $row[ $key_field ], $row[ $start_field ], $row[ $end_field ], $row[ $value_field ] );
		}

		$cur->close();
		return true;
	}

	public function load_array( DbConnection &$conn, string $sql, $key_field = 0, $field_names = null ): bool {
		$cur = $conn->getCursor( $sql, (is_string( $key_field ) ? DbCursor::FETCH_ASSOC : DbCursor::FETCH_NUMERIC) );

		if( $cur == null ) {
			DebugLog::log( 1, 'Cache::load_array: Query inválida: ' . $sql );
			return false;
		}

		while( $row = $cur->next() ) {
			$arr = array();

			foreach( $row as $k => $v ) {
				if( $k != $key_field && ( $field_names == null || in_array( $k, $field_names ) ) ) {
					$arr[ $k ] = $v;
				}
			}

			$this->put( $row[ $key_field ], $arr );
		}

		$cur->close();
		return true;
	}

	public function load_range_array( DbConnection &$conn, string $sql, $key_field = 0, $start_field = 1, $end_field = 2, $field_names = null ): bool {
		$cur = $conn->getCursor( $sql, (is_string( $key_field ) ? DbCursor::FETCH_ASSOC : DbCursor::FETCH_NUMERIC) );

		if( $cur == null ) {
			DebugLog::log( 1, 'Cache::load_range_array: Query inválida: ' . $sql );
			return false;
		}

		while( $row = $cur->next() ) {
			$arr = array();

			foreach( $row as $k => $v ) {
				if( $k != $key_field && $k != $start_field && $k != $end_field &&
						( $field_names == null || in_array( $k, $field_names ) ) ) {
					$arr[ $k ] = $v;
				}
			}

			$this->put_range( $row[ $key_field ], $row[ $start_field ], $row[ $end_field ], $arr );
		}

		$cur->close();
		return true;
	}
}

class CacheList {
	private $cache_list;

	public function __construct( $names = null ) {
		$this->clear();

		if( $names != null ) {
			if( ! is_array( $names ) ) {
				$names = func_get_args();
			}

			foreach( $names as $n ) {
				$this->create( $n );
			}
		}
	}

	public function clear() {
		$this->cache_list = array();
	}

	public function create( string $name ): Cache {
		$cache = new Cache();
		$this->cache_list[ $name ] = $cache;
		return $cache;
	}

	public function add( string $name, Cache &$cache ): CacheList {
		$this->cache_list[ $name ] = $cache;
		return $this;
	}

	public function delete( string $name ): CacheList {
		$this->cache_list[ $name ] = null;
		unset( $this->cache_list[ $name ] );
		return $this;
	}

	public function exists( string $name ): bool {
		return array_key_exists( $name, $this->cache_list );
	}

	public function get( string $name ): Cache {
		if( ! $this->exists( $name ) ) {
			$this->create( $name );
		}

		return $this->cache_list[ $name ];
	}

	public function getValue( string $name, $key ) {
		$cache = $this->get( $name );

		if( isset( $cache ) ) {
			return $cache->get( $key );
		}

		return null;
	}

	public function getRangeValue( string $name, $key, $middle ) {
		$cache = $this->get( $name );

		if( isset( $cache ) ) {
			return $cache->get_range( $key, $middle );
		}

		return null;
	}

	public function putValue( string $name, $key, $value ) {
		$cache = $this->get( $name );

		if( ! isset( $cache ) ) {
			$cache = $this->create( $name );
		}

		$cache->put( $key, $value );
	}

	public function putRangeValue( string $name, $key, $start, $end, $value ) {
		$cache = $this->get( $name );

		if( ! isset( $cache ) ) {
			$cache = $this->create( $name );
		}

		$cache->put_range( $key, $start, $end, $value );
	}
}
/*
// Teste do CacheList
$cl = new CacheList();
$cl->create( 'a' );
$a = $cl->get( 'a' );
echo gettype( $a ) . "\n";
$a->put( 'aa', 3 );
$a = null;
$v = $cl->get( 'a' )->get( 'aa' );
echo gettype( $v ) . " $v\n";


$cl->create( 'b' )->put( 'bb', 7 );
$v = $cl->get( 'b' )->get( 'bb' );
echo gettype( $v ) . " $v\n";
*/
?>

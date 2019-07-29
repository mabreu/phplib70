<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 22/11/2017 - Ultima atualizacao: 22/11/2017
 * Descricao: Classe Config, classe de manipulação dos arquivos de configuração
 */
class Config {
	private static $filename;
	private static $config;

	public static function load( string $filename = null ) {
		if( $filename != null ) {
			self::setFilename( $filename );
		}

		self::$config = require( self::$filename );
		return self::class;
	}

	public static function setFilename( string $filename ) {
		if( $filename != self::$filename ) {
			self::$config = null;
		}

		self::$filename = $filename;
		return self::class;
	}

	public static function set( $value, ...$keys ) {
		if( self::$config == null ) {
			return null;
		}

		$cfg = &self::$config;

		foreach( $keys as $k ) {
			if( is_array( $cfg[ $k ] ) ) {
				$cfg = &$cfg[ $k ];
			} else {
				$cfg[ $k ] = $value;
				break;
			}
		}

		return self::class;
	}

	public static function exists( ...$keys ): bool {
		if( $keys == null || self::$config == null ) {
			return false;
		}

		$cfg = &self::$config;

		foreach( $keys as $k ) {
			if( ! is_array( $cfg ) || ! array_key_exists( $k, $cfg ) ) {
				return false;
			} else {
				$cfg = &$cfg[ $k ];
			}
		}

		return true;
	}

	public static function get( ...$keys ) {
		if( self::$config == null ) {
			return null;
		}

		if( $keys == null ) {
			return self::$config;
		}

		$cfg = &self::$config;

		foreach( $keys as $k ) {
			if( ! is_array( $cfg ) || ! array_key_exists( $k, $cfg ) ) {
				return null;
			} else {
				$cfg = &$cfg[ $k ];
			}
		}

		return $cfg;
	}
}
/*
Config::load( '../../Portabilidade/src/importa_portab.cfg' );
echo Config::get( 'conexao', 'amazon', 'host' ) . "\n";
Config::set( 'Marco Abreu', 'conexao', 'amazon', 'host' );
echo Config::exists( 'conexao', 'amazon', 'host' ) . "\n";
echo Config::exists( 'conexao', 'google', 'host' ) . "\n";
echo Config::exists( 'conexao', 'amazon', 'host', 'teste' ) . "\n";
echo Config::get( 'conexao', 'amazon', 'host' ) . "\n";
*/
?>
<?php
/*
 * Biblioteca PHP
 * Autor: Marco Antonio Abreu
 * Data: 04/09/2015 - Ultima atualizacao: 12/12/2017
 * Rotinas de depuração
 */
require_once( 'date_utils.php' );

const LOG_DIR = 'log';

class DebugLog {
	public static $DL_NONE = 0;
	public static $DL_ERROR = 1;
	public static $DL_MESSAGE = 2;
	public static $DL_VERBOSE = 3;

	private static $currentLevel = 1;		// --> 0 = sem log, 1 = erros, 2 = acoes, 3 = detalhes
	private static $output = 'p'; 			// --> 'p' = print, 'f' = file
	private static $filePrefix = 'debug';	// --> prefixo para nome do arquivo, diferenciando cada qual
	private static $fileName = '';			// --> armazena o nome do arquivo de log
	private static $fileHandle = null;		// --> armazena o ponteiro para o arquivo de log
	private static $cacheSize = 100;		// --> tamanho do cache de gravacao em arquivo
	private static $writeCounter = 0;		// --> contador de linhas gravadas para flush do cache

	public static function getCacheSize(): int {
		return self::$cacheSize;
	}

	public static function setCacheSize( $cs ) {
		self::$cacheSize = $cs;
	}

	public static function getLevel(): int {
		return self::$currentLevel;
	}

	public static function setLevel( $level ) {
		self::$currentLevel = min( max( floor( $level ), self::$DL_NONE ), self::$DL_VERBOSE );
	}

	public static function getOutput(): string {
		return self::$output;
	}

	public static function setOutput( $op, $fp = null ) {
		if( $op != null && ( $op == 'p' || $op == 'f' ) ) {
			self::$output = $op;

			if( $op == 'f' && gettype( $fp ) == 'string' ) {
				self::$filePrefix = $fp;
			}
		} else {
			self::$output = 'p';
		}
	}

	public static function close() {
		if( self::$fileHandle != null ) {
			fclose( self::$fileHandle );
			chmod( self::$fileName, 0664 );
			self::$fileHandle = null;
		}
	}

	public static function log( int $level, $txt ) {
		if( $level <= self::$currentLevel && $level > self::$DL_NONE ) {
			$params = func_get_args();
			unset( $params[ 0 ] ); // remove o primeiro parametro (level) da lista

			foreach( $params as $k => $v ) {
				if( is_object( $v ) && get_class( $v ) == 'DateTime' ) {
					$params[ $k ] = $v->format( 'Y-m-d H:i:s' );
				} elseif( is_array( $v ) ) {
					$params[ $k ] = print_r( $v, true );
				} else {
					$params[ $k ] = "$v";
				}
			}

			$agora = DateUtils::getDatetimeMicro();
			$txt = $agora->format( 'Y-m-d H:i:s.u' ) . ': ' . implode( "\t", $params ) . "\n";

			if( self::$output == 'p' ) {
				echo( $txt );
			} else {
				if( self::$fileHandle == null ) {
					if( ! file_exists( LOG_DIR ) ) {
						mkdir( LOG_DIR, 0775 );
					}

					$sequencial = 0;

					while( true ) {
						self::$fileName = sprintf( LOG_DIR . DIRECTORY_SEPARATOR . '%s_%s_%d.log', self::$filePrefix, date( 'Ymd_His' ), $sequencial );

						if( ! file_exists( self::$fileName ) ) {
							break;
						}

						$sequencial++;
					}

					self::$fileHandle = fopen( self::$fileName, 'w' );
				}

				fwrite( self::$fileHandle, $txt );
				self::$writeCounter++;

				if( self::$writeCounter >= self::$cacheSize ) {
					fflush( self::$fileHandle );
					self::$writeCounter = 0;
				}
			}
		}
	}
}
?>
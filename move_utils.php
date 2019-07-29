<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 31/08/2016 - Ultima atualizacao: 11/11/2016
 * Descricao: Rotina padrão para se mover arquivos nos diretórios
 */
require_once( 'debuglog.php' );

class MoveUtils {
	public static function moverArquivos( string $destdir, int $nivel, string $basedir, array $niveis ): int {
		DebugLog::log( 3, 'Processando: Nivel ' . $nivel . ' - ' . $basedir );
		$arquivos = scandir( $basedir );

		if( $arquivos === false ) {
			return 0;
		}

		$res = 0;
		$mask = ($nivel < count( $niveis ) ? $niveis[ $nivel ] : '*');
		DebugLog::log( 3, 'Mascara: ' . $mask . "" );

		foreach( $arquivos as $j => $arq ) {
			if( $arq != '.' && $arq != '..' ) {
				DebugLog::log( 3, "Arquivo: $arq" );
				$origem = $basedir . DIRECTORY_SEPARATOR . $arq;
				DebugLog::log( 3, 'Origem: ' . $origem . "" );

				if( is_file( $origem ) ) {
					DebugLog::log( 3, 'Mascara: ' . $mask );

					if( $mask == '*' || strtoupper( $arq ) == strtoupper( $mask ) ) {
						DebugLog::log( 3, $origem . ' -> ' . $destdir . DIRECTORY_SEPARATOR . $arq );

						if( rename( $origem, $destdir . DIRECTORY_SEPARATOR . $arq ) ) {
							if( $res >= 0 ) {
								$res++;
							}
						} else {
							$res = -1;
						}
					}
				} elseif( is_dir( $origem ) ) {
					if( ( $mask == '*' || $arq == $mask ) && $nivel < count( $niveis ) ) {
						MoveUtils::moverArquivos( $destdir, $nivel + 1, $origem, $niveis );
					}
				}
			}
		}

		return $res;
	}

	public static function moverArqImportado( string $destdir, string $basedir, $niveis ): int {
		if( is_string( $niveis ) ) {
			$niveis = explode( DIRECTORY_SEPARATOR, $niveis );
		}

		return MoveUtils::moverArquivos( $destdir, 0, $basedir, $niveis );
	}
}
?>
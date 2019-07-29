<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 08/07/2015 - Ultima atualizacao: 10/10/2017
 * Descricao: Rotinas auxiliares genéricas
 */

class Utils {
	public static function parseCabec( string $line, string $delim = ';' ): array {
		// exemplo:  projeto;provedor;integrador;numero;vigencia
		$vlin = explode( $delim, trim( $line ) );
		$res = array();

		for( $i = 0; $i < count( $vlin ); $i++ ) {
			$res[ $vlin[$i] ] = $i;
		}

		return $res;
	}

	public static function arrayOfNulls( $names ): array {
		if( ! is_array( $names ) ) {
			$names = func_get_args();
		}

		$res = array();

		foreach( $names as $k => $v ) {
			$res[$v] = null;
		}

		return $res;
	}
}
?>

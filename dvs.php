<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 26/11/2015 - Ultima atualizacao: 10/10/2017
 * Descricao: Rotinas de calculos de digitos verificadores
 */
class DV {
	public static function modulo11B( string $num ): string {
		$total = 0;
		$fator = 2;

		for( $i = strlen( $num ) - 1; $i >= 0; $i-- ) {
			$total += $fator * ((int) substr( $num, $i, 1 ));

			if( $fator == 9 )
				$fator = 2;
			else
				$fator++;
		}

		$resto = $total % 11;
		return ( $resto < 2 ? '0' : '' . (11 - $resto) );
	}

	public static function modulo11C( string $num ): string {
		$total = 0;
		$fator = 2;

		for( $i = strlen( $num ) - 1; $i >= 0; $i-- ) {
			$total += $fator * ((int) substr( $num, $i, 1 ));
			$fator++;
		}

		$resto = $total % 11;
		return ( $resto < 2 ? '0' : "" . (11 - $resto) );
	}

	public static function verificaCnpj( string $cnpj ): bool {
		$cnpj = str_pad( trim( $cnpj ), 14, '0', STR_PAD_LEFT );
		$aux = substr( $cnpj, 0, 12 );
		$dv = self::modulo11B( $aux );
		$aux .= $dv;
		$dv = self::modulo11B( $aux );
		$aux .= $dv;
		return ($aux == $cnpj);
	}

	public static function verificaCpf( string $cpf ): bool {
		$cpf = str_pad( trim( $cpf ), 11, '0', STR_PAD_LEFT );
		$aux = substr( $cpf, 0, 9 );
		$dv = self::modulo11C( $aux );
		$aux .= $dv;
		$dv = self::modulo11C( $aux );
		$aux .= $dv;
		return ($aux == $cpf);
	}
}
?>

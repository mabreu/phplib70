<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 07/10/2016 - Ultima atualizacao: 10/10/2017
 * Descricao: Rotinas de manipulação de strings
 */
class StrUtils {
	private static $_chars_acentuados = array( '/(á|à|ã|â|ä)/', '/(Á|À|Ã|Â|Ä)/', '/(é|è|ê|ë)/', '/(É|È|Ê|Ë)/', '/(í|ì|î|ï)/', '/(Í|Ì|Î|Ï)/',
												'/(ó|ò|õ|ô|ö)/', '/(Ó|Ò|Õ|Ô|Ö)/', '/(ú|ù|û|ü)/', '/(Ú|Ù|Û|Ü)/',  '/(ñ)/', '/(Ñ)/', '/(ç)/', '/(Ç)/' );
	private static $_chars_sem_acentos = array( 'a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U', 'n', 'N', 'c', 'C' );

	public static function endsWith( string $str, string $end, bool $cs = true ): bool {
		if( ! $cs ) {
			return (strToUpper( substr( $str, - strlen( $end ) ) ) == strToUpper( $end ));
		}

		return (substr( $str, - strlen( $end ) ) == $end);
	}

	public static function merge( string $str, string $new, int $pos = 0 ): string {
		for( $i = 0; $i < strlen( $new ); $i++ ) {
			$str[ $pos + $i ] = $new[ $i ];
		}

		return $str;
	}

	public static function nullIfEmpty( string $v ) {
		return (empty( $v ) ? null : $v);
	}

	public static function removeAccents( string $str ): string {
		return preg_replace( self::$_chars_acentuados, self::$_chars_sem_acentos, $str );
	}

	public static function removeDoubleBlanks( string $str ): string {
		$str = str_replace( "\t", ' ', $str );

		do {
			$conta = 0;
			$str = str_replace( '  ', ' ', $str, $conta );
		} while( $conta > 0 );

		return $str;
	}

	public static function removeQuote( string $str ): string {
		if( ( substr( $str, 0, 1 ) == "'" && substr( $str, -1, 1 ) == "'" ) ||
				( substr( $str, 0, 1 ) == '"' && substr( $str, -1, 1 ) == '"' ) ) {
			return substr( $str, 1, strlen( $str ) - 2 );
		}

		return $str;
	}

	public static function startsWith( string $str, string $start, bool $cs = true ): bool {
		if( ! $cs ) {
			return (strToUpper( substr( $str, 0, strlen( $start ) ) ) == strToUpper( $start ));
		}

		return (substr( $str, 0, strlen( $start ) ) == $start);
	}

	public static function strZero( $num, int $tam ): string {
		if( $num < 0 ) {
			return '-' . str_pad( $num * -1, $tam - 1, '0', STR_PAD_LEFT );
		}

		return str_pad( $num, $tam, '0', STR_PAD_LEFT );
	}
}
?>

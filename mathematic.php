<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 07/10/2016 - Ultima atualizacao: 05/09/2019
 * Descricao: Funções matemáticas
 */

class Math {
	public static function avg( array $vals ): float {
		$total = 0;
		$qtde = 0;

		foreach( $vals as $v ) {
			$total += $v;
			$qtde++;
		}

		return $total / $qtde;
	}

	public static function factorial( int $num ): int {
		if( $num < 0 ) {
			return 0;
		}

		$res = $num;

		while( --$num > 1 ) {
			$res *= $num;
		}

		return $res;
	}

	public static function factDivFact( int $num, int $div ): float {
		if( $num > $div ) {
			$res = $num;

			for( $ind = $num - 1; $ind >= $div + 1; $ind-- ) {
				$res *= $ind;
			}
		} elseif( $div > $num ) {
			$res = $div;

			for( $ind = $div - 1; $ind >= $num + 1; $ind-- ) {
				$res *= $ind;
			}

			$res = 1 / $res;
		} else {
			$res = 1;
		}

		return $res;
	}

	public static function fibonacciNumber( int $num ): int {
		$a = 0;
		$b = 1;

		for( $i = 0; $i < $num; $i++ ) {
			$c = $a;
			$a = $b;
			$b += $c;
		}

		return $a;
	}

	public static function fibonacciSequence( int $final, int $init = 0 ): array {
		$res = array();
		$a = 0;
		$b = 1;

		for( $i = 0; $i <= $final; $i++ ) {
			if( $a >= $init ) {
				array_push( $res, $a );
			}

			$c = $a;
			$a = $b;
			$b += $c;
		}

		return $res;
	}

	public static function isPrime( int $num ): bool {
		$num = abs( $num );

		if( $num == 2 ) {
			return true;
		}

		if( $num < 2 || $num % 2 == 0 ) {
			return false;
		}

		$limit = intdiv( $num, 3 );

		for( $div = 3; $div <= $limit; $div += 2 ) {
			if( $num % $div == 0 ) {
				return false;
			}
		}

		return true;
	}

	public static function mdc( int ...$num ): int {
		$x = $num[0];

		if( func_num_args() > 2 ) {
			unset( $num[0] );
			$y = self::mdc( ...$num );
		} else {
			$y = $num[1];
		}

		$a = max( $x, $y );
		$b = min( $x, $y );

		if( $a % $b == 0 ) {
			return $b;
		}

		return self::mdc( $b, ( $a % $b ) );
	}

	public static function mmc( int ...$num ): int {
		$x = $num[0];

		if( func_num_args() > 2 ) {
			unset( $num[0] );
			$y = self::mmc( ...$num );
		} else {
			$y = $num[1];
		}

		return ( $x * $y ) / self::mdc( $x, $y );
	}

	public static function stdDev( array $vals ): float {
		$media = self::avg( $vals );
		$total = 0;
		$qtde = 0;

		foreach( $vals as $v ) {
			$total += (($v - $media) ^ 2);
			$qtde++;
		}

		return sqrt(1 / ($qtde - 1) * $total);
	}

	public static function sum( array $vals ): float {
		$total = 0;

		foreach( $vals as $v ) {
			$total += $v;
		}

		return $total;
	}
}
/*
echo (Math::isPrime( 1 ) ? 'Primo' : "Não Primo") . "\n";
echo (Math::isPrime( 2 ) ? 'Primo' : "Não Primo") . "\n";
echo (Math::isPrime( 3 ) ? 'Primo' : "Não Primo") . "\n";
echo (Math::isPrime( 6 ) ? 'Primo' : "Não Primo") . "\n";
echo (Math::isPrime( 7 ) ? 'Primo' : "Não Primo") . "\n";
echo (Math::isPrime( -5 ) ? 'Primo' : "Não Primo") . "\n";
echo (Math::isPrime( -9 ) ? 'Primo' : "Não Primo") . "\n";
echo Math::stdDev( [1, 12, 43, 85, 348] );
echo Math::fibonacciNumber( 10 ) . "\n";
echo print_r( Math::fibonacciSequence( 10 ) ) . "\n";
*/
?>
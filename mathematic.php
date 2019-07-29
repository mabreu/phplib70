<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 07/10/2016 - Ultima atualizacao: 27/04/2019
 * Descricao: Funções matemáticas
 */
class Math {
	public static function sum( array $vals ): float {
		$total = 0;

		foreach( $vals as $v ) {
			$total += $v;
		}

		return $total;
	}

	public static function avg( array $vals ): float {
		$total = 0;
		$n = 0;

		foreach( $vals as $v ) {
			$total += $v;
			$n++;
		}

		return $total / $n;
	}

	public static function stdDev( array $vals ): float {
		$media = Math::avg( $vals );
		$total = 0;
		$n = 0;

		foreach( $vals as $v ) {
			$total += (($v - $media) ^ 2);
			$n++;
		}

		return sqrt((1 / ($n - 1)) * $total);
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

	public static function mdc( int ...$n ): int {
		$x = $n[0];

		if( func_num_args() > 2 ) {
			unset( $n[0] );
			$y = self::mdc( ...$n );
		} else {
			$y = $n[1];
		}

		$a = max( $x, $y );
		$b = min( $x, $y );

		if( $a % $b == 0 ) {
			return $b;
		} else {
			return self::mdc( $b, ( $a % $b ) );
		}
	}

	public static function mmc( int ...$n ): int {
		$x = $n[0];

		if( func_num_args() > 2 ) {
			unset( $n[0] );
			$y = self::mmc( ...$n );
		} else {
			$y = $n[1];
		}

		return ( $x * $y ) / self::mdc( $x, $y );
	}

	public static function isPrime( int $number ): bool {
		if( $number < 2 || ( $number % 2 == 0 && $number != 2 ) ) {
			return false;
		}

		if( $number == 2 ) {
			return true;
		}

		$limit = intdiv( $number, 3 );

		for( $div = 3; $div <= $limit; $div += 2 ) {
			if( $number % $div == 0 ) {
				return false;
			}
		}

		return true;
	}
}
?>
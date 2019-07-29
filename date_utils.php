<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 08/07/2015 - Ultima atualizacao: 10/10/2017
 * Descricao: Rotinas auxiliares genéricas
 */
//~ set_include_path( get_include_path() . PATH_SEPARATOR . '/var/projetos/phplib/PHPMailer-master' );

require_once( 'str_utils.php' );

class DateUtils {
	public static function Secs2HMS( int $secs, int $dh = 2 ): string {
		$h = floor( $secs / 3600 );
		$secs = $secs - ($h * 3600);
		$m = floor( $secs / 60 );
		$s = $secs - ($m * 60);
		return StrUtils::StrZero( $h, $dh ) . StrUtils::StrZero( $m, 2 ) . StrUtils::StrZero( $s, 2 );
	}

	public static function HMS2Secs( string $hms ): int {
		$secs = (int) substr( $hms, -2, 2 );
		$hms = substr( $hms, 0, strlen( $hms ) - 2 );

		if( $hms != '' ) {
			$secs += (((int) substr( $hms, -2, 2 )) * 60);
			$hms = substr( $hms, 0, strlen( $hms ) - 2 );

			if( $hms != '' ) {
				$secs += (((int) $hms ) * 3600);
			}
		}

		return $secs;
	}

	public static function bissexto( int $y ): bool {
		return ( ( ( $y % 4 ) == 0 && ( $y % 100 ) != 0 ) || ( $y % 400 ) == 0 );
	}

	public static function cmes( int $m ): string {
		switch( $m ) {
		case 1:
			return 'Janeiro';
		case 2:
			return 'Fevereiro';
		case 3:
			return 'Março';
		case 4:
			return 'Abril';
		case 5:
			return 'Maio';
		case 6:
			return 'Junho';
		case 7:
			return 'Julho';
		case 8:
			return 'Agosto';
		case 9:
			return 'Setembro';
		case 10:
			return 'Outubro';
		case 11:
			return 'Novembro';
		case 12:
			return 'Dezembro';
		}

		return '';
	}

	public static function isDateValid( string $dat ): bool {
		$y = substr( $dat, 0, 4 );
		$m = substr( $dat, 5, 2 );
		$d = substr( $dat, 8, 2 );

		$ty = intval( date('Y') );
		$tm = intval( date('m') );
		$td = intval( date('d') );

		$yy = 0;
		$mm = 0;
		$dd = 0;

		// Ano inválido
		if( ! is_numeric( $y ) || strlen( $y ) != 4 ) {
			return false;
		} else {
			$yy = intval( $y );

			if( $yy < 2015 || $y > $ty ) {
				return false;
			}
		}

		// Mês inválido
		if( ! is_numeric( $m ) || strlen( $m ) != 2 ) {
			return false;
		} else {
			$mm = intval( $m );

			if( $mm < 1 || $mm > 12 || ( $yy == $ty && $mm > $tm ) ) {
				return false;
			}
		}

		// Dia inválido
		if( ! is_numeric( $d ) || strlen( $d ) != 2 ) {
			return false;
		} else {
			$dd = intval( $d );

			if( $dd < 1 || $dd > 31 || ( $yy == $ty && $mm == $tm && $dd > $td ) ||
					 ( ( $mm == 4 || $mm == 6 || $mm == 9 || $mm == 11 ) && $dd > 30 ) ||
					 ( $mm == 2 && $dd > ( self::bissexto( $y ) ? 29 : 28 ) ) ) {
				return false;
			}
		}

		return true;
	}

	public static function isTimeValid( string $dat ): bool {
		$h = substr( $dat, 11, 2 );
		$n = substr( $dat, 14, 2 );
		$s = substr( $dat, 17, 2 );

		// Hora inválida
		if( ! is_numeric( $h ) || strlen( $h ) != 2 ) {
			return false;
		} else {
			$hh = intval( $h );

			if( $hh < 0 || $hh > 23 ) {
				return false;
			}
		}

		// Minuto inválido
		if( ! is_numeric( $n ) || strlen( $n ) != 2 ) {
			return false;
		} else {
			$nn = intval( $n );

			if( $nn < 0 || $nn > 59 ) {
				return false;
			}
		}

		// Segundo inválido
		if( ! is_numeric( $s ) || strlen( $s ) != 2 ) {
			return false;
		} else {
			$ss = intval( $s );

			if( $ss < 0 || $ss > 59 ) {
				return false;
			}
		}

		return true;
	}

	public static function getDatetimeMicro(): Datetime {
		$agora = DateTime::createFromFormat( 'U.u', microtime( true ) );

		if( $agora === false ) {
			usleep( 100 );
			$agora = DateTime::createFromFormat( 'U.u', microtime( true ) );
		}

		$agora->setTimeZone( new DateTimeZone( date_default_timezone_get() ) );
		return $agora;
	}

	public static function getNextYM( string $ym, int $inc = 1 ): string {
		$year = (int) substr( $ym, 0, 4 );

		if( ctype_digit( substr( $ym, 4, 1 ) ) ) {
			$month = ((int) substr( $ym, 4, 2 )) + $inc;
		} else {
			$month = ((int) substr( $ym, 5, 2 )) + $inc;
		}

		if( $inc > 0 ) {
			while( $month > 12 ) {
				$month -= 12;
				$year++;
			}
		} else {
			while( $month < 1 ) {
				$month += 12;
				$year--;
			}
		}

		return $year . (ctype_digit( substr( $ym, 4, 1 ) ) ? '' : '-') . str_pad( $month, 2, '0', STR_PAD_LEFT );
	}

	public static function getFormatted( string $dt ): string {
		$res = substr( $dt, 0, 4 ) . '-' . substr( $dt, 4, 2 ) . '-' . substr( $dt, 6, 2 );

		if( strlen( $dt ) >= 10 ) {
			$res .= ' ' . substr( $dt, 8, 2 );

			if( strlen( $dt ) >= 12 ) {
				$res .= ':' . substr( $dt, 10, 2 );

				if( strlen( $dt ) >= 14 ) {
					$res .= ':' . substr( $dt, 12, 2 );
				}
			}
		}

		return $res;
	}

	public static function getUnformatted( string $dt ): string {
		return str_replace( ['-', ' ', ':'], '', $dt );
	}

//~ 	public static lastDay( int $m ): int {
//~ 		if( $m == 1 || $m == 3 || $m == 5 || $m == 7 || $m == 8 || $m == 10 || $m == 12 ) {
//~ 			return 31;
//~ 		} elseif( $m == 4 || $m == 6 || $m == 9 || $m == 11 ) {
//~ 			reutn 30;
//~ 		} else {
//~ 			return 28;
//~ 		}
//~ 	}
}

//~ $d = DateUtils::getDatetimeMicro();
//~ echo $d->format( "Y-m-d H:i:s.u\n" );
//~ echo DateUtils::getFormatted( '20180703' ) . "\n";
//~ echo DateUtils::getFormatted( '2018070315' ) . "\n";
//~ echo DateUtils::getFormatted( '201807031507' ) . "\n";
//~ echo DateUtils::getFormatted( '20180703150710' ) . "\n";
?>
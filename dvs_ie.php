<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 29/02/2016 - Ultima atualizacao: 10/10/2017
 * Descricao: Rotinas de checagem dos digitos verificadores das Incrições Estaduais
 */

class DVIE {
	//Acre
	public static function checkAC( string $ie ): bool {
		if( strlen( $ie ) < 13 ) {
			$ie = str_pad( $ie, 13, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 13 || substr( $ie, 0, 2 ) != '01' ) {
			return false;
		}

		$b = 4;
		$soma = 0;

		for( $i = 0; $i <= 10; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 9;
			}
		}

		$dig = 11 - ($soma % 11);

		if( $dig >= 10 ) {
			$dig = 0;
		}

		if( $dig != $ie[11] ) {
			return false;
		}

		$b = 5;
		$soma = 0;

		for( $i = 0; $i <= 11; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 9;
			}
		}

		$dig = 11 - ($soma % 11);

		if( $dig >= 10 ) {
			$dig = 0;
		}

		return ($dig == $ie[12]);
	}

	// Alagoas
	public static function checkAL( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 || substr($ie, 0, 2 ) != '24' ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i=0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$soma *= 10;
		$dig = $soma - ( ( (int) ($soma / 11) ) * 11 );

		if( $dig == 10 ) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}

	//Amazonas
	public static function checkAM( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i=0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		if( $soma <= 11 ) {
			$dig = 11 - $soma;
		} else {
			$r = $soma % 11;
			$dig = ($r <= 1 ? 0 : 11 - $r);
		}

		return ($dig == $ie[8]);
	}

	//Amapá
	public static function checkAP( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 || substr( $ie, 0, 2 ) != '03' ) {
			return false;
		}

		$i = substr($ie, 0, -1);

		if( $i >= 3000001 && $i <= 3017000 ) {
			$p = 5;
			$d = 0;
		} elseif( $i >= 3017001 && $i <= 3019022 ) {
			$p = 9;
			$d = 1;
		} elseif( $i >= 3019023 ) {
			$p = 0;
			$d = 0;
		}

		$b = 9;
		$soma = $p;

		for( $i=0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$dig = 11 - ($soma % 11);

		if( $dig == 10 ) {
			$dig = 0;
		} elseif( $dig == 11 ) {
			$dig = $d;
		}

		return ($dig == $ie[8]);
	}

	//Bahia
	public static function checkBA( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		$s = strlen( $ie );

		if( $s != 9 ) {
			return false;
		}

		$mod = (strpos( '679', $ie[1] ) === false ? 10 : 11);  // Se segundo digito é 6, 7 ou 9, modulo 11, senão modulo 10
		// Calcula o segundo digito
		$soma = 0;
		$peso = 2;

		for( $i = 6; $i >= 0; $i-- ) {
			$soma += ((int) $ie[$i]) * $peso;
			$peso++;
		}

		$resto = $soma % $mod;

		if( $mod == 11 ) {
			$dig = ($resto <= 1 ? 0 : $mod - $resto);
		} else {
			$dig = ($resto == 0 ? 0 : $mod - $resto);
		}

		if( ('' . $dig) != $ie[8] ) {
			return false;
		}

		// Calcula o primeiro digito
		$soma = $dig * 2;
		$peso = 3;

		for( $i = 6; $i >= 0; $i-- ) {
			$soma += ((int) $ie[$i]) * $peso;
			$peso++;
		}

		$resto = $soma % $mod;

		if( $mod == 11 ) {
			$dig = ($resto <= 1 ? 0 : $mod - $resto);
		} else {
			$dig = ($resto == 0 ? 0 : $mod - $resto);
		}

		return ($dig == $ie[7]);
	}

	//Ceará
	public static function checkCE( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$dig = 11 - ($soma % 11);

		if( $dig >= 10 ) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}

	// Distrito Federal
	public static function checkDF( string $ie ): bool {
		if( strlen( $ie ) < 13 ) {
			$ie = str_pad( $ie, 13, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 13 || substr( $ie, 0, 2 ) != '07' ) {
			return false;
		}

		$b = 4;
		$soma = 0;

		for( $i = 0; $i <= 10; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 9;
			}
		}

		$dig = 11 - ($soma % 11);

		if( $dig >= 10 ) {
			$dig = 0;
		}

		if( $dig != $ie[11] ) {
			return false;
		}

		$b = 5;
		$soma = 0;

		for( $i = 0; $i <= 11; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 9;
			}
		}

		$dig = 11 - ($soma % 11);

		if( $dig >= 10 ) {
			$dig = 0;
		}

		return ($dig == $ie[12]);
	}

	//Espirito Santo
	public static function checkES( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % 11;
		$dig = ($i < 2 ? 0 : 11 - $i);
		return ($dig == $ie[8]);
	}

	//Goias
	public static function checkGO( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 ) {
			return false;
		}

		$s = substr( $ie, 0, 2 );

		if( $s != 10 && $s != 11 && $s != 15 ) {
			return false;
		}

		$n = substr( $ie, 0, 8 );

		if( $n == '11094402' ) {
			if( $ie[8] != 0 ) {
				return ($ie[8] == 1);
			} else {
				return true;
			}
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % 11;

		if( $i == 0 ) {
			$dig = 0;
		} else {
			if( $i == 1 ) {
				$dig = ($n >= 10103105 && $n <= 10119997 ? 1 : 0);
			} else {
				$dig = 11 - $i;
			}
		}

		return ($dig == $ie[8]);
	}

	// Maranhão
	public static function checkMA( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 || substr( $ie, 0, 2 ) != 12 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % 11;
		$dig = ( $i < 2 ? 0 : 11 - $i);
		return ($dig == $ie[8]);
	}

	// Mato Grosso
	public static function checkMT( string $ie ): bool {
		if( strlen( $ie ) < 11 ) {
			$ie = str_pad( $ie, 11, '0', STR_PAD_LEFT );
		}

		if( strlen($ie) != 11 ) {
			return false;
		}

		$b = 3;
		$soma = 0;

		for( $i = 0; $i <= 9; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 9;
			}
		}

		$i = $soma % 11;
		$dig = ($i < 2 ? 0 : 11 - $i);
		return ($dig == $ie[10]);
	}

	// Mato Grosso do Sul
	public static function checkMS( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen($ie) != 9 || substr( $ie, 0, 2 ) != 28 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % 11;
		$dig = ($i == 0 ? 0 : 11 - $i);

		if( $dig > 9 ) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}

	//Minas Gerais
	public static function checkMG( string $ie ): bool {
		if( strlen( $ie ) < 13 ) {
			$ie = str_pad( $ie, 13, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 13 ) {
			return false;
		}

		$ie2 = substr( $ie, 0, 3 ) . '0' . substr( $ie, 3 );
		$b = 1;
		$soma = "";

		for( $i = 0; $i <= 11; $i++ ) {
			$soma .= $ie2[$i] * $b;
			$b++;

			if( $b == 3 ) {
				$b = 1;
			}
		}

		$s = 0;

		for( $i = 0; $i < strlen( $soma ); $i++ ) {
			$s += $soma[$i];
		}

	//~ 	$i = substr( $ie2, 9, 2 );
	//~ 	$dig = $i - $s;

		$dig = $s % 10;

		if( $dig != 0 )
			$dig = 10 - $dig;

		if( $dig != $ie[11] ) {
			return false;
		}

		$b = 3;
		$soma = 0;

		for( $i = 0; $i <= 11; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 11;
			}
		}

		$i = $soma % 11;
		$dig = ($i <= 1 ? 0 : 11 - $i);
		return ($dig == $ie[12]);
	}

	//Pará
	public static function checkPA( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 || substr( $ie, 0, 2 ) != 15 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % 11;
		$dig = ($i <= 1 ? 0 : 11 - $i);
		return ($dig == $ie[8]);
	}

	//Paraíba
	public static function checkPB( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % 11;
		$dig = ($i <= 1 ? 0 : 11 - $i);

		if( $dig > 9 ) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}

	//Paraná
	public static function checkPR( string $ie ): bool {
		if( strlen( $ie ) < 10 ) {
			$ie = str_pad( $ie, 10, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 10 ) {
			return false;
		}

		$b = 3;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 7;
			}
		}

		$i = $soma % 11;
		$dig = ($i <= 1 ? 0 : 11 - $i);

		if( $dig != $ie[8] ) {
			return false;
		}

		$b = 4;
		$soma = 0;

		for( $i = 0; $i <= 8; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 7;
			}
		}

		$i = $soma % 11;
		$dig = ($i <= 1 ? 0 : 11 - $i);
		return ($dig == $ie[9]);
	}

	//Pernambuco
	public static function checkPE( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		$s = strlen( $ie );

		if( $s != 9 && $s != 14 ) {
			return false;
		}

		if( $s == 9 ) {
			$b = 8;
			$soma = 0;

			for( $i = 0; $i <= 6; $i++ ) {
				$soma += $ie[$i] * $b;
				$b--;
			}

			$i = $soma % 11;
			$dig = ($i <= 1 ? 0 : 11 - $i);

			if ( $dig != $ie[7] ) {
				return false;
			}

			$b = 9;
			$soma = 0;

			for( $i = 0; $i <= 7; $i++ ) {
				$soma += $ie[$i] * $b;
				$b--;
			}

			$i = $soma % 11;
			$dig = ($i <= 1 ? 0 : 11 - $i);
			return ($dig == $ie[8]);
		} else {
			$b = 5;
			$soma = 0;

			for( $i = 0; $i <= 12; $i++ ) {
				$soma += $ie[$i] * $b;
				$b--;

				if( $b == 0 ) {
					$b = 9;
				}
			}

			$dig = 11 - ($soma % 11);

			if( $dig > 9 ) {
				$dig = $dig - 10;
			}

			return ($dig == $ie[13]);
		}
	}

	//Piauí
	public static function checkPI( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % 11;
		$dig = ($i <= 1 ? 0 : 11 - $i);

		if( $dig >= 10 ) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}

	// Rio de Janeiro
	public static function checkRJ( string $ie ): bool {
		if( strlen( $ie ) < 8 ) {
			$ie = str_pad( $ie, 8, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 8 ) {
			return false;
		}

		$b = 2;
		$soma = 0;

		for( $i = 0; $i <= 6; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 7;
			}
		}

		$i = $soma % 11;
		$dig = ($i <= 1 ? 0 : 11 - $i);
		return ($dig == $ie[7]);
	}

	//Rio Grande do Norte
	public static function checkRN( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		$s = strlen( $ie );

		if( $s != 9 && $s != 10 ) {
			return false;
		}

		$b = strlen( $ie );
		$s = ($b == 9 ? 7 : 8);
		$soma = 0;

		for( $i = 0; $i <= $s; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$soma *= 10;
		$dig = $soma % 11;

		if( $dig == 10 ) {
			$dig = 0;
		}

		$s++;
		return ($dig == $ie[$s]);
	}

	// Rio Grande do Sul
	public static function checkRS( string $ie ): bool {
		if( strlen( $ie ) < 10 ) {
			$ie = str_pad( $ie, 10, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 10 ) {
			return false;
		}

		$b = 2;
		$soma = 0;

		for( $i = 0; $i <= 8; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;

			if( $b == 1 ) {
				$b = 9;
			}
		}

		$dig = 11 - ($soma % 11);

		if( $dig >= 10 ) {
			$dig = 0;
		}

		return ($dig == $ie[9]);
	}

	// Rondônia
	public static function checkRO( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		$s = strlen( $ie );

		if( $s != 9 && $s != 14 ) {
			return false;
		}

		if( $s == 9 ) {
			$b = 6;
			$soma = 0;

			for( $i = 3; $i <= 7; $i++ ) {
				$soma += $ie[$i] * $b;
				$b--;
			}

			$dig = 11 - ($soma % 11);

			if( $dig >= 10 ) {
				$dig = $dig - 10;
			}

			return ($dig == $ie[8]);
		} else {
			$b = 6;
			$soma = 0;

			for( $i = 0; $i <= 12; $i++ ) {
				$soma += $ie[$i] * $b;
				$b--;

				if( $b == 1 ) {
					$b = 9;
				}
			}

			$dig = 11 - ($soma % 11);

			if( $dig > 9 ) {
				$dig = $dig - 10;
			}

			return ($dig == $ie[13]);
		}
	}

	//Roraima
	public static function checkRR( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 || substr( $ie, 0, 2 ) != 24 ) {
			return false;
		}

		$b = 1;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b++;
		}

		$dig = $soma % 9;
		return ($dig == $ie[8]);
	}

	//Santa Catarina
	public static function checkSC( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen($ie) != 9 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$r = $soma % 11;

		if( $r == 0 || $r == 1 ) {
			$dig = '0';
		} else {
			$dig = '' . (11 - $r);
		}

		return ($dig == $ie[8]);
	}

	//São Paulo
	public static function checkSP( string $ie ): bool {
		if( strtoupper( $ie[0] ) == 'P' ) {
	//~ 		if( strlen( $ie ) < 13 ) {
	//~ 			$ie = str_pad( $ie, 13, '0', STR_PAD_LEFT );
	//~ 		}

			if( strlen( $ie ) != 13 ) {
				return false;
			}

			$b = 1;
			$soma = 0;

			for( $i = 1; $i <= 8; $i++ ) {
				$soma += $ie[$i] * $b;
				$b++;

				if( $b == 2 ) {
					$b = 3;
				} elseif( $b == 9 ) {
					$b = 10;
				}
			}

			$dig = $soma % 11;

			if( $dig > 9 ) {
				$dig = 0;
			}

			return ($dig == $ie[9]);
		} else {
			if( strlen( $ie ) < 12 ) {
				$ie = str_pad( $ie, 12, '0', STR_PAD_LEFT );
			}

			if( strlen( $ie ) != 12 ) {
				return false;
			}

			$b = 1;
			$soma = 0;

			for( $i = 0; $i <= 7; $i++ ) {
				$soma += $ie[$i] * $b;
				$b++;

				if( $b == 2 ) {
					$b = 3;
				} elseif( $b == 9 ) {
					$b = 10;
				}
			}

			$dig = $soma % 11;

			if( $dig > 9 ) {
				$dig = 0;
			}

			if( $dig != $ie[8] ) {
				return false;
			}

			$b = 3;
			$soma = 0;

			for( $i = 0; $i <= 10; $i++ ) {
				$soma += $ie[$i] * $b;
				$b--;

				if( $b == 1 ) {
					$b = 10;
				}
			}

			$dig = $soma % 11;

			if( $dig > 9 ) {
				$dig = 0;
			}

			return ($dig == $ie[11]);
		}
	}

	//Sergipe
	public static function checkSE( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		if( strlen( $ie ) != 9 ) {
			return false;
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$dig = 11 - ($soma % 11);

		if( $dig > 9 ) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}

	//Tocantins
	public static function checkTO( string $ie ): bool {
		if( strlen( $ie ) < 9 ) {
			$ie = str_pad( $ie, 9, '0', STR_PAD_LEFT );
		}

		$t = strlen( $ie );

		if( $t != 11 && $t != 9 ) {
			return false;
		}

		if( $t == 11 ) {
			$s = substr( $ie, 2, 2 );

			if( $s != '01' && $s != '02' && $s != '03' && $s != '99' ) {
				return false;
			}

			$ie = substr( $ie, 0, 2 ) . substr( $ie, 4 );
		}

		$b = 9;
		$soma = 0;

		for( $i = 0; $i <= 7; $i++ ) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % 11;
		$dig = ($i < 2 ? 0 : 11 - $i);
		return ($dig == $ie[8]);
	}

	public static function check( string $ie, string $uf ): bool {
		if( strtoupper( $ie ) == 'ISENTO' ) {
			return true;
		}

		$ie = preg_replace( "[()-./,:]", '', $ie );
		$comando = '$valida = DVIE::check' . $uf . '("' . $ie . '");';
		eval( $comando );
		return $valida;
	}
}

//números para testes
//01.004.823/001-12 AC
//240000048 AL
//030123459 AP
//~ echo( DVIE::check( '999999990', 'AM' ) ? 1 : 0 ); // AM
//123456-63 BA
//06000001-5 CE
//073.00001.001-09 DF
//999999990 ES
//10.987.654-7 GO
//120000385 MA
//0013000001-9 MT
//283115947 MS
//062.307.904/0081 MG
//15-999999-5 PA
//06000001-5 PB
//123.45678-50 PR
//0321418-40 PE
//18.1.001.0000004-9 PE
//012345679 PI
//99999993 RJ
//20.040.040-1 RN
//20.0.040.040-0 RN
//224/3658792 RS
//101.62521-3 RO
//0000000062521-3 RO
//24006628-1 RR
//251.040.852 SC
//110.042.490.114 SP
//27123456-3 SE
//29010227836 TO

//~ echo( DVIE::check( '042229880', 'AM' ) ? 1 : 0 );
//~ echo( DVIE::check( '65155796', 'BA' ) ? 1 : 0 );
//~ echo( DVIE::check( '29010227836', 'TO' ) ? 1 : 0 );
//~ echo( "\n\n" );

// Inscrições Estaduais da Cambridge
/*
echo( DVIE::check( '0104045000106', 'AC' ) ? 1 : 0 );
echo( DVIE::check( '242980813', 'AL' ) ? 1 : 0 );
echo( DVIE::check( '053469887', 'AM' ) ? 1 : 0 );
echo( DVIE::check( '030473292', 'AP' ) ? 1 : 0 );
echo( DVIE::check( '113805444', 'BA' ) ? 1 : 0 );

echo( DVIE::check( '067219101', 'CE' ) ? 1 : 0 );
echo( DVIE::check( '0766293100257', 'DF' ) ? 1 : 0 );
echo( DVIE::check( '083017836', 'ES' ) ? 1 : 0 );
echo( DVIE::check( '105918563', 'GO' ) ? 1 : 0 );
echo( DVIE::check( '124226728', 'MA' ) ? 1 : 0 );

echo( DVIE::check( '0022536050084', 'MG' ) ? 1 : 0 );
echo( DVIE::check( '283920076', 'MS' ) ? 1 : 0 );
echo( DVIE::check( '00135147840', 'MT' ) ? 1 : 0 );
echo( DVIE::check( '154298891', 'PA' ) ? 1 : 0 );
echo( DVIE::check( '162303416', 'PB' ) ? 1 : 0 );

echo( DVIE::check( '055100740', 'PE' ) ? 1 : 0 );
echo( DVIE::check( '195320824', 'PI' ) ? 1 : 0 );
echo( DVIE::check( '9065597575', 'PR' ) ? 1 : 0 );
echo( DVIE::check( '86542072', 'RJ' ) ? 1 : 0 );
echo( DVIE::check( '75916272', 'RJ' ) ? 1 : 0 );
echo( DVIE::check( '202959767', 'RN' ) ? 1 : 0 );

echo( DVIE::check( '00000003976190', 'RO' ) ? 1 : 0 );
echo( DVIE::check( '240253667', 'RR' ) ? 1 : 0 );
echo( DVIE::check( '0963613669', 'RS' ) ? 1 : 0 );
echo( DVIE::check( '250261251', 'SC' ) ? 1 : 0 );
echo( DVIE::check( '257225064', 'SC' ) ? 1 : 0 );
echo( DVIE::check( '251040852', 'SC' ) ? 1 : 0 );
echo( DVIE::check( '271421304', 'SE' ) ? 1 : 0 );

echo( DVIE::check( '149305144111', 'SP' ) ? 1 : 0 );
echo( DVIE::check( '294530843', 'TO' ) ? 1 : 0 );

echo( DVIE::check( '12345663', 'BA' ) ? 1 : 0 );
echo( DVIE::check( '113805444', 'BA' ) ? 1 : 0 );
echo( DVIE::check( '63839112', 'BA' ) ? 1 : 0 );
echo( "\n" );
*/
?>

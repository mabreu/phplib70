<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 03/07/2018 - Ultima atualizacao: 14/12/2018
 * Descricao: classe para geração de números pseudo aleatórios
 */
require_once( 'mathematic.php' );

class PRNG {
	private $start;
	private $end;
	private $last;
	private $seed;
	private $factor;

	//~ $s = 0xb5ad4eceda1ce2a9;

	function __construct( int $start, int $end, int $seed = 0, int $factor = 0 ) {
		$this->start = $start;
		$this->end = $end;
		$this->last = -1;

		$this->seed = ( $seed != 0 ? $seed : $this->getRandom() );
		$this->factor = ( $factor != 0 ? $factor : $this->getRandom() );
	}

	public function getLast(): int {
		return $this->last;
	}

	public function getNext(): int {
		if( $this->last == -1 ) {
			$this->last = $this->seed;
			return $this->last;
		}

		$number = ($this->last == 0 ? $this->seed : (($this->last + $this->factor) % $this->end ));

		if( $number == 0 ) {
			$number = $this->end;
		}

		$this->last = $number;
		return $number;
	}

	private function getPrime( int $base ): int {
		if( $base % 2 == 0 ) {
			$base++;
		}

		while( ! Math::isPrime( $base ) ) {
			$base += 2;
		}

		return $base;
	}

	private function getRandom(): int {
		return rand( $this->start, $this->end );
	}
}
/*
//~ $prng = new PRNG( 1, 10000, 537, 325647 );
$prng = new PRNG( 1, 1000000000, 4294967291, 836425783 );
echo $milliseconds = microtime(true) . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $prng->getNext() . "\n";
echo $milliseconds = microtime(true) . "\n";
*/
?>
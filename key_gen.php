<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 23/08/2017 - Ultima atualizacao: 23/08/2017
 * Descricao: Rotinas para controle de geradores sequenciais em memória
 */

class KeyGen {
	protected $increment = null;
	protected $value = null;

	public function __construct( int $initial = 0, int $increment = 1 ) {
		$this->increment = $increment;
		$this->value = $initial;
	}

	public function last(): int {
		return $this->value;
	}

	public function next( int $increment = null ): int {
		$this->value += ($increment ?? $this->increment);
		return $this->value;
	}

	public function setValue( int $value ): KeyGen {
		$this->value = $value;
		return $this;
	}

	public function setIncrement( int $incr ): KeyGen {
		$this->increment = $incr;
		return $this;
	}
}

class KeyGenList {
	protected $generators;

	public function __construct( $names = null ) {
		$this->generators = array();

		if( $names != null ) {
			if( ! is_array( $names ) ) {
				$names = func_get_args();
			}

			foreach( $names as $n ) {
				$this->create( $n );
			}
		}
	}

	public function add( string $name, KeyGen $generator ): KeyGenList {
		$this->generators[ $name ] = $generator;
		return $this;
	}

	public function create( string $name, int $initial = 0, int $increment = 1 ): KeyGen {
		$kg = new KeyGen( $initial, $increment );
		$this->add( $name, $kg );
		return $kg;
	}

	public function delete( string $name ): KeyGenList {
		$this->generators[ $name ] = null;
		unset( $this->generators[ $name ] );
		return $this;
	}

	public function get( string $name ): KeyGen {
		return $this->generators[ $name ];
	}

	public function getLast( string $name ): int {
		return $this->generators[ $name ]->last();
	}

	public function getNext( string $name, int $increment = 1 ): int {
		return $this->generators[ $name ]->next( $increment );
	}

	public function setValue( string $name, int $value ) {
		return $this->generators[ $name ]->set( $value );
	}

	public function setIncrement( string $name, int $incr ) {
		return $this->generators[ $name ]->setIncrement( $incr );
	}
}
/*
$g = new KeyGen(0, 2);
echo $g->next() . "\n";
echo $g->next( 2 ) . "\n";
echo $g->set( 10 )->next() . "\n";
*/
?>

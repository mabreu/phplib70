<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 21/11/20107 - Ultima atualizacao: 15/02/2019
 * Descricao: Classe Processo, classe padrão para os processos que são executados, manual ou automaticamente, nos sistemas
 */
require_once( 'debuglog.php' );
require_once( 'db_conn.php' );
require_once( 'proc_lock.php' );
require_once( 'config.php' );

abstract class Processo {
	private $db_conns;

	protected $status;
	protected $log_outro_proc;
	protected $log_desabilitado;

	public function __construct( string $arquivo_config = null ) {
		$this->status = 0;
		$this->log_outro_proc = true;
		$this->log_desabilitado = true;
		Config::setFilename( $arquivo_config );
		$this->db_conns = array();
	}

	public function getLogDesabilitado(): bool {
		return $this->log_desabilitado;
	}

	public function setLogDesabilitado( bool $logDesabilitado ) {
		$this->log_desabilitado = $logDesabilitado;
	}

	public function getLogOutroProc(): bool {
		return $this->log_outro_proc;
	}

	public function setLogOutroProc( bool $logOutroProc ) {
		$this->log_outro_proc = $logOutroProc;
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function setStatus( int $status ) {
		$this->status = $status;
	}

	public function addDBConnection( string $name, DbConnection $conn ) {
		$this->closeDBConnection( $name );
		$this->db_conns[ $name ] = $conn;
	}

	public function getDBConnection( string $name ) {
		if( array_key_exists( $name, $this->db_conns ) ) {
			return $this->db_conns[ $name ];
		}

		return null;
	}

	public function closeDBConnection( string $name ) {
		$conn = $this->getDBConnection( $name );

		if( $conn != null ) {
			$conn->disconnect();
			$this->db_conns[ $name ] = null;
		}
	}

	public function connectDB( string $name ): bool {
		$conn = $this->getDBConnection( $name );

		if( $conn == null ) {
			DebugLog::log( 1, 'Conexão não encontrada: ' . $name );
			return false;
		}

		if( ! $conn->connect() ) {
			DebugLog::log( 1, 'Erro ao conectar no Banco de Dados ' . $name );
			DebugLog::log( 1, print_r( $this->conn->getLastError(), true ) );
			return false;
		}

		return true;
	}

	public function continuar(): bool {
		Config::load();
		return Config::exists( 'processo', 'ativo' ) && Config::get( 'processo', 'ativo' );
	}

	protected function pre_executar( array &$params = null ): int {
		Config::load();
		return 0;
	}

	protected function pre_travar( array &$params = null ): int {
		return 0;
	}

	protected function pos_executar( array &$params = null ): int {
		return 0;
	}

	protected function inicializar( array &$params = null ): int {
		DebugLog::log( 2, 'Inicializando processo...' );
		return 0;
	}

	protected function finalizar( array &$params = null ): int {
		DebugLog::log( 2, 'Finalizando processo...' );

		foreach( $this->db_conns as $name => $conn ) {
			$conn->disconnect();
		}

		return 0;
	}

	public function executar( array $params = null ): int {
		$this->pre_executar( $params );

		DebugLog::setLevel( Config::get( 'debug', 'level' ) );
		DebugLog::setOutput( Config::get( 'debug', 'output' ), Config::get( 'debug', 'filename' ) );
		DebugLog::setCacheSize( Config::get( 'debug', 'cache_size' ) );
		DebugLog::log( 2, 'Executando Processo...' );

		if( ! $this->continuar() ) {
			if( $this->getLogDesabilitado() ) {
				DebugLog::log( 1, 'As configuracoes do processo estao marcadas como desabilitada. Verifique as configuracoes.' );
			}

			return -1;
		}

		$this->pre_travar( $params );
		$procLock = new ProcLock( Config::get( 'dirs', 'dir_base' ), Config::get( 'processo', 'nome' ) );

		if( ! $procLock->lock() ) {
			if( $this->getLogOutroProc() ) {
				DebugLog::log( 1, 'Outro processo está sendo executado.' );
			}

			DebugLog::close();
			return -2;
		}

		$this->setStatus( $this->inicializar( $params ) );

		if( $this->getStatus() == 0 ) {
			$this->setStatus( $this->processar( $params ) );
		}

		$this->finalizar( $params );

		$procLock->unlock();
		DebugLog::close();

		$this->pos_executar( $params );
		return $this->status;
	}

	protected function getNextRow( &$fhandler, int $pulo = 0 ) {
		while( true ) {
			$line = fgets( $fhandler );

			if( $line === null ) {
				break;
			}

			$line = trim( $line );

			if( $line !== '' ) {
				return $this->parseLine( $line );
			}
		}

		return null;
	}

	protected function parseLine( $line ): array {
		return [ 0 => $line ];
	}

	abstract public function processar( array &$params = null ): int;
}
?>
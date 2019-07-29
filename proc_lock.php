<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 12/09/2016 - Ultima atualizacao: 10/10/2017
 * Descricao: Rotinas para controle de lock de processos
 */
require_once( 'debuglog.php' );

define( 'LOCK_SUFFIX', '.lck' );

class ProcLock {
	private $pid;
	private $lock_file;

	function __construct( string $lockdir, string $lockfile = null ) {
		global $argv;
		$this->pid = getMyPid();
		$this->lock_file = $lockdir . DIRECTORY_SEPARATOR . ($lockfile ?? $argv[0]) . LOCK_SUFFIX;
	}

	function __clone() {}

	private function isRunning( int $pid ): bool {
		$pids = explode( PHP_EOL, `ps -e | awk '{print $1}'` );
		return in_array( $pid, $pids );
	}

	public function lock(): bool {
		if( file_exists( $this->lock_file ) ) {
			// Is running?
			$pid = file_get_contents( $this->lock_file );

			if( self::isRunning( $pid ) ) {
				DebugLog::log( 3, '==' . $pid . '== Already in progress...' );
				return false;
			}

			DebugLog::log( 3, '==' . $pid . '== Previous job died abruptly...' );
		}

		file_put_contents( $this->lock_file, $this->pid );
		DebugLog::log( 3, '==' . $this->pid . '== Lock acquired, processing the job...' );
		return true;
	}

	public function unlock(): bool {
		if( file_exists( $this->lock_file ) )
			unlink( $this->lock_file );

		DebugLog::log( 3, '==' . $this->pid . '== Releasing lock...' );
		return true;
	}
}
?>

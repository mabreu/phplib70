<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 16/02/2016 - Ultima atualizacao: 18/06/2018
 * Descricao: Rotinas para subir e baixar arquivos em FTP
 */
set_include_path( get_include_path() . PATH_SEPARATOR . '/var/projetos/phplib70/phpseclib' );

require_once( 'Net/SSH2.php' );
require_once( 'Net/SFTP.php' );
require_once( 'Crypt/RSA.php' );
require_once( 'debuglog.php' );
require_once( 'str_utils.php' );

/*
Estrutura do array dados:

server_name => endereço do servidor FTP
user_name => nome do usuário de login no servidor FTP
password (opcional) => senha para login no servidor FTP
ppk_file (opcional) => nome do arquivo de chave pública para login no servidor FTP
prefix (opcional) => inicio do nome dos arquivos que serão transferidos
extension (opcional) => final do nome dos arquivos que serão transferidos
local_dir => diretório local onde os arquivos serão transferidos
remote_dir => diretório do servidor FTP de onde ou para onde os arquivos  serão transferidos
processed_dir => diretório (remoto ou local) para onde os arquivos serão movidos depois de transferidos
 */

abstract class FTPCustom {
	protected $connection;
	protected $extension;
	protected $local_dir;
	protected $password;
	protected $ppk_file;
	protected $prefix;
	protected $processed_dir;
	protected $remote_dir;
	protected $server_name;
	protected $user_name;
	protected $conv_case;
	protected $subdirs;
	protected $except_dirs;

	public function __construct( $server_name = null, string $user_name = null, string $password = null, string $ppkfile = null, string $remote_dir = null, string $local_dir = null, string $processed_dir = null ) {
		$this->conv_case = 0;
		$this->subdirs = false;

		if( getType( $server_name ) == 'array' ) {
			if( array_key_exists( 'server_name', $server_name ) ) {
				$this->setServerName( $server_name['server_name'] );
			}

			if( array_key_exists( 'user_name', $server_name ) ) {
				$this->setUserName( $server_name['user_name'] );
			}

			if( array_key_exists( 'password', $server_name ) ) {
				$this->setPassword( $server_name['password'] );
			}

			if( array_key_exists( 'ppkfile', $server_name ) ) {
				$this->setPPKFile( $server_name['ppkfile'] );
			}

			if( array_key_exists( 'local_dir', $server_name ) ) {
				$this->setLocalDir( $server_name['local_dir'] );
			}

			if( array_key_exists( 'remote_dir', $server_name ) ) {
				$this->setRemoteDir( $server_name['remote_dir'] );
			}

			if( array_key_exists( 'processed_dir', $server_name ) ) {
				$this->setProcessedDir( $server_name['processed_dir'] );
			}
		} else {
			if( isset( $server_name ) && $server_name != null ) {
				$this->setServerName( $server_name );
			}

			if( isset( $user_name ) && $user_name != null ) {
				$this->setUserName( $user_name );
			}

			if( isset( $password ) && $password != null ) {
				$this->setPassword( $password );
			}

			if( isset( $ppkfile ) && $ppkfile != null ) {
				$this->setPPKFile( $ppkfile );
			}

			if( isset( $remote_dir ) && $remote_dir != null ) {
				$this->setRemoteDir( $remote_dir );
			}

			if( isset( $local_dir ) && $local_dir != null ) {
				$this->setLocalDir( $local_dir );
			}

			if( isset( $processed_dir ) && $processed_dir != null ) {
				$this->setProcessedDir( $processed_dir );
			}
		}
	}

	public function setServerName( string $sn ) {
		$this->server_name = $sn;
		return $this;
	}

	public function getServerName() {
		return $this->server_name;
	}

	public function setUserName( string $un ) {
		$this->user_name = $un;
		return $this;
	}

	public function getUserName() {
		return $this->user_name;
	}

	public function setPassword( string $pwd ) {
		$this->password = $pwd;
		return $this;
	}

	public function getPassword() {
		return $this->password;
	}

	public function setPPKFile( string $ppk ) {
		$this->ppk_file = $ppk;
		return $this;
	}

	public function getPPKFile() {
		return $this->ppk_file;
	}

	public function setPrefix( string $pref ) {
		$this->prefix = $pref;
		return $this;
	}

	public function getPrefix() {
		return $this->prefix;
	}

	public function setExtension( string $ext ) {
		$this->extension = $ext;
		return $this;
	}

	public function getExtension() {
		return $this->extension;
	}

	public function setLocalDir( string $ld ) {
		$this->local_dir = $ld;
		return $this;
	}

	public function getLocalDir() {
		return $this->local_dir;
	}

	public function setRemoteDir( string $rd ) {
		$this->remote_dir = $rd;
		return $this;
	}

	public function getRemoteDir() {
		return $this->remote_dir;
	}

	public function setProcessedDir( string $pd ) {
		$this->processed_dir = $pd;
		return $this;
	}

	public function getProcessedDir() {
		return $this->processed_dir;
	}

	public function setConvertCase( int $cc ) {
		$this->conv_case = $cc;
		return $this;
	}

	public function getConvertCase(): int {
		return $this->conv_case;
	}

	public function setSubdirs( bool $subdirs = true ) {
		$this->subdirs = $subdirs;
		return $this;
	}

	public function getSubdirs(): bool {
	}

	public function setExceptDirs( string $except ) {
		$this->except_dirs = $except;
		return $this;
	}

	public function getExceptDirs() {
		return $this->except_dirs;
	}

	public function isConnected(): bool {
		return( $this->connection != null && $this->connection !== false );
	}

	public function connect() {
		if( $this->isConnected() ) {
			return $this->connection;
		}

		if( $this->ftp_connect() ) {
			return $this->connection;
		}

		return null;
	}

	public function disconnect() {
		if( $this->connection === null || $this->connection === false ) {
			return;
		}

		$this->ftp_disconnect();
		$this->connection = null;
//~ 		unset( $this->connection );
	}

	private function _download( $pref, $ext, $curdir, $callback, &$object ): int {
		$dir = $this->getList();

		if( is_bool( $dir ) ) {
			return 0;
		}

		$qtde = 0;

		foreach( $dir as $k => $l ) {
			$f = $this->parseFileInfo( $l );

			if( $f === null ) {
				continue;
			}

			$filename = $f['filename'];
//~ 			DebugLog::log( 2, "SFTP: Baixando arquivo $filename..." );

			if( $this->isFile( $f ) ) {
				if( ( empty( $pref ) || StrUtils::startsWith( $filename, $pref ) ) &&
						( empty( $ext ) || StrUtils::endsWith( $filename, $ext ) ) ) {
					$dest_name = ($this->conv_case > 0 ? strtoupper( $filename ) : ($this->conv_case < 0 ? strtolower( $filename ) : $filename));
//~ 					DebugLog::log( 2, "Baixando arquivo $curdir / $filename -> " . $this->getLocalDir() . " / $dest_name..." );

					if( ! $this->getFile( $curdir . '/' . $filename, $this->getLocalDir() . DIRECTORY_SEPARATOR . $dest_name ) ) {
						DebugLog::log( 1, "Erro baixando arquivo. $filename" );
						return -2;
					}

					if( $this->getProcessedDir() != null && $this->getProcessedDir() != '' ) {
//~ 						DebugLog::log( 3, "Movendo arquivo $filename..." );
//~ 						DebugLog::log( 2, "SFTP: callback $callback" );

						if( $callback == null ) {
							$dest_dir = $this->getProcessedDir();
							$dest_name = $dest_dir . '/' . $filename;
						} else {
							if( $object == null ) {
								$dest_name = call_user_func( $callback, $filename, $this->getProcessedDir(), $curdir );
							} else {
								$dest_name = call_user_func( array( $object, $callback ), $filename, $this->getProcessedDir(), $curdir );
							}

	//~ 						DebugLog::log( 3, "SFTP: destname $dest_name" );

							if( $dest_name === null || $dest_name === false ) {
								break;
							}

							if( $dest_name == '' || $dest_name === true ) {
								$dest_dir = $this->getProcessedDir();
								$dest_name = $dest_dir . '/' . $filename;
							} else {
								$dest_dir = dirname( $dest_name );
							}
						}

	//~ 					DebugLog::log( 1, "dest_dir: $dest_dir  dest_name: $dest_name" );

						if( ! empty( $dest_dir ) && ! $this->chdir( $dest_dir ) ) {
							if( $this->mkdir( $dest_dir) == false ) {
								DebugLog::log( 1, "Não foi possível criar o diretório destino do arquivo: " . $dest_dir );
								return -3;
							}
						}

						$this->chdir( $curdir );

						if( ! $this->rename( $curdir . '/' . $filename, $dest_name ) ) {
							DebugLog::log( 1, "Erro movendo arquivo $curdir/$filename para $dest_name" );
						}
					}

					$qtde++;
				}
			} elseif( $this->isDir( $f ) && $f['filename'] != '.' && $f['filename'] != '..' ) {
				if( $this->subdirs ) {
					$newdir = $curdir . '/' . $f['filename'];
//~ 					DebugLog::log( 2, 'Subdir: ' . $newdir );

					if( empty( $this->except_dirs ) || strpos( $this->except_dirs, $newdir ) === false ) {
						$this->chdir( $newdir );
						$res = $this->_download( $pref, $ext, $newdir, $callback, $object );
						$this->chdir( $curdir );

						if( $res < 0 ) {
							return $res;
						}

						$qtde += $res;
					}
				}
			}
		}

		return $qtde;
	}

	public function download( string $pref = null, string $ext = null, string $callback = null, &$object = null ): int {
		if( $pref != null ) {
			$this->setPrefix( $pref );
		}

		if( $ext != null ) {
			$this->setExtension( $ext );
		}

		if( $this->connect() == null ) {
			return -1;
		}

//~ 		DebugLog::log( 2, 'SFTP: Baixando arquivos...' );
		$this->chdir( $this->getRemoteDir() );
		$qtde = $this->_download( $this->getPrefix(), $this->getExtension(), $this->getRemoteDir(), $callback, $object );
		return $qtde;
	}

	public function upload( string $pref = null, string $ext = null, string $callback = null, &$object = null ): int {
		if( $pref != null ) {
			$this->setPrefix( $pref );
		}

		if( $ext != null ) {
			$this->setExtension( $ext );
		}

		$pref = $this->getPrefix();
		$ext = $this->getExtension();

		if( $this->connect() == null ) {
			return -1;
		}

//~ 		DebugLog::log( 2, 'Subindo arquivos...' );
		$this->chdir( $this->getRemoteDir() );
		$filelist = scandir( $this->getLocalDir() );

		if( is_bool( $filelist ) ) {
			return 0;
		}

		$qtde = 0;

		foreach( $filelist as $k => $filename ) {
			if( ( empty( $pref ) || StrUtils::startsWith( $filename, $pref ) ) &&
					( empty( $ext ) || StrUtils::endsWith( $filename, $ext ) ) ) {
//~ 				DebugLog::log( 3, "Subindo arquivo $filename..." );
				$dest_name = ($this->conv_case > 0 ? strtoupper( $filename ) : ($this->conv_case < 0 ? strtolower( $filename ) : $filename));
//~ 				DebugLog::log( 2, "FTP: Subindo arquivo " . $this->getLocalDir() . DIRECTORY_SEPARATOR . $filename . ' -> ' . $dest_name );

				if( ! $this->putFile( $this->getLocalDir() . DIRECTORY_SEPARATOR . $filename, $dest_name ) ) {
					DebugLog::log( 1, "Erro subindo arquivo. $filename" );
					return -2;
				}

				if( ! empty( $this->getProcessedDir() ) ) {
//~ 					DebugLog::log( 3, "Movendo arquivo $filename..." );

					if( $callback == null ) {
						$dest_dir = $this->getProcessedDir();
						$dest_name = $dest_dir . DIRECTORY_SEPARATOR . $filename;
					} else {
						if( $object == null ) {
							$dest_name = call_user_func( $callback, $filename, $this->getProcessedDir(), $this->getLocalDir() );
						} else {
							$dest_name = call_user_func( array( $object, $callback ), $filename, $this->getProcessedDir(), $this->getLocalDir() );
						}

						if( $dest_name === null || $dest_name === false ) {
							break;
						}

						if( $dest_name == '' || $dest_name === true ) {
							$dest_dir = $this->getProcessedDir();
							$dest_name = $dest_dir . DIRECTORY_SEPARATOR . $filename;
						} else {
							$dest_dir = dirname( $dest_name );
						}
					}

					if( ! empty( $dest_dir ) ) {
						if( ! file_exists( $dest_dir ) ) {
							if( ! mkdir( $dest_dir, 0777, true ) ) {
								DebugLog::log( 1, "Não foi possível criar o diretório destino do arquivo: " . $dest_dir );
								return -3;
							}
						}

						if( ! rename( $this->getLocalDir() . DIRECTORY_SEPARATOR . $filename, $dest_name ) ) {
							DebugLog::log( 1, "Erro movendo arquivo " . $this->getLocalDir() . DIRECTORY_SEPARATOR . "$filename para $dest_name" );
						}
					}
				}

				$qtde++;
			}
		}

		return $qtde;
	}

	abstract protected function ftp_connect(): bool;
	abstract protected function ftp_disconnect();
	abstract protected function chdir( string $dir ): bool;
	abstract protected function mkdir( string $name ): bool;
	abstract protected function rename( string $old_name, string $new_name ): bool;
	abstract protected function getFile( string $remote, string $local ): bool;
	abstract protected function putFile( string $local, string $remote ): bool;
	abstract protected function isFile( array &$fileInfo ): bool;
	abstract protected function isDir( array &$fileInfo ): bool;
	abstract protected function parseFileInfo( $line );
}

class FTP extends FTPCustom {
	public function __construct( $server_name = null, $user_name = null, $password = null, $ppkfile = null, $remote_dir = null, $local_dir = null, $processed_dir = null ) {
		parent::__construct( $server_name, $user_name, $password, $ppkfile, $remote_dir, $local_dir, $processed_dir );
	}

	public function setPassiveMode( bool $opt ): bool {
		return ftp_pasv( $this->connection, $opt );
	}

	protected function ftp_connect(): bool {
//~ 		DebugLog::log( 2, 'Conectando no FTP ' . $this->getServerName() . '...' );
		$sftp = ftp_connect( $this->getServerName() );

		if( $sftp == null || $sftp === false ) {
			DebugLog::log( 1, 'Não foi possível conectar no servidor.' );
			return false;
		}

//~ 		DebugLog::log( 3, 'Fazendo login ' . $this->getUserName() . ' / ' . $this->getPassword() . '...' );

		if( ! ftp_login( $sftp, $this->getUserName(), $this->getPassword() ) ) {
			DebugLog::log( 1, 'Não foi possível fazer o login.' );
			return false;
		}

		$this->connection = $sftp;
		return true;
	}

	protected function ftp_disconnect() {
		ftp_close( $this->connection );
	}

	public function chdir( string $dir ): bool {
		if( ftp_chdir( $this->connection, $dir ) ) {
			$this->setRemoteDir( $dir );
			return true;
		}

		return false;
	}

	public function getList() {
		return ftp_rawlist( $this->connection, $this->getRemoteDir() );
	}

	public function mkdir( string $name ): bool {
		return (ftp_mkdir( $this->connection, $name ) !== false);
	}

	public function rename( string $old_name, string $new_name ): bool {
		return ftp_rename( $this->connection, $old_name, $new_name );
	}

	protected function getFile( string $remote, string $local ): bool {
		return ftp_get( $this->connection, $local, $remote, FTP_BINARY );
	}

	protected function putFile( string $local, string $remote ): bool {
//~ 		DebugLog::log( 2, "FTP: PutFile: " . $local . ' -> ' . $remote );
		return ftp_put( $this->connection, $remote, $local, FTP_BINARY );
	}

	protected function isFile( array &$fileInfo ): bool {
		return ($fileInfo['type'] == '-');
	}

	protected function isDir( array &$fileInfo ): bool {
		return ($fileInfo['type'] == 'd');
	}

	protected function parseFileInfo( $line ) {
		if( substr( strtolower( $line ), 0, 5) == 'total' ) { // first line, skip it
			return null;
		}

		preg_match( '/' . str_repeat( '([^\s]+)\s+', 7 ) . '([^\s]+) (.*)/', $line, $matches );
		list( $permissions, $children, $owner, $group, $size, $month, $day, $time, $filename ) = array_slice( $matches, 1 );
		$date = date( 'd/m/y H:i', (strpos( $time, ':' ) ? mktime( substr( $time, 0, 2 ), substr( $time, -2 ), 0, (int) $month, $day ) : mktime( 0, 0, 0, (int) $month, $day, $time )) );
		$file_info = array( 'filename' => $filename, 'type' => $permissions[0], 'permissions' => substr( $permissions, 1 ),
							'children' => $children, 'owner' => $owner, 'group' => $group, 'size' => $size, 'date' => $date );
		return $file_info;
	}
}

class SFTP extends FTPCustom {
	public function __construct( $server_name = null, string $user_name = null, string $password = null, string $ppkfile = null, string $remote_dir = null, string $local_dir = null, string $processed_dir = null ) {
		parent::__construct( $server_name, $user_name, $password, $ppkfile, $remote_dir, $local_dir, $processed_dir );
	}

	protected function ftp_connect(): bool {
		$key = new Crypt_RSA();

		if( $this->getPPKFile() != null )  {
			$key->loadKey( file_get_contents( $this->getPPKFile() ) );

		} elseif( $this->getPassword() != null )  {
//~ 			$key->setPassword( $this->getPassword() );
			$key = $this->getPassword();
		}

//~ 		DebugLog::log( 2, 'Conectando no FTP ' . $this->getServerName() . '...' );
		$sftp = new Net_SFTP( $this->getServerName() );

		if( $sftp == null || $sftp === false ) {
			DebugLog::log( 1, 'Não foi possível conectar no servidor.' );
			return false;
		}

		if( ! $sftp->login( $this->getUserName(), $key ) ) {
			DebugLog::log( 1, 'Não foi possível fazer o login.' );
			return false;
		}

		$this->connection = $sftp;
		return true;
	}

	protected function ftp_disconnect() {
	}

	public function chdir( string $dir ): bool {
		if( $this->connection->chdir( $dir ) ) {
			$this->setRemoteDir( $dir );
			return true;
		}

		return false;
	}

	public function getList() {
		return $this->connection->rawlist();
	}

	public function mkdir( string $name ): bool {
		return ($this->connection->mkdir( $name, 0777, true ) !== false );
	}

	public function rename( string $old_name, string $new_name ): bool {
		return $this->connection->rename( $old_name, $new_name );
	}

	protected function getFile( string $remote, string $local ): bool {
		return ($this->connection->get( $remote, $local ) == 1);
	}

	protected function putFile( string $local, string $remote ): bool {
		return ($this->connection->put( $remote, $local, NET_SFTP_LOCAL_FILE ) == 1);
	}

	protected function isFile( array &$fileInfo ): bool {
		return ($fileInfo['type'] == NET_SFTP_TYPE_REGULAR);
	}

	protected function isDir( array &$fileInfo ): bool {
		return ($fileInfo['type'] == NET_SFTP_TYPE_DIRECTORY);
	}

	protected function parseFileInfo( $line ) {
		return $line;
	}
}
/*
$a = null;
$v = array();
$v[1] = null;
echo '[', is_null( $a ), '-', isset( $a ), '-', empty( $a ) , ']', "\n"; // [1--1]
echo '[', is_null( $v ), '-', isset( $v ), '-', empty( $v ) , ']', "\n"; // [-1-]
echo '[', is_null( $v[1] ), '-', isset( $v[1] ), '-', empty( $v[1] ) , ']', "\n"; // [1--1]
echo '[', '-', isset( $v[2] ), '-', empty( $v[2] ) , ']', "\n"; // [--1]
*/
?>

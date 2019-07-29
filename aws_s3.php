<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 04/07/2018 - Ultima atualizacao: 04/07/2018
 * Descricao: Classes de auxilio para utilização de recursos da AWS
 */
require_once( 'debuglog.php' );
require_once( 'vendor/autoload.php' );

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AwsS3 {
	private $bucket;
	private $region;
	private $version;
	private $s3_client;
	private $last_error;
	private $last_result;

	public function __construct( string $bucket, string $region = 'sa-east-1', string $version = 'latest' ) {
		$this->bucket = $bucket;
		$this->region = $region;
		$this->version = $version;
		$this->s3_client = null;
	}

	public function setBucket( string $bucket ): AwsS3 {
		$this->bucket = $bucket;
		$this->s3_client = null;
		return $this;
	}

	public function getBucket(): string {
		return $this->bucket;
	}

	public function setRegion( string $region ): AwsS3 {
		$this->region = $region;
		$this->s3_client = null;
		return $this;
	}

	public function getRegion(): string {
		return $this->region;
	}

	public function setVersion( string $version ): AwsS3 {
		$this->version = $version;
		$this->s3_client = null;
		return $this;
	}

	public function getVersion(): string {
		return $this->version;
	}

	public function getError() {
		return $this->last_error;
	}

	public function getErrorMessage() {
		if( $this->last_error == null ) {
			return null;
		}

		return $this->last_error->getMessage();
	}

	public function getResult() {
		return $this->last_result;
	}

	private function getClient() {
		if( $this->s3_client == null ) {
			$s3_config = ['region' => $this->getRegion(), 'version' => $this->getVersion()];
			$sdk = new Aws\Sdk( $s3_config );
			$this->s3_client = $sdk->createS3();
		}

		return $this->s3_client;
	}

	public function putObject( string $key, string $filename ): bool {
		$this->last_error = null;
		$this->last_result = null;

		try {
			$s3_client = $this->getClient();
			$this->last_result = $s3_client->putObject( [
				'Bucket'     => $this->getBucket(),
				'Key'        => $key,
				'SourceFile' => $filename
			] );
		} catch( S3Exception $e ) {
			$this->last_error = $e;
			return false;
		}

		return true;
	}

	public function putFiles( string $dir_orig, string $dir_s3, string $dir_dest = null, $prefix = null, $sufix = null,
				$callback_dirs3 = null, $callback_dirdest = null, $object = null ): int {
		$arquivos = scandir( $dir_orig );
		$qtd = 0;

		if( $arquivos !== false ) {
			foreach( $arquivos as $k => $arq ) {
				if( ( $prefix == null || StrUtils::startsWith( $arq, $prefix ) ) &&
						( $sufix == null || StrUtils::endsWith( $arq, $sufix ) ) ) {
					if( $callback_dirs3 == null ) {
						$dir_write = $dir_s3;
					} else {
						if( $object == null ) {
							$dir_write = call_user_func( $callback_dirs3, $arq, $dir_orig, $dir_s3, $dir_dest );
						} else {
							$dir_write = call_user_func( array( $object, $callback_dirs3 ), $arq, $dir_orig, $dir_s3, $dir_dest );
						}
					}

					$res = $this->putObject( $dir_write . DIRECTORY_SEPARATOR . $arq, $dir_orig . DIRECTORY_SEPARATOR . $arq );

					if( ! $res ) {
						DebugLog::log( 1, "Erro copiando o arquivo '$arq' para S3: " . $this->getErrorMessage() );
						return -$k;
					}

					$qtd++;

					if( $dir_dest !== null ) {
						if( $callback_dirdest == null ) {
							$dir_write = $dir_dest;
						} else {
							if( $object == null ) {
								$dir_write = call_user_func( $callback_dirdest, $arq, $dir_orig, $dir_s3, $dir_dest );
							} else {
								$dir_write = call_user_func( array( $object, $callback_dirdest ), $arq, $dir_orig, $dir_s3, $dir_dest );
							}
						}

						if( ! file_exists( $dir_write ) ) {
							mkdir( $dir_write, 0777, true );
						}

						rename( $dir_orig . DIRECTORY_SEPARATOR . $arq, $dir_write . DIRECTORY_SEPARATOR . $arq );
					}
				}
			}
		}

		return $qtd;
	}
}
?>
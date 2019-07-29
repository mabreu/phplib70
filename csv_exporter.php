<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 27/04/2018 - Ultima atualizacao: 04/07/2018
 * Descricao: Classe para auxiliar na geração arquivos CSV
 */
require_once( 'db_conn.php' );

class CsvExporter {
	// Delimited Fields ('...,"Marco Abreu",...' OR '...,Marco Abreu,...')
	const CED_NONE		= 0;
	const CED_STRINGS	= 1;
	const CED_DATES		= 2;
	const CED_NUMBERS	= 4;
	const CED_BOOLEANS	= 8;
	const CED_ALL		= 15;

	const CEF_NONE			= 0;
	const CEF_STANDARD		= 1;
	const CEF_ALTERNATIVE	= 2;

	private $abort_on_no_rows, $bool_true_as, $bool_false_as, $dbconn, $cur, $delete_file_on_error, $delimiter, $delimited_fields,
			$field_types_delim, $fhandler, $filename, $open_mode, $terminator, $null_as, $separator, $sql, $has_header;

	protected function __construct( DbConnection $dbconn, string $filename = null, string $sql = null ) {
		$this->dbconn = $dbconn;
		$this->filename = $filename;
		$this->sql = $sql;
		$this->open_mode = 'w';
		$this->abort_on_no_rows = false;
		$this->delete_file_on_error = true;
		$this->has_header = true;
		$this->separator = ',';
		$this->decimal = '.';
		$this->delimiter = '"';
		$this->field_types_delim = self::CED_STRINGS;
		$this->terminator = "\n";
		$this->null_as = '';
		$this->bool_true_as = 'true';
		$this->bool_false_as = 'false';
		$this->delimited_fields = array();
	}

	public function setDbConnection( DbConnection $dbconn ): CsvExporter {
		$this->dbconn = $dbconn;
		return $this;
	}

	public function getDbConnection(): DbConnection {
		return $this->dbconn;
	}

	public function setFilename( string $filename ): CsvExporter {
		$this->filename = $filename;
		$this->cur = null;
		return $this;
	}

	public function getFilename(): string {
		return $this->filename;
	}

	public function setSQL( string $sql ): CsvExporter {
		$this->sql = $sql;
		return $this;
	}

	public function getOpenMode(): string {
		return $this->open_mode;
	}

	public function setOpenMode( string $open_mode = 'w' ): CsvExporter {
		$this->open_mode = $open_mode;
		return $this;
	}

	public function getFormat(): int {
		return $this->format;
	}

	public function setFormat( int $format = self::CEF_STANDARD ): CsvExporter {
		if( $format == self::CEF_STANDARD ) {
			$this->setSeparator( ',' )->setDelimiter( '"' )->setDecimal( '.' );
		} elseif( $format == self::CEF_ALTERNATIVE ) {
			$this->setSeparator( ';' )->setDelimiter( '"' )->setDecimal( ',' );
		}

		$this->format = $format;
		return $this;
	}

	public function getSeparator(): string {
		return $this->separator;
	}

	public function setSeparator( string $separator = ',' ): CsvExporter {
		$this->separator = $separator;
		return $this;
	}

	public function getDelimiter(): string {
		return $this->delimiter;
	}

	public function setDelimiter( string $delimiter = '"' ): CsvExporter {
		$this->delimiter = $delimiter;
		return $this;
	}

	public function getFieldTypesDelimited(): string {
		return $this->field_types_delim;
	}

	public function setFieldTypesDelimited( int $ftd = self::CED_STRINGS ): CsvExporter {
		$this->field_types_delim = $ftd;
		return $this;
	}

	public function addDelimitedField( string ...$names ): CsvExporter {
		foreach( $names as $n ) {
			$this->delimited_fields[ $n ] = true;
		}

		return $this;
	}

	public function getDelimitedField( string $name ): bool {
		return ( isset( $this->delimited_fields[ $name ] ) ? $this->delimited_fields[ $name ] : false);
	}

	public function getDecimal(): string {
		return $this->decimal;
	}

	public function setDecimal( string $decimal = '.' ): CsvExporter {
		$this->decimal = $decimal;
		return $this;
	}

	public function getTerminator(): string {
		return $this->terminator;
	}

	public function setTerminator( string $terminator = "\n" ): CsvExporter {
		$this->terminator = $terminator;
		return $this;
	}

	public function getNullAs(): string {
		return $this->null_as;
	}

	public function setNullAs( string $null_as = '' ): CsvExporter {
		$this->null_as = $null_as;
		return $this;
	}

	public function getBooleanTrueAs(): string {
		return $this->bool_true_as;
	}

	public function getBooleanFalseAs(): string {
		return $this->bool_false_as;
	}

	public function setBooleanTrueAs( string $bool_true_as = 'true' ): CsvExporter {
		$this->bool_true_as = $bool_true_as;
		return $this;
	}

	public function setBooleanFalseAs( string $bool_false_as = 'false' ): CsvExporter {
		$this->bool_false_as = $bool_false_as;
		return $this;
	}

	public function setBooleanAs( string $bool_true_as = 'true', string $bool_false_as = 'false' ): CsvExporter {
		$this->bool_true_as = $bool_true_as;
		$this->bool_false_as = $bool_false_as;
		return $this;
	}

	public function getAbortOnNoRows(): bool {
		return $this->abort_on_no_rows;
	}

	public function setAbortOnNoRows( bool $abort_on_no_rows = true ): CsvExporter {
		$this->abort_on_no_rows = $abort_on_no_rows;
		return $this;
	}

	public function getDeleteFileOnError(): bool {
		return $this->delete_file_on_error;
	}

	public function setDeleteFileOnError( bool $delete_file_on_error = true ): CsvExporter {
		$this->delete_file_on_error = $delete_file_on_error;
		return $this;
	}

	public function getHasHeader(): bool {
		return $this->has_header;
	}

	public function setHasHeader( bool $has_header = true ): CsvExporter {
		$this->has_header = $has_header;
		return $this;
	}

	protected function writeLine( $line ): int {
		return fwrite( $this->fhandler, $line );
	}

	private function closeFile( bool $error = false ) {
		fclose( $this->fhandler );

		if( $error && $this->GetDeleteFileOnError() ) {
			unlink( $this->getFilename() );
		}
	}

	public function export( string $filename = '' ): int {
		if( $filename !== '' ) {
			$this->setFilename( $filename );
		}

		$res = $this->beforeOpenCursor();

		if( $res != 0 ) {
			return $res;
		}

		$this->cur = $this->GetDbConnection()->getCursor( $this->sql );

		if( $this->cur == null ) {
			DebugLog::log( 1, 'Erro buscando dados para exportar. ' . $this->sql );
			DebugLog::log( 1, print_r( $this->getDbConnection()->getLastError(), true ) );
			return -1;
		}

		$res = $this->beforeOpenFile();

		if( $res != 0 ) {
			$this->cur->close();
			return $res;
		}

		$this->fhandler = fopen( $this->getFilename(), $this->getOpenMode() );

		if( $this->fhandler === false ) {
			DebugLog::log( 1, 'Não foi possível criar o arquivo ' . $this->getFilename() );
			$this->cur->close();
			return -2;
		}

		$row = $this->cur->next();

		if( $row == null ) {
			$res = $this->onNoRows( $row );

			if( $res != 0 ) {
				$this->cur->close();
				$this->closeFile( true );
				return $res;
			}

			if( $this->getAbortOnNoRows() ) {
				DebugLog::log( 1, 'Sem linhas para exportar: ' . $this->getFilename() );
				$this->cur->close();
				$this->closeFile( true );
				return -3;
			}
		}

		if( $this->getHasHeader() ) {
			$header = $this->onGetHeader( $row );

			if( $header === null ) {
				if( $row != null ) {
					$line = implode( $this->getSeparator(), array_keys( $row ) ) . $this->getTerminator();
					$this->writeLine( $line );
				} else {
					DebugLog::log( 1, 'Sem colunas para gerar cabeçalho: ' . $this->getFilename() );
					$this->cur->close();
					$this->closeFile( true );
					return -4;
				}
			} else {
				if( is_array( $header ) ) {
					$line = implode( $this->getSeparator(), $header ) . $this->getTerminator();
					$this->writeLine( $line );
				} else {
					$this->writeLine( $header );
				}
			}
		}

		$res = $this->onFirstRow( $row );

		if( $res != 0 ) {
			$this->cur->close();
			$this->closeFile( true );
			return $res;
		}

		while( $row ) {
			$res = $this->onParseRow( $row );

			if( $res != 0 ) {
				$this->cur->close();
				$this->closeFile( true );
				return $res;
			}

			foreach( $row as $c => $v ) {
				if( is_null( $v ) ) { // se for nulo
					if( $this->getDelimitedField( $c ) ) {
						$row[ $c ] = $this->getDelimiter() . $this->getNullAs() . $this->getDelimiter();
					} else {
						$row[ $c ] = $this->getNullAs();
					}

				} elseif( is_bool( $v ) ) { // Se for boolean
					if( ( $this->getFieldTypesDelimited() & self::CED_BOOLEANS ) == self::CED_BOOLEANS || $this->getFieldTypesDelimited() == self::CED_ALL ||
							$this->getDelimitedField( $c ) ) {
						$row[ $c ] = $this->getDelimiter() . ($v ? $this->getBooleanTrueAs() : $this->getBooleanFalseAs()) . $this->getDelimiter();
					} else {
						$row[ $c ] = ($v ? $this->getBooleanTrueAs() : $this->getBooleanFalseAs());
					}

				} elseif( is_numeric( $v ) ) {  // Se for número
					if( ( $this->getFieldTypesDelimited() & self::CED_NUMBERS ) == self::CED_NUMBERS || $this->getFieldTypesDelimited() == self::CED_ALL ||
							$this->getDelimitedField( $c ) ) {
						$row[ $c ] = $this->getDelimiter() . str_replace( '.', $this->getDecimal(), '' . $v ) . $this->getDelimiter();
					} else {
						$row[ $c ] = str_replace( '.', $this->getDecimal(), '' . $v );
					}

				} elseif( is_object( $v ) && $v instanceOf DateTime ) {  // Se for objeto e instancia de data
					if( ( $this->getFieldTypesDelimited() & self::CED_DATES ) == self::CED_DATES || $this->getFieldTypesDelimited() == self::CED_ALL ||
							$this->getDelimitedField( $c ) ) {
						$row[ $c ] = $this->getDelimiter() . $v->format( 'Y-m-d H:i:s' ) . $this->getDelimiter();
					} else {
						$row[ $c ] = $v->format( 'Y-m-d H:i:s' );
					}


				} else { // Senão pode ser data ou string
					if( strlen( $v ) == 19 && strpos( '-', $v ) !== false ) {
						$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $v );
					} elseif( strlen( $v ) == 10 && strpos( '-', $v ) !== false ) {
						$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $v . ' 00:00:00' );
					} else {
						$dt = false;
					}

					if( $dt !== false ) { // É uma data válida
						if( ( $this->getFieldTypesDelimited() & self::CED_DATES ) == self::CED_DATES || $this->getFieldTypesDelimited() == self::CED_ALL ||
								$this->getDelimitedField( $c ) ) {
							$row[ $c ] = $this->getDelimiter() . $v . $this->getDelimiter();
						} else {
							$row[ $c ] = $v;
						}
					} else { // Então trata como string
						if( ( $this->getFieldTypesDelimited() & self::CED_STRINGS ) == self::CED_STRINGS || $this->getFieldTypesDelimited() == self::CED_ALL ||
								$this->getDelimitedField( $c ) ) {
							$row[ $c ] = $this->getDelimiter() . $v . $this->getDelimiter();
						} else {
							$row[ $c ] = $v;
						}
					}
				}
			}

			$line = implode( $this->getSeparator(), $row ) . $this->getTerminator();
			$line = $this->beforeWriteLine( $line );

			if( $line === null ) {
				$this->cur->close();
				$this->closeFile( true );
				return -3;
			}

			$this->writeLine( $line );
			$row = $this->cur->next();
			$res = $this->onNextRow( $row );

			if( $res != 0 ) {
				$this->cur->close();
				$this->closeFile( true );
				return $res;
			}
		}

		$res = $this->afterLastRow();
		$this->cur->close();
		$this->closeFile( $res !== 0 );

		if( $res == 0 ) {
			$res = $this->afterCloseFile();
		}

		return $res;
	}

	protected function beforeOpenCursor(): int {
		return 0;
	}

	protected function beforeOpenFile(): int {
		return 0;
	}

	protected function beforeWriteLine( string $line ): string {
		return $line;
	}

	protected function onNoRows( &$row ): int {
		return 0;
	}

	protected function onGetHeader( &$row ) {
		return null;
	}

	protected function onFirstRow( &$row ): int {
		return 0;
	}

	protected function onParseRow( &$row ): int {
		return 0;
	}

	protected function onNextRow( &$row ): int {
		return 0;
	}

	protected function afterLastRow(): int {
		return 0;
	}

	protected function afterCloseFile(): int {
		return 0;
	}
}
?>
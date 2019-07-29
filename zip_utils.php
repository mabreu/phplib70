<?php
/*
 * Autor: Marco Antonio Abreu
 * Data: 08/07/2015 - Ultima atualizacao: 10/10/2017
 * Descricao: Rotinas para compactar e descompactar arquivos
 */
//~ set_include_path( get_include_path() . PATH_SEPARATOR . '/var/projetos/phplib/PHPMailer-master' );

class ZipUtils {
	public static function decompress( string $file_name ): string {
		// Raising this value may increase performance
		$buffer_size = 32768; // read 32kb at a time
		$out_file_name = str_replace( '.gz', '', $file_name );

		// Open our files (in binary mode)
		$file = gzopen( $file_name, 'rb' );
		$out_file = fopen( $out_file_name, 'wb' );

		// Keep repeating until the end of the input file

		while( ! gzeof( $file ) ) {
			fwrite( $out_file, gzread( $file, $buffer_size ) );
		}

		fclose( $out_file );
		gzclose( $file );
		return $out_file_name;
	}

	/* creates a compressed zip file */
	public static function compress( $files = array(), string $destination = '', bool $overwrite = false, bool $move = false ): bool {
		//if the zip file already exists and overwrite is false, return false
		if( file_exists( $destination ) ) {
			if( ! $overwrite ) {
				return false;
			}
		} else {
			$overwrite = false;
		}

		//vars
		$valid_files = array();

		//if files were passed in...
		if( is_array( $files ) ) {
			//cycle through each file
			foreach( $files as $file ) {
				//make sure the file exists
				if( file_exists( $file ) ) {
					array_push( $valid_files, $file );
				}
			}
		} else {
			if( file_exists( $files ) ) {
				array_push( $valid_files, $files );
			}
		}

		//if we have good files...
		if( count( $valid_files ) == 0 ) {
			return false;
		}

		//create the archive
		$zip = new ZipArchive();

		if( $zip->open( $destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE ) !== true ) {
			return false;
		}

		//add the files
		foreach( $valid_files as $file ) {
			if( ! $zip->addFile( $file, basename( $file ) ) ) {
				$zip->close();
				return false;
			}
		}

		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

		//close the zip -- done!
		$zip->close();

		if( $move ) {
			foreach( $valid_files as $file ) {
				unlink( $file );
			}
		}

		//check to make sure the file exists
		return file_exists( $destination );
	}
}
?>
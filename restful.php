<?php
/*
 * Arquivo: restfull.php
 * Autor: Marco Antonio Abreu
 * Data: 13/09/2019 - Ultima atualização: 01/10/2019
 * Objetivo: Criar classes para executar metodos de API Rest
 */

//~ require_once( 'debuglog.php' );

class RestRoute {
	// Todos os métodos chamam o método geral para o caso dele não ser sobrescrito na classe filha

	public function connect( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	public function delete( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	public function head( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	public function get( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	public function options( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	public function patch( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	public function post( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	public function put( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	public function trace( Request &$request, Response &$response, Params &$params ): int {
		return $this->methodNotImplemented( $request, $response, $params );
	}

	private function methodNotImplemented( Request &$request, Response &$response, Params &$params ): int {
		$response->addData( 'error', 'Verb not implemented.' );
		return 501;
	}
}

class Params {
	private $params;

	public function __construct( array &$params ) {
		$this->params = $params;
	}

	public function get( string $param_name ): string {
		return $this->params[ $param_name ];
	}

	public function set( string $param_name, string $param_value ): Params {
		$this->params[ $param_name ] = $param_value;
		return $this;
	}
}

class Request {
	private $server;

	public function __construct( array &$server ) {
		$this->server = $server;
	}

	public function getVerb(): string {
		return strtoupper( $this->getInfo( 'REQUEST_METHOD' ) );
	}

	public function getURI(): string {
		return $this->getInfo( 'REQUEST_URI' );
	}

	public function getInfo( string $info ): string {
		return $this->server[ $info ];
	}
}

class Response {
	private $charset, $contents, $data, $headers, $response_code;

	public function __construct() {
		$this->response_code = 0;
		$this->charset = 'UTF-8';
		$this->headers = array();
		$this->data = array();
		$this->contents = array();
	}

	public function addHeader( string $header ): Response {
//~ 		DebugLog::log( 2, "addHeader $header" );
		$this->headers[] = $header;
		return $this;
	}

	public function addContentType( string $content_type ): Response {
		$this->addHeader( 'Content-type:' . $content_type . '; charset=' . $this->charset );
		return $this;
	}

	public function addContentTypeCSS(): Response {
		$this->addContentType( 'text/css' );
		return $this;
	}

	public function addContentTypeHTML(): Response {
		$this->addContentType( 'text/html' );
		return $this;
	}

	public function addContentTypeJavaScript(): Response {
		$this->addContentType( 'text/javascript' );
		return $this;
	}

	public function addContentTypeJSON(): Response {
//~ 		DebugLog::log( 2, "AddContentTypeJSON" );
		$this->addContentType( 'application/json' );
		return $this;
	}

	public function addContentTypeJPEG(): Response {
		$this->addContentType( 'image/jpeg' );
		return $this;
	}

	public function addContentTypePNG(): Response {
		$this->addContentType( 'image/png' );
		return $this;
	}

	public function addContentTypePDF(): Response {
		$this->addContentType( 'application/pdf' );
		return $this;
	}

	public function addContentTypeText(): Response {
		$this->addContentType( 'text/plain' );
		return $this;
	}

	public function addContentTypeXML(): Response {
		$this->addContentType( 'text/xml' );
		return $this;
	}

	public function addData( string $key, $value ): Response {
		$this->data[ $key ] = $value;
		return $this;
	}

	public function addValue( $value ): Response {
		array_push( $this->data, $value );
		return $this;
	}

	public function setCharset( string $charset ) {
		$this->charset = $charset;
	}

	public function setResponseCode( int $code, string $message = '' ) {
		$this->response_code = $code;

		if( ! empty( $message ) ) {
			$this->addData( 'error', $message );
		}
	}

	public function getResponse(): string {
		http_response_code( $this->response_code );

		foreach( $this->headers as $h ) {
			header( $h );
		}

		return json_encode( $this->data );
	}
}

class Restful {
	private $routes_list, $request, $response, $params;

	public function __construct() {
//~ 		DebugLog::log( 2, "Iniciando" );
		$this->routes_list = array();
		$this->request = new Request( $_SERVER );
		$this->params = new Params( $_REQUEST );
		$this->response = new Response();
//~ 		DebugLog::log( 2, "instanciados" );
	}

	public function addRoute( string $route_path, string $classname, string $filename ) {
//~ 		DebugLog::log( 2, "Adicionando rota $route_path" );
		$this->routes_list[ $route_path ] = ['classname' => $classname, 'filename' => $filename];
	}

	private function parseURI( string $request_uri, string $model_uri ) {
//~ 		DebugLog::log( 2, "parseURI: $request_uri $model_uri" );
		$hRequest = split( '/', $request_uri );
		$hModel = split( '/', $model_uri );

		foreach( $hModel as $i => $h ) {
			$pos = strpos( $h, '{' );

			if( $pos !== false ) {
				$pos_fim = strpos( $h, '}', $pos + 1 );

				if( $pos_fim !== false ) {
					$name = substr( $h, $pos + 1, $pos_fim - $pos - 1 );

					if( array_key_exists( $i, $hRequest ) ) {
						$this->params->set( $name, $hRequest[ $i ] );
					}
				}
			}
		}
	}

	private function getRoute( string $request_uri ): ?array {
		$msize = -1;
		$route = null;
		$uri = null;
//~ 		DebugLog::log( 2, "getRoute" );

		foreach( $this->routes_list as $u => $rt ) {
			$size = strlen( $u );

			if( substr( $request_uri, 0, $size ) == $u ) {
				if( $size > $msize ) {
					$msize = $size;
					$route = $rt;
					$uri = $u;
				}
			}
		}

		if( ! empty( $uri ) && strpos( $uri, '{' ) !== false ) {
			$this->parseURI( $request_uri, $uri );
		}

		return $route;
	}

	public function processRequest() {
//~ 		DebugLog::log( 2, "processRequest" );

		do {
			$route = $this->getRoute( $this->request->getURI() );

			if( empty( $route ) ) {
				$this->response->setResponseCode( 404, 'Route not implemented.' );
				break;
			}

			$filename = $route['filename'];

			if( ! empty( $filename ) ) {
				$dir = dirname( $filename );

				if( $dir == '' ) {
					$dir = dirname( $this->request->getInfo( 'SCRIPT_FILENAME' ) );
				}

				if( $dir != '' && substr( $dir, strlen( $dir ) - 1, 1 ) != DIRECTORY_SEPARATOR ) {
					$dir .= DIRECTORY_SEPARATOR;
				}

//~ 				DebugLog::log( 2, "Carregando arquivo $filename" );
				require_once( $dir . $filename );
			}

			$classname = $route['classname'];
//~ 			DebugLog::log( 2, "Classname $classname" );

			if( empty( $classname ) ) {
				$this->response->setResponseCode( 500, 'Class not defined.' );
				break;
			}

//~ 			DebugLog::log( 2, "Reflection" );
//~ 			$reflection = new ReflectionClass( $classname );

//~ 			if( $reflection == null ) {
//~ 				DebugLog::log( 2, "Reflection NULL" );
//~ 				$this->response->setResponseCode( 500, 'Class not found.' );
//~ 				break;
//~ 			}

//~ 			if( ! $reflection->isSubclassOf( 'RestRoute' ) ) {
//~ 				DebugLog::log( 2, "Reflection wrong" );
//~ 				$this->response->setResponseCode( 500, 'Invalid class definition.' );
//~ 				break;
//~ 			}

//~ 			DebugLog::log( 2, "New instance" );

//~ 			try {
//~ 				$instance = $reflection->newInstance();
//~ 			} catch( Exception $e ) {
//~ 				DebugLog::log( 2, 'Exceção capturada: ' . $e->getMessage() );
//~ 			} finally {
//~ 				DebugLog::log( 2, "Sem Exceção" );
//~ 			}

			try {
//~ 				DebugLog::log( 2, "New instance" );
				$instance = new $classname;
			} catch( Exception $e ) {
//~ 				DebugLog::log( 2, 'Exceção capturada: ' . $e->getMessage() );
//~ 			} finally {
//~ 				DebugLog::log( 2, "Sem Exceção" );
			}

			if( $instance == null ) {
//~ 				DebugLog::log( 2, "Class cannot be instanciated" );
				$this->response->setResponseCode( 500, 'Class cannot be instanciated' );
				break;
			}

			if( ! ( $instance instanceOf RestRoute ) ) {
//~ 				DebugLog::log( 2, "Invalid class inheritance" );
				$this->response->setResponseCode( 500, 'Invalid class inheritance' );
				break;
			}

			$this->response->addContentTypeJSON();
//~ 			DebugLog::log( 2, "getVerb " . $this->request->getVerb() );

			switch( $this->request->getVerb() ) {
				case 'CONNECT':
					$this->response->setResponseCode( $instance->connect( $this->request, $this->response, $this->params ) );
					break;

				case 'DELETE':
					$this->response->setResponseCode( $instance->delete( $this->request, $this->response, $this->params ) );
					break;

				case 'GET':
					$this->response->setResponseCode( $instance->get( $this->request, $this->response, $this->params ) );
					break;

				case 'HEAD':
					$this->response->setResponseCode( $instance->head( $this->request, $this->response, $this->params ) );
					break;

				case 'OPTIONS':
					$this->response->setResponseCode( $instance->options( $this->request, $this->response, $this->params ) );
					break;

				case 'PATCH':
					$this->response->setResponseCode( $instance->patch( $this->request, $this->response, $this->params ) );
					break;

				case 'POST':
					$this->response->setResponseCode( $instance->post( $this->request, $this->response, $this->params ) );
					break;

				case 'PUT':
					$this->response->setResponseCode( $instance->put( $this->request, $this->response, $this->params ) );
					break;

				case 'TRACE':
					$this->response->setResponseCode( $instance->trace( $this->request, $this->response, $this->params ) );
					break;

				default:
					$this->response->setResponseCode( $instance->get( $this->request, $this->response, $this->params ) );
					break;
			}
		} while( false );

		echo $this->response->getResponse();
	}

	public function saveToFile( string $filename ): bool {
		$res = file_put_contents( $filename, json_encode( $this->routes_list ) );
		return ($res === false ? false : true);
	}

	public function loadFromFile( string $filename ): bool {
		if( ! file_exists( $filename ) ) {
			return false;
		}

		$this->routes_list = json_decode( file_get_contents( $filename ), true );
		return is_array( $this->routes_list );
	}
}
/*
$matches = null;
echo preg_match_all( '/\{[a-zA-Z0-9-_]+\}/', '/api/v1/options/{id}/{all}', $matches ) . "\n";
var_dump( $matches );
*/
?>

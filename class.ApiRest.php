<?php
    /* File : Rest.inc.php
     * Author : Arun Kumar Sekar
    */
    class ApiRest {

	public $_allow = array();
	public $_content_type = "application/json";
	public $_request = array();
	private $_method = "";
	private $_code = 200;
        protected $_traceFile = 'trace.log';
	protected $_defaultFunction = null;
        protected $statusList = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => '(Unused)',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'Authentification required',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported' );
        
        public function __construct() {
            $this->_method = $_SERVER['REQUEST_METHOD'];
            $this->inputs();
	}
        
        protected function traceToFile($textToLog){
            file_put_contents($this->_traceFile, date('Y-m-d H:i:s', time()).";".$textToLog."\r\n", FILE_APPEND);
        }    
        
        protected function jsonEncode($data){
            return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }

        protected function jsonDecode($data){
            return json_decode($data);
        }
    
        
	public function getReferer() {
            return $_SERVER['HTTP_REFERER'];
	}
        
	public function response($data, $status) {
            if ($data == ""){
                $data = array("status" => $status, "message" => $this->statusList[$status]);
            }
            $this->_code = ($status) ? $status : 200;
            $this->setHeaders();
            echo $this->jsonEncode($data);
            exit;
	}

	public function json( $data ) {
            if ( is_array( $data ) ) {
                return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
	}
        
	protected function getStatusMessage($code = "") {
            if ($code == ""){
                $code = $this->_code;
            }
            return $this->statusList[$code] ? $this->statusList[$code] : $this->statusList[500];
	}
        
	public function getRequestMethod() {
            return $_SERVER['REQUEST_METHOD'];// == "OPTIONS" ? "GET" : $_SERVER['REQUEST_METHOD'];
	}
        
	private function inputs() {
            
            switch ( $this->getRequestMethod() ) {
            case "OPTIONS": // preflight always ok !
                $this->response( '', 200 );
                break;
            case "POST":
                $this->_request = $this->cleanInputs( $_POST );
                break;
            case "GET":
            case "DELETE":
                $this->_request = $this->cleanInputs( $_GET );
                break;
            case "PUT":
                parse_str( file_get_contents( "php://input" ), $this->_request );
                $this->_request = $this->cleanInputs( $this->_request );
                break;
            default:
                $this->response( '', 406 );
                break;
            }
	}
        
	private function cleanInputs( $data ) {
		$clean_input = array();
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				$clean_input[$k] = $this->cleanInputs( $v );
			}
		} else {
			if ( get_magic_quotes_gpc() ) {
				$data = trim( stripslashes( $data ) );
			}
			$data = strip_tags( $data );
			$clean_input = trim( $data );
		}
		return $clean_input;
	}
        
	private function setHeaders() {
            header("HTTP/1.1 ".$this->_code." ".$this->getStatusMessage());
            header("Content-Type:".$this->_content_type );
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: authorization,content-type");
//            header("Cache-Control", "no-cache");
//            header("Access-Control-Allow-Methods: DELETE,GET,HEAD,PATCH,POST,PUT,OPTIONS");
//            header("Access-Control-Allow-Credentials: true");
//            header("Access-Control-Max-Age", "1728000");
        }
        
        protected function getPostBody(){
            return file_get_contents('php://input');
        }
        
        protected function getGetBody(){
            return $_GET;
        }
        
    }	
?>
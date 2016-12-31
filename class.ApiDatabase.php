<?php
class ApiDatabase extends ApiRest{
    
    public $insertId = null;
    public $affectedRows = null;
    
    private $mysqli;
    
    private $host = null;
    private $user = null;
    private $password = null;
    private $database = null;
    private $defaultLimit = 100;
    private $defaultOffset = 0;
    private $defaultSelect = "*";
    
    public function __construct()
    {
        parent::__construct();
        $this->connect($this->host, $this->user, $this->password, $this->database);
    }
    
    public function processApi()
    {    
        $req = $_REQUEST['rquest'];
        
        $url = explode('/', $req);
        
        if (count($url) < 2){
            $this->response('', 400); 
        }
        $database = $url[0];
        $func = $url[1];
        if (count($url)>2){
        $param = $url[2];
        }else{
            $param = "";
        }
        
        if (!$this->loadDatabaseConfiguration($database)){
            $this->returnResult(null, "500", $this->mysqli->connect_errno." - ".$this->mysqli->connect_error);
        }else{
            if (!$this->grant()){
                $this->response('', 403);
                return;                
            }

            if ((int)method_exists($this, $func) > 0){
                $this->$func($param);
            } else{
                if (isset($this->_defaultFunction) && ((int)method_exists($this, $this->_defaultFunction) > 0)){
                    $f = $this->_defaultFunction;
                    $this->$f($func);
                    $_SESSION['lastRequest'] = $req;
                }else{
                    $this->response('', 400); 
                }
            }
        }
    }

    
    private function loadDatabaseConfiguration($database){
        $config = file_get_contents($database.".json");
        $conf = $this->jsonDecode($config);
        $this->host = $conf->host;
        $this->user = $conf->user;
        $this->password = $conf->password;
        $this->database = $database;
        return $this->connect();
    }
    
    private function connect(){
        $this->mysqli = new mysqli($this->host, $this->user, $this->password, $this->database);
        if ($this->mysqli->connect_errno) {
            return false;
        }
        $this->mysqli->set_charset("utf8");
        $this->_defaultFunction = "read"; 
        return true;
    }
    
    public function allowUser($user, $password){  
        $sql = "SELECT count(*) count FROM user WHERE email='".$user."' AND password='".$password."'";
        $ret = $this->getArray($sql);
        
        return $ret[0]["count"] > 0;
    }  
    
    public function grant(){
        if (isset($_SERVER['PHP_AUTH_USER'])){
            return $this->allowUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);              
        }       
        return false;
    }
    
    private function extractString($text, $from, $to, $caseSensistive = false){
        $ret = $text;
        $pos1 = strpos(($caseSensistive ? $text : strtoupper($text)), $from);
        $pos2 = strpos(($caseSensistive ? $text : strtoupper($text)), $to);
        if ($pos1 !== false || $pos2 !== false){
            $ret = substr($text, $pos1 + strlen($from), ($pos2 > 0 ? $pos2 - $pos1 - strlen($from) : 1000));
        }
        return $ret;
    }
    
    private function getFrom($sql){
        $ret = null;
        if (strpos(strtoupper($sql), 'FROM') !== false){
            $ret = trim($this->extractString($sql, "FROM", "WHERE"));
            if (strpos($ret, "ORDER")){
                $ret = trim($this->extractString($sql, "FROM", "ORDER"));
            }
            if (strpos($ret, "GROUP")){
                $ret = trim($this->extractString($sql, "FROM", "GROUP"));
            }
            if (strpos($ret, "LIMIT")){
                $ret = trim($this->extractString($sql, "FROM", "LIMIT"));
            }
            if (strpos($ret, "OFFSET")){
                $ret = trim($this->extractString($sql, "FROM", "OFFSET"));
            }
            if (strpos($ret, "GROUP")){
                $ret = trim($this->extractString($sql, "FROM", "GROUP"));
            }
        }
        return $ret;
    }  
    
    private function getIdFieldName($tableName){
        $ret = null;
        if ($tableName != null){
            $sql = "SHOW COLUMNS FROM ".$tableName;
            $req = $this->mysqli->query($sql);
            if ($req !== false){
                while ($data = $req->fetch_assoc()) {
                    if ($data["Key"] === "PRI"){
                        $ret = $data["Field"];
                        break;
                    }
                }
            }
        }
        return $ret;
    }
    
    private function fieldExists($field, $tableName){
        $count = 0;
        if ($tableName != "" && $tableName != null){
            $sql = "SHOW FIELDS FROM ".$tableName." WHERE FIELD='".$field."'";
            $req = $this->mysqli->query($sql);
            while ($data = $req->fetch_assoc()) {
                $count ++;
                break;
            }
        }
        return $count > 0;
    }    
    
    private function getArray($sql){
        $ret = array();
        $req = $this->mysqli->query($sql);
        $tableName = $this->getFrom($sql);
        $idFielName = $this->getIdFieldName($tableName);
        if (is_object($req)){
            while ($data = $req->fetch_assoc()) {
                if (isset($tableName)){
                    $data["__tableName"] = $tableName;
                }
                if (isset($idFielName)){
                    $data["__idFieldName"] = $idFielName;
                    if (key_exists($idFielName, $data)){
                        $data["__idValue"] = $data[$data["__idFieldName"]];
                    }
                }
                array_push($ret, $data);
            }
        }
        return $ret;
    }
    
    private function returnResult($sql, $returnCode, $results, $send = true){
        $ret = array();
        $ret["sql"] = $sql;
        $ret["returnCode"] = $returnCode;
        $ret["insertedId"] = $this->mysqli->insert_id;
        $ret["resultCount"] = count($results);
        $ret["sqlError"] = $this->mysqli->error;
        $ret["affectedRows"] = $this->mysqli->affected_rows;
        $ret["results"] = $results;
        if ($send){
            $json = $this->jsonEncode($ret);
            if (!$json){
                $ret["results"] = null;
                $ret["error"] = "Too many lines to return, reduce the limit parameter or Json encoding problems (see jsonError)";
                $ret["jsonError"] = json_last_error_msg();
            }
            $this->response($ret, $returnCode);
        }
        return $ret;
    }
    
    public function count($param){
        $tableName = str_replace('/', '', $param);
        $sql = "SELECT count(*) count FROM ".$tableName.$this->getQueryEnd();
        if (isset($sql)){
            $this->returnResult($sql, 200, $this->getArray($sql));
        }else{
            $this->response(null, 400, null);
        }
    }

    public function sql($param){
        $body = $this->getPostBody();
        $obj = $this->jsonDecode($body);
        $sql = $obj->sql;
        $this->returnResult($sql, 200, $this->getArray($sql));
    }

    public function getQueryEnd($whereOnly = false){
        if ($this->getRequestMethod() == "POST"){
            $body = json_decode($this->getPostBody());
            $where = (isset($body->__where) && $body->__where != "" ? " WHERE ".$body->__where : "");
            $orderby = (isset($body->__orderby)  && $body->__orderby != "" ? " ORDER BY ".$body->__orderby : "");
            $offset = (isset($body->__offset) && $body->__offset != "" ? " OFFSET ".$body->__offset : " OFFSET ".$this->defaultOffset);
            $limit = (isset($body->__limit) && $body->__limit != "" ? " LIMIT ".$body->__limit : " LIMIT ".$this->defaultLimit);
            $groupby = (isset($body->__groupby) && $body->__groupby != "" ? " GROUP BY ".$body->__groupby : "");
            $sql = " ".$where.(!$whereOnly ? $groupby.$orderby.$limit.$offset : "");
        }else{
            if ($this->getRequestMethod() == "GET"){
                $offset = (isset($_GET["offset"]) ? $_GET["offset"] : $this->defaultOffset) ;
                $limit = (isset($_GET["limit"]) ? $_GET["limit"] : $this->defaultLimit);
                $sql = " LIMIT ".$limit." OFFSET ".$offset;
            }else{
                $sql = null;
            }
        }   
        return $sql;
    }
    
    public function read($param){
        $tableName = str_replace('/', '', $param);
        if ($this->getRequestMethod() == "POST"){
            $body = json_decode($this->getPostBody());
            $select = (isset($body->__select) && $body->__select != "" ? $body->__select : $this->defaultSelect);
            $sql = "SELECT ".$select." FROM ".$tableName.$this->getQueryEnd();
            $this->returnResult($sql, 200, $this->getArray($sql));
        }else{
            if ($this->getRequestMethod() == "GET"){
                $sql = "SELECT ".$this->defaultSelect." FROM ".$tableName.$this->getQueryEnd();
                $this->returnResult($sql, 200, $this->getArray($sql));
            }else{
                $this->response(null, 400, null);
            }
        }   
    }
    
    private function getUpdateFields($object, $tableName){
        $array = get_object_vars($object);
        $ret = "";
        $where = "";
        if (key_exists("__where", $array)){
            $where = " WHERE ".$array["__where"];
            foreach ($array as $key => $value) {
                if ($this->fieldExists($key, $tableName)){
                    $ret .= ($ret == "" ? "" : ", ")." ".$key."='".$this->mysqli->real_escape_string($value)."'";
                }
            }
        }
        return "SET ".$ret." ".$where;
    }
    
    private function getInsertFields($object, $tableName){
        $array = get_object_vars($object);
        $ret1 = "";
        $ret2 = "";
        foreach ($array as $key => $value) {
            if ($this->fieldExists($key, $tableName)){
                $ret1 .= ($ret1 == "" ? "" : ", ").$key;
                $ret2 .= ($ret2 == "" ? "" : ", ")."'".$this->mysqli->real_escape_string($value)."'";
            }
        }
        return "(".$ret1.") VALUES (".$ret2.")";
    }

    public function update($param){
        $tableName = str_replace('/', '', $param);
        if ($this->getRequestMethod() == 'POST' || $this->getRequestMethod() == 'PATCH'){
            $ret = array();
            $object = $this->jsonDecode($this->getPostBody());
            if ($object != null){
                if (is_array($object)){
                    foreach($object as $obj){
                        $sql = "UPDATE ".$tableName." ".$this->getUpdateFields($obj, $tableName);
                        array_push($ret, $this->returnResult($sql, 200, $this->getArray($sql), false));
                    }
                    $this->returnResult(null, 200, $ret);
                }else{
                    $sql = "UPDATE ".$tableName." ".$this->getUpdateFields($object, $tableName);
                    $this->returnResult($sql, 200, $this->getArray($sql));
                }
            }else{
                $this->response(null, 400, null);
            }
        }else{
            $this->response(null, 400, null);
        }   
    }
    
    public function insert($param){
        $tableName = str_replace('/', '', $param);
        if ($this->getRequestMethod() == 'POST' || $this->getRequestMethod() == 'PUT'){
            $ret = array();
            $object = $this->jsonDecode($this->getPostBody());
            if ($object != null){
                if (is_array($object)){
                    foreach($object as $obj){
                        $sql = "INSERT INTO ".$tableName." ".$this->getInsertFields($obj, $tableName);
                        array_push($ret, $this->returnResult($sql, 200, $this->getArray($sql), false));
                    }
                    $this->returnResult(null, 200, $ret);
                }else{
                    $sql = "INSERT INTO ".$tableName." ".$this->getInsertFields($object, $tableName);
                    $this->returnResult($sql, 200, $this->getArray($sql));
                }  
            }else{
                $this->response(null, 400, null);
            } 
        }else{
            $this->response(null, 400, null);
        }   
    }
        
    public function delete($param){
        $tableName = str_replace('/', '', $param);
        if ($this->getRequestMethod() == 'POST' || $this->getRequestMethod() == 'DELETE'){
            $ret = array();
            $object = $this->jsonDecode($this->getPostBody());
            if ($object != null){
                if (is_array($object)){
                    foreach($object as $obj){
                        $sql = "DELETE FROM ".$tableName.$this->getQueryEnd(true);
                        array_push($ret, $this->returnResult($sql, 200, $this->getArray($sql), false));
                    }
                    $this->returnResult(null, 200, $ret);
                }else{
                    $sql = "DELETE FROM ".$tableName.$this->getQueryEnd(true);
                    $this->returnResult($sql, 200, $this->getArray($sql));
                }
            }else{
                $this->response(null, 400, null);
            }
        }else{
            $this->response(null, 400, null);
        }   
    }
    
    private function getFields($tableName){
        $sql = "SHOW FIELDS FROM ".$tableName;
        return $this->getArray($sql);
    }
    
    private function emptyRecord($tableName, $showType = false){
        $ret = array();
        $idFieldName = "";
        if ($this->getRequestMethod() == 'GET'){
            $fields = $this->getFields($tableName);
            foreach($fields as $field){
                $ret[$field["Field"]] = ($showType ? $field["Type"] : "");
                if ($field["Key"] === "PRI"){
                    $idFieldName = $field["Field"];
                }
            }
        }
        $ret["__tableName"] = $tableName;
        $ret["__idFieldName"] = $idFieldName;
        return array($ret);
    }
    
    public function fresh($param){
        $tableName = str_replace('/', '', $param);
        if ($this->getRequestMethod() == 'GET'){
            $this->returnResult("", 200, $this->emptyRecord($tableName, false));
        }else{
            $this->response(null, 400, null);
        }   

    }
        
    public function fields($param){
        $tableName = str_replace('/', '', $param);
        if ($this->getRequestMethod() == 'GET'){
            $this->returnResult("", 200, $this->getFields($tableName));
        }else{
            $this->response(null, 400, null);
        }   
    }
    
    public function tables($param){
        if ($this->getRequestMethod() == 'GET'){
            $sql = "SHOW TABLES";
            $this->returnResult("", 200, $this->getArray($sql));
        }else{
            $this->response(null, 400, null);
        }   
    }
    public function server($param){
        $this->returnResult("", 200, $_SERVER);
    }
    
    public function request($param){
        $this->returnResult("", 200, $_REQUEST);
    } 
      
}
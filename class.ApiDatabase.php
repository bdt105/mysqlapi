<?php
class ApiDatabase extends ApiRest{
    
    public $insertId = null;
    public $affectedRows = null;
    
    private $mysqli;
    
    private $host = null;
    private $user = null;
    private $password = null;
    private $database = null;
    private $defaultLimit = 15;
    //private $updateDeleteLimit = 15;
    private $defaultOffset = 0;
    private $defaultSelect = "*";
    
    public function __construct(){
        parent::__construct();
        //$this->connect($this->host, $this->user, $this->password, $this->database);
    }
    
    public function processApi(){    
        $req = $_REQUEST['rquest'];
        
        $url = explode('/', $req);
        
        if (count($url) < 2){
            $this->response('', 400); 
        }
        $databaseConfiguration = $url[0];
        $func = $url[1];
        if (count($url)>2){
        $param = $url[2];
        }else{
            $param = "";
        }
        
        if (!$this->loadDatabaseConfiguration($databaseConfiguration)){
            $this->returnResult(null, "500", $this->mysqli->connect_errno." - file: ".$databaseConfiguration.' '.$this->mysqli->connect_error);
        }else{
            if (!$this->grant()){
                if (!isset($_SERVER['PHP_AUTH_USER'])){
                    $this->response('', 418);
                }else{
                    $this->response('', 403);
                }
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

    private function loadDatabaseConfiguration($databaseConfiguration){
        $config = file_get_contents($databaseConfiguration);
        $conf = $this->jsonDecode($config);
        $this->host = $conf->host;
        $this->user = $conf->user;
        $this->password = $conf->password;
        $this->database = $conf->database;
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
            $this->traceToFile($_SERVER['PHP_AUTH_USER']);
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
        return $sql;
/*        $ret = $sql;
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
            if (strpos($ret, "JOIN")){
                $ret = trim($this->extractString($sql, "FROM", "JOIN"));
            }
            if (strpos($ret, "LEFT")){
                $ret = trim($this->extractString($sql, "FROM", "LEFT"));
            }
            if (strpos($ret, "RIGHT")){
                $ret = trim($this->extractString($sql, "FROM", "RIGHT"));
            }
        }
        return $ret;*/
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
    
    private function returnResultFromSql($sql, $send = true){
        $message = "";
        if ($this->isValidSql($sql, $message)){
            return $this->returnResult($sql, 200, $this->getArray($sql), $send);
        }else{
            if ($send){
                return $this->response($sql, 400, $message);
            }else{
                return $this->returnResult($sql, 400, $message, $send);
            }
        }
    }
    
    private function returnResult($sql, $returnCode, $results, $send = true){
        $ret = array();
        $ret["sql"] = $sql;
        $ret["returnCode"] = $returnCode;
        $ret["insertedId"] = $this->mysqli->insert_id;
        $ret["resultCount"] = count($results);
        $ret["sqlError"] = $this->mysqli->error;
        $ret["sqlInfo"] = $this->mysqli->info;
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
    
    public function count($param, $send = true, $whereMandatory = false){
        $tableName = str_replace('/', '', $param);
        $sql = "SELECT count(*) count FROM ".$tableName.$this->getQueryEnd();
        if (!$send && $whereMandatory && strpos($sql, "WHERE") === FALSE){
            return 0;
        }else{
            return $this->returnResultFromSql($sql, $send);
        }
    }

    private function isValidSql($sql, &$message){
        $ok = false;
        $message = $sql." is not allowed";
        if (strpos(strtoupper($sql), "UPDATE") !== false){
            $ok = strstr(strtoupper($sql), " WHERE ") !== false;
            $message = ($ok ? "" : "WHERE is mandatory for update");
        }
        if (strpos(strtoupper($sql), "DELETE") !== false){
            $ok = strstr(strtoupper($sql), " WHERE ") !== false;
            $message = ($ok ? "" : "WHERE is mandatory for delete");
        }
        if (strpos(strtoupper($sql), "INSERT") !== false){
            $ok = strstr(strtoupper($sql), " SELECT ") === false;
            $message = ($ok ? "" : "INSERT INTO SELECT is not allowed");
        }
        if (strpos(strtoupper($sql), "SELECT") !== false){
            $ok = strstr(strtoupper($sql), " LIMIT ") !== false;
            $message = ($ok ? "" : "LIMIT is mandatory for select");
        }
        if (strpos(strtoupper($sql), "SHOW") !== false){
            $ok = true;
            $message = "";
        }
        return $ok;
    }
    
    public function sql($param){
        $body = $this->getPostBody();
        $obj = $this->jsonDecode($body);
        if (strpos(strtoupper($obj->__sql), 'LIMIT') === false){
            if (property_exists($obj, "limit") && $obj->limit === false){
                $sql = $obj->__sql;
            }else{
                $sql = $obj->__sql." LIMIT ".(property_exists($obj, "limit") && $obj->__limit != "" ? $obj->__limit : $this->defaultLimit)." OFFSET ".(property_exists($obj, "offset") && $obj->__offset != "" ? $obj->__offset : 0);
            }
        }else{
            $sql = $obj->__sql;
        }
        
        $this->returnResultFromSql($sql);
    }

    private function whereAll($tableName, $search, $separator, $prefix, $suffix){
        $ret = "";
        if ($search != "" && $tableName){
            $fields = $this->getFields($tableName);
            foreach ($fields as $field) {
                $ret .= ($ret === "" ? "" : $separator).$field["Field"].$prefix.$search.$suffix;
            }
        }        
        return $ret;
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
                $offset = (isset($_GET["__offset"]) ? $_GET["__offset"] : $this->defaultOffset) ;
                $limit = (isset($_GET["__limit"]) ? $_GET["__limit"] : $this->defaultLimit);
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
            $select = (isset($body->select) && $body->select != "" ? $body->select : $this->defaultSelect);
            $sql = "SELECT ".$select." FROM ".$tableName.$this->getQueryEnd(false);
            
            $this->returnResultFromSql($sql);
        }else{
            if ($this->getRequestMethod() == "GET"){
                $sql = "SELECT ".$this->defaultSelect." FROM ".$tableName.$this->getQueryEnd();
                $this->returnResultFromSql($sql);
            }else{
                $this->response(null, 400, null);
            }
        }   
    }
    
    private function getUpdateFields($object, $tableName){
        $array = get_object_vars($object);
        $ret = "";
        $where = "";
        foreach ($array as $key => $value) {
            if ($this->fieldExists($key, $tableName)){
                $ret .= ($ret == "" ? "" : ", ").$key.
                ($value === null ? "=null" : "='".$this->mysqli->real_escape_string($value)."'");
            }
        }
        if (key_exists("__where", $array)){
            $where = " WHERE ".$array["__where"];
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
                        array_push($ret, $this->returnResultFromSql($sql, false));
                    }
                    $this->returnResult(null, 200, $ret);
                }else{
                    $sql = "UPDATE ".$tableName." ".$this->getUpdateFields($object, $tableName);
                    $this->returnResultFromSql($sql);
                }
            }else{
                $this->response(null, 400, "Incorect parameters ".$this->getPostBody());
            }
        }else{
            $this->response(null, 400, "Only PATCH and POST are allowed");
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
                        array_push($ret, $this->returnResultFromSql($sql, false));
                    }
                    $this->returnResult(null, 200, $ret);
                }else{
                    $sql = "INSERT INTO ".$tableName." ".$this->getInsertFields($object, $tableName);
                    $this->returnResultFromSql($sql);
                }  
            }else{
                $this->response(null, 400, "Incorect parameters ".$this->getPostBody());
            } 
        }else{
            $this->response(null, 400, "Only PUT and POST are allowed");
        }   
    }
        
    public function save($param){
        $resCount = $this->count($param, false, true);
        $results = $resCount["results"];
        $count = $results[0]["count"];
        if ($count == 1){
            $this->update($param);
        }else{
            $this->insert($param);
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
                        if (strstr(strtoupper($sql), ' WHERE ') === false){
                            $this->returnResult($sql, 400, "Delete is not possible without a 'where'", true);
                        }else{
                            array_push($ret, $this->returnResult($sql, 200, $this->getArray($sql), false));
                        }
                    }
                    $this->returnResult(null, 200, $ret);
                }else{
                    $sql = "DELETE FROM ".$tableName.$this->getQueryEnd(true);
                    if (strstr(strtoupper($sql), ' WHERE ') === false){
                        $this->returnResult($sql, 400, "Delete is not possible without a 'where'", true);
                    }else{
                        $this->returnResult($sql, 200, $this->getArray($sql));
                    }
                }
            }else{
                $this->response(null, 400, null);
            }
        }else{
            $this->response(null, 400, null);
        }   
    }
    
    private function getFieldsFormTable($tableName){
        $sql = "SHOW FIELDS FROM ".$tableName;
        return $this->getArray($sql);
    }
    
    private function getFieldsFormSql($sql){
        $tableName = $this->getFrom($sql);
        $res = $this->getArray($sql);
        $retu = array();
        if (count($res) > 0){
            foreach ($res[0] as $key => $value) {
                $ret = array();
                $ret["Field"] = $key;
                $ret["__tableName"] = $tableName;
                array_push($retu, $ret);
            }    
        }
        return $retu;
    }

    private function sqlFromIsTable($sql){
        $tableName = $this->getFrom($sql);
        return strpos($tableName, ' ') === false && strpos($tableName, ',') === false;
    }
    
    private function getFields($sqlOrTableName){
        if ($this->sqlFromIsTable($sqlOrTableName)){
            return $this->getFieldsFormTable($this->getFrom($sqlOrTableName));
        }else{
            return $this->getFieldsFormSql($sqlOrTableName);
        }
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
        if ($this->getRequestMethod() == 'GET' || $this->getRequestMethod() == 'POST'){
            $this->returnResult("", 200, $this->getFields($tableName));
        }else{
            $this->response(null, 400, null);
        }
    }
    
    public function tables($param){
        if ($this->getRequestMethod() == 'GET'){
            $sql = "SHOW TABLES";
            $req = $this->mysqli->query($sql);
            $arra = array();
            if (is_object($req)){
                while ($data = $req->fetch_array()) {
                    array_push($arra, $data[0]);
                }
            }
            $this->returnResult($sql, 200, $arra);
        }else{
            if ($this->getRequestMethod() == 'POST'){
                $body = $this->getPostBody();
                $obj = $this->jsonDecode($body);
                $sql = $obj->sql;
                $tableNames = $this->getFrom($sql);
                $this->returnResult("", 200, array_map('trim', explode(',', $tableNames)));
            }else{
                $this->response(null, 400, null);
            }   
        }   
    }
    
    public function server($param){
        $this->returnResult("", 200, $_SERVER);
    }
    
    public function request($param){
        $this->returnResult("", 200, $_REQUEST);
    } 
}
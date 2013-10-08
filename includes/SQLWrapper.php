<?php

/**
 * Description of SQLWrapper
 *
 * @author user
 */
class SQLWrapper {
    //put your code here
    
    private static $dbwhitelist = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890-';
    private $NumberDatatypes = array("BIT","TINYINY","BOOL","BOOLEAN","SMALLINT","INT","INTEGER","BIGINT","DECIMAL","DEC","FLOAT","DOUBLE");
    private $DateDatetypes = array("DATE","DATETIME","TIMESTAMP","TIME");
    private $StringDatatypes = array("CHAR","NCHAR","VARCHAR","NVARCHAR","BINARY","VARBINARY","TINYBLOB","TINYTEXT","BLOB","TEXT","MEDIUMBLOB","MEDIUMTEXT","LONGBLOB","LONGTEXT","ENUM","SET");
    
    private $param = array();
    private $table;
    private $columns;
    private $sql;
    private $newid;
    private $executiontime;
    private $sqlerror;
    private $sqlauditid;
    private $primarykey;
    private $hasreference=false;
    private $refcolumn;
    
    public function __construct($table) {
        $this->table = $table;
        $this->loadTable();
    }
    
    public function setRefColumn($refcolumn) {
        $this->refcolumn=$refcolumn;
        $this->hasreference=true;
    }
    
    public function isReferenced() {
        return $this->hasreference;
    }
    
    public function getRefColumn() {
        return $this->refcolumn;
    }
    
    public function getTable() {
        return $this->table;
    }
    
    public function setTable($table) {
        $this->table=$table;
        $this->loadTable();
    }
    
    public function loadTable() {
        $result = self::showTables($this->table);
        if ($result!=false) {
            if ($result!=$this->table) {
                $this->table = $result;
            }
            $this->columns = self::showColumns($this->table);
            $this->findPrimaryKey();
        } else {
            die ("table ".$this->table." is not found.");
        }
    }
    
    private function findPrimaryKey() {
        //find primary key
        for ($i=0;$i<count(array_keys($this->columns));$i++) {
            if ($this->columns[$i]["Key"]=="PRI") {
                $this->primarykey=$this->columns[$i]["Field"];
            }
        }
    }
    
    public function getColumns() {
        return $this->columns;
    }
    
    public function getParamCount() {
        return count($this->param);
    }
    
    private function getExecutionTime() {
        return $this->executiontime;
    }
    
    private function getSQLError() {
        return $this->sqlerror;
    }
    
    public function getPrimaryKey() {
        return $this->primarykey;
    }
            
    public function addparam($column,$value,$datatype=NULL) {
        
        //search for parameter
        $found=false;
        
        for ($i=0;$i<count($this->columns);$i++){
            if (strcasecmp($column, $this->columns[$i]["Field"])==0) {
                if ($datatype==NULL) {
                    //check data conversion matching
                    if (array_search(strtoupper(preg_replace('/\(.*\)/','',$this->columns[$i]['Type'])),$this->NumberDatatypes)!==false) {
                        $datatype="int";
                    } elseif (array_search(strtoupper(preg_replace('/\(.*\)/','',$this->columns[$i]['Type'])),$this->DateDatetypes)!==false) {
                        $datatype="date";
                    } elseif (array_search(strtoupper(preg_replace('/\(.*\)/','',$this->columns[$i]['Type'])),$this->StringDatatypes)!==false) {
                        $datatype="text";
                    } else {
                        die("unable to find data type for parameter $column.");
                    }
                }
                $found=true;
            } 
        }
        
        if ($found) {
            $this->param[] = new Datafield ($column,$value,$datatype);
            return $this->getParamCount();
        } else {
            die ("Field $this->table.$column is not found.");
        }
        
    }
    
    public function removeparam() {
        array_pop($this->param);
    }
    
    public function removeallparam() {
        $this->param = array();
    }
    
    private function autoinsertparam($column,$defaultvalue) {
        //check if createdby
        $found = false;
        for ($i=0; $i<$this->getParamCount();$i++) {
            if (strcasecmp($this->param[$i]->getColumn(),$column)==0) {
                $found=true;
            }
        }
        
        //if false check table
        if (!$found) {
            for ($i=0;$i<count($this->columns);$i++) {
                if (strcasecmp($this->columns[$i]["Field"],$column)==0) {
                    $found=true;
                }
            }
            if ($found) {
                //add parameter
                $this->addparam($column, $defaultvalue);
            }
        }
    } 
    
    private function autocondition ($condition) {
        if (is_numeric($condition)) {
            //check if number
            return "$this->primarykey = $condition";
        } 
    }


    public function insert() {
        //auto add createdby and createddate
        $this->autoinsertparam("active", 1);
        if (isset($_SESSION["user_id"])) {
            $this->autoinsertparam("createdby", $_SESSION["user_id"]);
        } 
        $this->autoinsertparam("createddate", now());
        
        $this->sql = "insert into ".$this->table." (";
        for ($i=0; $i<$this->getParamCount();$i++) {
            //generate Columns
            $this->sql .= $this->param[$i]->getColumn();
            if ($i<($this->getParamCount()-1)) {
                $this->sql .=",";
            }
        }
        $this->sql .= ") values (";
        for ($i=0; $i<$this->getParamCount();$i++) {
            //Generate values
            $this->sql .= $this->getSQLValueStatement($this->param[$i]->getValue(), $this->param[$i]->getDatatype());
            
            if ($i<($this->getParamCount()-1)) {
                $this->sql .=",";
            }
        }
        $this->sql .= ")";
        
        $result = $this->executeSQL();
        $this->logInsertUpdateDelete("INSERT",$result);
        $this->removeallparam();
        return $result;
    }
    
    public function update($condition) {
        if ($condition!="") {
            
            //check if $condition is a number
            if (is_numeric($condition)) {
                //auto construct condition
                $condition = $this->autocondition($condition);
            }
            
            //auto add modifiedby and modifieddate
            if (isset($_SESSION["user_id"])) {
                $this->autoinsertparam("modifiedby", $_SESSION["user_id"]);
            }
            $this->autoinsertparam("modifieddate", now());

            $this->sql = "update ".$this->table." set ";
            for ($i=0; $i<$this->getParamCount();$i++) {
                //generate Columns
                $this->sql .= $this->param[$i]->getColumn(). " = ";
                $this->sql .= $this->getSQLValueStatement($this->param[$i]->getValue(), $this->param[$i]->getDatatype());

                if ($i<($this->getParamCount()-1)) {
                    $this->sql .=",";
                }
            }
            $this->sql .= " where ".$condition;

            $this->executeSQL();
            $result = mysql_affected_rows();
            $this->logInsertUpdateDelete("UPDATE",$result,$condition);
            $this->removeallparam();
            return $result;
        } else {
            die("Cannot update table without condition");
        }
    }
    
    public function delete($condition) {
        if ($condition!="") {
            
            //check if $condition is a number
            if (is_numeric($condition)) {
                //auto construct condition
                $condition = $this->autocondition($condition);
            }
            
            $this->sql = "delete from $this->table where $condition";
            $this->executeSQL();
            $result = mysql_affected_rows();
            $this->logInsertUpdateDelete("DELETE",$result,$condition);
            $this->removeallparam();
            return $result; 
        } else {
            die("Cannot update table without condition");
        }
    }
    private function logInsertUpdateDelete ($type,$newid=NULL,$condition=null) {
        //prevent endless loop
        if (strcasecmp($this->table,"SYS_LogInsertUpdateDelete")!=0) {
            $log = new SQLWrapper("SYS_LogInsertUpdateDelete");
            $log->addparam("tablename", $this->table);
            $log->addparam("updatetype", $type);
            $log->addparam("newid", $newid);
            $log->addparam("wherecondition", $condition);
            $log->insert();
        }
    }
    
    private function getSQLValueStatement ($value, $datatype) {
        if (!is_null($value)) {
            if ($datatype=="text" || $datatype=="date") {
                return "'".escapesql($value)."'";
            } elseif ($datatype=="int" || $datatype=="float") {
                return escapesql($value);
            }
        } else {
            return "NULL";
        }
    }
    
    private function executeSQL () {
        //get current connection
        $currenttime = microtime(true);
        $result = mysql_query($this->sql, SQLWrapperConfiguration::getConnection());
        $this->executiontime = microtime(true) - $currenttime;
        if ($result) {
            if (substr_compare($this->sql, "insert into", 0,11)==0) {
                $result= mysql_insert_id();
            } else {
                $result = mysql_affected_rows();
            }
            $this->logSQL();
            return $result;
        } else {
            $this->sqlerror = mysql_error();
            $this->logSQL();
            $this->logSQLError();
            die ("Query: [$this->sql];<br />$this->sqlerror");
            return $result;
        }
    }
    
    private function logSQL() {
        //add query into log
        $logsql = "insert into SYS_SQLAudit(SQLStatement,newrecordid,sessionid,executiontime,createdby,createddate) values (";
        $logsql .= "'". escapesql($this->sql)."',";
        $logsql .= "'".$this->newid."',";
        $logsql .= "'".session_id()."',";
        $logsql .= $this->executiontime.",";
        if (isset($_SESSION["user_id"])) {
            if (!is_nan($_SESSION["user_id"])) {
                $logsql .= $_SESSION["user_id"];
            } else {
                $logsql .= "NULL,";
            }
        } else {
            $logsql .= "NULL,";
        }
        $logsql .= "'".now()."'";
        $logsql .= ")";
        $result = mysql_query($logsql, SQLWrapperConfiguration::getConnection());
        if ($result) {
            $this->sqlauditid =  mysql_insert_id();
        } else {
            echo mysql_error() ."<br />";
            echo("SQL Logging unavailable. Please contact system administrator.");
            exit();
        }
    }
    
    private function logSQLError() {
        $logsql = "insert into SYS_SQLError(SQLAudit_id,SQLStatement,Errormessage,CreatedBy,CreatedDate) values (";
        $logsql .= $this->sqlauditid.",";
        $logsql .= "'".escapesql($this->sql)."',";
        $logsql .= "'".escapesql($this->sqlerror)."',";
        if (isset($_SESSION["user_id"])) {
            if (!is_nan($_SESSION["user_id"])) {
                $logsql .= $_SESSION["user_id"];
            } else {
                $logsql .= "NULL";
            }
        } else {
            $logsql .= "NULL,";
        }
        $logsql .= "'".now()."'";
        $logsql .= ")";
        $result = mysql_query($logsql, SQLWrapperConfiguration::getConnection());
        if (!$result) {
            echo mysql_error() ."<br />";
            echo ("SQL Error Logging unavailable. Please contact system administrator.");
            exit();
        }
    }
    
    public static function showTables($filter) {
        $sql = "Show tables ";
        if ($filter!="") {
            $sql .= "like '".escapesql($filter)."'";
            $result = mysql_query($sql, SQLWrapperConfiguration::getConnection());
            if (mysql_num_rows($result)==0) {
                //get list of tables
                $sql = "show tables";
                $result = mysql_query($sql, SQLWrapperConfiguration::getConnection());
                if (mysql_num_rows($result)>0) {
                    //allow only white charaters
                    while ($row = mysql_fetch_row($result)) {
                        if (strcasecmp(whitelist($row[0], self::$dbwhitelist),$filter)==0) {
                            return $row[0];
                        }
                    }
                    return false;
                } else {
                    return false;
                }
            } else {
                while ($row = mysql_fetch_row($result)) {
                    return $row[0];
                }
            }
        } else {
            return false;
        }
    } 
    
    public static function showColumns($table) {
        
        if ($table!="") {
            $sql = "Show columns from ".escapesql($table);
            $output = array();
            
            $result = mysql_query($sql, SQLWrapperConfiguration::getConnection());
            if ($result) {
                if (mysql_num_rows($result)>0) {
                    while ($row = mysql_fetch_array($result)){
                        array_push($output, $row);
                    }
                    return $output;
                } else {
                    return false;
                }
            } else {
                die("table $table not found.");
            }
        } else {
            return false;
        }
        
        
    }
    
}

class Datafield {
    
    private $column;
    private $value;
    private $datatype;
            
    public function __construct($column, $value, $datatype) {
        $this->column = $column;
        $this->value = $value;
        $this->datatype = $datatype;
    } 
    
    public function getColumn() {
        return $this->column;
    }
    
    public function getValue() {
        return $this->value;
    }
    
    public function getDatatype() {
        return $this->datatype;
    }
}

class SQLWrapperConfiguration {
    
    private static $conn;
    private static $server;
    private static $username;
    private static $password;
    private static $database;
    
    public static function setConnection ($server,$username,$password,$database) {
        self::$server = $server;
        self::$username = $username;
        self::$password = $password;
        self::$database = $database;
        
        self::$conn = mysql_connect(self::$server, self::$username, self::$password);
        if(!self::$conn){
                die("Database connection failed: " . mysql_error());
        }

        // 2. Select a database to use
        $db_select = mysql_select_db(self::$database, self::$conn);
        if(!$db_select) {
                die("Database connection failed: " . mysql_error());
        }
        
        return self::$conn;
    }
    
    public static function getConnection () {
        return self::$conn;
    } 
}
?>

<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of databasecommand
 *
 * @author user
 */

class AddRecordCommand extends BaseCommand {
    
    private $table;
    private $conn;
    
    public function __construct($table) {
        $this->table = $table;
        $this->conn = new SQLWrapper($table);
    }
    
    public function execute($args) {
        $keys = array_keys($args);
        for ($i=0;$i<count($keys);$i++) {
            $this->conn->addparam($keys[$i], $args[$keys[$i]]);
        }
        return $this->conn->insert();
    }
}

class EditRecordCommand extends BaseCommand {
    
    private $table;
    private $conn;
    
    public function __construct($table) {
        $this->table = $table;
        $this->conn = new SQLWrapper($table);
    }
    
    public function execute($args,$condition) {
        $keys = array_keys($args);
        for ($i=0;$i<count($keys);$i++) {
            $this->conn->addparam($keys[$i], $args[$keys[$i]]);
        }
        return $this->conn->update($condition);
    }
}

class EnableRecordCommand extends BaseCommand {
    
    private $table;
    private $conn;
    
    public function __construct($table) {
        $this->table = $table;
        $this->conn = new SQLWrapper($table);
    }
    
    public function execute($condition) {
        //check if active field is present in table
        
    }
}

class DisableRecordCommand extends BaseCommand {
    
    public function execute($table,$args) {
        echo $args;
    }
}

?>

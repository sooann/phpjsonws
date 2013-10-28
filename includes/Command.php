<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of command
 *
 * @author user
 */
class Command {
    //put your code here
    private $name;
    private $option = array();
    private $arguement;
    private $command;
    
    public function __construct($name) {
        $this->name = $name;
        $this->loadCommand();
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function setName($name) {
        $this->name = $name;
    }
    
    public function getOptionCount() {
        return count($this->option);
    }
    
    public function getOptions() {
        return $this->option;
    }
    
    public function option($name,$value) {
        $this->option[$name] = $value;
    }
    
    public function removeOption($name) {
        if (!array_key_exists($name, $this->option)) {
            unset($this->option[$name]);
        }
    }
    
    public function setArguement($value) {
        $this->arguement=$value;
    }
    
    public function getArguement() {
        return $this->arguement;
    }
    
    public function arguement($value) {
        $this->setArguement($value);
    }
    
    private function loadCommand() {
        
        $commandfound = false;
        $customclass = false;
        $databasetable = false;
        
        //find custom command class in class folder
        $match = glob("class/".$this->name.".php");
        if (!$match) {
            //find in subsequent folders
            $match = glob("class/*/".$this->name.".php");
            if (!$match) {
                //find table name in commandline
                if ( 
                        substr($this->name,0,1)=="+" ||
                        substr($this->name,0,1)=="-" ||
                        substr($this->name,0,1)=="*" ||
                        substr($this->name,0,1)=="~" 
                   ) {
                    $commandfound=true;
                    $databasetable=true;
                }
            } else {
                $commandfound=true;
                $customclass=true;
            }
        } else {
            $commandfound=true;
            $customclass=true;
        }
        
        if ($commandfound) {
            if ($customclass) {
                //include class
                require $match[0];
                
                $this->command = new $this->name();
                
                //check if class implements ICommnad
                if (array_search("BaseCommand",  class_parents($this->command))===false) {
                    die("Command: '".$this->name."' is not found.");
                } 
                
            } elseif ($databasetable) {
                //check if table name
                if (!SQLWrapper::showTables(substr($this->name, 1))) {
                    die("Command: table '".substr($this->name, 1)."' is not found");
                } else {
                    require '../class/databasecommand.php';
                    
                    switch (substr($this->name,0,1)) {
                        case "+":
                            $this->command = new AddRecordCommand (substr($this->name, 1));
                            break;
                        case "-":
                            $this->command = new EditRecordCommand (substr($this->name, 1));
                            break;
                        case "*":
                            $this->command = new EnableRecordCommand (substr($this->name, 1));
                            break;
                        case "~":
                            $this->command = new DisableRecordCommand (substr($this->name, 1));
                            break;
                    }
                }
            }
        } else {
            die("Command: '".$this->name."' is not found.");
        } 
    }
   
    public function execute($option=null,$arguement=null) {
        //execute
        if ($option==null) {
            $this->command->setOption($this->option);
        } else {
            $this->command->setOption($option);
        }
        
        if ($arguement==NULL) {
            $this->command->setArguement($this->arguement);
        } else {
            $this->command->setArguement($arguement);
        }
        
        switch (substr($this->name,0,1)) {
            case "+":
                return $this->command->execute($this->option);
                break;
            case "-":
                return $this->command->execute($this->option,$this->arguement);
                break;
            case "*":
                return $this->command->execute($this->arguement);
                break;
            case "~":
                return $this->command->execute($this->arguement);
                break;
            default:
                //call_user_func
                if (count($this->option)!=0) {
                    call_user_func_array(array($this->command,"execute"),$this->option);
                } else {
                    call_user_func(array($this->command,"execute"), $this->arguement);
                }
                break;
        }
    }
    
}

abstract class BaseCommand {
    
    protected $option;
    protected $arguement;
    
    public function setOption($option) {
        $this->option = $option;
    }
    
    public function setArguement($arguement) {
        $this->arguement = $arguement;
    }
    
}

?>

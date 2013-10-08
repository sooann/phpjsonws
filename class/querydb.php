<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class querydb extends BaseCommand {
    public function execute($args) {
        if (strtolower(substr(trim($args),0,6))=="select") {
            global $conn;
            $result = mysql_query($args);
            if ($result!==false) {
                $data = array();
                while ($row=mysql_fetch_assoc($result)) {
                    $data[]=$row;
                }
                $packet["totalrow"]=count($data);
                $packet["data"]=$data;
                echo (json_encode($packet));
            }
        } else {
            $packet["error"]=1;
            $packet["errormsg"]="Invalid Query";
            echo (json_encode($packet));
        }
    }
}

?>

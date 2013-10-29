<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class querydb extends BaseCommand {
    private $BlobDatatypes = array("TINYBLOB","BLOB","MEDIUMBLOB","LONGBLOB");
    
    public function execute($args) {
        if (strtolower(substr(trim($args),0,6))=="select") {
            global $conn;
            $result = mysql_query($args);
            if ($result!==false) {
                $data = array();
                while ($row=mysql_fetch_assoc($result)) {
                    //check for blob
                    $numfields = mysql_num_fields($result);
                    for ($i = 0; $i<$numfields; $i += 1) {
                        $field = mysql_fetch_field($result, $i);
                        if ($field->blob==1) {
                            if ($row[$field->name]!=null) {
                                $row[$field->name]=unpack("H*",$row[$field->name])[1];
                            }
                        }
                    }
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

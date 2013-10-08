<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class querydb extends BaseCommand {
    
    public function execute($args) {
        if (strtolower(substr(trim($args),0,6))=="select") {
            
        } else {
            echo "invalid query";
        }
    }
}

?>

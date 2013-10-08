<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

    require "global.php";
    require "Slim/Slim.php";
   
    //set autoloader
    \Slim\Slim::registerAutoloader();
    
    //load slim
    $app = new \Slim\Slim(array('debug' => true));

    $app->get('/phpinfo', function () {
        echo phpinfo();
    });
    
    $app->get('/hello/:name', function ($name) {
        echo "Hello, $name";
    });
            
    $app->run();
?>

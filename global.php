<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require 'functions.php';
require 'includes/Command.php';
require 'includes/SQLWrapper.php'; 

date_default_timezone_set('Asia/Singapore'); 

session_start();

//setup SQL Connection
$conn = SQLWrapperConfiguration::setConnection("localhost:3306", "unicenta", "unicenta", "unicenta");

?>

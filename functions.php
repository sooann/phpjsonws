<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function autoload($className) {
    
    $includedir = array('/Form/element','/Form/statemachine','/Form/validation');
    
    $classnamearr=explode("\\",$className);
    $thisClass=end($classnamearr);

    $baseDir = __DIR__.'/';

    if (substr($baseDir, -strlen($thisClass)) === $thisClass) {
        $baseDir = substr($baseDir, 0, -strlen($thisClass));
    }

    $className = ltrim($className, '\\');
    $fileName  = $baseDir;
    $namespace = '';
    if ($lastNsPos = strripos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    
    if (file_exists($fileName)) {
        require_once $fileName;
    } else {
        foreach ($includedir as $dir) {
            $array = glob(__DIR__.$dir.'/*.php');
            foreach ($array as $file) {
                $temp = explode("/",$file);
                if (str_replace(".php","",end($temp))==$className) {
                    require_once $file;
                }
            }
        }
    }  
}

function now() {
    return date("Y-m-d H:i:s");
}

function whitelist($text,$symbols) {
    return preg_replace("/[^" . preg_quote($symbols, '/') . "]/i", "", $text);
}

function escapesql($text) {
    return str_replace("'", "''", $text);
}

function debug ($text) {
    if (is_array($text)) {
        echo "[";
        print_r($text);
        echo "]<br />";
    } else {
        echo "[$text]<br />";
    }
}

function getBasePath() {
    return str_replace($_SERVER["PATH_INFO"], "", $_SERVER["REQUEST_URI"]);
}
?>


<?php
// Import all
foreach (glob(__DIR__ . "/../php-jwt/*.php") as $filename) {
    require_once $filename;
}
foreach (glob(__DIR__ . "/*.php") as $filename) {
    require_once $filename;
}

Firebase\JWT\JWT::$leeway = 5;

function exception_handler($exception) {
    echo "Error: " , $exception->getMessage(), "\n";
}
  
set_exception_handler('exception_handler');

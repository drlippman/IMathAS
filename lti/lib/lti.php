<?php
// Import all
foreach (glob(__DIR__ . "/../php-jwt/*.php") as $filename) {
    require_once $filename;
}
foreach (glob(__DIR__ . "/*.php") as $filename) {
    require_once $filename;
}

Firebase\JWT\JWT::$leeway = 5;

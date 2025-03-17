<?php

// require_once "wp-load.php";

define('APP_PATH', dirname(__FILE__));

$preload_patterns = [
    "wp-load.php",
    "wp-settings.php",
    "wasmer/plugins/*.php",
    "wasmer/plugins/*/*.php",
    "wp-includes/*.php",
    "wp-includes/*/*.php",
    "wp-includes/*/*/*.php",
    "wp-includes/*/*/*/*.php",
    "wp-includes/*/*/*/*/*.php",
    "wp-includes/*/*/*/*/*/*.php",
    "wp-includes/*/*/*/*/*/*/*.php",
    "wp-includes/*/*/*/*/*/*/*/*.php",
    "wp-content/themes/*/*.php",
    "wp-content/themes/*/*/*.php",
    "wp-content/themes/*/*/*/*.php",
];

foreach ($preload_patterns as $pattern) {
    $files = glob(APP_PATH . "/" . $pattern);

    foreach ($files as $file) {
        opcache_compile_file($file);
    }
}

echo "Preloading complete" . PHP_EOL;
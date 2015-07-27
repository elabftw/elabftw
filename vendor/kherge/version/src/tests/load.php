<?php

define('BASE', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR);

spl_autoload_register(
    function ($class) {
        $path = BASE . str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\')) . '.php';

        if (file_exists($path)) {
            require $path;
        }
    }
);


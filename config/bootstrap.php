<?php
/**
 * Allow classes to autoload
 */
spl_autoload_register(function ($class)
{
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }

});
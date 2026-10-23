<?php
require_once __DIR__ . '/LiftedAutoloader.php';

spl_autoload_register(static function (string $class): void {
    $lifted = \Modulix\LiftedAutoloader::path($class, dirname(__DIR__));
    if ($lifted !== null) {
        if (is_readable($lifted)) {
            require $lifted;
        }
        return;
    }
    if (strncmp($class, 'Modulix\\', 8) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 8)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

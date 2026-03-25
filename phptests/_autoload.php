<?php
define("APP_GAMEMODULE_PATH", getenv("APP_GAMEMODULE_PATH"));
require_once APP_GAMEMODULE_PATH . "/php/stubs/BgaFrameworkStubs.php";
spl_autoload_register(function ($class_name) {
    // Production code namespace
    $namespacePrefix = "Bga\\Games\\wayfarers\\";
    if (strpos($class_name, $namespacePrefix) === 0) {
        $relativeClass = substr($class_name, strlen($namespacePrefix));
        $filePath = __DIR__ . "/../modules/php/" . str_replace("\\", "/", $relativeClass) . ".php";
        if (file_exists($filePath)) {
            require $filePath;
            return;
        }
    }

    // Test code namespace
    $testPrefix = "Tests\\";
    if (strpos($class_name, $testPrefix) === 0) {
        $relativeClass = substr($class_name, strlen($testPrefix));
        $filePath = __DIR__ . "/" . str_replace("\\", "/", $relativeClass) . ".php";
        if (file_exists($filePath)) {
            require $filePath;
            return;
        }
    }
});

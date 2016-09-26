<?php
declare(strict_types=1);

$composer = require(dirname(__DIR__) . "/vendor/autoload.php");
if (class_exists("\\MattyG\\AutoCodeLoader\\Autoloader") === true) {
    \MattyG\AutoCodeLoader\Autoloader::registerAutoloader(dirname(__DIR__) . "/var/classgen");
} else {
    $composer->addPsr4("", dirname(__DIR__) . "/var/classgen");
}

return require(__DIR__ . "/di.php");

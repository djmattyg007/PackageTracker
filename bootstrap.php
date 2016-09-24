<?php
declare(strict_types=1);

$composer = require(__DIR__ . "/vendor/autoload.php");
if (class_exists("\\MattyG\\AutoCodeLoader\\Autoloader") === true) {
    \MattyG\AutoCodeLoader\Autoloader::registerAutoloader(__DIR__ . "/var/classgen");
} else {
    $composer->addPsr4("", __DIR__ . "/var/classgen");
}

require(__DIR__ . "/cli.php");
require(__DIR__ . "/check.php");
require(__DIR__ . "/download.php");
require(__DIR__ . "/provider.php");

return require(__DIR__ . "/di.php");

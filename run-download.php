<?php
declare(strict_types=1);

$di = require(__DIR__ . "/bootstrap.php");
$di->values["cli_options"] = array("v");

$stdio = $di->get("cli_stdio");
$getopt = $di->get("cli_getopt");

$filename = $getopt->get(1, null);
if ($getopt->hasErrors() || $filename === null) {
    if ($getopt->hasErrors()) {
        foreach ($getopt->getErrors() as $error) {
            $stdio->errln($error->getMessage());
        }
    }
    if ($filename === null) {
        $stdio->errln("Filename was not specified");
    }
    exit(Aura\Cli\Status::USAGE);
}

$downloader = $di->newInstance("MattyG\\DependencyTracker\\Download\\Downloader", array("cacheLifetime" => 43200));

$packageList = json_decode(file_get_contents($argv[1]), true);
$downloader->downloadData($packageList);

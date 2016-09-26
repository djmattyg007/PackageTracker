<?php
declare(strict_types=1);

$di = require(dirname(__DIR__) . "/src/bootstrap.php");
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

$checker = $di->newInstance("MattyG\\PackageTracker\\Check\\Checker");

$packageList = json_decode(file_get_contents($argv[1]), true);
$packageData = $checker->checkVersions($packageList);

$tableFormatter = $di->newInstance("MattyG\\PackageTracker\\Check\\TableFormatter");
$stdio->out($tableFormatter->prepareTable($packageData));

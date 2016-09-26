<?php
declare(strict_types=1);

$di = require(dirname(__DIR__) . "/src/bootstrap.php");
$di->values["cli_options"] = array();

$stdio = $di->get("cli_stdio");
$getopt = $di->get("cli_getopt");

$versionString = $getopt->get(1, null);
if ($getopt->hasErrors() || $versionString === null) {
    if ($getopt->hasErrors()) {
        foreach ($getopt->getErrors() as $error) {
            $stdio->errln($error->getMessage());
        }
    }
    if ($versionString === null) {
        $stdio->errln("Version string was not specified");
    }
    exit(Aura\Cli\Status::USAGE);
}

$versionParser = $di->newInstance("Composer\\Semver\\VersionParser");
$stdio->outln($versionParser->normalize($versionString));

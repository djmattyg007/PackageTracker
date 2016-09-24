<?php
declare(strict_types=1);

use Aura\Di\ContainerBuilder;

$builder = new ContainerBuilder();
$di = $builder->newInstance(true);

$di->types["Aura\\Di\\Container"] = $di;

$di->types["Doctrine\\Common\\Cache\\CacheProvider"] = $di->lazyGet("cache");
$di->set("cache", $di->lazyNew("Doctrine\\Common\\Cache\\FilesystemCache", array("directory" => __DIR__ . "/cache")));
$di->types["GuzzleHttp\\Client"] = $di->lazyGet("guzzle");
$di->set("guzzle", $di->lazyNew("GuzzleHttp\\Client", array("config" => array("http_errors" => false))));

$di->types["Aura\\Cli\\Context"] = $di->lazyGet("cli_context");
$di->set("cli_context", $di->lazy(array($di->lazyNew("Aura\\Cli\\CliFactory"), "newContext"), $GLOBALS));
$di->set("cli_getopt", $di->lazyGetCall("cli_context", "getopt", $di->lazyValue("cli_options")));

$di->types["Aura\\Cli\\Stdio\\Formatter"] = $di->lazyGet("cli_formatter");
$di->types["MattyG\\PackageTracker\\Cli\\Formatter"] = $di->lazyGet("cli_formatter");
$di->set("cli_stdin", $di->lazyNew("Aura\\Cli\\Stdio\\Handle", array("name" => $di->lazyValue("stdin_resource"), "mode" => "r")));
$di->set("cli_stdout", $di->lazyNew("Aura\\Cli\\Stdio\\Handle", array("name" => $di->lazyValue("stdout_resource"), "mode" => "w+")));
$di->set("cli_stderr", $di->lazyNew("Aura\\Cli\\Stdio\\Handle", array("name" => $di->lazyValue("stderr_resource"), "mode" => "w+")));
$di->set("cli_formatter", $di->lazyNew("MattyG\\PackageTracker\\Cli\\Formatter", array("isPosix" => $di->lazyGetCall("cli_stdout", "isPosix"))));

$di->values["stdin_resource"] = "php://stdin";
$di->values["stdout_resource"] = "php://stdout";
$di->values["stderr_resource"] = "php://stderr";
$di->values["stdio_verbose"] = $di->lazyGetCall("cli_getopt", "get", "-v", false);

$di->types["MattyG\\PackageTracker\\Cli\\Stdio"] = $di->lazyGet("cli_stdio");
$di->set(
    "cli_stdio",
    $di->lazyNew(
        "MattyG\\PackageTracker\\Cli\\Stdio",
        array(
            "stdin" => $di->lazyGet("cli_stdin"),
            "stdout" => $di->lazyGet("cli_stdout"),
            "stderr" => $di->lazyGet("cli_stderr"),
        ),
        array(
            "setVerbose" => $di->lazyGetCall("cli_getopt", "get", "-v", false),//$di->lazyValue("stdio_verbose"),
        )
    )
);

$di->types["Zend_Text_Table_Decorator_Interface"] = $di->lazyGet("table_decorator");
$di->set("table_decorator", $di->lazyNew("Zend_Text_Table_Decorator_Unicode"));

return $di;

<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Cli;

use Aura\Cli\Stdio as BaseStdio;

class Stdio extends BaseStdio
{
    /**
     * @var bool
     */
    protected $verbose = false;

    /**
     * @param bool $verbose
     */
    public function setVerbose(bool $verbose)
    {
        $this->verbose = $verbose;
    }

    /**
     * @param string $string
     * @param bool $verbose
     */
    public function out($string = null, bool $verbose = false)
    {
        if ($verbose === false || ($verbose === true && $this->verbose === true)) {
            parent::out($string);
        }
    }

    /**
     * @param string $string
     * @param bool $verbose
     */
    public function outln($string = null, bool $verbose = false)
    {
        if ($verbose === false || ($verbose === true && $this->verbose === true)) {
            parent::outln($string);
        }
    }

    /**
     * @param string $string
     * @param bool $verbose
     */
    public function err($string = null, bool $verbose = false)
    {
        if ($verbose === false || ($verbose === true && $this->verbose === true)) {
            parent::err($string);
        }
    }

    /**
     * @param string $string
     * @param bool $verbose
     */
    public function errln($string = null, bool $verbose = false)
    {
        if ($verbose === false || ($verbose === true && $this->verbose === true)) {
            parent::errln($string);
        }
    }
}

<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Cli;

use Aura\Cli\Stdio\Formatter as BaseFormatter;

class Formatter extends BaseFormatter
{
    /**
     * @param bool $isPosix
     */
    public function __construct(bool $isPosix)
    {
        parent::__construct();
        $this->isPosix = $isPosix;
    }

    /**
     * @param string $string
     * @param bool|null $posix
     * @return string
     */
    public function format($string, $posix = null)
    {
        if ($posix === null) {
            return parent::format($string, $this->isPosix);
        } else {
            return parent::format($string, $posix);
        }
    }
}

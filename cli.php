<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Cli;

use Aura\Cli\Stdio as BaseStdio;
use Aura\Cli\Stdio\Formatter as BaseFormatter;
use Zend_Text_Table as BaseTable;
use Zend_Text_Table_Decorator_Interface as TableDecorator;
use Zend_Text_Table_Column as BaseTableColumn;
use Zend_Text_Table_Row as BaseTableRow;

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

class Table extends BaseTable
{
    /**
     * @param TableDecorator $tableDecorator
     * @param array $columnWidths
     * @param int $padding
     */
    public function __construct(TableDecorator $tableDecorator, array $columnWidths, int $padding)
    {
        $this->setDecorator($tableDecorator);
        $this->setColumnWidths($columnWidths);
        $this->setPadding($padding);
    }
}

class TableRow extends BaseTableRow
{
    // AutoCodeLoader doesn't work with classes in the global namespace
}

class TableColumn extends BaseTableColumn
{
    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var string
     */
    protected $fgColor = null;

    /**
     * @var string
     */
    protected $bgColor = null;

    /**
     * @var bool
     */
    protected $isHeading = false;

    /**
     * @param Formatter $formatter
     * @param string $content
     * @param bool $isHeading
     */
    public function __construct(Formatter $formatter, string $content, bool $isHeading = false)
    {
        $this->formatter = $formatter;
        $this->isHeading = $isHeading;
        parent::__construct($content);
    }

    public function setFgColor(/* ?string */ $fgColor)
    {
        $this->fgColor = $fgColor;
    }

    public function setBgColor(/* ?string */ $bgColor)
    {
        $this->bgColor = $bgColor;
    }

    /**
     * @param int $columnWidth
     * @param int $padding
     * @return string
     */
    public function render($columnWidth, $padding = 0)
    {
        $result = parent::render($columnWidth, $padding);

        $formattingCode = "";
        if ($this->isHeading === true) {
            $formattingCode = "bold";
        }
        if ($this->fgColor !== null) {
            $formattingCode = $formattingCode ? "{$formattingCode} {$this->fgColor}" : "{$this->fgColor}";
        }
        if ($this->bgColor !== null) {
            $formattingCode = $formattingCode ? "{$formattingCode} {$this->bgColor}bg" : "{$this->bgColor}bg";
        }
        if ($formattingCode) {
            $result = $this->formatter->format("<<{$formattingCode}>>$result<<reset>>");
        }

        return $result;
    }
}

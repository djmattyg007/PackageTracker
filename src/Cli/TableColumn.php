<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Cli;

use Zend_Text_Table_Column as BaseTableColumn;

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

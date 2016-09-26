<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Cli;

use Zend_Text_Table as BaseTable;
use Zend_Text_Table_Decorator_Interface as TableDecorator;

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

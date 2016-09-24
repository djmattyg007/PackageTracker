<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Check;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Doctrine\Common\Cache\CacheProvider;
use MattyG\PackageTracker\Cli\Stdio;
use MattyG\PackageTracker\Cli\Table;
use MattyG\PackageTracker\Cli\TableFactory;
use MattyG\PackageTracker\Cli\TableColumnFactory;
use MattyG\PackageTracker\Cli\TableRow;
use MattyG\PackageTracker\Cli\TableRowFactory;

class Checker
{
    /**
     * @var Stdio
     */
    private $stdio;

    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @param Stdio $stdio
     * @param CacheProvider $cache
     * @param VersionParser $versionParser
     */
    public function __construct(Stdio $stdio, CacheProvider $cache, VersionParser $versionParser)
    {
        $this->stdio = $stdio;
        $this->cache = $cache;
        $this->versionParser = $versionParser;
    }

    /**
     * @param array $versionList
     */
    public function checkVersions(array $versionList)
    {
        $packageData = array();
        foreach ($versionList as $packageType => $packageVersions) {
            $this->cache->setNamespace($packageType);

            foreach ($packageVersions as $packageName => $packageVersion) {
                $packageInfo = $this->checkPackage($packageType, $packageName, $packageVersion);
                if ($packageInfo) {
                    $packageData[] = $packageInfo;
                }
            }
        }
        return $packageData;
    }

    /**
     * @param string $type
     * @param string $name
     * @param string $version
     * @return array|null
     */
    private function checkPackage(string $type, string $name, string $version)// : ?array
    {
        $versions = $this->cache->fetch($name);
        if (!$versions) {
            $this->stdio->errln(sprintf('Versions not in cache, skipping (type: "%1$s", name: "%2$s")', $type, $name));
            return null;
        }
        $normalizedVersion = $this->versionParser->normalize($version);

        $latestVersion = $versions[count($versions) - 1];
        $this->stdio->outln(sprintf('%1$s - current: %2$s, latest: %3$s', $name, $normalizedVersion, $latestVersion), true);
        return array(
            "type" => $type,
            "name" => $name,
            "latest" => $latestVersion,
            "current" => $normalizedVersion,
        );
    }
}

class TableFormatter
{
    const VERSION_SEPARATOR = ".";

    /**
     * @var TableFactory
     */
    private $tableFactory;

    /**
     * @var TableRowFactory
     */
    private $rowFactory;

    /**
     * @var TableColumnFactory
     */
    private $columnFactory;

    /**
     * @var int
     */
    private $padding = 1;

    /**
     * @param TableFactory $tableFactory
     * @param TableRowFactory $rowFactory
     * @param TableColumnFactory $columnFactory
     */
    public function __construct(TableFactory $tableFactory, TableRowFactory $rowFactory, TableColumnFactory $columnFactory)
    {
        $this->tableFactory = $tableFactory;
        $this->rowFactory = $rowFactory;
        $this->columnFactory = $columnFactory;
    }

    /**
     * @param int $padding
     */
    public function setPadding(int $padding)
    {
        $this->padding = $padding;
    }

    public function prepareTable(array $packageData): Table
    {
        $formattedPackageData = array();
        foreach ($packageData as $packageInfo) {
            $formattedPackageData[] = $this->formatPackageInfo($packageInfo);
        }
        $columnWidths = $this->findColumnWidths($formattedPackageData);

        $table = $this->tableFactory->create(array("columnWidths" => $columnWidths, "padding" => $this->padding));
        $table->appendRow($this->getHeaderRow());

        foreach ($formattedPackageData as $formattedPackageInfo) {
            $table->appendRow($this->prepareRow($formattedPackageInfo));
        }

        return $table;
    }

    /**
     * @return TableRow
     */
    private function getHeaderRow(): TableRow
    {
        $headingRow = $this->rowFactory->create();
        foreach (array("Type", "Name", "Current", "Latest") as $heading) {
            $headingRow->appendColumn($this->columnFactory->create(array("content" => $heading, "isHeading" => true)));
        }
        return $headingRow;
    }

    /**
     * @param array $packageInfo
     * @return array
     */
    private function formatPackageInfo(array $packageInfo): array
    {
        $explodedLatestVersion = explode(self::VERSION_SEPARATOR, $packageInfo["latest"]);
        $explodedCurrentVersion = explode(self::VERSION_SEPARATOR, $packageInfo["current"]);
        if (count($explodedLatestVersion) === count($explodedCurrentVersion) &&
            end($explodedLatestVersion) === "0" &&
            end($explodedCurrentVersion) === "0"
        ) {
            array_pop($explodedLatestVersion);
            array_pop($explodedCurrentVersion);
            $packageInfo["latest"] = implode(self::VERSION_SEPARATOR, $explodedLatestVersion);
            $packageInfo["current"] = implode(self::VERSION_SEPARATOR, $explodedCurrentVersion);
        }

        return $packageInfo;
    }

    /**
     * @param array $formattedPackageData
     * @return array
     */
    private function findColumnWidths(array $formattedPackageData): array
    {
        $columnWidths = array();
        foreach ($formattedPackageData as $row) {
            $columnIndex = 0;
            foreach (array_values($row) as $column) {
                $columnWidths[$columnIndex] = max($columnWidths[$columnIndex] ?? 0, mb_strlen($column));
                $columnIndex++;
            }
        }

        $totalPadding = $this->padding * 2;
        return array_map(function($columnWidth) use ($totalPadding) {
            return $columnWidth + ($totalPadding * 2);
        }, $columnWidths);
    }

    /**
     * @param array $columns
     * @return TableRow
     */
    private function prepareRow(array $columns): TableRow
    {
        $row = $this->rowFactory->create();

        $row->appendColumn($this->columnFactory->create(array("content" => $columns["type"])));
        $row->appendColumn($this->columnFactory->create(array("content" => $columns["name"])));
        $row->appendColumn($this->columnFactory->create(array("content" => $columns["latest"])));

        $columnCurrent = $this->columnFactory->create(array("content" => $columns["current"]));
        $outOfDate = Comparator::greaterThan($columns["latest"], $columns["current"]);
        $columnCurrent->setBgColor($outOfDate ? "red" : "green");
        if ($outOfDate === false) {
            $columnCurrent->setFgColor("black");
        }
        $row->appendColumn($columnCurrent);

        return $row;
    }
}

<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Check;

use Composer\Semver\VersionParser;
use Doctrine\Common\Cache\CacheProvider;
use MattyG\PackageTracker\Cli\Stdio;

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

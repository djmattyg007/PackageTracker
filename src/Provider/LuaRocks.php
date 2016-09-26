<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Provider;

use Composer\Semver\VersionParser;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Client\GuzzleException;

abstract class LuaRocks implements ProviderInterface
{
    /**
     * @var Guzzle
     */
    private $guzzle;

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @var array
     */
    private static $packageData = array();

    /**
     * @param Guzzle $guzzle
     * @param VersionParser $versionParser
     */
    public function __construct(Guzzle $guzzle, VersionParser $versionParser)
    {
        $this->guzzle = $guzzle;
        $this->versionParser = $versionParser;
    }

    /**
     * @param string $packageName
     * @return array
     * @throws FetchAvailableVersionsFailureException
     */
    public function fetchAvailableVersions(string $packageName): array
    {
        $versions = $this->fetchPackageData($packageName);

        $normalisedVersions = array_map(array($this, "normaliseVersion"), $versions);
        return array_unique(array_filter($normalisedVersions));
    }

    /**
     * @return string
     */
    public function getLuaVersion(): string
    {
        return static::LUA_VERSION;
    }

    /**
     * @param string $packageName
     * @return array
     * @throws FetchAvailableVersionsFailureException
     */
    private function fetchPackageData(string $packageName): array
    {
        if (isset(self::$packageData[static::LUA_VERSION]) === false) {
            $this->loadPackageData();
        }

        if (isset(self::$packageData[static::LUA_VERSION][$packageName])) {
            return array_keys(self::$packageData[static::LUA_VERSION][$packageName]);
        } else {
            throw new FetchAvailableVersionsFailureException();
        }
    }

    /**
     * @throws FetchAvailableVersionsFailureException
     */
    private function loadPackageData()
    {
        try {
            $response = $this->guzzle->request("GET", "https://luarocks.org/manifest-5.1.json");
            if ($response->getStatusCode() !== 200) {
                throw new FetchAvailableVersionsFailureException();
            }
        } catch (GuzzleException $e) {
            throw new FetchAvailableVersionsFailureException();
        }

        $content = json_decode($response->getBody()->getContents(), true);
        self::$packageData[static::LUA_VERSION] = $content["repository"];
    }

    /**
     * @param string $version
     * @return string
     */
    private function normaliseVersion(string $version): string
    {
        $result = preg_match('/^(.+)-\d{1,2}$/', $version, $matches);
        if (!$result) {
            return "";
        }
        return $this->versionParser->normalize($matches[1]);
    }
}

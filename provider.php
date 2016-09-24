<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Provider;

use Aura\Di\Container as DiContainer;
use Composer\Semver\VersionParser;
use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Client\GuzzleException;

final class ProviderFactory
{
    /**
     * @var DiContainer
     */
    private $diContainer;

    /**
     * @var array
     */
    private $providerTypes = array(
        "luarocks-lua51" => LuaRocks51::class,
        "luarocks-lua52" => LuaRocks52::class,
        "luarocks-lua53" => LuaRocks53::class,
        "npm" => Npm::class,
        "packagist" => Packagist::class,
        "pypi" => PyPI::class,
        "rubygems" => RubyGems::class,
    );

    public function __construct(DiContainer $diContainer, array $additionalProviderTypes = array())
    {
        $this->diContainer = $diContainer;
        $this->providerTypes = array_merge($this->providerTypes, $additionalProviderTypes);
    }

    /**
     * @param string $providerType
     * @return ProviderInterface
     */
    public function create(string $providerType): ProviderInterface
    {
        if (isset($this->providerTypes[$providerType]) === false) {
            throw new Exception(sprintf("Unrecognised provider type provided: %s", $providerType));
        }
        return $this->diContainer->newInstance($this->providerTypes[$providerType]);
    }
}

interface ProviderInterface
{
    /**
     * @param string $packageName
     * @return array
     * @throws FetchAvailableVersionsFailureException
     */
    public function fetchAvailableVersions(string $packageName): array;
}

final class FetchAvailableVersionsFailureException extends Exception
{
}

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

class LuaRocks51 extends LuaRocks
{
    const LUA_VERSION = "5.1";
}

class LuaRocks52 extends LuaRocks
{
    const LUA_VERSION = "5.2";
}

class LuaRocks53 extends LuaRocks
{
    const LUA_VERSION = "5.3";
}

class Npm implements ProviderInterface
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
        try {
            $response = $this->guzzle->request("GET", sprintf("https://registry.npmjs.org/%s", $packageName));
            if ($response->getStatusCode() !== 200) {
                throw new FetchAvailableVersionsFailureException();
            }
        } catch (GuzzleException $e) {
            throw new FetchAvailableVersionsFailureException();
        }

        $content = json_decode($response->getBody()->getContents(), true);
        $versions = array_keys($content["versions"]);

        $normalisedVersions = array_map(array($this->versionParser, "normalize"), $versions);
        return $normalisedVersions;
    }
}

class Packagist implements ProviderInterface
{
    /**
     * @var Guzzle
     */
    private $guzzle;

    /**
     * @param Guzzle $guzzle
     */
    public function __construct(Guzzle $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @param string $packageName
     * @return array
     * @throws FetchAvailableVersionsFailureException
     */
    public function fetchAvailableVersions(string $packageName): array
    {
        try {
            $response = $this->guzzle->request("GET", sprintf("https://packagist.org/p/%s.json", $packageName));
            if ($response->getStatusCode() !== 200) {
                throw new FetchAvailableVersionsFailureException();
            }
        } catch (GuzzleException $e) {
            throw new FetchAvailableVersionsFailureException();
        }

        $content = json_decode($response->getBody()->getContents(), true);
        $packageData = $content["packages"][$packageName];
        $versions = array_column($packageData, "version_normalized");

        $filteredVersions = $this->filterDevVersions($versions);
        return $filteredVersions;
    }

    /**
     * @param array $versions
     * @return array
     */
    protected function filterDevVersions(array $versions): array
    {
        return array_filter($versions, function($version) {
            return VersionParser::parseStability($version) !== "dev";
        });
    }
}

class PyPI implements ProviderInterface
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
        try {
            $response = $this->guzzle->request("GET", sprintf("https://pypi.python.org/pypi/%s/json", $packageName));
            if ($response->getStatusCode() !== 200) {
                throw new FetchAvailableVersionsFailureException();
            }
        } catch (GuzzleException $e) {
            throw new FetchAvailableVersionsFailureException();
        }

        $content = json_decode($response->getBody()->getContents(), true);
        $versions = array_keys($content["releases"]);

        $normalisedVersions = array_map(array($this->versionParser, "normalize"), $versions);
        return $normalisedVersions;
    }
}

class RubyGems implements ProviderInterface
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
        try {
            $response = $this->guzzle->request("GET", sprintf("https://rubygems.org/api/v1/versions/%s.json", $packageName));
            if ($response->getStatusCode() !== 200) {
                throw new FetchAvailableVersionsFailureException();
            }
        } catch (GuzzleException $e) {
            throw new FetchAvailableVersionsFailureException();
        }

        $content = json_decode($response->getBody()->getContents(), true);
        $versions = array_column($content, "number");

        $normalisedVersions = array_map(array($this, "normaliseVersion"), $versions);
        return $normalisedVersions;
    }

    /**
     * @param string $version
     * @return string
     */
    private function normaliseVersion(string $version): string
    {
        $version = str_replace(".pre", ".RC", $version);
        return $this->versionParser->normalize($version);
    }
}

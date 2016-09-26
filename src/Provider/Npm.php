<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Provider;

use Composer\Semver\VersionParser;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Client\GuzzleException;

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

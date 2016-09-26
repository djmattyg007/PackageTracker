<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Provider;

use Composer\Semver\VersionParser;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Client\GuzzleException;

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

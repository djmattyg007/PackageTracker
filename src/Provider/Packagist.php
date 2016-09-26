<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Provider;

use Composer\Semver\VersionParser;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Client\GuzzleException;

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

<?php
declare(strict_types=1);

namespace MattyG\DependencyTracker\Download;

use Composer\Semver\Semver;
use Doctrine\Common\Cache\CacheProvider;
use MattyG\DependencyTracker\Cli\Stdio;
use MattyG\DependencyTracker\Provider\FetchAvailableVersionsFailureException;
use MattyG\DependencyTracker\Provider\ProviderFactory;
use MattyG\DependencyTracker\Provider\ProviderInterface;

class Downloader
{
    /**
     * @var Stdio
     */
    private $stdio;

    /**
     * @var ProviderFactory
     */
    private $providerFactory;

    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @var int
     */
    private $cacheLifetime;

    /**
     * @param Stdio $stdio
     * @param ProviderFactory $providerFactory
     * @param CacheProvider $cache
     * @param int $cacheLifetime
     */
    public function __construct(Stdio $stdio, ProviderFactory $providerFactory, CacheProvider $cache, int $cacheLifetime)
    {
        $this->stdio = $stdio;
        $this->providerFactory = $providerFactory;
        $this->cache = $cache;
        $this->cacheLifetime = $cacheLifetime;
    }

    /**
     * @param array $packageList
     */
    public function downloadData(array $packageList)
    {
        foreach ($packageList as $packageType => $packageNames) {
            $provider = $this->providerFactory->create($packageType);
            $this->cache->setNamespace($packageType);

            foreach ($packageNames as $packageName) {
                $this->checkPackage($provider, $packageType, $packageName);
            }
        }
    }

    /**
     * @param ProviderInterface $provider
     * @param string $packageType
     * @param string $packageName
     */
    private function checkPackage(ProviderInterface $provider, string $packageType, string $packageName)
    {
        if ($this->cache->contains($packageName)) {
            $this->stdio->outln(sprintf('Versions in cache, not fetching (type: "%1$s", name: "%2$s")', $packageType, $packageName), true);
            return;
        }

        try {
            $versions = $provider->fetchAvailableVersions($packageName);
        } catch (FetchAvailableVersionsFailureException $e) {
            $this->stdio->errln(sprintf('Error while fetching versions (type: "%1$s", name: "%2$s")', $packageType, $packageName));
        }

        $sortedVersions = Semver::sort($versions);
        $this->cache->save($packageName, $sortedVersions, $this->cacheLifetime);
        $this->stdio->outln(sprintf('Successfully downloaded %1$d versions (type: "%2$s", name: "%3$s")', count($versions), $packageType, $packageName), true);
    }
}

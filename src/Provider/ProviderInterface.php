<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Provider;

interface ProviderInterface
{
    /**
     * @param string $packageName
     * @return array
     * @throws FetchAvailableVersionsFailureException
     */
    public function fetchAvailableVersions(string $packageName): array;
}

<?php
declare(strict_types=1);

namespace MattyG\PackageTracker\Provider;

use Aura\Di\Container as DiContainer;
use Exception;

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

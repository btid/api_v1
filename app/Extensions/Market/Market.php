<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\App\Extensions\Market;

use ArrayIterator\Api\Crypt\App\Extensions\Market\Schema\IndodaxSchema;
use ArrayIterator\Api\Crypt\Source\App;
use ArrayIterator\Api\Crypt\Source\Extension;
use ArrayIterator\Api\Crypt\Source\GroupRouteInterface;
use ArrayIterator\Api\Crypt\Source\MigrationSupportInterface;

/**
 * Class Market
 * @package ArrayIterator\Api\Crypt\App\Extensions\Market
 */
class Market extends Extension implements GroupRouteInterface, MigrationSupportInterface
{
    /**
     * @return string
     */
    public function getMigrationPath(): string
    {
        return __DIR__ . '/Migrations/';
    }

    /**
     * @return array
     */
    public function getDatabaseSchema(): array
    {
        return [
            IndodaxSchema::class
        ];
    }

    protected function afterInit()
    {
        // register autoloader
        $this->extensionHelperRegisterObjectAutoloader();
    }

    public function __invoke(App $app)
    {
        // TODO: Implement __invoke() method.
    }

    public function routeGetGroupPrefix(): string
    {
        return 'market';
    }
}

<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

/**
 * Interface GroupRouteInterface
 * @package ArrayIterator\Api\Crypt\Source
 */
interface GroupRouteInterface
{
    /**
     * @param App $app
     * @return mixed
     */
    public function __invoke(App $app);

    /**
     * @return string
     */
    public function routeGetGroupPrefix() : string;
}

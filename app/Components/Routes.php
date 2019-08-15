<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Components;

use ArrayIterator\Api\Crypt\Source\App;
use ArrayIterator\Api\Crypt\Source\ExtensionLoader;
use ArrayIterator\Api\Crypt\Source\GroupRouteInterface;
use ArrayIterator\Api\Crypt\Source\Route;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @var App $this
 * @var ServerRequestInterface $request
 * @var ExtensionLoader $extensionLoader
 */
$container = $this->getContainer();
/**

 */
$request = $container->has('request') ? $container['request'] : null;
$request = $request && $request instanceof ServerRequestInterface ? $request : null;
$path = $request ? $request->getUri()->getPath() : null;
$extensionLoader = $container['extension'];
foreach ($extensionLoader as $keyExt => $ext) {
    $extension = $extensionLoader->load($ext);
    if (!$extension instanceof GroupRouteInterface) {
        continue;
    }
    $def = $extension->routeGetGroupPrefix();
    if ($request && $path) {
        // check if route group prefix does not contain capturing group
        if (is_string($def) && !preg_match('~[()\[\]{}]~', $def)) {
            // check route with current request prefix
            $defQ = preg_quote('/' . ltrim($def, '/'), '/');
            if (!preg_match("/{$defQ}/i", $path)) {
                continue;
            }
        }
        if (is_string($def)) {
            $def = ltrim($def, '/');
            Route::group("/{root_route: {$def}}", $extension);
        }
    }
}

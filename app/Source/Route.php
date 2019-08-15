<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

use Slim\App;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;

/**
 * Class Route
 * @package ArrayIterator\Api\Crypt\Source
 *
 * @method static RouteInterface GET(string $pattern, callable $callback);
 * @method static RouteInterface POST(string $pattern, callable $callback);
 * @method static RouteInterface ANY(string $pattern, callable $callback);
 * @method static RouteInterface PUT(string $pattern, callable $callback);
 * @method static RouteInterface DELETE(string $pattern, callable $callback);
 * @method static RouteInterface OPTIONS(string $pattern, callable $callback);
 * @method static RouteInterface HEAD(string $pattern, callable $callback);
 * @method static RouteInterface map(string[] $methods, string $pattern, callable $callback);
 * @method static RouteInterface {*}(string $pattern, callable $callback);
 * @method static RouteGroupInterface group(string $pattern, callable $callback);
 */
class Route
{
    /**
     * @var App[]
     */
    private static $app = [];

    /**
     * @var string
     */
    protected static $selectedApp = 'default';

    /**
     * @param App $slim
     * @param string|null $name
     *
     * @return App
     */
    public static function setApp(App $slim, string $name = null)
    {
        $name = $name === null ? static::$selectedApp : $name;
        static::$app[$name] = $slim;

        return $slim;
    }

    /**
     * @param string $name
     *
     * @return App
     */
    public static function switchApp(string $name)
    {
        if (isset(static::$app[$name])) {
            static::$selectedApp = $name;
            return static::$app[$name];
        }

        throw new \RuntimeException(
            sprintf('Application %s has not been registered', $name)
        );
    }

    /**
     * @param BaseRoute|string $className
     *
     * @return RouteInterface
     */
    public static function route($className)
    {
        /**
         * @var BaseRoute $className
         */
        if (is_string($className) && class_exists($className) && is_subclass_of($className, BaseRoute::class)
            || is_object($className) && $className instanceof BaseRoute
        ) {
            $methods = [];
            foreach ((array) $className::METHODS as $key => $v) {
                if (is_string($v)) {
                    $methods[] = $v;
                }
            }

            return static::map(empty($methods) ? ['GET'] : $methods, $className::PATTERN, $className);
        }
        if (is_string($className)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid class name %s', $className)
            );
        }
        throw new \InvalidArgumentException(
            sprintf('Invalid parameter %s', 'route class name or object')
        );
    }

    /**
     * @param string|null $name
     *
     * @return null|App
     */
    public static function getApp(string $name = null)
    {
        $name = $name === null ? static::$selectedApp : $name;
        if (isset(static::$app[$name])) {
            return static::$app[$name];
        }

        return null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $app = static::getApp();
        if (!$app) {
            throw new \RuntimeException(
                'No default app set'
            );
        }
        if (method_exists($app, $name) || strtolower($name) === 'map') {
            return call_user_func_array([$app, $name], $arguments);
        }
        return $app->map([$name], ...$arguments);
    }

    public function __call(string $name, array $arguments)
    {
        return static::__callStatic($name, $arguments);
    }
}

<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

use Exception;
use ArrayIterator\Api\Crypt\Source\Exception\Unauthorized;
use ArrayIterator\Api\Crypt\Source\Exception\Forbidden;
use ArrayIterator\Api\Crypt\Source\Exception\Expired;
use ArrayIterator\Api\Crypt\Source\Generator\Json;
use ArrayIterator\Api\Crypt\Source\Handler\Unauthorized as UnauthorizedHandler;
use ArrayIterator\Api\Crypt\Source\Handler\Forbidden as ForbiddenHandler;
use ArrayIterator\Api\Crypt\Source\Handler\Expired as ExpiredHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as Slim;
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;
use Slim\Exception\SlimException;

/**
 * Class App
 * @package ArrayIterator\Api\Crypt\Source
 */
class App extends Slim
{
    /**
     * @param bool $silent
     * @return ResponseInterface
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function run($silent = false)
    {
        // set request
        Json::setRequest($this->getContainer()->get('request'));
        // set root dir for temporary
        Json::setRootDir(dirname(__DIR__));
        return parent::run($silent);
    }

    /**
     * @param Exception $e
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed|ResponseInterface
     */
    protected function handleException(Exception $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        $container = $this->getContainer();
        if ($e instanceof MethodNotAllowedException) {
            $handler = 'notAllowedHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e->getAllowedMethods()];
        } elseif ($e instanceof NotFoundException) {
            $handler = 'notFoundHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e];
        } elseif ($e instanceof Unauthorized) {
            $handler = 'unauthorizedHandler';
            if (!$container->has($handler)) {
                $container[$handler] = function () {
                    return new UnauthorizedHandler();
                };
            }
            $params = [$e->getRequest(), $e->getResponse(), $e];
        } elseif ($e instanceof Forbidden) {
            $handler = 'forbiddenHandler';
            if (!$container->has($handler)) {
                $container[$handler] = function () {
                    return new ForbiddenHandler();
                };
            }
            $params = [$e->getRequest(), $e->getResponse(), $e];
        } elseif ($e instanceof Expired) {
            $handler = 'expiredHandler';
            if (!$container->has($handler)) {
                $container[$handler] = function () {
                    return new ExpiredHandler();
                };
            }
            $params = [$e->getRequest(), $e->getResponse(), $e];
        } elseif ($e instanceof SlimException) {
            // This is a Stop exception and contains the response
            return $e->getResponse();
        } else {
            // Other exception, use $request and $response params
            $handler = 'errorHandler';
            $params = [$request, $response, $e];
        }

        if ($container->has($handler)) {
            $callable = $container->get($handler);
            // Call the registered handler
            return call_user_func_array($callable, $params);
        }

        return Json::exception($e);
        // No handlers found, so just throw the exception
        // throw $e;
    }

    /**
     * @return bool
     */
    public static function isLocalHost() : bool
    {
        $server = $_SERVER;
        return isset($server['REMOTE_ADDR'])
            && $server['REMOTE_ADDR'] === '127.0.0.1';
    }
}

<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Components;

use ArrayIterator\Api\Crypt\Source\Generator\Json;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Uri;

$this->add(function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    callable $next
) {
    // set Request & Response Default
    $request = Json::setRequest($request);
    $response = Json::setResponse($response);
    Json::setRootDir(dirname(dirname(__DIR__)));

    /**
     * @var Environment $environment
     * @var ContainerInterface$this
     */
    // continue
    $environment = $this['environment'];
    if (!isset($serverParams['REQUEST_TIME_FLOAT'])) {
        $environment['REQUEST_TIME_FLOAT'] = microtime(true);
        $request = Request::createFromEnvironment($environment);
    }
    if (isset($this['oldRequest'])) {
        unset($this['oldRequest']);
    }
    $this['oldRequest'] = function () use ($request) {
        return $request;
    };

    $uri = $request->getUri();
    $fileName = $environment->get('SCRIPT_FILENAME');
    $scriptName = $environment->get('SCRIPT_NAME');
    $reqUri = $environment->get('REQUEST_URI');
    if ($uri instanceof Uri && is_string($scriptName) && is_string($reqUri)) {
        // when it run on php -S
        if (is_string($fileName)
            && ($scriptName === '/' || $scriptName === $reqUri)
        ) {
            $environment['SCRIPT_NAME'] = '/'.basename($fileName);
            $environment['PHP_SELF'] = $environment['SCRIPT_NAME'];
            $request = $request->withUri($uri->createFromEnvironment($environment));
        } elseif ($environment->get('SCRIPT_NAME') === $uri->getBasePath()) {
            $environment['SCRIPT_NAME'] = dirname($environment['SCRIPT_NAME']);
            $request = $request->withUri($uri->createFromEnvironment($environment));
        }
    }

    if (function_exists('header_remove')) {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
        }
    }
    $request = Json::setRequest(
        $request
            ->withAddedHeader(
                'Content-Type',
                'application/json;charset=utf-8'
            )->withHeader(
                'Accept',
                'application/json'
            )
    );

    $response = Json::setResponse(
        $response
            ->withHeader(
                'Content-Type',
                'application/json;charset=utf-8'
            )->withHeader(
                'X-Robots-Tag',
                'noindex, nofollow, noodp, noydir, noarchive'
            )->withAddedHeader(
                'Access-Control-Allow-Origin',
                '*'
            )->withAddedHeader(
                'Access-Control-Allow-Methods',
                'POST, OPTIONS, GET'
            )->withAddedHeader(
                'Access-Control-Request-Headers',
                'Content-Type, X-PINGOTHER, Data-Type'
            )
    );

    return $next($request, $response);
});

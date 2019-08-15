<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Generator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Class Json
 * @package ArrayIterator\Api\Crypt\Source\Generator
 * @mixin JsonPatent
 */
class Json
{
    /**
     * @var ResponseInterface
     */
    protected static $response;

    /**
     * @var ServerRequestInterface
     */
    protected static $request = null;

    /**
     * @var JsonPatent
     */
    protected static $jsonPatent;

    /**
     * @return JsonPatent
     */
    public static function getJsonPatent() : JsonPatent
    {
        if (!static::$jsonPatent) {
            static::$jsonPatent = new JsonPatent(static::$request);
        }

        return static::$jsonPatent;
    }

    /**
     * @param JsonPatent|string $jsonPatent
     */
    public static function setJsonPatent($jsonPatent)
    {
        if (is_string($jsonPatent)) {
            if (is_subclass_of($jsonPatent, JsonPatent::class)) {
                $jsonPatent = new JsonPatent(static::$request);
            }
        }

        if (!is_object($jsonPatent) || !$jsonPatent instanceof JsonPatent) {
            return;
        }

        static::$jsonPatent = $jsonPatent;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public static function setResponse(ResponseInterface $response) : ResponseInterface
    {
        return static::$response = $response;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    public static function setRequest(ServerRequestInterface $request) : ServerRequestInterface
    {
        return static::$request = $request;
    }

    /**
     * @return ResponseInterface
     */
    public static function getResponse() : ResponseInterface
    {
        return static::$response;
    }

    /**
     * @return ServerRequestInterface
     */
    public static function getRequest() : ServerRequestInterface
    {
        return static::$request;
    }

    /**
     * @param string|null $string
     * @return string
     */
    public static function setRootDir(string $string = null)
    {
        $args = func_get_args();
        return static::getJsonPatent()->setRootDirectory(...$args);
    }

    /**
     * @return string|null
     */
    public static function getRootDir()
    {
        return static::getJsonPatent()->getRootDirectory();
    }

    /**
     * @param $data
     * @param int $options
     * @param int $depth
     *
     * @return false|string
     */
    public static function encode($data, int $options = 0, int $depth = 512)
    {
        $args = func_get_args();
        return json_encode(...$args);
    }

    /**
     * @param string $data
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     *
     * @return mixed
     */
    public static function decode(string $data, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $args = func_get_args();
        return json_decode(...$args);
    }

    /**
     * @param $data
     * @param ResponseInterface|null $response
     *
     * @return ResponseInterface
     */
    public static function success($data, ResponseInterface $response = null) : ResponseInterface
    {
        return static::getJsonPatent()->success($response?? static::getResponse(), $data);
    }

    /**
     * @param $data
     * @param int $statusCode
     * @param ResponseInterface|null $response
     *
     * @return ResponseInterface
     */
    public static function successCode(
        $data,
        int $statusCode = 200,
        ResponseInterface $response = null
    ) : ResponseInterface {
        $args = [
            $response?? static::getResponse(),
            $data
        ];
        unset($data);
        if ($statusCode !== null) {
            $args[] = $statusCode;
        }

        return static::getJsonPatent()->successCode(...$args);
    }

    /**
     * @param mixed $message
     * @param int $statusCode
     * @param ResponseInterface|null $response
     *
     * @return ResponseInterface
     */
    public static function errorCode(
        $message = null,
        int $statusCode = null,
        ResponseInterface $response = null
    ) : ResponseInterface {
        $args = [
            $response?? static::getResponse(),
            $message
        ];
        unset($message);
        if ($statusCode !== null) {
            $args[] = $statusCode;
        }

        return static::getJsonPatent()->errorCode(...$args);
    }

    /**
     * @param Throwable $e
     * @param ResponseInterface|null $response
     * @param int $code
     * @return ResponseInterface
     */
    public static function exception(
        Throwable $e,
        ResponseInterface $response = null,
        int $code = null
    ) : ResponseInterface {
        $args = [
            $response?? static::getResponse(),
            $e
        ];
        if ($code !== null) {
            $args[] = $code;
        }

        return static::getJsonPatent()->exceptionCode(...$args);
    }

    /**
     * @param mixed $message
     * @param ResponseInterface|null $response
     *
     * @return ResponseInterface
     */
    public static function error(
        $message = null,
        ResponseInterface $response = null
    ) : ResponseInterface {
        $args = [$response?? static::getResponse()];
        if (func_num_args() > 0) {
            $args[] = $message;
        }

        return static::getJsonPatent()->error(...$args);
    }

    /**
     * @param mixed $message
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public static function notFound(
        $message = null,
        ResponseInterface $response = null
    ) : ResponseInterface {
        $args = [
            $response?? static::getResponse(),
            $message,
            404
        ];
        return static::getJsonPatent()->errorCode(...$args);
    }

    /**
     * @param mixed $message
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public static function preconditionFailed(
        $message = null,
        ResponseInterface $response = null
    ) : ResponseInterface {
        $args = [
            $response?? static::getResponse(),
            $message,
            412
        ];
        return static::getJsonPatent()->errorCode(...$args);
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, array $arguments)
    {
        return call_user_func_array([static::getJsonPatent(), $name], $arguments);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        return static::__callStatic($name, $arguments);
    }
}

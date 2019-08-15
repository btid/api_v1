<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Generator;

/**
 * Class UserAgent
 * @package ArrayIterator\Api\Crypt\Source\Generator
 * @mixin DesktopUserAgent
 */
class UserAgent
{
    /**
     * @var DesktopUserAgent
     */
    protected $userAgent;
    /**
     * @var UserAgent
     */
    protected static $instance;

    /**
     * UserAgent constructor.
     */
    public function __construct()
    {
        $this->userAgent = new DesktopUserAgent();
        static::$instance = $this;
    }

    public function getUserAgent() : DesktopUserAgent
    {
        return $this->userAgent;
    }

    /**
     * @return UserAgent
     */
    public static function getInstance() : UserAgent
    {
        if (!static::$instance instanceof UserAgent) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (!static::$instance) {
            static::$instance = static::getInstance();
        }

        return call_user_func_array([static::$instance->getUserAgent(), $name], $arguments);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->getUserAgent(), $name], $arguments);
    }

    public static function chrome(...$args)
    {
        return static::getInstance()->getUserAgent()->chrome(...$args);
    }

    public static function firefox(...$args)
    {
        return static::getInstance()->getUserAgent()->firefox(...$args);
    }

    public static function safari(...$args)
    {
        return static::getInstance()->getUserAgent()->safari(...$args);
    }

    public static function edge(...$args)
    {
        return static::getInstance()->getUserAgent()->edge(...$args);
    }
}

<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class BaseRoute
 * @package ArrayIterator\Api\Crypt\Source
 */
abstract class BaseRoute
{
    const PATTERN = '';
    const METHODS = ['GET'];

    /**
     * @var ServerRequestInterface $request
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var ExtensionLoader
     */
    protected $extensionLoader;
    protected $app;

    /**
     * BaseRoute constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @return ExtensionLoader
     */
    public function getExtensionLoader(): ExtensionLoader
    {
        return $this->extensionLoader;
    }

    /**
     * @param array $params
     *
     * @return ResponseInterface
     */
    abstract public function handle(array $params) : ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $params
     * @final
     *
     * @return ResponseInterface
     */
    final public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params
    ) : ResponseInterface {
        $this->request = $request;
        $this->response = $response;
        return $this->handle($params);
    }
}

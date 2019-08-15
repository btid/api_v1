<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Handler;

use ArrayIterator\Api\Crypt\Source\Generator\Json;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\NotFound as SlimNotFound;

/**
 * Class NotFound
 * @package ArrayIterator\Api\Crypt\Source\Handler
 */
class NotFound extends SlimNotFound
{
    protected $option = JSON_PRETTY_PRINT;

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function determineContentType(ServerRequestInterface $request)
    {
        return 'application/json';
    }

    /**
     * Return a response for application/json content not found
     *
     * @return string
     */
    protected function renderJsonNotFoundOutput()
    {
        return Json::getJsonPatent()->encodeJson(
            Json::getJsonPatent()->generateErrorCode(404)
        );
    }
}

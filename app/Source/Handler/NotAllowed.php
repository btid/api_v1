<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Handler;

use ArrayIterator\Api\Crypt\Source\Generator\Json;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\NotAllowed as SlimNotAllowed;

/**
 * Class NotAllowed
 * @package ArrayIterator\Api\Crypt\Source\Handler
 */
class NotAllowed extends SlimNotAllowed
{
    /**
     * @var int
     */
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
     * Render JSON not allowed message
     *
     * @param  array                  $methods
     * @return string
     */
    protected function renderJsonNotAllowedMessage($methods)
    {
        return Json::getJsonPatent()->encodeJson(
            Json::getJsonPatent()->generateErrorCode(
                sprintf(
                    "Method not allowed. Must be one of: %s",
                    implode(', ', $methods)
                )
            )
        );
    }
}

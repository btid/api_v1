<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Handler;

use Exception;
use ArrayIterator\Api\Crypt\Source\Generator\Json;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\Error as SlimError;

/**
 * Class Error
 * @package ArrayIterator\Api\Crypt\Source\Handler
 */
class Error extends SlimError
{
    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function determineContentType(ServerRequestInterface $request)
    {
        return 'application/json';
    }

    /**
     * Render JSON error
     *
     * @param \Exception $exception
     *
     * @return string
     */
    protected function renderJsonErrorMessage(Exception $exception)
    {
        $messageKey = Json::getJsonPatent()->getErrorMessageKey();
        $error = [
            $messageKey => 'Application Error'
        ];

        if ($this->displayErrorDetails) {
            $error = Json::getJsonPatent()->generateExceptionCode($exception, 'Application Error');
        }

        return Json::getJsonPatent()->encodeJson($error);
    }
}

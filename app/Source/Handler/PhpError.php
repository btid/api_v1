<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Handler;

use ArrayIterator\Api\Crypt\Source\Generator\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\PhpError as SlimPhpError;
use Sentry;
use Throwable;

/**
 * Class PhpError
 * @package ArrayIterator\Api\Crypt\Source\Handler
 */
class PhpError extends SlimPhpError
{
    protected $captureId;

    /**
     * {@inheritDoc}
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Throwable $error)
    {
        // capture exception
        $this->captureId = Sentry\captureException($error)?:null;
        return parent::__invoke($request, $response, $error);
    }

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
     * @param Throwable $exception
     *
     * @return string
     */
    protected function renderJsonErrorMessage(Throwable $exception)
    {
        $messageKey = Json::getJsonPatent()->getErrorMessageKey();
        $error = [
            $messageKey => 'Application Error'
        ];

        if ($this->displayErrorDetails) {
            $error = Json::getJsonPatent()->generateExceptionCode($exception, 'Application Error');
        }
        if ($this->captureId) {
            $error['captureId'] = $this->captureId;
        }
        return Json::getJsonPatent()->encodeJson($error);
    }
}

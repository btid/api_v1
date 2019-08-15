<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Handler;

use ArrayIterator\Api\Crypt\Source\Generator\Json;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\AbstractHandler;
use Slim\Http\RequestBody;
use UnexpectedValueException;

/**
 * Class Forbidden
 * @package ArrayIterator\Api\Crypt\Source\Handler
 */
class Forbidden extends AbstractHandler
{
    /**
     * Invoke Forbidden handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() === 'OPTIONS') {
            $contentType = 'text/plain';
            $output = $this->renderPlainForbiddenOutput();
        } else {
            $contentType = $this->determineContentType($request);
            switch ($contentType) {
                case 'application/json':
                    $output = $this->renderJsonForbiddenOutput();
                    break;

                case 'text/xml':
                case 'application/xml':
                    $output = $this->renderXmlForbiddenOutput();
                    break;

                case 'text/html':
                    $output = $this->renderHtmlForbiddenOutput($request);
                    break;

                default:
                    throw new UnexpectedValueException('Cannot render unknown content type ' . $contentType);
            }
        }

        $body = new RequestBody();
        $body->write($output);

        return $response->withStatus(403)
                        ->withHeader('Content-Type', $contentType)
                        ->withBody($body);
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
     * @return string
     */
    protected function renderPlainForbiddenOutput()
    {
        return 'Forbidden';
    }

    /**
     * @return string
     */
    protected function renderJsonForbiddenOutput()
    {
        return Json::getJsonPatent()->encodeJson(Json::getJsonPatent()->generateErrorCode(403));
    }

    /**
     * @return string
     */
    protected function renderXmlForbiddenOutput()
    {
        return '<root><message>Forbidden</message></root>';
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    protected function renderHtmlForbiddenOutput(ServerRequestInterface $request)
    {
        return <<<END
<html>
    <head>
        <title>Forbidden</title>
        <style>
            body{
                margin:0;
                padding:30px;
                font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
            }
            h1{
                margin:0;
                font-size:48px;
                font-weight:normal;
                line-height:48px;
            }
            strong{
                display:inline-block;
                width:65px;
            }
        </style>
    </head>
    <body>
        <h1>Forbidden</h1>
        <p>
            You have not enough permission to access this page.
        </p>
    </body>
</html>
END;
    }
}

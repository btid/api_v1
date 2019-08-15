<?php
namespace ArrayIterator\Api\Crypt\Source\Handler;

use ArrayIterator\Api\Crypt\Source\Generator\Json;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\AbstractHandler;
use Slim\Http\Body;
use UnexpectedValueException;

/**
 * Class Expired
 * @package ArrayIterator\Api\Crypt\Source\Handler
 */
class Expired extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() === 'OPTIONS') {
            $contentType = 'text/plain';
            $output = $this->renderPlainNotFoundOutput();
        } else {
            $contentType = $this->determineContentType($request);
            switch ($contentType) {
                case 'application/json':
                    $output = $this->renderJsonExpiredOutput();
                    break;

                case 'text/xml':
                case 'application/xml':
                    $output = $this->renderXmlExpiredOutput();
                    break;

                case 'text/html':
                    $output = $this->renderHtmlExpiredOutput($request);
                    break;

                default:
                    throw new UnexpectedValueException('Cannot render unknown content type ' . $contentType);
            }
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response->withStatus(410)
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
    protected function renderPlainNotFoundOutput()
    {
        return 'Expired';
    }

    /**
     * @return string
     */
    protected function renderJsonExpiredOutput()
    {
        return Json::getJsonPatent()->encodeJson(
            Json::getJsonPatent()->generateErrorCode(
                'Expired access',
                410
            )
        );
    }

    /**
     * @return string
     */
    protected function renderXmlExpiredOutput()
    {
        return '<root><message>Expired</message></root>';
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    protected function renderHtmlExpiredOutput(ServerRequestInterface $request)
    {
        return <<<END
<html>
    <head>
        <title>Expired</title>
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
        <h1>Expired</h1>
        <p>
            You have requested expired page.
        </p>
    </body>
</html>
END;
    }
}

<?php
declare(strict_types=1);

namespace ArrayIterator\Api\Crypt\Source\Generator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Class JsonPatent
 * @package ArrayIterator\Api\Crypt\Source\Generator
 */
class JsonPatent
{
    /**
     * @var string for debug mode (this usually for exception)
     */
    protected $debugKey = 'debug';

    /**
     * @var string in compression or not
     */
    protected $compressKey = 'compress';

    /**
     * @var string for root key
     */
    protected $rootKey = 'data';

    /**
     * @var string for error default key
     */
    protected $errorMessageKey = 'message';

    /**
     * @var string for error exception default key
     */
    protected $exceptionKey = 'exception';

    /**
     * @var int default error code
     */
    protected $defaultErrorCode = 500;

    /**
     * @var string default json header value field
     */
    protected $defaultJsonHeaderValue = 'application/json; charset=utf-8';

    /**
     * @var string default message for exception error
     */
    protected $defaultExceptionErrorMessage = 'Exception Error';

    /**
     * @var string default message for error message
     */
    protected $defaultErrorMessage   = 'Internal Server Error';

    /**
     * @var string
     */
    protected $rootDirectoryPlaceholder = '{ROOT}';

    /**
     * @var string[]
     */
    protected $defaultActiveTrueArray = ['true', '1', 'on', 'yes'];

    /**
     * @var string[]
     */
    protected $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var string
     */
    protected $rootDirectory = null;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * JsonPatent constructor.
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request = null)
    {
        $this->request = $request;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getRootKey(): string
    {
        return $this->rootKey;
    }

    /**
     * @param string $rootKey
     */
    public function setRootKey(string $rootKey)
    {
        $this->rootKey = $rootKey;
    }

    /**
     * @return string
     */
    public function getDefaultJsonHeaderValue(): string
    {
        return $this->defaultJsonHeaderValue;
    }

    /**
     * @param string $defaultJsonHeaderValue
     */
    public function setDefaultJsonHeaderValue(string $defaultJsonHeaderValue)
    {
        $this->defaultJsonHeaderValue = $defaultJsonHeaderValue;
    }

    /**
     * @return string
     */
    public function getRootDirectoryPlaceholder() : string
    {
        return $this->rootDirectoryPlaceholder;
    }

    /**
     * @param string $rootDirectoryPlaceholder
     */
    public function setRootDirectoryPlaceholder(string $rootDirectoryPlaceholder)
    {
        $this->rootDirectoryPlaceholder = $rootDirectoryPlaceholder;
    }

    /**
     * @param string $string
     * @return string|null
     */
    public function setRootDirectory(string $string = null)
    {
        if ($string === null) {
            return $this->rootDirectory = null;
        }

        $string = realpath($string)?: $string;
        return $this->rootDirectory = $string;
    }

    /**
     * @return string|null
     */
    public function getRootDirectory()
    {
        return $this->rootDirectory;
    }

    /**
     * @return string[]
     */
    public function getDefaultActiveTrueArray() : array
    {
        return $this->defaultActiveTrueArray;
    }

    /**
     * @param string[] $defaultActiveTrueArray
     */
    public function setDefaultActiveTrueArray(array $defaultActiveTrueArray)
    {
        $this->defaultActiveTrueArray = $defaultActiveTrueArray;
    }

    /**
     * @return string[]
     */
    public function getPhrases(): array
    {
        return $this->phrases;
    }

    /**
     * @param int $code
     * @param string|null $default
     * @return string|null
     */
    public function getPhrase(int $code, string $default = null)
    {
        $phrase = $this->getPhrases();
        return $phrase[$code]??$default;
    }

    /**
     * @return string
     */
    public function getRootDirPlaceholder() : string
    {
        return $this->rootDirectoryPlaceholder;
    }

    /**
     * @return int
     */
    public function getDefaultErrorCode() : int
    {
        return $this->defaultErrorCode;
    }

    /**
     * @return string
     */
    public function getSuccessRootKey() : string
    {
        return $this->rootKey;
    }

    /**
     * @return string
     */
    public function getErrorMessageKey() : string
    {
        return $this->errorMessageKey;
    }

    /**
     * @return string
     */
    public function getExceptionKey() : string
    {
        return $this->exceptionKey;
    }

    /**
     * @return string
     */
    public function getCompressKey() : string
    {
        return $this->compressKey;
    }

    /**
     * @return string
     */
    public function getDebugKey() : string
    {
        return $this->debugKey;
    }

    /**
     * @return string
     */
    public function getDefaultExceptionErrorMessage() : string
    {
        return $this->defaultExceptionErrorMessage;
    }

    /**
     * @return string
     */
    public function getDefaultErrorMessage() : string
    {
        return $this->defaultErrorMessage;
    }

    /**
     * @return string
     */
    public function getJsonHeaderValue() : string
    {
        return $this->defaultJsonHeaderValue;
    }

    /**
     * @return array
     */
    public function getActiveTrueArray() : array
    {
        return ['true', '1', 'on', 'yes'];
    }

    /**
     * @return array
     */
    public function getQueryParams() : array
    {
        return $this->getRequest()->getQueryParams();
    }

    /**
     * @return bool
     */
    public function isCompressed() : bool
    {
        $params = $this->getQueryParams();
        $compressKey = $this->getCompressKey();
        return isset($params[$compressKey])
            && is_string($params[$compressKey])
            && in_array($params[$compressKey], $this->getActiveTrueArray());
    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        $params = $this->getQueryParams();
        $debugKey = $this->getDebugKey();
        return isset($params[$debugKey])
            && is_string($params[$debugKey])
            && in_array($params[$debugKey], $this->getActiveTrueArray());
    }

    /**
     * @param int $code
     * @return string
     */
    public function getErrorInternal(int $code = null) : string
    {
        $code = $code ?: $this->getDefaultErrorCode();
        return $this->getPhrase($code, $this->getDefaultErrorMessage());
    }

    /**
     * @param ResponseInterface $response
     * @param array $data
     * @return ResponseInterface
     */
    protected function serveResponse(
        ResponseInterface $response,
        array $data
    ) : ResponseInterface {
        $body = $response->getBody();
        // $body = ! $body->getSize() ? $response->getBody() : new RequestBody();
        $body->rewind(); // rewind
        $body->write($this->encodeJson($data, $this->isCompressed()));
        return $response
            ->withBody($body)
            ->withHeader(
                'Content-Type',
                $this->getJsonHeaderValue()
            );
    }

    /**
     * @param $data
     * @param bool $compress
     * @return string
     */
    public function encodeJson($data, bool $compress = null) : string
    {
        $compress = $compress === null ? $this->isCompressed() : $compress;
        $options = JSON_UNESCAPED_SLASHES;
        $options |= $compress ? ~JSON_PRETTY_PRINT : JSON_PRETTY_PRINT;
        return json_encode($data, $options);
    }

    /**
     * @param ResponseInterface $response
     * @param mixed $data
     * @param int|null $statusCode
     * @return ResponseInterface
     */
    public function successCode(
        ResponseInterface $response,
        $data,
        int $statusCode = 200
    ) : ResponseInterface {
        if (is_int($data)
            && ($phrase = $this->getPhrase($data))
            && func_num_args() < 3
        ) {
            $statusCode = $data;
        }

        if ($statusCode) {
            $response = $response->withStatus($statusCode);
        }

        return $this->serveResponse(
            $response,
            $this->generateSuccessCode($data, $statusCode)
        );
    }

    /**
     * @param $data
     * @param int $statusCode
     * @return array
     */
    public function generateSuccessCode(
        $data,
        int $statusCode = null
    ) {
        if ($statusCode === null
            && func_num_args() < 2
            && is_int($data)
            && ($phrase = $this->getPhrase($data))
        ) {
            $data       = $phrase;
        }

        return [
            $this->getSuccessRootKey() => $data
        ];
    }

    /**
     * @param null $message
     * @param int|null $statusCode
     * @return array
     */
    public function generateErrorCode(
        $message = null,
        int $statusCode = null
    ) : array {
        $phrase = $this->getPhrases();
        if (is_int($message) && isset($phrase[$message]) && func_num_args() < 2) {
            $statusCode = $message;
            $message = null;
        }

        $statusCode = $statusCode ?: $this->getDefaultErrorCode();
        $message = $message?:$this->getErrorInternal($statusCode);
        $messageKey = $this->getErrorMessageKey();
        $data = [$messageKey => $message];
        if (is_array($message) && array_key_exists($messageKey, $message)) {
            $data = $message;
        }

        return $data;
    }

    /**
     * @param ResponseInterface $response
     * @param null $message
     * @param int $statusCode
     * @return ResponseInterface
     */
    public function errorCode(
        ResponseInterface $response,
        $message = null,
        int $statusCode = null
    ) : ResponseInterface {
        $args = func_get_args();
        array_shift($args);
        if ($statusCode) {
            $response = $response->withStatus($statusCode);
        }
        return $this->serveResponse(
            $response,
            $this->generateErrorCode(...$args)
        );
    }

    /**
     * @param ResponseInterface $response
     * @param mixed $data
     * @return ResponseInterface
     */
    public function success(
        ResponseInterface $response,
        $data
    ) : ResponseInterface {
        return $this->successCode($response, $data, 200);
    }

    /**
     * @param ResponseInterface $response
     * @param mixed $message
     * @return ResponseInterface
     */
    public function error(
        ResponseInterface $response,
        $message = null
    ) : ResponseInterface {
        return $this->errorCode($response, $message, 500);
    }

    /**
     * @param string $message
     * @param int $code
     * @param int $line
     * @param string $file
     * @param array $trace
     * @return array
     */
    public function generateResultExceptionCode(
        string $message = '',
        int $code = 0,
        int $line = 0,
        string $file = '',
        array $trace = []
    ) : array {
        return [
            $this->getErrorMessageKey() => $this->getDefaultExceptionErrorMessage(),
            $this->getExceptionKey() => [
                [
                    'type'    => 'Exception',
                    'message' => $message,
                    'code'    => $code,
                    'line'    => $line,
                    'file'    => $this->hidePathException($file),
                    'trace' => $trace
                ]
            ]
        ];
    }

    /**
     * @param ResponseInterface $response
     * @param string $message
     * @param int $code
     * @param int $line
     * @param string $file
     * @param array $trace
     * @return ResponseInterface
     */
    public function withManualResultException(
        ResponseInterface $response,
        string $message = '',
        int $code = 0,
        int $line = 0,
        string $file = '',
        array $trace = []
    ) : ResponseInterface {
        return $this->errorCode(
            $response,
            $this->generateResultExceptionCode(
                $message,
                $code,
                $line,
                $file,
                $trace
            ),
            500
        );
    }

    /**
     * @param Throwable $e
     * @param string|null $message
     * @return array
     */
    public function generateExceptionCode(Throwable $e, string $message = null) : array
    {
        $message      = $message ?: $this->getDefaultExceptionErrorMessage();
        $messageKey   = $this->getErrorMessageKey();
        $exceptionKey = $this->getExceptionKey();
        $message = [
            $messageKey => $message,
            $exceptionKey => []
        ];
        $isDebug = $this->isDebugMode();
        do {
            $m = [
                'type' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
            if ($isDebug) {
                $m['file']  = $this->hidePathException($e->getFile());
                $m['line']  = $e->getLine();
                $m['trace'] = explode("\n", $e->getTraceAsString());
            }
            $message[$exceptionKey][] = $m;
        } while ($e = $e->getPrevious());
        unset($m);

        return $message;
    }

    /**
     * @param string $file
     * @return string
     */
    public function hidePathException(string $file) : string
    {
        // define root dir
        $rootDir = $this->getRootDirectory();
        if (!$rootDir || !is_string($rootDir)) {
            return $file;
        }

        $sub     = preg_quote($rootDir, '/');
        $rootDirPlaceHolder = $this->getRootDirPlaceholder();
        return (string) preg_replace_callback(
            '/(.+)([^\/]+)$/',
            function ($m) use ($sub, $rootDirPlaceHolder) {
                return preg_replace("/^{$sub}/", $rootDirPlaceHolder, $m[1]).$m[2];
            },
            $file
        );
    }

    /**
     * @param ResponseInterface $response
     * @param Throwable $e
     * @return ResponseInterface
     */
    public function exception(
        ResponseInterface $response,
        Throwable $e
    ) : ResponseInterface {
        return $this->exceptionCode($response, $e, 500);
    }

    /**
     * @param ResponseInterface $response
     * @param Throwable $e
     * @param int $code
     * @return ResponseInterface
     */
    public function exceptionCode(
        ResponseInterface $response,
        Throwable $e,
        int $code = 500
    ) : ResponseInterface {
        return $this->errorCode(
            $response,
            $this->generateExceptionCode($e),
            $code
        );
    }

    /**
     * @param ResponseInterface $response
     * @param mixed $message
     * @return ResponseInterface
     */
    public function notFound(
        ResponseInterface $response,
        $message = null
    ) : ResponseInterface {
        return $this->errorCode($response, $message, 404);
    }

    /**
     * @param ResponseInterface $response
     * @param mixed $message
     * @return ResponseInterface
     */
    public function preconditionFailed(
        ResponseInterface $response,
        $message = null
    ) : ResponseInterface {
        return $this->errorCode($response, $message, 412);
    }
}

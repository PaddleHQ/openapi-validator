<?php

namespace PaddleHq\OpenApiValidator\Exception;

use Throwable;

class ContentTypeNotFoundException extends Exception
{
    /**
     *
     * @param string         $contentType  Request Content Type
     * @param int            $responseCode Request Response Code
     * @param string         $method       Request Method
     * @param string         $path         Request OpenApi Path
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $contentType,
        int $responseCode,
        string $method,
        string $path,
        int $code = 0,
        Throwable $previous = null
    ) {
        $message = sprintf(
            'Content type "%s" not found for \"%s %s - %s\"',
            $contentType,
            $responseCode,
            $method,
            $path
        );
        parent::__construct($message, $code, $previous);
    }
}
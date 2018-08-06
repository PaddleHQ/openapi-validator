<?php

namespace PaddleHq\OpenApiValidator\Exception;

use Throwable;

class ResponseNotFoundException extends Exception
{
    /**
     *
     * @param int            $responseCode Request Response Code
     * @param string         $method       Request Method
     * @param string         $path         Request OpenApi Path
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(int $responseCode, string $method, string $path, int $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Response "%s" not defined for "%s %s"', $responseCode, $method, $path);
        parent::__construct($message, $code, $previous);
    }
}
<?php

namespace PaddleHq\OpenApiValidator\Exception;

use Throwable;

class MethodNotFoundException extends Exception
{
    /**
     *
     * @param string         $method Request Method
     * @param string         $path   Request OpenApi Path
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $method, string $path, int $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Method "%s" does not exist in path "%s"', $method, $path);
        parent::__construct($message, $code, $previous);
    }
}
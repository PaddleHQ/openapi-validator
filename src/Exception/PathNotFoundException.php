<?php

namespace PaddleHq\OpenApiValidator\Exception;

use Throwable;

class PathNotFoundException extends Exception
{
    /**
     *
     * @param string         $path   Request OpenApi Path
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $path, int $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Path "%s"  does not exist in schema', $path);
        parent::__construct($message, $code, $previous);
    }
}
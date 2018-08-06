<?php

namespace PaddleHq\OpenApiValidator;

use Psr\Http\Message\ResponseInterface;

interface OpenApiValidatorInterface
{
    /**
     * Validate a response against the OpenApi schema
     *
     * Needed to add some error handling to make common errors more clear
     *
     * @param ResponseInterface $response,
     * @param string $pathName,
     * @param string $method,
     * @param int $responseCode,
     * @param string $contentType
     */
    public function validate(
        ResponseInterface $response,
        string $pathName,
        string $method,
        int $responseCode,
        string $contentType
    );
}
<?php

namespace PaddleHq\OpenApiValidator;

use PaddleHq\OpenApiValidator\Exception\InvalidRequestException;
use PaddleHq\OpenApiValidator\Exception\InvalidResponseException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface OpenApiValidatorInterface
{
    /**
     * Validate a response against the OpenApi schema.
     *
     * Needed to add some error handling to make common errors more clear
     *
     * @param ResponseInterface $response,
     * @param string            $pathName,
     * @param string            $method,
     * @param int               $responseCode,
     * @param string            $contentType
     *
     * @return bool - true if valid
     *
     * @throws InvalidResponseException
     */
    public function validateResponse(
        ResponseInterface $response,
        string $pathName,
        string $method,
        int $responseCode,
        string $contentType = 'application/json'
    ): bool;

    /**
     * Validate a response against the OpenApi schema.
     *
     * @param RequestInterface $request
     * @param string $pathName
     * @param string $method
     * @param string $contentType
     * @return bool - true if valid
     *
     * @throws InvalidRequestException
     */
    public function validateRequest(
        RequestInterface $request,
        string $pathName,
        string $method,
        string $contentType = 'application/json'
    ): bool;
}

<?php

namespace PaddleHq\OpenApiValidator;

use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use PaddleHq\OpenApiValidator\Exception\ContentTypeNotFoundException;
use PaddleHq\OpenApiValidator\Exception\MethodNotFoundException;
use PaddleHq\OpenApiValidator\Exception\PathNotFoundException;
use PaddleHq\OpenApiValidator\Exception\InvalidResponseException;
use PaddleHq\OpenApiValidator\Exception\ResponseNotFoundException;
use JsonSchema\Validator as JsonSchemaValidator;
use Psr\Http\Message\ResponseInterface;

class OpenApiV3Validator implements OpenApiValidatorInterface
{

    /**
     * @var JsonSchemaValidator
     */
    private $jsonSchemaValidator;

    /**
     * @var string
     */
    private $currentPath;

    /**
     * @var string
     */
    private $currentMethod;

    /**
     * @var int
     */
    private $currentResponseStatusCode;

    /**
     * @var string
     */
    private $currentContentType;

    /**
     * @var SchemaStorage
     */
    private $schemaStorage;

    /**
     * @var string
     */
    private $openApiSchemaFileName;

    /**
     * @param string $openApiSchemaFileName
     * @param OpenApiV3ToJsonSchemaConverter $converter
     * @param SchemaStorage $schemaStorage
     */
    public function __construct(
        string $openApiSchemaFileName,
        SchemaStorage $schemaStorage
    )
    {
        $this->openApiSchemaFileName = $openApiSchemaFileName;
        $this->schemaStorage = $schemaStorage;
        $this->jsonSchemaValidator = new JsonSchemaValidator(new Factory($this->schemaStorage));
    }

    /**
     * Validate a response against the OpenApi schema.
     *
     * {@inheritdoc}
     */
    public function validateResponse(
        ResponseInterface $response,
        string $pathName,
        string $method,
        int $responseCode,
        string $contentType = 'application/json'
    ): bool
    {
        if (!$this->emptyResponseExpected($responseCode)) {
            $responseSchemaPath = $this->getResponseSchemaPath(preg_replace('/\?.*/', '', $pathName), $method, $responseCode, $contentType);
            $responseJson = json_decode($response->getBody());
            $this->jsonSchemaValidator->validate($responseJson, (object)['$ref' => $responseSchemaPath]);
        }

        if (!$this->jsonSchemaValidator->isValid()) {
            throw new InvalidResponseException($response, $this->schemaStorage->resolveRef($responseSchemaPath), $this->jsonSchemaValidator->getErrors());
        }

        return true;
    }

    /**
     * @param string $pathName
     * @param string $method
     * @param int $responseCode
     * @param string $contentType
     *
     * @return string
     *
     * @throws ContentTypeNotFoundException
     * @throws MethodNotFoundException
     * @throws PathNotFoundException
     * @throws ResponseNotFoundException
     */
    private function getResponseSchemaPath(string $pathName, string $method, int $responseCode, string $contentType): string
    {
        $this->setSchemaPath($pathName)
            ->setPathMethod($method)
            ->setResponseStatusCode($responseCode)
            ->setContentType($contentType);

        return sprintf(
            '%s#paths/%s/%s/responses/%d/content/%s/schema',
            $this->openApiSchemaFileName,
            str_replace('/', '~1', $this->currentPath),
            $this->currentMethod,
            $this->currentResponseStatusCode,
            str_replace('/', '~1', $this->currentContentType)
        );
    }

    /**
     * @param string $pathName
     *
     * @return OpenApiV3Validator
     *
     * @throws PathNotFoundException
     */
    private function setSchemaPath(string $pathName): self
    {
        if (!property_exists(
            $this->schemaStorage
                ->getSchema($this->openApiSchemaFileName)
                ->paths,
            $pathName
        )) {
            throw new PathNotFoundException($pathName);
        }

        $this->currentPath = $pathName;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return OpenApiV3Validator
     *
     * @throws MethodNotFoundException
     */
    private function setPathMethod(string $method): self
    {
        $method = strtolower($method);

        if (!property_exists(
            $this->schemaStorage
                ->getSchema($this->openApiSchemaFileName)
                ->paths
                ->{$this->currentPath},
            $method
        )) {
            throw new MethodNotFoundException($method, $this->currentPath);
        }

        $this->currentMethod = $method;

        return $this;
    }

    /**
     * @param int $responseCode
     *
     * @return OpenApiV3Validator
     *
     * @throws ResponseNotFoundException
     */
    private function setResponseStatusCode(int $responseCode): self
    {
        if (!property_exists(
            $this->schemaStorage
                ->getSchema($this->openApiSchemaFileName)
                ->paths
                ->{$this->currentPath}
                ->{$this->currentMethod}
                ->responses,
            $responseCode
        )) {
            throw new ResponseNotFoundException($responseCode, $this->currentMethod, $this->currentPath);
        }

        $this->currentResponseStatusCode = $responseCode;

        return $this;
    }

    /**
     * @param string $contentType
     *
     * @return OpenApiV3Validator
     *
     * @throws ContentTypeNotFoundException
     */
    private function setContentType(string $contentType): self
    {
        if (!property_exists(
            $this->schemaStorage
                ->getSchema($this->openApiSchemaFileName)
                ->paths
                ->{$this->currentPath}
                ->{$this->currentMethod}
                ->responses
                ->{$this->currentResponseStatusCode}
                ->content,
            $contentType
        )) {
            throw new ContentTypeNotFoundException(
                $contentType,
                $this->currentResponseStatusCode,
                $this->currentMethod,
                $this->currentPath
            );
        }

        $this->currentContentType = $contentType;

        return $this;
    }

    /**
     * @param string $responseCode
     *
     * @return bool
     */
    private function emptyResponseExpected($responseCode): bool
    {
        return 204 === $responseCode;
    }

}

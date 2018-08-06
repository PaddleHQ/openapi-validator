<?php

namespace PaddleHq\OpenApiValidator;

use PaddleHq\OpenApiValidator\Exception\ContentTypeNotFoundException;
use PaddleHq\OpenApiValidator\Exception\MethodNotFoundException;
use PaddleHq\OpenApiValidator\Exception\PathNotFoundException;
use PaddleHq\OpenApiValidator\Exception\ResponseInvalidException;
use PaddleHq\OpenApiValidator\Exception\ResponseNotFoundException;
use League\JsonReference\Dereferencer;
use HSkrasek\OpenAPI\Converter;
use JsonSchema\Validator as JsonSchemaValidator;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class OpenApiV3Validator implements OpenApiValidatorInterface
{
    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var Dereferencer
     */
    private $dereferencer;

    /**
     * @var JsonSchemaValidator
     */
    private $jsonSchemaValidator;

    /**
     * @var stdClass
     */
    private $openApiV3Schema;

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

    public function __construct(
        string $openApiSchema,
        Converter $converter,
        Dereferencer $dereferencer,
        JsonSchemaValidator $jsonSchemaValidator
    ) {
        $this->converter = $converter;
        $this->dereferencer = $dereferencer;
        $this->jsonSchemaValidator = $jsonSchemaValidator;
        $this->setupSchema($openApiSchema);
    }

    /**
     * Expands all `$ref` and builds object that can be used by validator
     *
     * NOTE - the json encode/decode is required as the json schema
     * validator does not like the proxy objects the dereferencer uses.
     *
     * @param string $openApiSchema
     */
    private function setupSchema(string $openApiSchema)
    {
        $schema = $this->dereferencer->dereference($openApiSchema);
        $this->openApiV3Schema = json_decode(json_encode($schema));
    }

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
     *
     * @throws \Exception
     */
    public function validate(
        ResponseInterface $response,
        string $pathName,
        string $method,
        int $responseCode,
        string $contentType = 'application/json'
    ) {
        $openApiV3ResponseSchema = $this->getResponseSchema($pathName, $method, $responseCode, $contentType);
        $jsonSchemaResponseSchema = $this->convertOpenApiV3ToJsonSchema($openApiV3ResponseSchema);
        $responseJson = json_decode($response->getBody());
        $this->jsonSchemaValidator->validate($responseJson, $jsonSchemaResponseSchema);

        if (!$this->jsonSchemaValidator->isValid()) {
            throw new ResponseInvalidException($response, $openApiV3ResponseSchema, $this->jsonSchemaValidator->getErrors());
        }
    }

    /**
     * @param string $pathName
     * @param string $method
     * @param int    $responseCode
     * @param string $contentType
     *
     * @return mixed
     * @throws ContentTypeNotFoundException
     * @throws MethodNotFoundException
     * @throws PathNotFoundException
     * @throws ResponseNotFoundException
     */
    private function getResponseSchema(string $pathName, string $method, int $responseCode, string $contentType)
    {
        $this->setSchemaPath($pathName)
            ->setPathMethod($method)
            ->setResponseStatusCode($responseCode)
            ->setContentType($contentType);

        return $this->openApiV3Schema
            ->paths
            ->{$this->currentPath}
            ->{$this->currentMethod}
            ->responses
            ->{$this->currentResponseStatusCode}
            ->content
            ->{$this->currentContentType}
            ->schema;
    }

    /**
     * Converts a schema from OpenApi v3 to JsonSchema draft 4 for validation
     *
     * @param stdClass $openApiV3Schema
     *
     * @return stdClass
     */
    private function convertOpenApiV3ToJsonSchema(stdClass $openApiV3Schema): stdClass
    {
        return $this->converter->convert($openApiV3Schema);
    }

    /**
     * @param string $pathName
     *
     * @return OpenApiV3Validator
     * @throws PathNotFoundException
     */
    private function setSchemaPath(string $pathName): self
    {
        if (! property_exists($this->openApiV3Schema->paths, $pathName)) {
            throw new PathNotFoundException($pathName);
        }

        $this->currentPath = $pathName;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return OpenApiV3Validator
     * @throws MethodNotFoundException
     */
    private function setPathMethod(string $method): self
    {
        $method = strtolower($method);

        if (! property_exists($this->openApiV3Schema->paths->{$this->currentPath}, $method)) {
            throw new MethodNotFoundException($method, $this->currentPath);
        }

        $this->currentMethod = $method;

        return $this;
    }

    /**
     * @param int $responseCode
     *
     * @return OpenApiV3Validator
     * @throws ResponseNotFoundException
     */
    private function setResponseStatusCode(int $responseCode): self
    {
        if (! property_exists(
            $this->openApiV3Schema
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
     * @throws ContentTypeNotFoundException
     */
    private function setContentType(string $contentType): self
    {
        if (! property_exists(
            $this->openApiV3Schema
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
}
<?php

namespace PaddleHq\OpenApiValidator;

use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use PaddleHq\OpenApiValidator\Exception\ContentTypeNotFoundException;
use PaddleHq\OpenApiValidator\Exception\MethodNotFoundException;
use PaddleHq\OpenApiValidator\Exception\PathNotFoundException;
use PaddleHq\OpenApiValidator\Exception\ResponseInvalidException;
use PaddleHq\OpenApiValidator\Exception\ResponseNotFoundException;
use HSkrasek\OpenAPI\Converter;
use JsonSchema\Validator as JsonSchemaValidator;
use Psr\Http\Message\ResponseInterface;

class OpenApiV3Validator implements OpenApiValidatorInterface
{
    /**
     * @var Converter
     */
    private $converter;

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

    public function __construct(
        string $openApiSchemaFileName,
        Converter $converter,
        SchemaStorage $schemaStorage
    ) {
        $this->converter = $converter;
        $this->openApiSchemaFileName = $openApiSchemaFileName;
        $this->schemaStorage = $schemaStorage;
        $this->setupValidator();
        $this->jsonSchemaValidator = new JsonSchemaValidator(new Factory($this->schemaStorage));
    }

    /**
     * Converts OpenApi v3 schema to json schema draft 4, adds it to storage.
     */
    private function setupValidator()
    {
        $openApiSchema = json_decode(file_get_contents($this->openApiSchemaFileName));
        $this->schemaStorage->addSchema($this->openApiSchemaFileName, $this->converter->convert($openApiSchema));
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
    ): bool {
        $responseSchemaPath = $this->getResponseSchemaPath($pathName, $method, $responseCode, $contentType);
        $responseJson = json_decode($response->getBody());
        $this->jsonSchemaValidator->validate($responseJson, (object) ['$ref' => $responseSchemaPath]);

        if (!$this->jsonSchemaValidator->isValid()) {
            throw new ResponseInvalidException($response, $this->schemaStorage->resolveRef($responseSchemaPath), $this->jsonSchemaValidator->getErrors());
        }

        return true;
    }

    /**
     * @param string $pathName
     * @param string $method
     * @param int    $responseCode
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
}

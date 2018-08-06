<?php

namespace PaddleHq\OpenApiValidator\Tests\Unit;

use JsonSchema\SchemaStorage;
use PaddleHq\OpenApiValidator\OpenApiV3Validator;
use League\JsonReference\Dereferencer;
use League\JsonReference\ReferenceSerializer\InlineReferenceSerializer;
use HSkrasek\OpenAPI\Converter;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;

class OpenApiV3ValidatorTest extends TestCase
{
    /**
     * @var OpenApiV3Validator
     */
    private $validator;

    private function mockResponse(int $statusCode, array $responseBody = []): Response
    {
        return new Response($statusCode, [], json_encode($responseBody));
    }

    public function setUp()
    {
        parent::setUp();

        $this->validator = new OpenApiV3Validator(
            'file://'.dirname(__DIR__).'/fixtures/openapiv3-schema.json',
            new Converter(),
            new SchemaStorage()
        );
    }

    /**
     * @expectedException \PaddleHq\OpenApiValidator\Exception\PathNotFoundException
     */
    public function testPathDoesNotExist()
    {
        $this->validator->validateResponse(
            $this->mockResponse(404),
            '/path-does/not/exist',
            'GET',
            404
        );
    }

    /**
     * @expectedException \PaddleHq\OpenApiValidator\Exception\MethodNotFoundException
     */
    public function testMethodDoesNotExist()
    {
        $this->validator->validateResponse(
            $this->mockResponse(404),
            '/check/health',
            'PUT',
            404
        );
    }

    /**
     * @expectedException \PaddleHq\OpenApiValidator\Exception\ResponseNotFoundException
     */
    public function testResponseDoesNotExist()
    {
        $this->validator->validateResponse(
            $this->mockResponse(404),
            '/check/health',
            'GET',
            404
        );
    }

    /**
     * @expectedException \PaddleHq\OpenApiValidator\Exception\ContentTypeNotFoundException
     */
    public function testContentTypeDoesNotExist()
    {
        $this->validator->validateResponse(
            $this->mockResponse(200),
            '/check/health',
            'GET',
            200,
            'application/xml'
        );
    }

    /**
     * @expectedException \PaddleHq\OpenApiValidator\Exception\ResponseInvalidException
     */
    public function testResponseIsInvalidEmpty()
    {
        $this->validator->validateResponse(
            $this->mockResponse(200, []),
            '/check/health',
            'GET',
            200
        );
    }

    /**
     * @expectedException \PaddleHq\OpenApiValidator\Exception\ResponseInvalidException
     */
    public function testResponseIsInvalidWrongType()
    {
        $this->validator->validateResponse(
            $this->mockResponse(200, ['health' => 123]),
            '/check/health',
            'GET',
            200
        );
    }

    /**
     * @expectedException \PaddleHq\OpenApiValidator\Exception\ResponseInvalidException
     */
    public function testResponseIsInvalidMissingRequiredField()
    {
        $this->validator->validateResponse(
            $this->mockResponse(200, ['other-field' => 'ok']),
            '/check/health',
            'GET',
            200
        );
    }

    public function testResponseIsValid()
    {
        $this->assertTrue(
            $this->validator->validateResponse(
                $this->mockResponse(200, ['health' => 'ok']),
                '/check/health',
                'GET',
                200
            )
        );
    }
}

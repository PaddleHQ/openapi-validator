<?php

namespace PaddleHq\OpenApiValidator\Tests\Unit;

use GuzzleHttp\Psr7\Request;
use JsonSchema\SchemaStorage;
use PaddleHq\OpenApiValidator\OpenApiV3Validator;
use PaddleHq\OpenApiValidator\OpenApiV3ToJsonSchemaConverter;
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

    private function mockRequest(
        string $path,
        string $method,
        array $requestBody = [],
        array $requestHeaders = []
    ): Request
    {
        $requestHeaders['Content-Type'] = 'application/json';
        return new Request($method, $path, $requestHeaders, json_encode($requestBody));
    }

    public function setUp()
    {
        parent::setUp();

        $this->validator = new OpenApiV3Validator(
            'file://'.dirname(__DIR__).'/fixtures/openapiv3-schema.json',
            new OpenApiV3ToJsonSchemaConverter(),
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
     * @expectedException \PaddleHq\OpenApiValidator\Exception\InvalidResponseException
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
     * @expectedException \PaddleHq\OpenApiValidator\Exception\InvalidResponseException
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
     * @expectedException \PaddleHq\OpenApiValidator\Exception\InvalidResponseException
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

    public function testEmptyResponseIsValid()
    {
        $this->assertTrue(
            $this->validator->validateResponse(
                $this->mockResponse(204, []),
                '/check/health',
                'POST',
                204
            )
        );
    }

    public function testValidatorDropsQueryString()
    {
        $this->assertTrue(
            $this->validator->validateResponse(
                $this->mockResponse(200, ['health' => 'ok']),
                '/check/health?thisis=fine',
                'GET',
                200
            )
        );
    }

    public function testItValidatesRequests()
    {
        $path = '/check/health?thisis=fine';
        $method = 'POST';
        $requestBody = ['test' => 'perfect'];
        $request = $this->mockRequest($path, $method, $requestBody);
        $result = $this->validator->validateRequest($request, $path, $method);
        $this->assertTrue($result);
    }

    /**
     * @expectedException \PaddleHq\OpenApiValidator\Exception\InvalidRequestException
     */
    public function testRequestIsInvalidMissingRequiredField()
    {
        $path = '/check/health?thisis=fine';
        $method = 'POST';
        $requestBody = ['other-field' => 'ok'];
        $request = $this->mockRequest($path, $method, $requestBody);
        $result = $this->validator->validateRequest($request, $path, $method);
        $this->assertTrue($result);
    }
}

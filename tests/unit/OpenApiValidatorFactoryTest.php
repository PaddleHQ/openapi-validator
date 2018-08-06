<?php

namespace PaddleHq\OpenApiValidator\Tests\Unit;

use PaddleHq\OpenApiValidator\OpenApiV3Validator;
use PaddleHq\OpenApiValidator\OpenApiValidatorFactory;
use PHPUnit\Framework\TestCase;

class OpenApiValidatorFactoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->factory = new OpenApiValidatorFactory();
    }

    public function testV3ValidatorSuccess()
    {
        $this->assertInstanceOf(
            OpenApiV3Validator::class,
            $this->factory->v3Validator('file://'.dirname(__DIR__).'/fixtures/openapiv3-schema.json')
        );
    }

    /**
     * @expectedException \League\JsonReference\SchemaLoadingException
     */
    public function testV3ValidatorFileNotFound()
    {
        $this->assertInstanceOf(
            OpenApiV3Validator::class,
            $this->factory->v3Validator('file://'.dirname(__DIR__).'/fixtures/doesnt-exist.json')
        );
    }
}

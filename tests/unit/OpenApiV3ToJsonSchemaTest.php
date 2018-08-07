<?php

namespace PaddleHq\OpenApiValidator\Tests\Unit;

use PaddleHq\OpenApiValidator\OpenApiV3ToJsonSchemaConverter;
use PHPUnit\Framework\TestCase;

/**
 * This class is taken directly from "hskrasek/openapi-schema-to-jsonschema"
 * It was extracted only because that package has a dependency on the abandoned
 * "league/json-reference" package.
 */
class OpenApiV3ToJsonSchemaConverterTest extends TestCase
{
    /**
     * @test
     */
    public function itConvertsProperties()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter();

        $expectedSchema = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'required' => ['bar'],
            'properties' => [
                'foo' => [
                    'type' => 'string',
                ],
                'bar' => [
                    'type' => ['string', 'null'],
                ],
            ],
        ]));

        $convertedSchema = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'required' => ['bar'],
            'properties' => [
                'foo' => [
                    'type' => 'string',
                    'example' => '2018-01-28T15:35:30Z',
                ],
                'bar' => [
                    'type' => 'string',
                    'nullable' => true,
                ],
            ],
        ])));

        $this->assertEquals($expectedSchema, $convertedSchema);
    }

    /**
     * @test
     */
    public function itConvertsItems()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter();

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'array',
            'items' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'array',
            'items' => [
                'type' => 'dateTime',
                'example' => '2018-01-29T09:54:36Z',
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itConvertsNullable()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter();

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => ['string', 'null'],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'string',
            'nullable' => true,
        ])));

        $this->assertEquals($expected, $converted);

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'string',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'string',
            'nullable' => false,
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itConvertsIntegerTypes()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter();

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'integer',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'integer',
        ])));

        $this->assertEquals($expected, $converted, 'Did not properly convert an integer to an integer');

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'integer',
            'format' => 'int64',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'integer',
            'format' => 'int64',
        ])));

        $this->assertEquals($expected, $converted, 'Did not properly convert an integer while maintaining format');

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'integer',
            'format' => 'int64',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'long',
        ])));

        $this->assertEquals($expected, $converted, 'Did not properly convert a long to an integer with format');
    }

    /**
     * @test
     */
    public function itConvertsNumberTypes()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter();

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'number',
            'format' => 'float',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'float',
        ])));

        $this->assertEquals($expected, $converted, 'Did not properly convert a float');

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'number',
            'format' => 'double',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'double',
        ])));

        $this->assertEquals($expected, $converted, 'Did not properly convert a double');

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'number',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'number',
        ])));

        $this->assertEquals($expected, $converted, 'Did not properly convert a number');

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'number',
            'format' => 'float',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'number',
            'format' => 'float',
        ])));

        $this->assertEquals($expected, $converted, 'Did not properly convert a number with a format');
    }

    /**
     * @test
     */
    public function itRemovesReadOnlyProperties()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter(['remove_read_only' => true]);

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itRemovesWriteOnlyProperties()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter(['remove_write_only' => true]);

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                    'writeOnly' => true,
                ],
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itRemovesReadOnlyEvenIfKeeping()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter(['remove_read_only' => true, 'keep_not_supported' => ['readOnly']]);

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itRemovesReadOnlyFromRequired()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter(['remove_read_only' => true]);

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'required' => ['prop2'],
            'properties' => [
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'required' => ['prop1', 'prop2'],
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itRemovesReadOnlyFromRequiredAndRemovesRequiredIfEmpty()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter(['remove_read_only' => true]);

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'required' => ['prop1'],
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itRemovesReadOnlyAndRemovesPropertiesIfEmpty()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter(['remove_read_only' => true]);

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itDoesNotRemovePropertiesByDefault()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter();

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                ],
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'prop2' => [
                    'type' => 'string',
                ],
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }

    /**
     * @test
     */
    public function itRemovesReadOnlyDeepSchema()
    {
        $converter = new OpenApiV3ToJsonSchemaConverter(['remove_read_only' => true]);

        $expected = json_decode(json_encode([
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'required' => ['prop2'],
            'properties' => [
                'prop2' => [
                    'allOf' => [
                        [
                            'type' => 'object',
                        ],
                        [
                            'type' => 'object',
                        ],
                    ],
                ],
            ],
        ]));

        $converted = $converter->convertSingleSchema(json_decode(json_encode([
            'type' => 'object',
            'required' => ['prop2'],
            'properties' => [
                'prop1' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'prop2' => [
                    'allOf' => [
                        [
                            'type' => 'object',
                            'properties' => [
                                'prop3' => [
                                    'type' => 'object',
                                    'readOnly' => true,
                                ],
                            ],
                        ],
                        [
                            'type' => 'object',
                            'properties' => [
                                'prop4' => [
                                    'type' => 'object',
                                    'readOnly' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ])));

        $this->assertEquals($expected, $converted);
    }
}

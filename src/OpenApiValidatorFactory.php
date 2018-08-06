<?php

namespace PaddleHq\OpenApiValidator;

use HSkrasek\OpenAPI\Converter;
use JsonSchema\SchemaStorage;

class OpenApiValidatorFactory
{
    public function v3Validator(string $openApiSchema): OpenApiValidatorInterface
    {
        return new OpenApiV3Validator($openApiSchema, new Converter(), new SchemaStorage());
    }
}

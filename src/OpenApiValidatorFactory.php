<?php

namespace PaddleHq\OpenApiValidator;

use JsonSchema\SchemaStorage;

class OpenApiValidatorFactory
{
    public function v3Validator(string $openApiSchema): OpenApiValidatorInterface
    {
        return new OpenApiV3Validator($openApiSchema, new OpenApiV3ToJsonSchemaConverter(), new SchemaStorage());
    }
}

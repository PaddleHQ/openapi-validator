<?php

namespace PaddleHq\OpenApiValidator;

use JsonSchema\SchemaStorage;

class OpenApiValidatorFactory
{
    /**
     * Get a OpenAPI v3 validator for a given schema file.
     *
     * @param string $openApiSchemaFileName
     *
     * @return OpenApiValidatorInterface
     */
    public function v3Validator(string $openApiSchemaFileName): OpenApiValidatorInterface
    {
        return new OpenApiV3Validator($openApiSchemaFileName, new OpenApiV3ToJsonSchemaConverter(), new SchemaStorage());
    }
}

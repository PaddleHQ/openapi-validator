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
        $converter = new OpenApiV3ToJsonSchemaConverter();
        $schemaStorage = new SchemaStorage();
        $openApiSchema = json_decode(file_get_contents($openApiSchemaFileName));
        $schemaStorage->addSchema($openApiSchemaFileName, $converter->convertDocument($openApiSchema));

        return new OpenApiV3Validator($openApiSchemaFileName, $schemaStorage);
    }

    /**
     * @param object $openApiSchema
     * @param string $id
     * @return OpenApiValidatorInterface
     */
    public static function v3ValidatorFromSchema($openApiSchema, string $id = 'file://openapi'): OpenApiValidatorInterface
    {
        $converter = new OpenApiV3ToJsonSchemaConverter();
        $schemaStorage = new SchemaStorage();
        $schemaStorage->addSchema($id, $converter->convertDocument($openApiSchema));

        return new OpenApiV3Validator($id, $schemaStorage);
    }
}

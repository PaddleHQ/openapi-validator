<?php

namespace PaddleHq\OpenApiValidator;

use League\JsonReference\Dereferencer;
use League\JsonReference\ReferenceSerializer\InlineReferenceSerializer;
use HSkrasek\OpenAPI\Converter;
use JsonSchema\Validator;

class OpenApiValidatorFactory
{
    public function v3Validator(string $openApiSchema): OpenApiValidatorInterface
    {
        $dereferencer = Dereferencer::draft4();
        $dereferencer->setReferenceSerializer(new InlineReferenceSerializer());

        return new OpenApiV3Validator(
            $openApiSchema,
            new Converter(),
            $dereferencer,
            new Validator()
        );
    }
}

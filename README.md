# OpenApi Validator

This package takes an [OpenApi 3]( schemahttps://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md) schema file, converts it to a [JSON Schema (draft 4)](http://json-schema.org/specification-links.html#draft-4) so it can be used for validation.

This is the used to validate a response that implements the `Psr\Http\Message\ResponseInterface.` interface against a given response in the OpenApi schema.

## Usage

```php
use PaddleHq\OpenApiValidator\OpenApiValidatorFactory;

$validatorFactory = new OpenApiValidatorFactory();

$schemaFilePath = __DIR__.'/schema.json';
$validator = $validatorFactory->v3Validator($schemaFilePath);

$response = new Psr7Response();
$pathName = '/check/health';
$method = 'GET';
$responseCode = 200;
$contentType = 'application/json';

$validator->validateResponse(
    $response,
    $pathName,
    $method,
    $responseCode,
    $contentType
);
```

`$validator->validateResponse` throws a `PaddleHq\OpenApiValidator\Exception\ResponseInvalidException` when the response does not pass the validation.
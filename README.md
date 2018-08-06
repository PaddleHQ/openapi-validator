# OpenApi Validator

This package takes an OpenApi Version 3 spec file, converts it to a JSON Schema (draft 4). This is the used use validate a response.

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
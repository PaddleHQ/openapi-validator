# OpenApi Validator

[![Latest Stable Version](https://poser.pugx.org/paddlehq/openapi-validator/v/stable)](https://packagist.org/packages/paddlehq/openapi-validator) [![Total Downloads](https://poser.pugx.org/paddlehq/openapi-validator/downloads)](https://packagist.org/packages/paddlehq/openapi-validator) [![Latest Unstable Version](https://poser.pugx.org/paddlehq/openapi-validator/v/unstable)](https://packagist.org/packages/paddlehq/openapi-validator) [![License](https://poser.pugx.org/paddlehq/openapi-validator/license)](https://packagist.org/packages/paddlehq/openapi-validator) [![Daily Downloads](https://poser.pugx.org/paddlehq/openapi-validator/d/daily)](https://packagist.org/packages/paddlehq/openapi-validator) 

This package takes an [OpenApi 3](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md) schema file, converts it to a [JSON Schema (draft 4)](http://json-schema.org/specification-links.html#draft-4) so it can be used for validation.

This is the used to validate a response that implements the `Psr\Http\Message\ResponseInterface.` interface against a given response in the OpenApi schema.

## Installation

### Library

```bash 
git clone https://github.com/PaddleHQ/openapi-validator.git
```


### Composer

[Install PHP Composer](https://getcomposer.org/doc/00-intro.md)

```bash
composer require paddlehq/openapi-validator
```

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

`$validator->validateResponse` throws a `PaddleHq\OpenApiValidator\Exception\InvalidResponseException` when the response does not pass the validation.

## Credits

This package largely relies on [justinrainbow/json-schema](https://github.com/justinrainbow/json-schema).

 The code that handles conversion from OpenApi V3 to Json Schema has been taken from [hskrasek/openapi-schema-to-jsonschema](https://github.com/hskrasek/openapi-schema-to-jsonschema)
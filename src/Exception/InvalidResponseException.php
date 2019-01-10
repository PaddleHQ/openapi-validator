<?php

namespace PaddleHq\OpenApiValidator\Exception;

use PaddleHq\OpenApiValidator\SchemaError;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use stdClass;

class InvalidResponseException extends Exception
{
    /**
     * @var array|SchemaError[]
     */
    private $errors;

    /**
     * InvalidResponseException constructor.
     *
     * @param ResponseInterface $response
     * @param stdClass          $openApiV3Response
     * @param array             $errors
     * @param int               $code
     * @param Throwable|null    $previous
     */
    public function __construct(
        ResponseInterface $response,
        stdClass $openApiV3Response,
        array $errors,
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->errors = $this->convertErrorsToObjects($errors);

        $message = sprintf(
            "Response does not match OpenAPI specification\n%s\n\nExpected Schema:\n%s\n\nActual Response:\n%s",
            $this->formatErrors($errors),
            json_encode($openApiV3Response, JSON_PRETTY_PRINT),
            $response->getBody()
        );
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return SchemaError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     *
     * @return string
     */
    private function formatErrors(array $errors): string
    {
        return implode(
            "\n\n",
            array_map(
                function (array $error): string {
                    return sprintf(
                        "\tError: \n\t\tConstraint: %s\n\t\tProperty: %s\n\t\tMessage: %s\n",
                        $error['constraint'],
                        $error['property'],
                        $error['message']
                    );
                },
                $errors
            )
        );
    }

    /**
     * @param array $errors
     * @return SchemaError[]
     */
    private function convertErrorsToObjects(array $errors): array
    {
        return array_map(function ($error) {
            return new SchemaError(
                $error['constraint'],
                $error['property'],
                $error['message']
            );
        }, $errors);
    }
}

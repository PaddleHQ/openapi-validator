<?php

namespace PaddleHq\OpenApiValidator\Exception;


use Psr\Http\Message\RequestInterface;
use stdClass;
use Throwable;

class InvalidRequestException extends \Exception
{
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
        RequestInterface $request,
        stdClass $openApiV3Response,
        array $errors,
        int $code = 0,
        Throwable $previous = null
    ) {
        $message = sprintf(
            "Response does not match OpenAPI specification\n%s\n\nExpected Schema:\n%s\n\nActual Response:\n%s",
            $this->formatErrors($errors),
            json_encode($openApiV3Response, JSON_PRETTY_PRINT),
            $request->getBody()
        );
        parent::__construct($message, $code, $previous);
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
}
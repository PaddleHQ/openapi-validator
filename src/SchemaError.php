<?php

namespace PaddleHq\OpenApiValidator;


class SchemaError
{
    /**
     * @var string
     */
    private $constraint;

    /**
     * @var string
     */
    private $property;

    /**
     * @var string
     */
    private $message;

    public function __construct(string $constraint, string $property, string $message)
    {
        $this->constraint = $constraint;
        $this->property = $property;
        $this->message = $message;
    }

    public function constraint(): string
    {
        return $this->constraint;
    }

    public function property(): string
    {
        return $this->property;
    }

    public function message(): string
    {
        return $this->message;
    }
}

<?php

namespace PaddleHq\OpenApiValidator;

/**
 * This class is taken directly from "hskrasek/openapi-schema-to-jsonschema"
 * It was extracted only because that package has a dependency on the abandoned
 * "league/json-reference" package.
 */
class OpenApiV3ToJsonSchemaConverter
{
    /**
     * @var string[]
     */
    private const STRUCTS = [
        'allOf',
        'anyOf',
        'oneOf',
        'not',
        'items',
        'additionalProperties',
    ];

    /**
     * @var string[]
     */
    private const NOT_SUPPORTED = [
        'nullable',
        'discriminator',
        'readOnly',
        'writeOnly',
        'xml',
        'externalDocs',
        'example',
        'deprecated',
    ];

    /**
     * @var array
     */
    private $options;

    /**
     * @param array|null $options
     */
    public function __construct(?array $options = null)
    {
        $this->options = $this->createOptions($options ?: []);
    }

    /**
     * Converts a full OpenApi v3 document by finding all schemas within it and converting them.
     *
     * @param \stdClass|array $document
     *
     * @return \stdClass|array
     */
    public function convertDocument($document)
    {
        $props = is_object($document) ? get_object_vars($document) : $document;

        foreach ($props as $key => $val) {
            if ('schemas' === $key) {
                $this->dataSet($document, $key, $this->convertSchemas($val));
            } elseif ('schema' === $key) {
                $this->dataSet($document, $key, $this->convertSchema($val));
            } elseif (is_array($val) || is_object($val)) {
                $this->dataSet($document, $key, $this->convertDocument($val));
            }
        }

        return $document;
    }

    /**
     * Convert a standalone schema.
     *
     * @param mixed $schema
     *
     * @return mixed
     */
    public function convertSingleSchema($schema)
    {
        $schema = $this->convertSchema($schema);
        $this->dataSet($schema, '$schema', 'http://json-schema.org/draft-04/schema#');

        return $schema;
    }

    /**
     * @param mixed $schemas
     *
     * @return mixed
     */
    private function convertSchemas($schemas)
    {
        $props = is_object($schemas) ? get_object_vars($schemas) : $schemas;

        foreach ($props as $schemaKey => $schemaVal) {
            $this->dataSet($schemas, $schemaKey, $this->convertSingleSchema($schemaVal));
        }

        return $schemas;
    }

    /**
     * @param mixed $schema
     *
     * @return mixed
     */
    private function convertSchema($schema)
    {
        foreach (self::STRUCTS as $i => $struct) {
            if (is_array($this->dataGet($schema, $struct))) {
                foreach ($schema->$struct as $j => $nestedStruct) {
                    $schema->$struct[$j] = $this->convertSchema($nestedStruct);
                }
            } elseif (is_object($this->dataGet($schema, $struct))) {
                $schema->$struct = $this->convertSchema($schema->$struct);
            }
        }

        if (is_object($properties = $this->dataGet($schema, 'properties'))) {
            $this->dataSet($schema, 'properties', $this->convertProperties($properties));

            if (is_array($required = $this->dataGet($schema, 'required'))) {
                $this->dataSet($schema, 'required', $required = $this->cleanRequired($required, $properties));

                if (0 === count($required)) {
                    $this->removeFromSchema($schema, 'required');
                }
            }

            if (0 === count(get_object_vars($properties))) {
                $this->removeFromSchema($schema, 'properties');
            }
        }

        $schema = $this->convertTypes($schema);

        if (is_object($this->dataGet($schema, 'x-patternProperties')) && $this->options['support_pattern_properties']) {
            $schema = $this->convertPatternProperties($schema, $this->options['pattern_properties_handler']);
        }

        foreach ($this->options['not_supported'] as $notSupported) {
            $this->removeFromSchema($schema, $notSupported);
        }

        return $schema;
    }

    /**
     * @param $properties
     *
     * @return mixed
     */
    private function convertProperties($properties)
    {
        foreach ($properties as $key => $property) {
            $removeProperty = false;

            foreach ($this->options['remove_properties'] as $prop) {
                if (true === $this->dataGet($property, $prop)) {
                    $removeProperty = true;
                }
            }

            if ($removeProperty) {
                $this->removeFromSchema($properties, $key);
                continue;
            }

            $this->dataSet($properties, $key, $this->convertSchema($property));
        }

        return $properties;
    }

    /**
     * @param mixed $schema
     *
     * @return mixed
     */
    private function convertTypes($schema)
    {
        if (null === $this->dataGet($schema, 'type')) {
            return $schema;
        }

        if ('string' === $this->dataGet($schema, 'type') && 'date' === $this->dataGet(
                $schema,
                'format'
            ) && true === $this->options['convert_date']) {
            $this->dataSet($schema, 'format', 'date-time');
        }

        $newType = null;
        $newFormat = null;

        switch ($this->dataGet($schema, 'type')) {
            case 'integer':
                $newType = 'integer';
                break;
            case 'long':
                $newType = 'integer';
                $newFormat = 'int64';
                break;
            case 'float':
                $newType = 'number';
                $newFormat = 'float';
                break;
            case 'double':
                $newType = 'number';
                $newFormat = 'double';
                break;
            case 'byte':
                $newType = 'string';
                $newFormat = 'byte';
                break;
            case 'binary':
                $newType = 'string';
                $newFormat = 'binary';
                break;
            case 'date':
                $newType = 'string';
                $newFormat = $this->options['convert_date'] ? 'date-time' : 'date';
                break;
            case 'dateTime':
                $newType = 'string';
                $newFormat = 'date-time';
                break;
            case 'password':
                $newType = 'string';
                $newFormat = 'password';
                break;
            default:
                $newType = $this->dataGet($schema, 'type');
        }

        $this->dataSet($schema, 'type', $newType);
        $this->dataSet($schema, 'format', is_string($newFormat) ? $newFormat : $this->dataGet($schema, 'format'));

        if (null === $this->dataGet($schema, 'format')) {
            $this->removeFromSchema($schema, 'format');
        }

        if (true === $this->dataGet($schema, 'nullable', false)) {
            $this->dataSet($schema, 'type', [$this->dataGet($schema, 'type'), 'null']);
        }

        return $schema;
    }

    /**
     * @param array|null $required
     * @param null       $properties
     *
     * @return array
     */
    private function cleanRequired(?array $required = [], $properties = null): array
    {
        foreach ($required as $key => $requiredProperty) {
            if (!isset($properties->{$requiredProperty}, $properties)) {
                unset($required[$key]);
            }
        }

        return array_values($required);
    }

    /**
     * @param          $schema
     * @param callable $handler
     *
     * @return mixed
     */
    private function convertPatternProperties($schema, callable $handler)
    {
        $this->dataSet($schema, 'patternProperties', $this->dataGet($schema, 'x-patternProperties'));
        $this->removeFromSchema($schema, 'x-patternProperties');

        return call_user_func($handler, $schema);
    }

    /**
     * @param $schema
     *
     * @return mixed
     */
    private function patternPropertiesHandler($schema)
    {
        $patternProperties = $this->dataGet($schema, 'patternProperties');

        if (!is_object($additionalProperties = $this->dataGet($schema, 'additionalProperties'))) {
            return $schema;
        }

        foreach ($patternProperties as $patternProperty) {
            if ($patternProperty == $additionalProperties) {
                $this->dataSet($schema, 'additionalProperties', false);
                break;
            }
        }

        return $schema;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function createOptions(array $options): array
    {
        $options['convert_date'] = $options['convert_date'] ?? false;
        $options['support_pattern_properties'] = $options['support_pattern_properties'] ?? false;
        $options['keep_not_supported'] = $options['keep_not_supported'] ?? [];
        $options['pattern_properties_handler'] = $options['pattern_properties_handler'] ?? [
            $this,
            'patternPropertiesHandler',
        ];

        $options['remove_properties'] = [];

        if ($options['remove_read_only'] ?? false) {
            $options['remove_properties'][] = 'readOnly';
        }

        if ($options['remove_write_only'] ?? false) {
            $options['remove_properties'][] = 'writeOnly';
        }

        $options['not_supported'] = $this->resolveNotSupported(self::NOT_SUPPORTED, $options['keep_not_supported']);

        return $options;
    }

    /**
     * @param array $notSupported
     * @param array $toRetain
     *
     * @return array
     */
    private function resolveNotSupported(array $notSupported, array $toRetain): array
    {
        return array_values(array_diff($notSupported, $toRetain));
    }

    /**
     * @param        $schema
     * @param string $key
     */
    private function removeFromSchema($schema, string $key): void
    {
        if (is_object($schema)) {
            unset($schema->{$key});

            return;
        }

        unset($schema[$key]);

        return;
    }

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     *
     * @source Laravel (Thanks Taylor!)
     */
    private function dataGet($target, $key, $default = null)
    {
        if (null === $key) {
            return $target;
        }
        $key = is_array($key) ? $key : explode('.', $key);
        while (null !== $segment = array_shift($key)) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }

    /**
     * Set an item on an array or object using dot notation.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $value
     * @param bool         $overwrite
     *
     * @return mixed
     *
     * @source Laravel (Thanks Taylor!)
     */
    private function dataSet(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        $segment = array_shift($segments);
        if (is_array($target)) {
            if ($segments) {
                if (!array_key_exists($segment, $target)) {
                    $target[$segment] = [];
                }
                $this->dataSet($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !array_key_exists($segment, $target)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                $this->dataSet($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            if ($segments) {
                $this->dataSet($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

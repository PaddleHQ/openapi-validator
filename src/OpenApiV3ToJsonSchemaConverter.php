<?php

namespace PaddleHq\OpenApiValidator;

/**
 * This class is taken directly from "hskrasek/openapi-schema-to-jsonschema"
 * It was extracted only because that package has a dependency on the abandoned
 * "league/json-reference" package.
 */
class OpenApiV3ToJsonSchemaConverter
{
    private const STRUCTS = [
        'allOf',
        'anyOf',
        'oneOf',
        'not',
        'items',
        'additionalProperties',
    ];

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

    public function __construct(?array $options = null)
    {
        $this->options = $this->createOptions($options ?: []);
    }

    /**
     * @param mixed $schema
     *
     * @return mixed
     */
    public function convert($schema)
    {
        $schema = $this->convertSchema($schema);
        $this->data_set($schema, '$schema', 'http://json-schema.org/draft-04/schema#');

        return $schema;
    }

    /**
     * @param mixed $schema
     *
     * @return mixed
     */
    private function convertSchema($schema)
    {
        foreach (self::STRUCTS as $i => $struct) {
            if (\is_array($this->data_get($schema, $struct))) {
                foreach ($schema->$struct as $j => $nestedStruct) {
                    $schema->$struct[$j] = $this->convertSchema($nestedStruct);
                }
            } elseif (\is_object($this->data_get($schema, $struct))) {
                $schema->$struct = $this->convertSchema($schema->$struct);
            }
        }

        if (\is_object($properties = $this->data_get($schema, 'properties'))) {
            $this->data_set($schema, 'properties', $this->convertProperties($properties));

            if (\is_array($required = $this->data_get($schema, 'required'))) {
                $this->data_set($schema, 'required', $required = $this->cleanRequired($required, $properties));

                if (0 === \count($required)) {
                    $this->removeFromSchema($schema, 'required');
                }
            }

            if (0 === \count(get_object_vars($properties))) {
                $this->removeFromSchema($schema, 'properties');
            }
        }

        $schema = $this->convertTypes($schema);

        if (\is_object($this->data_get($schema, 'x-patternProperties')) && $this->options['support_pattern_properties']) {
            $schema = $this->convertPatternProperties($schema, $this->options['pattern_properties_handler']);
        }

        foreach ($this->options['not_supported'] as $notSupported) {
            $this->removeFromSchema($schema, $notSupported);
        }

        return $schema;
    }

    private function convertProperties($properties)
    {
        foreach ($properties as $key => $property) {
            $removeProperty = false;

            foreach ($this->options['remove_properties'] as $prop) {
                if (true === $this->data_get($property, $prop)) {
                    $removeProperty = true;
                }
            }

            if ($removeProperty) {
                $this->removeFromSchema($properties, $key);
                continue;
            }

            $this->data_set($properties, $key, $this->convertSchema($property));
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
        if (null === $this->data_get($schema, 'type')) {
            return $schema;
        }

        if ('string' === $this->data_get($schema, 'type') && 'date' === $this->data_get(
                $schema,
                'format'
            ) && true === $this->options['convert_date']) {
            $this->data_set($schema, 'format', 'date-time');
        }

        $newType = null;
        $newFormat = null;

        switch ($this->data_get($schema, 'type')) {
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
                $newType = $this->data_get($schema, 'type');
        }

        $this->data_set($schema, 'type', $newType);
        $this->data_set($schema, 'format', \is_string($newFormat) ? $newFormat : $this->data_get($schema, 'format'));

        if (null === $this->data_get($schema, 'format')) {
            $this->removeFromSchema($schema, 'format');
        }

        if (true === $this->data_get($schema, 'nullable', false)) {
            $this->data_set($schema, 'type', [$this->data_get($schema, 'type'), 'null']);
        }

        return $schema;
    }

    private function cleanRequired(?array $required = [], $properties = null): array
    {
        foreach ($required as $key => $requiredProperty) {
            if (!isset($properties->{$requiredProperty}, $properties)) {
                unset($required[$key]);
            }
        }

        return array_values($required);
    }

    private function convertPatternProperties($schema, callable $handler)
    {
        $this->data_set($schema, 'patternProperties', $this->data_get($schema, 'x-patternProperties'));
        $this->removeFromSchema($schema, 'x-patternProperties');

        return call_user_func($handler, $schema);
    }

    private function patternPropertiesHandler($schema)
    {
        $patternProperties = $this->data_get($schema, 'patternProperties');

        if (!\is_object($additionalProperties = $this->data_get($schema, 'additionalProperties'))) {
            return $schema;
        }

        foreach ($patternProperties as $patternProperty) {
            if ($patternProperty == $additionalProperties) {
                $this->data_set($schema, 'additionalProperties', false);
                break;
            }
        }

        return $schema;
    }

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

    private function resolveNotSupported(array $notSupported, array $toRetain): array
    {
        return array_values(array_diff($notSupported, $toRetain));
    }

    private function removeFromSchema($schema, string $key): void
    {
        if (\is_object($schema)) {
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
    private function data_get($target, $key, $default = null)
    {
        if (null === $key) {
            return $target;
        }
        $key = \is_array($key) ? $key : explode('.', $key);
        while (null !== $segment = array_shift($key)) {
            if (\is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (\is_object($target) && isset($target->{$segment})) {
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
    private function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = \is_array($key) ? $key : explode('.', $key);
        $segment = array_shift($segments);
        if (\is_array($target)) {
            if ($segments) {
                if (!array_key_exists($segment, $target)) {
                    $target[$segment] = [];
                }
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !array_key_exists($segment, $target)) {
                $target[$segment] = $value;
            }
        } elseif (\is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

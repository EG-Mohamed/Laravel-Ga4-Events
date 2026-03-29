<?php

namespace MohamedSaid\LaravelGa4Events\Support;

use MohamedSaid\LaravelGa4Events\Exceptions\InvalidGa4EventPayloadException;

class Ga4EventValidator
{
    public function __construct(private readonly array $config) {}

    public function validate(array $payload, bool $throwOnError = false): array
    {
        $errors = [];
        $name = $this->normalizeEventName($payload['name'] ?? null, $errors);
        $params = $this->normalizeParams($payload['params'] ?? [], $errors);
        $options = $this->normalizeOptions($payload['options'] ?? []);
        $valid = $errors === [];

        if (! $valid && $throwOnError) {
            throw InvalidGa4EventPayloadException::fromErrors($errors);
        }

        return [
            'valid' => $valid,
            'errors' => array_values($errors),
            'name' => $name,
            'params' => $params,
            'options' => $options,
        ];
    }

    private function normalizeEventName(mixed $value, array &$errors): string
    {
        if (! is_string($value)) {
            $errors[] = (string) __('Event name must be a string.');

            return '';
        }

        $name = trim($value);

        if ($name === '') {
            $errors[] = (string) __('Event name is required.');

            return '';
        }

        $maxLength = $this->intConfig('max_event_name_length', 40);
        $pattern = (string) ($this->config['allowed_name_pattern'] ?? '/^[a-zA-Z][a-zA-Z0-9_]*$/');

        if (mb_strlen($name) > $maxLength) {
            $errors[] = (string) __('Event name exceeds the allowed length.');
        }

        if (@preg_match($pattern, $name) !== 1) {
            $errors[] = (string) __('Event name does not match the allowed pattern.');
        }

        return $name;
    }

    private function normalizeParams(mixed $value, array &$errors): array
    {
        if (! is_array($value)) {
            $errors[] = (string) __('Event params must be an object-like array.');

            return [];
        }

        $output = [];
        $maxParams = $this->intConfig('max_params', 25);

        foreach ($value as $key => $paramValue) {
            if (! is_string($key)) {
                $errors[] = (string) __('Event param keys must be strings.');

                continue;
            }

            if (count($output) >= $maxParams) {
                $errors[] = (string) __('Event params exceed the maximum allowed count.');

                break;
            }

            $normalizedKey = trim(str_replace(' ', '_', $key));

            if ($normalizedKey === '') {
                $errors[] = (string) __('Event param key cannot be empty.');

                continue;
            }

            if (mb_strlen($normalizedKey) > $this->intConfig('max_param_key_length', 40)) {
                $errors[] = (string) __('Event param key exceeds the allowed length.');

                continue;
            }

            if ($this->isBlockedParamKey($normalizedKey)) {
                $errors[] = (string) __('Event param key is blocked or reserved.');

                continue;
            }

            $normalizedValue = $this->normalizeParamValue($paramValue, $errors);

            if ($normalizedValue === null && $paramValue !== null) {
                continue;
            }

            $output[$normalizedKey] = $normalizedValue;
        }

        return $output;
    }

    private function normalizeParamValue(mixed $value, array &$errors): string|int|float|bool|null
    {
        if (is_string($value)) {
            if (mb_strlen($value) > $this->intConfig('max_param_value_length', 100)) {
                $errors[] = (string) __('Event param value exceeds the allowed length.');

                return mb_substr($value, 0, $this->intConfig('max_param_value_length', 100));
            }

            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        $errors[] = (string) __('Event param value type is not supported.');

        return null;
    }

    private function normalizeOptions(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $allowed = [
            'send_to',
            'event_callback',
            'event_timeout',
            'non_interaction',
            'transport_type',
            'debug_mode',
        ];

        return collect($value)
            ->only($allowed)
            ->toArray();
    }

    private function isBlockedParamKey(string $key): bool
    {
        $blockedKeys = $this->config['blocked_param_keys'] ?? [];

        if (is_array($blockedKeys) && in_array($key, $blockedKeys, true)) {
            return true;
        }

        $prefixes = $this->config['reserved_prefixes'] ?? [];

        if (! is_array($prefixes)) {
            return false;
        }

        foreach ($prefixes as $prefix) {
            if (! is_string($prefix) || $prefix === '') {
                continue;
            }

            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = $this->config[$key] ?? $default;

        if (! is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}

<?php

namespace MohamedSaid\LaravelGa4Events;

use MohamedSaid\LaravelGa4Events\Exceptions\InvalidGa4ConfigurationException;
use MohamedSaid\LaravelGa4Events\Support\Ga4EventValidator;

class LaravelGa4Events
{
    public function __construct(private readonly array $config) {}

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    public function measurementId(): ?string
    {
        $measurementId = trim((string) ($this->config['measurement_id'] ?? ''));

        return $measurementId !== '' ? $measurementId : null;
    }

    public function requireMeasurementId(): string
    {
        $measurementId = $this->measurementId();

        if ($measurementId === null) {
            throw InvalidGa4ConfigurationException::missingMeasurementId();
        }

        return $measurementId;
    }

    public function track(string $name, array $params = [], array $options = []): array
    {
        return $this->validator()->validate([
            'name' => $name,
            'params' => $params,
            'options' => array_merge($this->defaultEventOptions(), $options),
        ], $this->strictValidation());
    }

    public function validator(): Ga4EventValidator
    {
        return new Ga4EventValidator($this->config);
    }

    public function toFrontendConfig(): array
    {
        return [
            'enabled' => $this->enabled(),
            'measurementId' => $this->measurementId(),
            'injectGtagScript' => (bool) ($this->config['inject_gtag_script'] ?? true),
            'gtagUrl' => (string) ($this->config['gtag_url'] ?? 'https://www.googletagmanager.com/gtag/js'),
            'gtagFunctionName' => (string) ($this->config['gtag_function_name'] ?? 'gtag'),
            'eventBusName' => (string) ($this->config['event_bus_name'] ?? 'ga4:event'),
            'livewireEventName' => (string) ($this->config['livewire_event_name'] ?? 'ga4-event'),
            'globalJsObject' => (string) ($this->config['global_js_object'] ?? 'GA4Events'),
            'autoPageView' => (bool) ($this->config['auto_page_view'] ?? true),
            'debug' => (bool) ($this->config['debug'] ?? false),
            'queueUntilGtagReady' => (bool) ($this->config['queue_until_gtag_ready'] ?? true),
            'maxQueueSize' => (int) ($this->config['max_queue_size'] ?? 100),
            'strictValidation' => $this->strictValidation(),
            'dropInvalidEvents' => (bool) ($this->config['drop_invalid_events'] ?? true),
            'maxEventNameLength' => (int) ($this->config['max_event_name_length'] ?? 40),
            'maxParams' => (int) ($this->config['max_params'] ?? 25),
            'maxParamKeyLength' => (int) ($this->config['max_param_key_length'] ?? 40),
            'maxParamValueLength' => (int) ($this->config['max_param_value_length'] ?? 100),
            'maxParamNesting' => (int) ($this->config['max_param_nesting'] ?? 4),
            'allowedNamePattern' => (string) ($this->config['allowed_name_pattern'] ?? '/^[a-zA-Z][a-zA-Z0-9_]*$/'),
            'blockedParamKeys' => is_array($this->config['blocked_param_keys'] ?? null) ? array_values($this->config['blocked_param_keys']) : [],
            'reservedPrefixes' => is_array($this->config['reserved_prefixes'] ?? null) ? array_values($this->config['reserved_prefixes']) : [],
            'defaultEventOptions' => $this->defaultEventOptions(),
            'consolePrefix' => (string) ($this->config['console_prefix'] ?? '[GA4 Events]'),
        ];
    }

    private function strictValidation(): bool
    {
        return (bool) ($this->config['strict_validation'] ?? false);
    }

    private function defaultEventOptions(): array
    {
        return is_array($this->config['default_event_options'] ?? null)
            ? $this->config['default_event_options']
            : [];
    }
}

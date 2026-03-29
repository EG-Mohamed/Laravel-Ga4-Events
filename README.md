# Laravel GA4 Events

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eg-mohamed/laravel-ga4-events.svg?style=flat-square)](https://packagist.org/packages/eg-mohamed/laravel-ga4-events)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/eg-mohamed/laravel-ga4-events/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/eg-mohamed/laravel-ga4-events/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/eg-mohamed/laravel-ga4-events/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/eg-mohamed/laravel-ga4-events/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/eg-mohamed/laravel-ga4-events.svg?style=flat-square)](https://packagist.org/packages/eg-mohamed/laravel-ga4-events)

Define, validate, and dispatch Google Analytics 4 events from Laravel, Livewire, and plain JavaScript through one unified bridge. The package validates payloads, supports queueing until `gtag` is ready, and provides browser-side debug and error detection through `console.log`.

## Features

- Unified GA4 event bridge for PHP, Livewire, and JavaScript
- Client-side event validation before firing `gtag('event', ...)`
- Queue and retry while `gtag` is not ready
- Strict mode and drop-invalid mode
- Config-driven behavior for event names, limits, and blocked keys
- Blade directive for easy frontend injection
- Console debug output with clear error/warning/info states

## Compatibility

- PHP: `^8.2`
- Laravel: `10.x`, `11.x`, `12.x`, `13.x`

## Installation

```bash
composer require eg-mohamed/laravel-ga4-events
```

Publish the package config:

```bash
php artisan vendor:publish --tag="ga4-events-config"
```

## Quick Start

1) Set your measurement ID in `.env`:

```dotenv
GA4_MEASUREMENT_ID=G-XXXXXXXXXX
GA4_EVENTS_ENABLED=true
GA4_EVENTS_DEBUG=true
```

2) Inject the bridge script once in your layout (before `</body>` is recommended):

```blade
<x-ga4-events />
```

Legacy directive is also available:

```blade
@ga4Events
```

3) Fire events from JavaScript:

```js
window.GA4Events.track('purchase_started', {
  currency: 'USD',
  value: 99.95,
  item_count: 2,
})
```

4) Fire events from custom browser events:

```js
window.dispatchEvent(new CustomEvent('ga4:event', {
  detail: {
    name: 'checkout_step',
    params: { step: 2 },
    options: { non_interaction: false },
  },
}))
```

## Livewire Usage

### Livewire v3 component event

```php
$this->dispatch('ga4-event', [
    'name' => 'profile_updated',
    'params' => [
        'section' => 'security',
    ],
]);
```

### JavaScript listener inside Livewire pages

The package automatically listens to `Livewire.on(config.livewireEventName, ...)` and forwards payloads to GA4.

## JavaScript API

After `<x-ga4-events />`, the package creates a global object (default: `window.GA4Events`).

### `track(name, params = {}, options = {})`

```js
window.GA4Events.track('add_to_cart', { product_id: 15, value: 150 })
```

### `dispatch(payload, source = 'manual')`

```js
window.GA4Events.dispatch({
  name: 'search',
  params: { term: 'sneakers' },
  options: { non_interaction: true },
})
```

### `flushQueue()`

```js
window.GA4Events.flushQueue()
```

### `config`

```js
console.log(window.GA4Events.config)
```

## Payload Contract

All payloads should follow this structure:

```json
{
  "name": "event_name",
  "params": {
    "key": "value"
  },
  "options": {
    "non_interaction": false
  }
}
```

Rules applied by validator:

- `name` must be a non-empty string
- `name` must match `allowed_name_pattern`
- `name` must not exceed `max_event_name_length`
- `params` must be an object-like map
- param keys are validated against length and blocked/reserved keys
- param values support `string`, `number`, `boolean`, or `null`
- string values are truncated to `max_param_value_length`

## Configuration

Published file: `config/ga4-events.php`

```php
<?php

return [
    'enabled' => (bool) env('GA4_EVENTS_ENABLED', true),
    'measurement_id' => env('GA4_MEASUREMENT_ID'),
    'inject_gtag_script' => (bool) env('GA4_EVENTS_INJECT_GTAG_SCRIPT', true),
    'gtag_url' => env('GA4_EVENTS_GTAG_URL', 'https://www.googletagmanager.com/gtag/js'),
    'gtag_function_name' => env('GA4_EVENTS_GTAG_FUNCTION_NAME', 'gtag'),
    'event_bus_name' => env('GA4_EVENTS_EVENT_BUS_NAME', 'ga4:event'),
    'livewire_event_name' => env('GA4_EVENTS_LIVEWIRE_EVENT_NAME', 'ga4-event'),
    'global_js_object' => env('GA4_EVENTS_GLOBAL_JS_OBJECT', 'GA4Events'),
    'auto_page_view' => (bool) env('GA4_EVENTS_AUTO_PAGE_VIEW', true),
    'debug' => (bool) env('GA4_EVENTS_DEBUG', false),
    'queue_until_gtag_ready' => (bool) env('GA4_EVENTS_QUEUE_UNTIL_GTAG_READY', true),
    'max_queue_size' => (int) env('GA4_EVENTS_MAX_QUEUE_SIZE', 100),
    'strict_validation' => (bool) env('GA4_EVENTS_STRICT_VALIDATION', false),
    'drop_invalid_events' => (bool) env('GA4_EVENTS_DROP_INVALID_EVENTS', true),
    'max_event_name_length' => (int) env('GA4_EVENTS_MAX_EVENT_NAME_LENGTH', 40),
    'max_params' => (int) env('GA4_EVENTS_MAX_PARAMS', 25),
    'max_param_key_length' => (int) env('GA4_EVENTS_MAX_PARAM_KEY_LENGTH', 40),
    'max_param_value_length' => (int) env('GA4_EVENTS_MAX_PARAM_VALUE_LENGTH', 100),
    'allowed_name_pattern' => env('GA4_EVENTS_ALLOWED_NAME_PATTERN', '/^[a-zA-Z][a-zA-Z0-9_]*$/'),
    'blocked_param_keys' => ['gclid', 'dclid'],
    'reserved_prefixes' => ['_', 'ga_', 'google_', 'gtm_', 'firebase_'],
    'default_event_options' => ['non_interaction' => false],
    'console_prefix' => env('GA4_EVENTS_CONSOLE_PREFIX', '[GA4 Events]'),
];
```

## Console Debug and Error Detection

Enable debug mode:

```dotenv
GA4_EVENTS_DEBUG=true
```

When enabled, the package logs via `console.log`:

- Bridge initialization state
- Invalid payload details
- Queueing and flush status
- Missing `gtag` handling
- Livewire listener registration
- Missing measurement ID warnings

Example output pattern:

```text
[GA4 Events] [INFO] GA4 bridge initialized.
[GA4 Events] [WARN] gtag is not ready. Event queued from dom.
[GA4 Events] [ERROR] Invalid GA4 payload from livewire.
```

## Package Command

Validate package runtime configuration:

```bash
php artisan ga4-events:check
```

## Advanced Scenarios

### Use a custom global JS object name

```dotenv
GA4_EVENTS_GLOBAL_JS_OBJECT=AnalyticsBridge
```

Now use:

```js
window.AnalyticsBridge.track('payment_info_added', { method: 'card' })
```

### Disable auto loading gtag script

If your app already loads Google tag:

```dotenv
GA4_EVENTS_INJECT_GTAG_SCRIPT=false
```

The package still validates and forwards events to existing `window.gtag`.

### Strict validation mode

```dotenv
GA4_EVENTS_STRICT_VALIDATION=true
```

Invalid payloads are blocked and never forwarded.

## Troubleshooting

### Events not reaching GA4

- Confirm `GA4_MEASUREMENT_ID` is set
- Confirm `<x-ga4-events />` is rendered in the page
- Confirm `window.gtag` exists (if auto inject disabled)
- Enable `GA4_EVENTS_DEBUG=true` and inspect browser console

### Livewire events not detected

- Confirm the emitted event name matches `livewire_event_name`
- Confirm Livewire scripts are loaded
- Check console logs for listener registration

### Payload rejected by validator

- Use a valid event name format
- Use only supported param value types
- Avoid blocked keys and reserved prefixes

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for contribution details.

## Security

Please review [our security policy](../../security/policy) for reporting vulnerabilities.

## Credits

- [Mohamed Said](https://github.com/EG-Mohamed)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

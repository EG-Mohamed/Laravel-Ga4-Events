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
    'blocked_param_keys' => [
        'gclid',
        'dclid',
    ],
    'reserved_prefixes' => [
        '_',
        'ga_',
        'google_',
        'gtm_',
        'firebase_',
    ],
    'default_event_options' => [
        'non_interaction' => false,
    ],
    'console_prefix' => env('GA4_EVENTS_CONSOLE_PREFIX', '[GA4 Events]'),
];

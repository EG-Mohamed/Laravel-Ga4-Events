<?php

namespace MohamedSaid\LaravelGa4Events\Exceptions;

use RuntimeException;

class InvalidGa4ConfigurationException extends RuntimeException
{
    public static function missingMeasurementId(): self
    {
        return new self((string) __('GA4 measurement ID is missing. Set GA4_MEASUREMENT_ID or ga4-events.measurement_id.'));
    }
}

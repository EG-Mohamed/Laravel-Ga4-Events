<?php

namespace MohamedSaid\LaravelGa4Events\Commands;

use Illuminate\Console\Command;
use MohamedSaid\LaravelGa4Events\LaravelGa4Events;

class LaravelGa4EventsCommand extends Command
{
    protected $signature = 'ga4-events:check';

    protected $description = '';

    public function __construct(private readonly LaravelGa4Events $ga4Events)
    {
        parent::__construct();

        $this->description = (string) __('Validate GA4 events package configuration.');
    }

    public function handle(): int
    {
        $frontend = $this->ga4Events->toFrontendConfig();

        $this->line((string) __('GA4 events package status'));
        $this->line((string) __('Enabled: :value', ['value' => $frontend['enabled'] ? 'yes' : 'no']));
        $this->line((string) __('Measurement ID: :value', ['value' => $frontend['measurementId'] ?? 'null']));
        $this->line((string) __('Event bus: :value', ['value' => $frontend['eventBusName']]));
        $this->line((string) __('Livewire event: :value', ['value' => $frontend['livewireEventName']]));
        $this->line((string) __('Debug mode: :value', ['value' => $frontend['debug'] ? 'yes' : 'no']));

        if ($frontend['enabled'] && $frontend['measurementId'] === null) {
            $this->error((string) __('The package is enabled but measurement ID is missing.'));

            return self::FAILURE;
        }

        $this->info((string) __('Configuration looks valid.'));

        return self::SUCCESS;
    }
}

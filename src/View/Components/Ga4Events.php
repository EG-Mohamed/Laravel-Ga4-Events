<?php

namespace MohamedSaid\LaravelGa4Events\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use MohamedSaid\LaravelGa4Events\LaravelGa4Events;

class Ga4Events extends Component
{
    public function __construct(private readonly LaravelGa4Events $ga4Events) {}

    public function render(): View
    {
        return view('ga4-events::components.ga4-events', [
            'ga4Config' => $this->ga4Events->toFrontendConfig(),
        ]);
    }
}

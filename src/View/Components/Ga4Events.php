<?php

namespace MohamedSaid\LaravelGa4Events\View\Components;

use Closure;
use Illuminate\View\Component;
use MohamedSaid\LaravelGa4Events\LaravelGa4Events;

class Ga4Events extends Component
{
    public function render(): Closure
    {
        return function (): string {
            return app(LaravelGa4Events::class)->renderBridge();
        };
    }
}

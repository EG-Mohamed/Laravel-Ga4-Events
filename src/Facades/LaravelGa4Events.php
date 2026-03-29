<?php

namespace MohamedSaid\LaravelGa4Events\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MohamedSaid\LaravelGa4Events\LaravelGa4Events
 */
class LaravelGa4Events extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MohamedSaid\LaravelGa4Events\LaravelGa4Events::class;
    }
}

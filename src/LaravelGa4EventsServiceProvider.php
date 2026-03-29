<?php

namespace MohamedSaid\LaravelGa4Events;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use MohamedSaid\LaravelGa4Events\Commands\LaravelGa4EventsCommand;
use MohamedSaid\LaravelGa4Events\View\Components\Ga4Events;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelGa4EventsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ga4-events')
            ->hasConfigFile('ga4-events')
            ->hasViews()
            ->hasCommand(LaravelGa4EventsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LaravelGa4Events::class, function (Application $app): LaravelGa4Events {
            $config = $app['config']->get('ga4-events', []);

            return new LaravelGa4Events(is_array($config) ? $config : []);
        });
    }

    public function packageBooted(): void
    {
        Blade::component('ga4-events', Ga4Events::class);

        Blade::directive('ga4Events', function (): string {
            return "<?php echo app('view')->make('laravel-ga4-events::components.ga4-events', ['ga4Config' => app('".LaravelGa4Events::class."')->toFrontendConfig()])->render(); ?>";
        });
    }
}

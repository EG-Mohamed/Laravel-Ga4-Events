<?php

namespace MohamedSaid\LaravelGa4Events;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use MohamedSaid\LaravelGa4Events\Commands\LaravelGa4EventsCommand;
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
        Blade::directive('ga4Events', function (): string {
            return "<?php echo view('laravel-ga4-events::partials.bridge')->render(); ?>";
        });
    }
}

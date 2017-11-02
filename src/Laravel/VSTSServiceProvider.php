<?php

namespace Jeylabs\VSTS\Laravel;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Jeylabs\VSTS\VSTS;
use Laravel\Lumen\Application as LumenApplication;

class VSTSServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->setupConfig($this->app);
    }

    protected function setupConfig(Application $app)
    {
        $source = __DIR__ . '/config/vsts.php';

        if ($app instanceof LaravelApplication && $app->runningInConsole()) {
            $this->publishes([$source => config_path('vsts.php')]);
        } elseif ($app instanceof LumenApplication) {
            $app->configure('vsts');
        }

        $this->mergeConfigFrom($source, 'vsts');
    }

    public function register()
    {
        $this->registerBindings($this->app);
    }

    protected function registerBindings(Application $app)
    {
        $app->singleton('vsts', function ($app) {
            $config = $app['config'];
            $instance = $config['account'] . '.' . $config['domain'];
            return new VSTS(
                $instance, $config['collection'], $config['version']
            );
        });

        $app->alias('vsts', VSTS::class);
    }

    public function provides()
    {
        return ['vsts'];
    }
}

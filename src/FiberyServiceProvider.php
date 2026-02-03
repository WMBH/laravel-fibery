<?php

namespace WMBH\Fibery;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use WMBH\Fibery\Commands\FiberyTestCommand;

class FiberyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('fibery')
            ->hasConfigFile()
            ->hasCommand(FiberyTestCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Fibery::class, function ($app) {
            $config = $app['config']['fibery'];

            return new Fibery(
                $config['workspace'] ?? '',
                $config['token'] ?? '',
                [
                    'timeout' => $config['timeout'] ?? 30,
                    'retry_times' => $config['retry']['times'] ?? 3,
                    'retry_sleep' => $config['retry']['sleep'] ?? 1000,
                ]
            );
        });

        $this->app->alias(Fibery::class, 'fibery');
    }
}

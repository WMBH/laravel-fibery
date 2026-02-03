<?php

namespace WMBH\Fibery\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use WMBH\Fibery\FiberyServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            FiberyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('fibery.workspace', 'test-workspace');
        config()->set('fibery.token', 'test-token');
        config()->set('fibery.timeout', 30);
        config()->set('fibery.retry.times', 3);
        config()->set('fibery.retry.sleep', 1000);
    }
}

<?php

namespace JeffersonGoncalves\GdprDsar\Tests;

use JeffersonGoncalves\GdprDsar\GdprDsarServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GdprDsarServiceProvider::class,
        ];
    }
}

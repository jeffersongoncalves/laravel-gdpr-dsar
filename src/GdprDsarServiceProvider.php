<?php

namespace JeffersonGoncalves\GdprDsar;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GdprDsarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-gdpr-dsar')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}

<?php

namespace JeffersonGoncalves\GdprDsar;

use JeffersonGoncalves\GdprDsar\Commands\OverdueCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GdprDsarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('gdpr-dsar')
            ->hasConfigFile()
            ->hasMigrations(['create_gdpr_dsar_requests_table'])
            ->hasCommand(OverdueCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(DsarManager::class);
    }
}

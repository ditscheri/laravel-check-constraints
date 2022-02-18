<?php

namespace Ditscheri\CheckConstraints;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Ditscheri\CheckConstraints\Commands\CheckConstraintsCommand;

class CheckConstraintsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-check-constraints')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-check-constraints_table')
            ->hasCommand(CheckConstraintsCommand::class);
    }
}
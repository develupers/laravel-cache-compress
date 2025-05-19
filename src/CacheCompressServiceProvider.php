<?php

namespace Develupers\CacheCompress;

use Develupers\CacheCompress\Commands\CacheCompressCommand;
use Develupers\CacheCompress\Compressors\DefaultStoreCompressor;
use Develupers\CacheCompress\Compressors\MongoDBStoreCompressor;
use Illuminate\Contracts\Foundation\Application;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CacheCompressServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-cache-compress')
            ->hasConfigFile()
            ->hasCommand(CacheCompressCommand::class);
    }

    public function packageRegistered(): void
    {
        // Register the compressors
        $this->app->singleton(DefaultStoreCompressor::class, function () {
            return new DefaultStoreCompressor;
        });

        $this->app->singleton(MongoDBStoreCompressor::class, function () {
            return new MongoDBStoreCompressor;
        });

        // Register the cache compress manager
        $this->app->singleton('cache.compress', function (Application $app) {
            return new CacheCompressManager($app);
        });
    }
}

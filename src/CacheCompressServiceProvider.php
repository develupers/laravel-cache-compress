<?php

namespace Develupers\CacheCompress;

use Develupers\CacheCompress\Commands\CacheCompressCommand;
use Develupers\CacheCompress\Compressors\DefaultStoreCompressor;
use Develupers\CacheCompress\Compressors\MongoDBStoreCompressor;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
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

    public function packageBooted(): void
    {
        // Add compatibility macro to Laravel's Cache facade
        if (! Cache::hasMacro('compress')) {
            Cache::macro('compress', function (bool $enabled = true) {
                // Get the app instance
                $app = app();

                // Get our compressed cache manager
                $cacheCompressManager = $app->make('cache.compress');

                // Get a compressed repository for the current store
                // The store name is determined automatically from the current configuration
                $repository = $cacheCompressManager->store();

                // Set compression enabled/disabled as requested
                return $repository->compress($enabled);
            });
        }

        // Add the compress macro to Repository instances (for Cache::store('file')->compress())
        if (! Repository::hasMacro('compress')) {
            Repository::macro('compress', function (bool $enabled = true) {
                // Get the app instance
                $app = app();

                // Get our compressed cache manager
                $cacheCompressManager = $app->make('cache.compress');

                // Get a repository for the default store - the specific store name is passed
                // through the CacheManager's store() method before getting here
                $repository = $cacheCompressManager->store();

                // Set compression enabled/disabled as requested
                return $repository->compress($enabled);
            });
        }
    }
}

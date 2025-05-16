<?php

namespace Develupers\CacheCompress;

use Develupers\CacheCompress\Commands\CacheCompressCommand;
use Develupers\CacheCompress\Listeners\CompressCacheListener;
use Develupers\CacheCompress\Listeners\DecompressCacheListener;
use Develupers\CacheCompress\Traits\HasCompression;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CompressCacheServiceProvider extends PackageServiceProvider
{
    use HasCompression;

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CompressCache::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (Config::get('cache-compress.enabled', true)) {
            $this->app['events']->listen(
                KeyWritten::class,
                CompressCacheListener::class
            );

            $this->app['events']->listen(
                CacheHit::class,
                DecompressCacheListener::class
            );
        }

        // Register the compression macro
        Cache::macro('compress', function (bool $enabled = true, ?int $level = null) {
            $this->compressionSettings = [
                'enabled' => $enabled,
                'level' => $level ?? config('cache-compress.compression_level', 6),
            ];

            return $this;
        });

        Cache::macro('compressionLevel', function (int $level) {
            if (! isset($this->compressionSettings)) {
                $this->compressionSettings = [
                    'enabled' => config('cache-compress.enabled', true),
                    'level' => $level,
                ];
            } else {
                $this->compressionSettings['level'] = $level;
            }

            return $this;
        });

        // Add a hook to set temporary settings before cache operations
        Cache::macro('getCompressionSettings', function () {
            return $this->compressionSettings ?? null;
        });

        Cache::macro('clearCompressionSettings', function () {
            $this->compressionSettings = null;
        });
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('cache-compress')
            ->hasConfigFile()
            ->hasCommand(CacheCompressCommand::class);
    }
}

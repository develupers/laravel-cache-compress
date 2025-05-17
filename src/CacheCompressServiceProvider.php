<?php

namespace Develupers\CacheCompress;

use Develupers\CacheCompress\Commands\CacheCompressCommand;
use Develupers\CacheCompress\Store\CustomCacheManager;
use Develupers\CacheCompress\Store\CustomCacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CacheCompressServiceProvider extends PackageServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CacheCompress::class);

        // Bind our custom cache manager
        $this->app->singleton('cache', function ($app) {
            return new CustomCacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the compression macro - These will target CustomCacheRepository
        Cache::macro('compress', function (bool $enabled = true, ?int $level = null) {
            if ($this instanceof CustomCacheRepository) {
                $this->setCompressionSetting('enabled', $enabled);
                $this->setCompressionSetting('level', $level ?? config('cache-compress.compression_level', 6));
            }
            return $this;
        });

        Cache::macro('compressionLevel', function (int $level) {
            if ($this instanceof CustomCacheRepository) {
                $this->setCompressionSetting('level', $level);
                if (!isset($this->compressionSettings) || !array_key_exists('enabled', $this->compressionSettings)) {
                    $this->setCompressionSetting('enabled', config('cache-compress.enabled', true));
                }
            }
            return $this;
        });
        
        // The getCompressionSettings and clearCompressionSettings macros might need adjustment
        // if CustomCacheRepository doesn't directly expose compressionSettings.
        // For now, assuming it will have a way to get/clear these or we'll adjust later.
        Cache::macro('getCompressionSettings', function () {
            if ($this instanceof CustomCacheRepository) {
                return $this->getCompressionSettingsForMacro();
            }
            return null;
        });

        Cache::macro('clearCompressionSettings', function () {
            if ($this instanceof CustomCacheRepository) {
                $this->clearCompressionSettingsForMacro();
            }
            return $this;
        });
        
        // Defer parent::boot() until the application is fully booted
        $this->app->booted(function () {
            // Check if parent has a boot method, just in case of future refactors of Spatie's package
            if (method_exists(get_parent_class($this), 'boot')) {
                 parent::boot();
            }
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
            ->name('laravel-cache-compress')
            ->hasConfigFile()
            ->hasCommand(CacheCompressCommand::class);
    }
}

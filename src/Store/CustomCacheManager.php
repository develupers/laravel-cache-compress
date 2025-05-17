<?php

namespace Develupers\CacheCompress\Store;

use Develupers\CacheCompress\CacheCompress;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository as IlluminateRepository;
use Illuminate\Contracts\Cache\Store as StoreContract;
use Illuminate\Support\Str;

class CustomCacheManager extends CacheManager
{
    /**
     * Create a new cache repository with the given implementation.
     */
    protected function repository(StoreContract $store): IlluminateRepository
    {
        $driverName = $this->determineDriverName($store);

        $repository = new CustomCacheRepository(
            $store,
            $this->app->make(CacheCompress::class),
            $driverName
        );

        if ($this->app->bound('events')) {
            $repository->setEventDispatcher($this->app['events']);
        }

        return $repository;
    }

    /**
     * Determine the driver name from the store instance.
     */
    protected function determineDriverName(StoreContract $store): string
    {
        if ($store instanceof \Illuminate\Cache\RedisStore) {
            return 'redis';
        }
        if ($store instanceof \Illuminate\Cache\MemcachedStore) {
            return 'memcached';
        }
        if ($store instanceof \Illuminate\Cache\FileStore) {
            return 'file';
        }
        if ($store instanceof \Illuminate\Cache\DatabaseStore) {
            return 'database';
        }
        if ($store instanceof \Illuminate\Cache\ArrayStore) {
            return 'array';
        }
        if ($store instanceof \Illuminate\Cache\DynamoDbStore) {
            return 'dynamodb';
        }

        // Check for Laravel MongoDB cache store (mongodb/laravel-mongodb package)
        $mongoStoreClass = 'MongoDB\Laravel\Cache\MongoStore';
        if (class_exists($mongoStoreClass) && $store instanceof $mongoStoreClass) {
            return 'mongodb';
        }

        // Fallback for older Jenssegers MongoDB cache store (if still relevant for some users)
        $jenssegersMongoStoreClass = 'Jenssegers\Mongodb\Cache\MongoStore';
        if (class_exists($jenssegersMongoStoreClass) && $store instanceof $jenssegersMongoStoreClass) {
            return 'mongodb';
        }

        $className = class_basename(get_class($store));
        if (Str::endsWith($className, 'Store')) {
            return Str::snake(substr($className, 0, -5));
        }

        return Str::snake($className);
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     *
     * @throws \InvalidArgumentException
     */
    public function store($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();
        $storeInstance = $this->resolve($name); // This will call our overridden createXDriver methods or parent's resolve

        // Instead of calling repository() directly here without the driver name,
        // we need to ensure our CustomCacheRepository gets the correct driver name.
        // The `resolve` method or `createXDriver` methods are better places to instantiate CustomCacheRepository
        // with the correct driver name from the $name parameter or its config.

        // The parent's store() method eventually calls 'repository' with the driver instance.
        // We need to make sure that when 'repository' is called, it has access to the driver *name*.

        // Let's override createDrive instead to ensure we pass the driver name.
        return parent::store($name); // This will eventually call our overridden 'repository' method
        // if we correctly intercept the store creation process.
    }

    /**
     * Create an instance of the driver.
     *
     * @param  string  $driverName
     * @return mixed
     */
    protected function createDriver($driverName)
    {
        // Get the actual store instance (e.g., RedisStore, FileStore)
        $store = parent::createDriver($driverName);

        // Get the driver string (e.g., 'redis', 'file') from the config for this store name
        $config = $this->getConfig($driverName);
        $actualDriver = $config['driver'] ?? $driverName; // Fallback to driverName if 'driver' key isn't in the specific store's config

        $repository = new CustomCacheRepository(
            $store,
            $this->app->make(CacheCompress::class),
            $actualDriver // Pass the actual driver string
        );

        if ($this->app->bound('events')) {
            $repository->setEventDispatcher($this->app['events']);
        }

        return $repository;
    }
}

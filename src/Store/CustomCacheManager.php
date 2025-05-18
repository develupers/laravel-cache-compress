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
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    public function repository(StoreContract $store, array $config = [])
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
        
        $storeName = $config['name'] ?? $driverName; 
        if (method_exists($repository, 'setStoreName')) {
            $repository->setStoreName($storeName);
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

        $mongoStoreClass = 'MongoDB\Laravel\Cache\MongoStore';
        if (class_exists($mongoStoreClass) && $store instanceof $mongoStoreClass) {
            return 'mongodb';
        }

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
}

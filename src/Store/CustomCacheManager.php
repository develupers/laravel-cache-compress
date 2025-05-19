<?php

namespace Develupers\CacheCompress\Store;

use Develupers\CacheCompress\CacheCompress;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Store as StoreContract;
use Illuminate\Support\Str;
use MongoDB\Laravel\Cache\MongoStore;

class CustomCacheManager extends CacheManager
{
    /**
     * Create a new cache repository with the given implementation.
     *
     * @return CustomCacheRepository
     */
    public function repository(StoreContract $store, array $config = []): CustomCacheRepository
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
        // Standard Laravel Cache Stores
        if ($store instanceof RedisStore) {
            return 'redis';
        }
        if ($store instanceof MemcachedStore) {
            return 'memcached';
        }
        if ($store instanceof FileStore) {
            return 'file';
        }
        if ($store instanceof DatabaseStore) {
            return 'database';
        }
        if ($store instanceof ArrayStore) {
            return 'array';
        }
        if ($store instanceof DynamoDbStore) {
            return 'dynamodb';
        }

        // Check for MongoDB store only if the package is installed
        if ($this->hasMongoDbSupport() && $this->isMongoDbStore($store)) {
            return 'mongodb';
        }

        $className = class_basename(get_class($store));
        if (Str::endsWith($className, 'Store')) {
            return Str::snake(substr($className, 0, -5));
        }

        return Str::snake($className);
    }

    /**
     * Create an instance of the MongoDB cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Cache\Store
     */
    protected function createMongodbDriver(array $config)
    {
        if (! $this->hasMongoDbSupport()) {
            throw new \RuntimeException(
                'MongoDB cache driver requires mongodb/laravel-mongodb package. ' .
                'Please install it using: `composer require mongodb/laravel-mongodb`'
            );
        }

        try {
            $connection = $config['connection'] ?? 'mongodb';
            
            // Get the MongoDB connection instance instead of the database
            $mongoConnection = $this->app['db']->connection($connection);
            
            if (! $mongoConnection) {
                throw new \RuntimeException("Could not establish MongoDB connection for cache driver.");
            }

            // Get the lock connection if specified, otherwise use the same connection
            $lockConnection = isset($config['lock_connection']) 
                ? $this->app['db']->connection($config['lock_connection'])
                : $mongoConnection;

            return new MongoStore(
                $mongoConnection,
                $config['table'] ?? 'cache',
                $config['prefix'] ?? '',
                $lockConnection
            );
        } catch (\Exception $e) {
            // Log the error for debugging
            if ($this->app->bound('log')) {
                $this->app['log']->error('MongoDB cache driver error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'config' => $config
                ]);
            }
            
            throw new \RuntimeException(
                'Failed to create MongoDB cache driver: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Check if MongoDB support is available.
     *
     * @return bool
     */
    protected function hasMongoDbSupport(): bool
    {
        return class_exists('MongoDB\Laravel\Cache\MongoStore');
    }

    /**
     * Check if the given store is a MongoDB store.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return bool
     */
    protected function isMongoDbStore(StoreContract $store): bool
    {
        return $this->hasMongoDbSupport() && $store instanceof \MongoDB\Laravel\Cache\MongoStore;
    }
}

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
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class CustomCacheManager extends CacheManager
{
    use Macroable;

    /**
     * Create a new cache repository with the given implementation.
     *
     * @throws BindingResolutionException
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

        // Check for MongoDB store
        if (class_exists('MongoDB\Laravel\Cache\MongoStore') && $store instanceof \MongoDB\Laravel\Cache\MongoStore) {
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
     */
    protected function createMongodbDriver(array $config): CustomCacheRepository
    {
        if (! class_exists('MongoDB\Laravel\Cache\MongoStore')) {
            throw new \RuntimeException('MongoDB cache driver requires mongodb/laravel-mongodb package.');
        }

        try {
            $connection = $config['connection'] ?? 'mongodb';

            // Get the MongoDB connection instance instead of the database
            $mongoConnection = $this->app['db']->connection($connection);

            if (! $mongoConnection) {
                throw new \RuntimeException('Could not establish MongoDB connection for cache driver.');
            }

            // Get the lock connection if specified, otherwise use the same connection
            $lockConnection = isset($config['lock_connection'])
                ? $this->app['db']->connection($config['lock_connection'])
                : $mongoConnection;

            $store = new MacroableMongoStore(
                $mongoConnection,
                $config['table'] ?? 'cache',
                $config['prefix'] ?? '',
                $lockConnection
            );

            return $this->repository($store);
        } catch (\Exception $e) {
            // Log the error for debugging
            if ($this->app->bound('log')) {
                $this->app['log']->error('MongoDB cache driver error: '.$e->getMessage(), [
                    'exception' => $e,
                    'config' => $config,
                ]);
            }

            throw new \RuntimeException(
                'Failed to create MongoDB cache driver: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}

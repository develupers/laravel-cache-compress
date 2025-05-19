<?php

namespace Develupers\CacheCompress;

use Develupers\CacheCompress\Compressors\DefaultStoreCompressor;
use Develupers\CacheCompress\Compressors\MongoDBStoreCompressor;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;

class CacheCompressManager
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * Create a new cache compress manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a compressed cache repository for the given driver.
     */
    public function store(?string $name = null): CompressedCacheRepository
    {
        $store = Cache::store($name);
        $storeInstance = $store->getStore();
        $storeClass = get_class($storeInstance);

        // Determine which compressor to use based on the store class
        $compressor = $this->getCompressorForStore($storeClass);

        return new CompressedCacheRepository($store, $compressor);
    }

    /**
     * Get the appropriate compressor for the given store class.
     */
    protected function getCompressorForStore(string $storeClass)
    {
        // Check if it's MongoDB store
        if (
            class_exists('MongoDB\Laravel\Cache\MongoStore') &&
            is_a($storeClass, 'MongoDB\Laravel\Cache\MongoStore', true)
        ) {
            return $this->app->make(MongoDBStoreCompressor::class);
        }

        // For all other stores, use the default compressor
        return $this->app->make(DefaultStoreCompressor::class);
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}

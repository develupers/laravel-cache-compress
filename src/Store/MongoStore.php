<?php

namespace Develupers\CacheCompress\Store;

use Illuminate\Cache\Store;
use Illuminate\Contracts\Cache\Store as StoreContract;
use MongoDB\Laravel\Cache\MongoStore as BaseMongoStore;
use MongoDB\Laravel\Connection;

class MongoStore extends Store implements StoreContract
{
    /**
     * The MongoDB store instance.
     *
     * @var \MongoDB\Laravel\Cache\MongoStore
     */
    protected $store;

    /**
     * Create a new MongoDB store.
     *
     * @return void
     */
    public function __construct(Connection $connection, string $table = 'cache', string $prefix = '', ?Connection $lockConnection = null)
    {
        $this->store = new BaseMongoStore($connection, $table, $prefix, $lockConnection);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->store->get($key);
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return $this->store->put($key, $value, $seconds);
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        return $this->store->putMany($values, $seconds);
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * @return array
     */
    public function many(array $keys)
    {
        return $this->store->many($keys);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        return $this->store->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->store->decrement($key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->store->forever($key, $value);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return $this->store->forget($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        return $this->store->flush();
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->store->getPrefix();
    }
}

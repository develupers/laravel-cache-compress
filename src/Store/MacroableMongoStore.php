<?php

namespace Develupers\CacheCompress\Store;

use Develupers\CacheCompress\CacheCompress;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Traits\Macroable;
use MongoDB\Laravel\Cache\MongoStore;

class MacroableMongoStore implements Store
{
    use Macroable;

    /**
     * The MongoDB store instance.
     *
     * @var \MongoDB\Laravel\Cache\MongoStore
     */
    protected $store;

    /**
     * Create a new MongoDB cache store instance.
     *
     * @param  \MongoDB\Laravel\Connection  $connection
     * @param  string  $table
     * @param  string  $prefix
     * @param  \MongoDB\Laravel\Connection|null  $lockConnection
     * @return void
     */
    public function __construct($connection, $table = 'cache', $prefix = '', $lockConnection = null)
    {
        $this->store = new MongoStore($connection, $table, $prefix, $lockConnection);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->store->get($key);

        if ($value !== null && is_string($value) && $this->shouldEncode($value)) {
            return $this->decodeValue($value);
        }

        return $value;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * @return array
     */
    public function many(array $keys)
    {
        $values = $this->store->many($keys);

        return array_map(function ($value) {
            if ($value !== null && is_string($value) && $this->shouldEncode($value)) {
                return $this->decodeValue($value);
            }

            return $value;
        }, $values);
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
        if (is_string($value) && $this->shouldEncode($value)) {
            $value = $this->encodeValue($value);
        }

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
        $encodedValues = [];
        foreach ($values as $key => $value) {
            if (is_string($value) && $this->shouldEncode($value)) {
                $encodedValues[$key] = $this->encodeValue($value);
            } else {
                $encodedValues[$key] = $value;
            }
        }

        return $this->store->putMany($encodedValues, $seconds);
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
        if (is_string($value) && $this->shouldEncode($value)) {
            $value = $this->encodeValue($value);
        }

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

    /**
     * Get the underlying MongoDB store instance.
     *
     * @return \MongoDB\Laravel\Cache\MongoStore
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Check if a value should be encoded for MongoDB storage.
     */
    protected function shouldEncode(string $value): bool
    {
        $settings = app(CacheCompress::class)->getSettings();

        return $settings['enabled'] && ! mb_check_encoding($value, 'UTF-8');
    }

    /**
     * Encode a value for MongoDB storage.
     */
    protected function encodeValue(string $value): string
    {
        return base64_encode($value);
    }

    /**
     * Decode a value from MongoDB storage.
     */
    protected function decodeValue(string $value): string
    {
        return base64_decode($value);
    }
}

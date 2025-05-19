<?php

namespace Develupers\CacheCompress;

use Closure;
use Develupers\CacheCompress\Contracts\StoreCompressor;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as RepositoryContract;
use Illuminate\Support\Facades\Config;

class CompressedCacheRepository implements RepositoryContract
{
    /**
     * The underlying cache repository.
     */
    protected Repository $repository;

    /**
     * The compressor for this cache store.
     */
    protected StoreCompressor $compressor;

    /**
     * Whether compression is enabled.
     */
    protected bool $compressionEnabled;

    /**
     * Create a new compressed cache repository.
     */
    public function __construct(Repository $repository, StoreCompressor $compressor)
    {
        $this->repository = $repository;
        $this->compressor = $compressor;
        $this->compressionEnabled = Config::get('cache-compress.enabled', true);
    }

    /**
     * Enable or disable compression for this repository instance.
     */
    public function compress(bool $enabled = true): self
    {
        $this->compressionEnabled = $enabled;

        return $this;
    }

    /**
     * Get an item from the cache.
     *
     * @param  mixed  $key
     */
    public function get($key, mixed $default = null): mixed
    {
        $value = $this->repository->get($key, $default);

        // If compression is disabled or value is default/null, return as is
        if (! $this->compressionEnabled || $value === $default || $value === null) {
            return $value;
        }

        // Only try to decompress if it's a string
        if (is_string($value)) {
            return $this->compressor->decompress($value);
        }

        return $value;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function put($key, $value, $ttl = null): bool
    {
        if ($this->compressionEnabled) {
            $value = $this->compressor->compress($value);
        }

        return $this->repository->put($key, $value, $ttl);
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function add($key, $value, $ttl = null): bool
    {
        if ($this->compressionEnabled) {
            $value = $this->compressor->compress($value);
        }

        return $this->repository->add($key, $value, $ttl);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->repository->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->repository->decrement($key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function forever($key, $value): bool
    {
        if ($this->compressionEnabled) {
            $value = $this->compressor->compress($value);
        }

        return $this->repository->forever($key, $value);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function remember($key, $ttl, Closure $callback): mixed
    {
        // We'll handle the caching manually since we need to compress before storing
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param  string  $key
     */
    public function rememberForever($key, Closure $callback): mixed
    {
        // We'll handle the caching manually since we need to compress before storing
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->forever($key, $value);

        return $value;
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     */
    public function forget($key): bool
    {
        return $this->repository->forget($key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        return $this->repository->flush();
    }

    /**
     * Get the cache key prefix.
     */
    public function getPrefix(): string
    {
        return $this->repository->getPrefix();
    }

    /**
     * Get the underlying cache repository.
     */
    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * Get multiple items from the cache.
     */
    public function many(array $keys): array
    {
        $values = $this->repository->many($keys);

        if (! $this->compressionEnabled) {
            return $values;
        }

        // Decompress each value in the array
        foreach ($values as $key => $value) {
            if (is_string($value)) {
                $values[$key] = $this->compressor->decompress($value);
            }
        }

        return $values;
    }

    /**
     * Store multiple items in the cache.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function putMany(array $values, $ttl = null): bool
    {
        if ($this->compressionEnabled) {
            foreach ($values as $key => $value) {
                $values[$key] = $this->compressor->compress($value);
            }
        }

        return $this->repository->putMany($values, $ttl);
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param  string  $key
     * @param  mixed  $default
     */
    public function pull($key, $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    /**
     * Get the cache store implementation.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getStore()
    {
        return $this->repository->getStore();
    }

    /**
     * Fire an event for this cache operation.
     *
     * @param  string  $event
     * @param  array  $payload
     */
    protected function event($event): void
    {
        $this->repository->event($event);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key
     */
    public function has($key): bool
    {
        return $this->repository->has($key);
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->put($key, $value, $ttl);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     */
    public function delete($key): bool
    {
        return $this->forget($key);
    }

    /**
     * Remove all items from the cache.
     */
    public function clear(): bool
    {
        return $this->flush();
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  iterable  $keys
     * @param  mixed  $default
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return $this->many(is_array($keys) ? $keys : iterator_to_array($keys));
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  iterable  $values
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->putMany(is_array($values) ? $values : iterator_to_array($values), $ttl);
    }

    /**
     * Remove multiple items from the cache.
     *
     * @param  iterable  $keys
     */
    public function deleteMultiple($keys): bool
    {
        $result = true;

        foreach (is_array($keys) ? $keys : iterator_to_array($keys) as $key) {
            if (! $this->forget($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @param  \Closure  $callback
     */
    public function sear($key, $callback): mixed
    {
        return $this->rememberForever($key, $callback);
    }
}

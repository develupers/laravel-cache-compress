<?php

namespace Develupers\CacheCompress\Store;

use Closure;
use Develupers\CacheCompress\CacheCompress;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Store as StoreContract;
use Illuminate\Support\Facades\Config; // For default compression settings

class CustomCacheRepository extends Repository
{
    /**
     * The underlying cache store instance.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    // Already defined in parent as protected $store;

    /**
     * The CacheCompress instance for compression/decompression.
     */
    protected CacheCompress $compressor;

    /**
     * The name of the cache driver (e.g., 'redis', 'file', 'mongodb').
     */
    protected string $driverName;

    /**
     * Per-call compression settings, populated by macros.
     *
     * @var ?array{enabled: bool, level: int}
     */
    public ?array $compressionSettings = null; // Made public for macros to easily access/modify

    /**
     * Create a new cache repository instance.
     *
     * @return void
     */
    public function __construct(StoreContract $store, CacheCompress $compressor, string $driverName)
    {
        parent::__construct($store);
        $this->compressor = $compressor;
        $this->driverName = $driverName;
        // Note: default_ttl is handled by parent Repository constructor if needed
    }

    /**
     * Get the current compression settings for an operation.
     */
    public function getCurrentCompressionConfiguration(): array
    {
        if ($this->compressionSettings !== null) {
            $settings = [
                'enabled' => $this->compressionSettings['enabled'] ?? Config::get('cache-compress.enabled', true),
                'level' => $this->compressionSettings['level'] ?? Config::get('cache-compress.compression_level', 6),
            ];
        } else {
            $settings = [
                'enabled' => Config::get('cache-compress.enabled', true),
                'level' => Config::get('cache-compress.compression_level', 6),
            ];
        }
        // After getting settings for current operation, clear them for the next call
        // This makes the macro settings truly per-call for single-item operations.
        // For multi-item operations (many, putMany), this means settings apply to the first item.
        // If a different behavior is desired for multi-item ops, this clearing needs to be more nuanced.
        $this->clearCompressionSettingsForMacro();

        return $settings;
    }

    // --- Macro support methods ---
    public function setCompressionSetting(string $key, $value): void
    {
        if ($this->compressionSettings === null) {
            $this->compressionSettings = [
                'enabled' => Config::get('cache-compress.enabled', true), // Default enable status
                'level' => Config::get('cache-compress.compression_level', 6), // Default level
            ];
        }
        $this->compressionSettings[$key] = $value;
    }

    public function getCompressionSettingsForMacro(): ?array
    {
        return $this->compressionSettings;
    }

    public function clearCompressionSettingsForMacro(): void
    {
        $this->compressionSettings = null;
    }
    // --- End Macro support methods ---

    protected function executeWithCompressionConfiguration(Closure $callback, bool $isReadOperation = false)
    {
        // Get the configuration for this specific operation.
        // getCurrentCompressionConfiguration also handles clearing the macro settings for this repository instance.
        $config = $this->getCurrentCompressionConfiguration();

        CacheCompress::setTemporarySettings($config);
        try {
            // Pass the resolved config to the callback if it needs it
            $result = $callback($config);
        } finally {
            CacheCompress::setTemporarySettings(null); // Reset for next independent operation
            // No longer need to call $this->clearCompressionSettingsForMacro(); here as it's done in getCurrentCompressionConfiguration
        }

        return $result;
    }

    protected function getMinutes($duration) // Helper from parent, make it accessible if needed
    {
        if ($duration === null) {
            return null;
        }
        $seconds = $this->getSeconds($duration);

        return $seconds === 0 ? 0 : (int) ceil($seconds / 60);
    }

    /**
     * Processes a raw value retrieved from the cache store, handling decompression and unserialization.
     *
     * @param  string  $storedValue  The raw string value from the cache.
     * @param  array  $operationConfig  The compression configuration for the current operation.
     * @return mixed The processed (decompressed and/or unserialized) value.
     */
    protected function _processRetrievedValue(string $storedValue, array $operationConfig): mixed
    {
        $data = null;
        if (! $operationConfig['enabled']) {
            try {
                $data = unserialize($storedValue);
            } catch (\Throwable $e) {
                $data = $storedValue; // Return raw if not unserializable
            }
        } else {
            try {
                $data = $this->compressor->decompress($storedValue, $this->driverName);
            } catch (\Throwable $e) {
                // Decompression failed, try unserialize as fallback
                try {
                    $data = unserialize($storedValue);
                } catch (\Throwable $e2) {
                    $data = $storedValue; // Return raw if all fails
                }
            }
        }

        return $data;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @param  mixed  $default
     */
    public function get($key, $default = null): mixed
    {
        return $this->executeWithCompressionConfiguration(function ($config) use ($key, $default) {
            $value = $this->store->get($this->itemKey($key));

            if (is_null($value)) {
                $this->event(new CacheMissed($key, []));

                return $default instanceof Closure ? $default() : $default;
            }

            // If value is not a string, it's unlikely to be compressed or serialized by us, return as is.
            if (! is_string($value)) {
                $this->event(new CacheHit($key, $value, []));

                return $value;
            }

            $data = $this->_processRetrievedValue($value, $config);

            $this->event(new CacheHit($key, $data, []));

            return $data;
        }, true);
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
        return $this->executeWithCompressionConfiguration(function ($config) use ($key, $value, $ttl) {
            $serializedValue = serialize($value);
            if (! $config['enabled']) {
                $processedValue = $serializedValue;
            } else {
                $processedValue = $this->compressor->compress($value, $this->driverName);
            }

            $result = $this->store->put($this->itemKey($key), $processedValue, $this->getSeconds($ttl));
            if ($result) {
                // Event should ideally get the original value, but KeyWritten takes the stored value.
                // For now, pass what was written, as per typical KeyWritten usage.
                $this->event(new KeyWritten($key, $processedValue, $this->getMinutes($ttl), []));
            }

            return $result;
        });
    }

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function add($key, $value, $ttl = null): bool
    {
        return $this->executeWithCompressionConfiguration(function ($config) use ($key, $value, $ttl) {
            $serializedValue = serialize($value);
            if (! $config['enabled']) {
                $processedValue = $serializedValue;
            } else {
                $processedValue = $this->compressor->compress($value, $this->driverName);
            }

            $result = $this->store->add($this->itemKey($key), $processedValue, $this->getSeconds($ttl));
            if ($result) {
                $this->event(new KeyWritten($key, $processedValue, $this->getMinutes($ttl), []));
            }

            return $result;
        });
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function forever($key, $value): bool
    {
        return $this->executeWithCompressionConfiguration(function ($config) use ($key, $value) {
            if (! $config['enabled']) {
                $processedValue = serialize($value);
            } else {
                $processedValue = $this->compressor->compress($value, $this->driverName);
            }
            $result = $this->store->forever($this->itemKey($key), $processedValue);
            if ($result) {
                $this->event(new KeyWritten($key, $processedValue, null, []));
            }

            return $result;
        });
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function remember($key, $ttl, Closure $callback): mixed
    {
        $value = $this->get($key);
        if (! is_null($value)) {
            return $value;
        }
        $result = $callback();
        $this->put($key, $result, $ttl);

        return $result;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param  string  $key
     */
    public function rememberForever($key, Closure $callback): mixed
    {
        $value = $this->get($key);
        if (! is_null($value)) {
            return $value;
        }
        $result = $callback();
        $this->forever($key, $result);

        return $result;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     */
    public function many(array $keys): array
    {
        return $this->executeWithCompressionConfiguration(function ($config) use ($keys) {
            $prefixedKeys = array_map([$this, 'itemKey'], $keys);
            $rawStoreValues = $this->store->many($prefixedKeys);

            $results = [];
            foreach ($keys as $key) {
                $prefixedKey = $this->itemKey($key);
                $value = $rawStoreValues[$prefixedKey] ?? null;

                if (is_null($value)) {
                    $this->event(new CacheMissed($key, []));
                    $results[$key] = null;

                    continue;
                }

                // If value is not a string, it's unlikely to be compressed or serialized by us, return as is.
                if (! is_string($value)) {
                    $this->event(new CacheHit($key, $value, []));
                    $results[$key] = $value;

                    continue;
                }

                $data = $this->_processRetrievedValue($value, $config);

                $this->event(new CacheHit($key, $data, []));
                $results[$key] = $data;
            }

            return $results;
        }, true);
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function putMany(array $values, $ttl = null): bool
    {
        return $this->executeWithCompressionConfiguration(function ($config) use ($values, $ttl) {
            $processedStoreValues = [];
            $eventValues = []; // For firing events with correct key

            foreach ($values as $key => $value) {
                if (! $config['enabled']) {
                    $processed = serialize($value);
                } else {
                    $processed = $this->compressor->compress($value, $this->driverName);
                }
                $processedStoreValues[$this->itemKey($key)] = $processed;
                $eventValues[$key] = $processed; // Store with original key for event
            }

            $result = $this->store->putMany($processedStoreValues, $this->getSeconds($ttl));
            if ($result) {
                foreach ($eventValues as $key => $processedValue) {
                    $this->event(new KeyWritten($key, $processedValue, $this->getMinutes($ttl), []));
                }
            }

            return $result;
        });
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param  string  $key
     * @param  mixed  $default
     */
    public function pull($key, $default = null): mixed
    {
        return parent::pull($key, $default);
    }
}

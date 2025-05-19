<?php

namespace Develupers\CacheCompress\Facades;

use Develupers\CacheCompress\CompressedCacheRepository;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CompressedCacheRepository store(string $name = null)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool put(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool add(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool forever(string $key, mixed $value)
 * @method static mixed remember(string $key, \DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static bool forget(string $key)
 * @method static bool flush()
 * @method static array many(array $keys)
 * @method static bool putMany(array $values, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static bool has(string $key)
 *
 * @see \Develupers\CacheCompress\CacheCompressManager
 */
class CacheCompress extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cache.compress';
    }
}

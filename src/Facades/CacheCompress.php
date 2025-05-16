<?php

namespace Develupers\CacheCompress\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string compress($value, string $driver)
 * @method static mixed decompress(string $value, string $driver)
 *
 * @see \Develupers\CacheCompress\CompressCache
 */
class CacheCompress extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cache-compress';
    }
} 
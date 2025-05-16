<?php

namespace Develupers\CacheCompress\Facades;

use Develupers\CacheCompress\CompressCache;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Develupers\CacheCompress\CompressCache
 */
class Skeleton extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CompressCache::class;
    }
}

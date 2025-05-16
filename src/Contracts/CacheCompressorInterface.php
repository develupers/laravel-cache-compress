<?php

namespace Develupers\CacheCompress\Contracts;

interface CacheCompressorInterface
{
    /**
     * Compress the given value for cache storage
     *
     * @param  mixed  $value
     */
    public function compress($value, string $driver): string;

    /**
     * Decompress the given value from cache storage
     *
     * @return mixed
     */
    public function decompress(string $value, string $driver);
}

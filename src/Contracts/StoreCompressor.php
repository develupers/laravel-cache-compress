<?php

namespace Develupers\CacheCompress\Contracts;

interface StoreCompressor
{
    /**
     * Compress a value for storage
     *
     * @param  mixed  $value  The value to compress
     * @return string The compressed value
     */
    public function compress($value): string;

    /**
     * Decompress a value from storage
     *
     * @param  string  $value  The compressed value
     * @return mixed The decompressed value
     */
    public function decompress(string $value);
}

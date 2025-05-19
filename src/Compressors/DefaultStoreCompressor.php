<?php

namespace Develupers\CacheCompress\Compressors;

use Develupers\CacheCompress\Contracts\StoreCompressor;
use Illuminate\Support\Facades\Config;

class DefaultStoreCompressor implements StoreCompressor
{
    /**
     * Get the current compression level
     */
    protected function getCompressionLevel(): int
    {
        return Config::get('cache-compress.compression_level', 6);
    }

    /**
     * Compress a value for storage
     *
     * @param  mixed  $value  The value to compress
     * @return string The compressed value
     */
    public function compress($value): string
    {
        return gzdeflate(
            serialize($value),
            $this->getCompressionLevel()
        );
    }

    /**
     * Decompress a value from storage
     *
     * @param  string  $value  The compressed value
     * @return mixed The decompressed value
     */
    public function decompress(string $value)
    {
        try {
            return unserialize(gzinflate($value));
        } catch (\Throwable $e) {
            // If decompression fails, try treating as a regular serialized string
            try {
                return unserialize($value);
            } catch (\Throwable $innerE) {
                // If all fails, return the raw value
                return $value;
            }
        }
    }
}

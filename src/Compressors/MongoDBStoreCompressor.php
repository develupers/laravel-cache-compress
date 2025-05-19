<?php

namespace Develupers\CacheCompress\Compressors;

use Develupers\CacheCompress\Contracts\StoreCompressor;
use Illuminate\Support\Facades\Config;

class MongoDBStoreCompressor implements StoreCompressor
{
    /**
     * Get the current compression level
     */
    protected function getCompressionLevel(): int
    {
        return Config::get('cache-compress.compression_level', 6);
    }

    /**
     * Compress a value for storage, with base64 encoding for MongoDB binary safety
     *
     * @param  mixed  $value  The value to compress
     * @return string The compressed and base64-encoded value
     */
    public function compress($value): string
    {
        // MongoDB requires UTF-8 safe strings, so we base64 encode after compression
        return base64_encode(
            gzdeflate(
                serialize($value),
                $this->getCompressionLevel()
            )
        );
    }

    /**
     * Decompress a value from storage
     *
     * @param  string  $value  The base64-encoded compressed value
     * @return mixed The decompressed value
     */
    public function decompress(string $value)
    {
        try {
            // First decode base64, then decompress
            return unserialize(gzinflate(base64_decode($value)));
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

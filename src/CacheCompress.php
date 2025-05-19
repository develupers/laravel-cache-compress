<?php

namespace Develupers\CacheCompress;

use Illuminate\Support\Facades\Config;

class CacheCompress
{
    /**
     * Get the enabled status from configuration.
     */
    public function isEnabled(): bool
    {
        return Config::get('cache-compress.enabled', true);
    }

    /**
     * Get the compression level from configuration.
     */
    public function getCompressionLevel(): int
    {
        return Config::get('cache-compress.compression_level', 6);
    }
}

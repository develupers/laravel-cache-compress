<?php

namespace Develupers\CacheCompress;

use Develupers\CacheCompress\Contracts\CacheCompressorInterface;
use Illuminate\Support\Facades\Config;

class CacheCompress implements CacheCompressorInterface
{
    /**
     * The temporary compression settings.
     *
     * @var array|null
     */
    protected static $temporarySettings = null;

    /**
     * Set temporary compression settings.
     *
     * @return void
     */
    public static function setTemporarySettings(?array $settings)
    {
        static::$temporarySettings = $settings;
    }

    /**
     * Get the current compression settings.
     */
    public function getSettings(): array
    {
        if (static::$temporarySettings !== null) {
            return static::$temporarySettings;
        }

        return [
            'enabled' => Config::get('cache-compress.enabled', true),
            'level' => Config::get('cache-compress.compression_level', 6),
        ];
    }

    /**
     * Compress the given value for cache storage
     *
     * @param  mixed  $value
     */
    public function compress($value, string $driver): string
    {
        $settings = $this->getSettings();

        if (! $settings['enabled']) {
            return serialize($value);
        }

        return gzdeflate(
            serialize($value),
            $settings['level']
        );
    }

    /**
     * Decompress the given value from cache storage
     *
     * @return mixed
     */
    public function decompress(string $value, string $driver)
    {
        $settings = $this->getSettings();

        if (! $settings['enabled']) {
            return unserialize($value);
        }

        return unserialize(gzinflate($value));
    }
}

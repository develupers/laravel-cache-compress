<?php

namespace Develupers\CacheCompress\Traits;

trait HasCompression
{
    /**
     * The compression settings for this cache operation.
     *
     * @var array|null
     */
    protected $compressionSettings = null;

    /**
     * Enable or disable compression for this cache operation.
     *
     * @param bool $enabled
     * @param int|null $level
     * @return $this
     */
    public function compress(bool $enabled = true, ?int $level = null)
    {
        $this->compressionSettings = [
            'enabled' => $enabled,
            'level' => $level ?? config('cache-compress.compression_level', 6)
        ];

        return $this;
    }

    /**
     * Set the compression level for this cache operation.
     *
     * @param int $level
     * @return $this
     */
    public function compressionLevel(int $level)
    {
        if (!isset($this->compressionSettings)) {
            $this->compressionSettings = [
                'enabled' => config('cache-compress.enabled', true),
                'level' => $level
            ];
        } else {
            $this->compressionSettings['level'] = $level;
        }

        return $this;
    }

    /**
     * Get the current compression settings.
     *
     * @return array|null
     */
    public function getCompressionSettings()
    {
        return $this->compressionSettings;
    }

    /**
     * Clear the compression settings.
     *
     * @return void
     */
    public function clearCompressionSettings()
    {
        $this->compressionSettings = null;
    }
} 
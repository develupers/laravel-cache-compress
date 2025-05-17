<?php

namespace Develupers\CacheCompress\Listeners;

use Develupers\CacheCompress\CacheCompress;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Support\Facades\Cache;

class DecompressCacheListener
{
    /**
     * @var CacheCompress
     */
    protected $compressor;

    /**
     * Create the event listener.
     */
    public function __construct(CacheCompress $compressor)
    {
        $this->compressor = $compressor;
    }

    /**
     * Handle the event.
     */
    public function handle(CacheHit $event): void
    {
        // Get the driver name from the cache configuration
        $driver = config("cache.stores.{$event->storeName}.driver", $event->storeName);
        $decompressed = $this->compressor->decompress($event->value, $driver);

        // Update the value with the decompressed version
        $event->value = $decompressed;
    }
}

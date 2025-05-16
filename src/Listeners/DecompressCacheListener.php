<?php

namespace Develupers\CacheCompress\Listeners;

use Develupers\CacheCompress\CompressCache;
use Illuminate\Cache\Events\CacheHit;

class DecompressCacheListener
{
    /**
     * @var CompressCache
     */
    protected $compressor;

    /**
     * Create the event listener.
     *
     * @param CompressCache $compressor
     */
    public function __construct(CompressCache $compressor)
    {
        $this->compressor = $compressor;
    }

    /**
     * Handle the event.
     *
     * @param CacheHit $event
     * @return void
     */
    public function handle(CacheHit $event)
    {
        $store = app('cache')->store($event->storeName);
        $driver = method_exists($store->getStore(), 'getDriver')
            ? $store->getStore()->getDriver()
            : $event->storeName;
        $decompressed = $this->compressor->decompress($event->value, $driver);
        
        // Update the value with the decompressed version
        $event->value = $decompressed;
    }
} 
<?php

namespace Develupers\CacheCompress\Listeners;

use Develupers\CacheCompress\CompressCache;
use Illuminate\Cache\Events\KeyWritten;

class CompressCacheListener
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
     * @param KeyWritten $event
     * @return void
     */
    public function handle(KeyWritten $event)
    {
        $store = app('cache')->store($event->storeName);
        $driver = method_exists($store->getStore(), 'getDriver')
            ? $store->getStore()->getDriver()
            : $event->storeName;
        $compressed = $this->compressor->compress($event->value, $driver);
        
        // Update the value with the compressed version
        $event->value = $compressed;
    }
} 
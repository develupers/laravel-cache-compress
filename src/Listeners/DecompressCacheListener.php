<?php

namespace Develupers\CacheCompress\Listeners;

use Develupers\CacheCompress\CompressCache;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Support\Facades\Cache;

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
    public function handle(CacheHit $event): void
    {
        $driver = Cache::getFacadeRoot()->store($event->storeName)->getConfig()['driver'];
        $decompressed = $this->compressor->decompress($event->value, $driver);

        // Update the value with the decompressed version
        $event->value = $decompressed;
    }
}

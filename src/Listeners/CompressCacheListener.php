<?php

namespace Develupers\CacheCompress\Listeners;

use Develupers\CacheCompress\CompressCache;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Cache;

class CompressCacheListener
{
    /**
     * @var CompressCache
     */
    protected $compressor;

    /**
     * Create the event listener.
     */
    public function __construct(CompressCache $compressor)
    {
        $this->compressor = $compressor;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(KeyWritten $event)
    {
        $driver = Cache::getFacadeRoot()->store($event->storeName)->getConfig()['driver'];
        $compressed = $this->compressor->compress($event->value, $driver);

        // Update the value with the compressed version
        $event->value = $compressed;
    }
}

<?php

namespace Develupers\CacheCompress\Listeners;

use Develupers\CacheCompress\CacheCompress;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Cache;

class CompressCacheListener
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

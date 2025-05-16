<?php

namespace Develupers\CacheCompress\Events;

class CacheCompressed
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * @var string
     */
    public $driver;

    /**
     * Create a new event instance.
     *
     * @param mixed $value
     * @param string $driver
     */
    public function __construct($value, string $driver)
    {
        $this->value = $value;
        $this->driver = $driver;
    }
} 
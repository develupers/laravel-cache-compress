<?php

namespace Develupers\CacheCompress\Commands;

use Illuminate\Console\Command;

class CacheCompressCommand extends Command
{
    public $signature = 'skeleton';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}

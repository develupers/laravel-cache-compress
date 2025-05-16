<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Cache Compression
    |--------------------------------------------------------------------------
    |
    | This option controls whether cache compression is enabled.
    | You can disable it by setting this to false.
    |
    */
    'enabled' => env('CACHE_COMPRESS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Compression Level
    |--------------------------------------------------------------------------
    |
    | This option controls the compression level used by gzdeflate.
    | The value must be between 0 and 9, where:
    | 0 = no compression
    | 1 = minimal compression (fastest)
    | 9 = maximum compression (slowest)
    |
    */
    'compression_level' => env('CACHE_COMPRESS_LEVEL', 6),
]; 
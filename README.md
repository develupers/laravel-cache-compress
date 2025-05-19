# Laravel Cache Compress

[![Latest Version on Packagist](https://img.shields.io/packagist/v/develupers/laravel-cache-compress.svg?style=flat-square)](https://packagist.org/packages/develupers/laravel-cache-compress)
[![Total Downloads](https://img.shields.io/packagist/dt/develupers/laravel-cache-compress.svg?style=flat-square)](https://packagist.org/packages/develupers/laravel-cache-compress)

This package provides transparent, on-the-fly compression and decompression for Laravel's cache, reducing storage size for drivers like Redis, Memcached, File, and MongoDB.

## Features

- Automatic compression before caching and decompression after retrieval.
- Supports `gzdeflate` for compression.
- Configurable compression level.
- Handles Base64 encoding for MongoDB to ensure UTF-8 compatibility.
- Per-call control over compression enablement and level using cache macros.
- Compatible with Laravel Cache Tags.
- Supports Laravel 11+ and PHP 8.2+.

## Installation

You can install the package via composer:

```bash
composer require develupers/laravel-cache-compress
```

The package will automatically register itself.

### MongoDB Support

If you plan to use MongoDB as your cache driver, you'll need to install the MongoDB package:

```bash
composer require mongodb/laravel-mongodb
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Develupers\CacheCompress\CacheCompressServiceProvider" --tag="laravel-cache-compress-config"
```

This is the contents of the published config file (`config/cache-compress.php`):

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Enable Cache Compression
    |--------------------------------------------------------------------------
    |
    | This option controls whether cache compression is enabled globally.
    |
    */
    'enabled' => env('CACHE_COMPRESS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Compression Level
    |--------------------------------------------------------------------------
    |
    | This option controls the compression level used by gzdeflate (0-9).
    | 0 = no compression, 1 = fastest, 9 = maximum compression.
    |
    */
    'compression_level' => env('CACHE_COMPRESS_LEVEL', 6),
];
```

## Usage

Once installed, the package works transparently with Laravel's standard `Cache` facade or `cache()` helper. Data will be automatically compressed before being stored and decompressed upon retrieval.

### Standard Cache Operations

```php
use Illuminate\Support\Facades\Cache;

// Value will be compressed before storing
Cache::put('my_key', 'This is a long string that will be compressed.', 600); // 10 Minutes
Cache::store('redis')->put('another_key', ['data' => 'complex'], now()->addHour());

// Value will be decompressed after retrieval
$value = Cache::get('my_key');
$anotherValue = Cache::store('redis')->get('another_key');

// Remember functions also work seamlessly
$complexData = Cache::remember('complex_data_key', 3600, function () {
    return ['user' => 1, 'posts' => ['a', 'b', 'c']];
});
```

### Per-Call Compression Control

You can override the global compression settings for individual cache operations using the provided macros:

```php
// Disable compression for this specific 'put' operation
Cache::compress(false)->put('uncompressed_key', 'This will not be compressed.', 600);

// Enable compression and set a specific level for this operation
Cache::compress(true)->compressionLevel(9)->put('max_compressed_key', 'Highly compressed data.', 600);

// Just set a specific compression level (uses default for enabled status)
Cache::compressionLevel(1)->put('fast_compressed_key', 'Fast compression.', 600);

// These macros also work when retrieving data, affecting how the system attempts to decompress.
// (Though typically decompression settings are symmetric to compression settings at the time of storage)
$value = Cache::compress(false)->get('uncompressed_key');
```

**Note on Macro Usage with Multi-Key Operations:**
When using macros like `compress()` or `compressionLevel()` with multi-key operations such as `Cache::many()` or `Cache::putMany()`, the per-call settings currently apply to the processing of the *first key* within that operation. Subsequent keys in the same `many()` or `putMany()` call will revert to the default compression settings. For consistent per-call settings across all items in a multi-key operation, apply settings individually if needed or rely on global configuration.

## Troubleshooting

### MongoDB Driver Not Supported Error

If you are using MongoDB as a cache driver and encounter an error like `Driver [mongodb] is not supported`, it usually indicates an issue with the order in which Laravel's service providers are loaded and booted. Specifically, the `MongoDB\Laravel\MongoDBServiceProvider` needs to register its cache driver extension *before* this package (or other packages like `spatie/laravel-responsecache`) attempt to resolve the MongoDB cache store.

To resolve this, you may need to manually define the order of service providers in your application:

1.  **Adjust `config/app.php`:**
    Open your `config/app.php` file and find the `providers` array. Ensure that `MongoDB\Laravel\MongoDBServiceProvider` is listed *before* `Develupers\CacheCompress\CacheCompressServiceProvider`:

    ```php
    'providers' => [
        // ... other framework service providers

        MongoDB\Laravel\MongoDBServiceProvider::class,
        Develupers\CacheCompress\CacheCompressServiceProvider::class,

        // ... other application and package service providers
    ],
    ```

2.  **Update `composer.json` to prevent auto-discovery (if necessary):**
    If these packages are also being auto-discovered by Laravel, you should instruct Laravel not to discover them to avoid conflicts with manual registration. Add the following to your `composer.json` file under the `extra.laravel` section:

    ```json
    "extra": {
        "laravel": {
            "dont-discover": [
                "develupers/laravel-cache-compress",
                "mongodb/laravel-mongodb"
            ]
        }
    },
    ```
    After modifying `composer.json`, run `composer dump-autoload` to update the autoloader.

This manual ordering ensures that the MongoDB cache driver is available when the `laravel-cache-compress` package initializes, and when other services (like `spatie/laravel-responsecache` if you use `RESPONSE_CACHE_DRIVER=mongodb`) attempt to use the MongoDB cache store.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Omar Robinson](https://github.com/your-github-username)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

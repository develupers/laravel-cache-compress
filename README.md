# Laravel Cache Compress

[![Latest Version on Packagist](https://img.shields.io/packagist/v/develupers/laravel-cache-compress.svg?style=flat-square)](https://packagist.org/packages/develupers/laravel-cache-compress)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/develupers/laravel-cache-compress/run-tests?label=tests)](https://github.com/develupers/laravel-cache-compress/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/develupers/laravel-cache-compress/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/develupers/laravel-cache-compress/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/develupers/laravel-cache-compress.svg?style=flat-square)](https://packagist.org/packages/develupers/laravel-cache-compress)

A Laravel package that adds compression to your Laravel cache, reducing storage requirements for large cache values.

## Features

- Automatically compresses cache values before storage
- Automatically decompresses values when retrieved
- Compatible with all Laravel cache drivers
- Special handling for MongoDB to ensure UTF-8 compatibility
- Control compression via environment variables or per-call settings
- Compatible with Laravel's Cache Tags

## Requirements

- PHP 8.2+
- Laravel 10|11+
- PHP zlib extension (for compression)

## Installation

You can install the package via composer:

```bash
composer require develupers/laravel-cache-compress
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="cache-compress-config"
```

This is the contents of the published config file:

```php
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
```

## Usage

### Basic Usage

The package provides a `CacheCompress` facade that works just like Laravel's `Cache` facade, but with compression:

```php
use Develupers\CacheCompress\Facades\CacheCompress;

// Store a value in the cache (will be compressed)
CacheCompress::put('key', $largeObject, 60); // 60 minutes

// Retrieve and automatically decompress the value
$value = CacheCompress::get('key');
```

### Specifying a Store

You can specify which cache store to use:

```php
// Use the Redis store
$value = CacheCompress::store('redis')->get('key');

// Store with the file driver
CacheCompress::store('file')->put('key', $value, 60);
```

### Disable Compression for Specific Operations

You can disable compression for specific operations:

```php
// Disable compression for this specific operation
$value = CacheCompress::store('redis')->compress(false)->get('key');

// Re-enable compression for subsequent operations
$value = CacheCompress::store('redis')->compress(true)->get('key');
```

### All Standard Cache Methods Supported

All standard Laravel cache methods are supported:

```php
// Remember pattern
$value = CacheCompress::remember('key', 60, function () {
    return expensive_operation();
});

// Forever
CacheCompress::forever('key', $value);

// Multiple items
$values = CacheCompress::many(['key1', 'key2']);

// Check if exists
if (CacheCompress::has('key')) {
    // ...
}

// Delete
CacheCompress::forget('key');
```

## Environment Variables

You can control compression through environment variables:

```
CACHE_COMPRESS_ENABLED=true
CACHE_COMPRESS_LEVEL=6
```

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

- [Omar Robinson](https://github.com/develupers)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

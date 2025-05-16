# Laravel Cache Compress

A Laravel package that adds compression support to Laravel's cache functionality. This package automatically compresses cache data before storage and decompresses it when retrieved, helping to reduce memory usage and improve performance.

## Requirements

- PHP 8.2+
- Laravel 11+

## Installation

You can install the package via composer:

```bash
composer require develupers/laravel-cache-compress
```

The package will automatically register itself.

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --tag="cache-compress-config"
```

This will create a `config/cache-compress.php` file in your app that you can modify to set your configuration. Also, make sure that you have the following in your `.env` file:

```env
CACHE_COMPRESS_ENABLED=true
CACHE_COMPRESS_LEVEL=6
```

### Configuration Options

- `enabled` (default: `true`): Enable or disable cache compression
- `compression_level` (default: `6`): Set the compression level (0-9)
  - 0 = no compression
  - 1 = minimal compression (fastest)
  - 9 = maximum compression (slowest)

## Usage

The package works automatically with Laravel's cache system. No additional configuration is needed. It will automatically compress data when storing in cache and decompress it when retrieving.

### Basic Usage

```php
use Illuminate\Support\Facades\Cache;

// Store data in cache (will be automatically compressed)
Cache::put('key', 'value', 600); // 10 minutes

// Retrieve data from cache (will be automatically decompressed)
$value = Cache::get('key');
```

### Inline Compression Control

You can control compression settings for individual cache operations:

```php
use Illuminate\Support\Facades\Cache;

// Disable compression for this operation
$value = Cache::compress(false)->get('key');

// Enable compression with default level
$value = Cache::compress(true)->get('key');

// Enable compression with custom level
$value = Cache::compress(true, 9)->get('key');

// Disable compression for storing
Cache::compress(false)->put('key', 'value', 600);

// Chain with other cache methods
$value = Cache::store('redis')
    ->compress(true, 9)
    ->remember('key', 600, function () {
        return 'value';
    });
```

### Supported Cache Drivers

The package supports the following cache drivers:
- Redis
- MongoDB
- Memcached
- File

### Manual Compression/Decompression

If you need to manually compress or decompress data, you can use the facade:

```php
use Develupers\CacheCompress\Facades\CacheCompress;

// Compress data
$compressed = CacheCompress::compress($data, 'redis');

// Decompress data
$decompressed = CacheCompress::decompress($compressed, 'redis');
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

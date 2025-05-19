# Laravel Cache Compress

## Purpose

This project is a Laravel package. The purpose of this package is to add compression support to Laravel's cache() functionality.

## Requirements

The package should be named "laravel-cache-compress" and should be installed via composer.

The package should be compatible with Laravel 11+

The package should be compatible with PHP 8.2+

The package should be compatible with Laravel Cache

The package should be compatible with Laravel Cache Tags

## Usage

### Storing Cache
The package should hook into Laravel cache() function and compress the data before it is stored.

#### For example:

```php

$value = Cache::store('file')->get('foo');

Cache::store('redis')->put('bar', 'baz', 600); // 10 Minutes

```

### Retrieving Cache
The package should also hook into Laravel cache:get event and decompress the data after it is retrieved.

#### For example:

```php

$value = Cache::store('file')->get('key');

$value = Cache::store('file')->get('key', 'default');

```

## Logic

### Compression
I have included an example of how the package compression should work. We will use the `serialize` function and then compress the data before it is stored. This allows it to be safe to store cache for most cache drivers.

In the case of MongoDB, we will encode the data to base64 before storing it.

For Example:

```php

$driver = $this->getCacheDriver();

$compressed = gzdeflate(serialize($object));

// If MongoDB, encode to base64 to ensure UTF-8 compatibility
if ($driver === 'mongodb') {
    return base64_encode($compressed);
}

return $compressed;

```

### Decompression

The package should also hook into Laravel cache:get event and decompress the data after it is retrieved. 

For Example:

```php

$driver = $this->getCacheDriver();

// If MongoDB, decode from base64
$safeObject = $driver === 'mongodb' ?
    base64_decode($object) : $object;

return unserialize(gzinflate($safeObject));

```

## Testing

The package should be tested with the following:

- [ ] Test that the package can compress and decompress data
- [ ] Test that the package can compress and decompress data for MongoDB
- [ ] Test that the package can compress and decompress data for Redis
- [ ] Test that the package can compress and decompress data for Memcached
- [ ] Test that the package can compress and decompress data for File

## Documentation

The package should be documented with the following:

- [ ] Installation
- [ ] Usage

## License

The package should be licensed under the MIT license.
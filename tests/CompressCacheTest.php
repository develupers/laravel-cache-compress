<?php

namespace Develupers\CacheCompress\Tests;

use Develupers\CacheCompress\CacheCompress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class CompressCacheTest extends TestCase
{
    protected CacheCompress $compressor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compressor = new CacheCompress;

        // Set up test cache configurations
        Config::set('cache.stores', [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'cache',
            ],
            'file' => [
                'driver' => 'file',
                'path' => storage_path('framework/cache/data'),
            ],
        ]);
    }

    /** @test */
    public function it_can_compress_and_decompress_data()
    {
        $originalData = ['test' => 'data', 'number' => 123];

        $compressed = $this->compressor->compress($originalData, 'redis');
        $decompressed = $this->compressor->decompress($compressed, 'redis');

        $this->assertEquals($originalData, $decompressed);
    }

    /** @test */
    public function it_can_handle_mongodb_base64_encoding()
    {
        $originalData = ['test' => 'data', 'number' => 123];

        $compressed = $this->compressor->compress($originalData, 'mongodb');
        $decompressed = $this->compressor->decompress($compressed, 'mongodb');

        $this->assertEquals($originalData, $decompressed);
    }

    /** @test */
    public function it_can_handle_large_data()
    {
        $originalData = str_repeat('test data', 1000);

        $compressed = $this->compressor->compress($originalData, 'redis');
        $decompressed = $this->compressor->decompress($compressed, 'redis');

        $this->assertEquals($originalData, $decompressed);
    }

    /** @test */
    public function it_respects_disabled_configuration()
    {
        Config::set('cache-compress.enabled', false);

        $originalData = ['test' => 'data'];
        $compressed = $this->compressor->compress($originalData, 'redis');
        $decompressed = $this->compressor->decompress($compressed, 'redis');

        $this->assertEquals($originalData, $decompressed);
        $this->assertEquals(serialize($originalData), $compressed);
    }

    /** @test */
    public function it_respects_compression_level()
    {
        Config::set('cache-compress.compression_level', 9);

        $originalData = str_repeat('test data', 1000);
        $compressed = $this->compressor->compress($originalData, 'redis');

        // Higher compression level should result in smaller output
        $this->assertLessThan(
            strlen(gzdeflate(serialize($originalData), 1)),
            strlen($compressed)
        );
    }

    /** @test */
    public function it_respects_temporary_settings()
    {
        $originalData = ['test' => 'data'];

        // Test with temporary settings disabled
        CacheCompress::setTemporarySettings(['enabled' => false, 'level' => 6]);
        $compressed = $this->compressor->compress($originalData, 'redis');
        $this->assertEquals(serialize($originalData), $compressed);

        // Test with temporary settings enabled and custom level
        CacheCompress::setTemporarySettings(['enabled' => true, 'level' => 9]);
        $compressed = $this->compressor->compress($originalData, 'redis');
        $decompressed = $this->compressor->decompress($compressed, 'redis');
        $this->assertEquals($originalData, $decompressed);

        // Clear temporary settings
        CacheCompress::setTemporarySettings(null);
    }

    /** @test */
    public function it_respects_compression_level_parameter()
    {
        $originalData = str_repeat('test data', 1000);

        // Test with level 1 (minimal compression)
        CacheCompress::setTemporarySettings(['enabled' => true, 'level' => 1]);
        $compressed1 = $this->compressor->compress($originalData, 'redis');

        // Test with level 9 (maximum compression)
        CacheCompress::setTemporarySettings(['enabled' => true, 'level' => 9]);
        $compressed9 = $this->compressor->compress($originalData, 'redis');

        // Level 9 should result in smaller output than level 1
        $this->assertLessThan(strlen($compressed1), strlen($compressed9));

        // Clear temporary settings
        CacheCompress::setTemporarySettings(null);
    }

    /** @test */
    public function it_resolves_correct_cache_driver(): void
    {
        // Test Redis driver
        $redisDriver = Cache::getFacadeRoot()->store('redis')->getConfig()['driver'];
        $this->assertEquals('redis', $redisDriver);

        // Test File driver
        $fileDriver = Cache::getFacadeRoot()->store('file')->getConfig()['driver'];
        $this->assertEquals('file', $fileDriver);

        // Test compression works with a resolved driver
        $originalData = ['test' => 'data'];

        // Test with Redis
        $compressedRedis = $this->compressor->compress($originalData, $redisDriver);
        $decompressedRedis = $this->compressor->decompress($compressedRedis, $redisDriver);
        $this->assertEquals($originalData, $decompressedRedis);

        // Test with File
        $compressedFile = $this->compressor->compress($originalData, $fileDriver);
        $decompressedFile = $this->compressor->decompress($compressedFile, $fileDriver);
        $this->assertEquals($originalData, $decompressedFile);
    }
}

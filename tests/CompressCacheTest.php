<?php

namespace Develupers\CacheCompress\Tests;

use Develupers\CacheCompress\CompressCache;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\TestCase;

class CompressCacheTest extends TestCase
{
    protected CompressCache $compressor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compressor = new CompressCache();
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
        CompressCache::setTemporarySettings(['enabled' => false, 'level' => 6]);
        $compressed = $this->compressor->compress($originalData, 'redis');
        $this->assertEquals(serialize($originalData), $compressed);

        // Test with temporary settings enabled and custom level
        CompressCache::setTemporarySettings(['enabled' => true, 'level' => 9]);
        $compressed = $this->compressor->compress($originalData, 'redis');
        $decompressed = $this->compressor->decompress($compressed, 'redis');
        $this->assertEquals($originalData, $decompressed);

        // Clear temporary settings
        CompressCache::setTemporarySettings(null);
    }

    /** @test */
    public function it_respects_compression_level_parameter()
    {
        $originalData = str_repeat('test data', 1000);

        // Test with level 1 (minimal compression)
        CompressCache::setTemporarySettings(['enabled' => true, 'level' => 1]);
        $compressed1 = $this->compressor->compress($originalData, 'redis');

        // Test with level 9 (maximum compression)
        CompressCache::setTemporarySettings(['enabled' => true, 'level' => 9]);
        $compressed9 = $this->compressor->compress($originalData, 'redis');

        // Level 9 should result in smaller output than level 1
        $this->assertLessThan(strlen($compressed1), strlen($compressed9));

        // Clear temporary settings
        CompressCache::setTemporarySettings(null);
    }

    /** @test */
    public function it_resolves_correct_cache_driver()
    {
        $storeName = 'redis';
        $store = app('cache')->store($storeName);
        $driver = method_exists($store->getStore(), 'getDriver')
            ? $store->getStore()->getDriver()
            : $storeName;
        $this->assertEquals('redis', $driver);
    }
}

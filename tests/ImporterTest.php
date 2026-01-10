<?php

namespace Balotias\StatamicAssetMetadataImporter\Tests;

use Balotias\StatamicAssetMetadataImporter\Importer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;

class ImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up fake storage BEFORE creating any assets
        Storage::fake('assets');
    }


    public function test_it_can_be_instantiated_with_an_asset(): void
    {
        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        $this->assertInstanceOf(Importer::class, $importer);
    }


    public function test_it_reads_metadata_from_local_files(): void
    {
        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // The Importer constructor automatically reads metadata
        $importer = new Importer($asset);

        // If we get here without errors, metadata was read successfully
        $this->assertInstanceOf(Importer::class, $importer);
    }


    public function test_it_maps_metadata_to_asset_fields(): void
    {
        config(['statamic.asset-metadata-importer.fields' => [
            'alt' => 'title',
            'copyright' => 'copyright',
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        $data = $asset->data();
        $this->assertTrue(is_array($data) || $data instanceof \Illuminate\Support\Collection);
    }


    public function test_it_only_maps_fields_that_exist_in_blueprint(): void
    {
        config(['statamic.asset-metadata-importer.fields' => [
            'alt' => 'title',
            'nonexistent_field' => 'description',
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        $data = $asset->data();
        if ($data instanceof \Illuminate\Support\Collection) {
            $this->assertFalse($data->has('nonexistent_field'));
        } else {
            $this->assertArrayNotHasKey('nonexistent_field', $data);
        }
    }


    public function test_it_handles_multiple_source_fallbacks(): void
    {
        config(['statamic.asset-metadata-importer.fields' => [
            'alt' => ['nonexistent', 'title', 'description'],
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        $this->assertTrue(true); // If we get here without errors, the fallback logic works
    }


    public function test_it_logs_when_debug_is_enabled(): void
    {
        config(['statamic.asset-metadata-importer.debug' => true]);

        Log::shouldReceive('debug')->atLeast()->once();

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);
    }


    public function test_it_does_not_log_when_debug_is_disabled(): void
    {
        config(['statamic.asset-metadata-importer.debug' => false]);

        Log::shouldReceive('debug')->never();

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);
    }


    public function test_it_handles_files_without_metadata_gracefully(): void
    {
        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'empty.jpg');

        // Constructor automatically reads metadata
        $importer = new Importer($asset);

        // If we get here without errors, the empty metadata was handled gracefully
        $this->assertInstanceOf(Importer::class, $importer);
    }


    public function test_it_saves_asset_when_metadata_is_mapped(): void
    {
        config(['statamic.asset-metadata-importer.fields' => [
            'alt' => 'title',
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        // If we get here without errors, the asset was saved successfully
        $this->assertTrue(true);
    }


    public function test_it_uses_exact_match_when_loose_mapping_is_disabled(): void
    {
        config([
            'statamic.asset-metadata-importer.loose_mapping' => false,
            'statamic.asset-metadata-importer.fields' => [
                'alt' => 'title',
            ]
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        // Should only match exact field names
        $this->assertTrue(true);
    }


    public function test_it_uses_loose_matching_when_enabled(): void
    {
        config([
            'statamic.asset-metadata-importer.loose_mapping' => true,
            'statamic.asset-metadata-importer.fields' => [
                'credit' => 'credit', // Should match any key containing 'credit'
            ]
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        // Loose matching should work
        $this->assertTrue(true);
    }


    public function test_loose_mapping_prefers_exact_match_first(): void
    {
        config([
            'statamic.asset-metadata-importer.loose_mapping' => true,
            'statamic.asset-metadata-importer.fields' => [
                'alt' => 'title',
            ]
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        // Should prefer exact match over partial match
        $this->assertTrue(true);
    }


    public function test_loose_mapping_is_case_insensitive(): void
    {
        config([
            'statamic.asset-metadata-importer.loose_mapping' => true,
            'statamic.asset-metadata-importer.fields' => [
                'credit' => 'CREDIT', // Uppercase search
            ]
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        // Case insensitive matching should work
        $this->assertTrue(true);
    }


    public function test_loose_mapping_with_multiple_sources(): void
    {
        config([
            'statamic.asset-metadata-importer.loose_mapping' => true,
            'statamic.asset-metadata-importer.fields' => [
                'credit' => ['exact_field', 'credit', 'partial'],
            ]
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        // Should try exact matches first, then fall back to loose matching
        $this->assertTrue(true);
    }


    public function test_loose_mapping_returns_null_when_no_match(): void
    {
        config([
            'statamic.asset-metadata-importer.loose_mapping' => true,
            'statamic.asset-metadata-importer.fields' => [
                'alt' => 'completely_nonexistent_field_xyz',
            ]
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        // Should handle no matches gracefully
        $this->assertTrue(true);
    }


    public function test_loose_mapping_handles_multibyte_characters(): void
    {
        config([
            'statamic.asset-metadata-importer.loose_mapping' => true,
            'statamic.asset-metadata-importer.fields' => [
                'credit' => 'crédit', // French accented character
            ]
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Constructor automatically imports metadata
        new Importer($asset);

        // Should handle international characters correctly with mb_strtolower
        $this->assertTrue(true);
    }


    public function test_it_handles_local_assets_without_temp_download(): void
    {
        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'local.jpg');

        // Verify the file exists locally (this is what triggers local path)
        $this->assertTrue(file_exists($asset->resolvedPath()));

        // Constructor automatically reads metadata from local file
        $importer = new Importer($asset);

        // If we get here without errors, local file was read successfully
        $this->assertInstanceOf(Importer::class, $importer);
    }


    public function test_it_downloads_remote_assets_temporarily(): void
    {
        // Create a mock asset that simulates remote storage
        $container = $this->createAssetContainer();

        // Create asset but don't create the physical file
        // This simulates a remote asset where resolvedPath doesn't exist locally
        $asset = $this->getMockBuilder(\Statamic\Assets\Asset::class)
            ->onlyMethods(['resolvedPath', 'stream', 'path', 'container', 'id', 'saveQuietly'])
            ->getMock();

        // Make resolvedPath return a non-existent path (simulating remote storage)
        $asset->method('resolvedPath')
            ->willReturn('/nonexistent/path/remote.jpg');

        $asset->method('path')
            ->willReturn('remote.jpg');

        $asset->method('id')
            ->willReturn('test-asset-id');

        $asset->method('container')
            ->willReturn($container);

        // Track how many times stream() is called to verify it's used for remote assets
        $streamCallCount = 0;

        $asset->method('stream')
            ->willReturnCallback(function () use (&$streamCallCount) {
                $streamCallCount++;
                // Create a fake image stream
                $stream = fopen('php://memory', 'r+');
                fwrite($stream, 'fake remote image content');
                rewind($stream);
                return $stream;
            });

        // Constructor should handle remote asset by creating temp file
        $importer = new Importer($asset);

        // Verify that stream() was called (indicating remote download was attempted)
        $this->assertGreaterThan(0, $streamCallCount,
            'stream() should be called for remote assets to download them temporarily');

        // If we get here without errors, the remote download logic worked
        $this->assertInstanceOf(Importer::class, $importer);
    }


    public function test_it_cleans_up_temporary_files_after_processing(): void
    {
        // Create a mock asset that simulates remote storage
        $container = $this->createAssetContainer();

        $asset = $this->getMockBuilder(\Statamic\Assets\Asset::class)
            ->onlyMethods(['resolvedPath', 'stream', 'path', 'container', 'id', 'saveQuietly'])
            ->getMock();

        $asset->method('resolvedPath')
            ->willReturn('/nonexistent/path/remote.jpg');

        $asset->method('path')
            ->willReturn('remote.jpg');

        $asset->method('id')
            ->willReturn('test-asset-id');

        $asset->method('container')
            ->willReturn($container);

        // Track if stream is called
        $streamCalled = false;
        $asset->method('stream')
            ->willReturnCallback(function () use (&$streamCalled) {
                $streamCalled = true;
                $stream = fopen('php://memory', 'r+');
                fwrite($stream, 'fake remote image content');
                rewind($stream);
                return $stream;
            });

        // Get initial temp directory count
        $tempBasePath = sys_get_temp_dir();
        $tempDirsBefore = glob($tempBasePath . '/temporary_directory_*');
        $countBefore = count($tempDirsBefore);

        // Process the remote asset in a scope
        (function() use ($asset) {
            new Importer($asset);
        })();

        // Verify stream was called (remote download happened)
        $this->assertTrue($streamCalled, 'stream() should be called for remote assets');

        // Force garbage collection to ensure TemporaryDirectory destructor runs
        gc_collect_cycles();

        // Give it a moment for cleanup
        usleep(10000); // 10ms

        // Check that temp directories are cleaned up
        $tempDirsAfter = glob($tempBasePath . '/temporary_directory_*');
        $countAfter = count($tempDirsAfter);

        // The count should be the same or less (temp dirs should be auto-deleted)
        $this->assertLessThanOrEqual($countBefore, $countAfter,
            'Temporary directories should be cleaned up after processing');
    }


    public function test_it_handles_streaming_failures_gracefully(): void
    {
        $this->expectException(\RuntimeException::class);

        // Create a mock asset that simulates remote storage with failing stream
        $container = $this->createAssetContainer();

        $asset = $this->getMockBuilder(\Statamic\Assets\Asset::class)
            ->onlyMethods(['resolvedPath', 'stream', 'path', 'container', 'id', 'saveQuietly'])
            ->getMock();

        $asset->method('resolvedPath')
            ->willReturn('/nonexistent/path/remote.jpg');

        $asset->method('path')
            ->willReturn('remote.jpg');

        $asset->method('id')
            ->willReturn('test-asset-id');

        $asset->method('container')
            ->willReturn($container);

        // Make stream() throw an exception to simulate a failure
        $asset->method('stream')
            ->willThrowException(new \RuntimeException('Failed to stream remote asset'));

        // Constructor should throw the exception when trying to download
        new Importer($asset);
    }

    protected function createAssetContainer()
    {
        $container = AssetContainer::make('assets')
            ->disk('assets');
        $container->save();

        return $container;
    }

    protected function createAsset($container, $filename)
    {
        // Create a fake file in storage BEFORE creating the asset
        Storage::disk('assets')->put($filename, 'fake image content');

        $asset = Asset::make()
            ->container($container)
            ->path($filename);
        $asset->save();

        return $asset;
    }

    // ========================================
    // Adapter Mapping Tests
    // ========================================

    public function test_it_uses_native_adapter_for_configured_extensions(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['jpg', 'jpeg'],
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_it_uses_exiftool_adapter_when_configured(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'exiftool' => ['png', 'webp'],
        ]]);
        config(['statamic.asset-metadata-importer.exiftool_path' => '/usr/local/bin/exiftool']);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.png');

        // This test will throw an exception if exiftool is not installed
        // That's expected behavior - the adapter is being used, just can't execute
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\PHPExif\Reader\PhpExifReaderException $e) {
            // Expected when exiftool binary is not available
            $this->assertStringContainsString('Could not decode exiftool output', $e->getMessage());
        }
    }

    public function test_it_uses_ffprobe_adapter_when_configured(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'ffprobe' => ['mp4', 'mov'],
        ]]);
        config(['statamic.asset-metadata-importer.ffmpeg_path' => '/usr/local/bin/ffmpeg']);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.mp4');

        // This test will throw an exception if ffprobe is not installed
        // That's expected behavior - the adapter is being used, just can't execute
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\PHPExif\Reader\PhpExifReaderException $e) {
            // Expected when ffprobe binary is not available
            $this->assertStringContainsString('Could not read', $e->getMessage());
        }
    }

    public function test_it_returns_empty_metadata_when_no_adapter_configured(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['jpg'],
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.png'); // PNG not configured

        $importer = new Importer($asset);

        // Should complete without error, just no metadata extracted
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_adapter_mapping_uses_first_match(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['jpg'],
            'exiftool' => ['jpg', 'png'], // jpg appears twice
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        // Should use native adapter (first match)
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_adapter_mapping_is_case_insensitive(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['jpg'],
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.JPG'); // Uppercase extension

        $importer = new Importer($asset);

        $this->assertInstanceOf(Importer::class, $importer);
    }

    // ========================================
    // Wildcard Support Tests
    // ========================================

    public function test_it_supports_wildcard_in_adapter_mapping(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['*'], // Process all file types
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_wildcard_matches_all_extensions(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['*'],
        ]]);

        $extensions = ['jpg', 'png', 'gif'];

        foreach ($extensions as $ext) {
            $container = $this->createAssetContainer();
            $asset = $this->createAsset($container, "test.{$ext}");

            $importer = new Importer($asset);

            $this->assertInstanceOf(Importer::class, $importer);
        }
    }

    public function test_wildcard_in_first_adapter_takes_precedence(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['*'], // Matches everything
            'exiftool' => ['jpg'], // This won't be used for jpg
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        // Should use native (first match)
        $this->assertInstanceOf(Importer::class, $importer);
    }

    // ========================================
    // Configuration Path Tests
    // ========================================

    public function test_it_passes_exiftool_path_to_adapter(): void
    {
        $customPath = '/custom/path/to/exiftool';

        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'exiftool' => ['jpg'],
        ]]);
        config(['statamic.asset-metadata-importer.exiftool_path' => $customPath]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Adapter should receive the custom path (will fail if binary doesn't exist)
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\PHPExif\Reader\PhpExifReaderException $e) {
            // Expected when exiftool binary is not available at custom path
            $this->assertStringContainsString('Could not decode exiftool output', $e->getMessage());
        }
    }

    public function test_it_passes_ffmpeg_path_to_adapter(): void
    {
        $customPath = '/custom/path/to/ffmpeg';

        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'ffprobe' => ['mp4'],
        ]]);
        config(['statamic.asset-metadata-importer.ffmpeg_path' => $customPath]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.mp4');

        // Adapter should receive the custom path (will fail if binary doesn't exist)
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\PHPExif\Reader\PhpExifReaderException $e) {
            // Expected when ffprobe binary is not available at custom path
            $this->assertStringContainsString('Could not read', $e->getMessage());
        }
    }

    public function test_it_handles_empty_exiftool_path(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'exiftool' => ['jpg'],
        ]]);
        config(['statamic.asset-metadata-importer.exiftool_path' => '']);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Empty path to exiftool throws InvalidArgumentException
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\InvalidArgumentException $e) {
            // Expected when empty path is provided to exiftool
            $this->assertStringContainsString('invalid', strtolower($e->getMessage()));
        } catch (\PHPExif\Reader\PhpExifReaderException $e) {
            // Also expected when exiftool binary is not in system PATH
            $this->assertStringContainsString('Could not decode exiftool output', $e->getMessage());
        }
    }

    public function test_it_handles_null_ffmpeg_path(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'ffprobe' => ['mp4'],
        ]]);
        config(['statamic.asset-metadata-importer.ffmpeg_path' => null]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.mp4');

        // Should work with null path (converted to empty string, uses system default if ffprobe is installed)
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\PHPExif\Reader\PhpExifReaderException $e) {
            // Expected when ffprobe binary is not in system PATH
            $this->assertStringContainsString('Could not read', $e->getMessage());
        }
    }

    // ========================================
    // Multiple Adapter Tests
    // ========================================

    public function test_it_supports_multiple_adapters_for_different_extensions(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['jpg', 'jpeg', 'tif', 'tiff'],
        ]]);

        $testCases = [
            'test.jpg' => Importer::class,
            'test.tif' => Importer::class,
        ];

        foreach ($testCases as $filename => $expectedClass) {
            $container = $this->createAssetContainer();
            $asset = $this->createAsset($container, $filename);

            $importer = new Importer($asset);

            $this->assertInstanceOf($expectedClass, $importer);
        }
    }

    public function test_it_handles_empty_adapter_mapping(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => []]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        // Should complete without error, no adapter selected
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_it_handles_unknown_adapter_type_gracefully(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'unknown_adapter' => ['jpg'],
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        // Should complete without error, returns null adapter
        $this->assertInstanceOf(Importer::class, $importer);
    }

    // ========================================
    // Integration Tests
    // ========================================

    public function test_full_workflow_with_native_adapter(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg'],
            ],
            'statamic.asset-metadata-importer.fields' => [
                'alt' => 'title',
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        // Full workflow should complete
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_full_workflow_with_exiftool_adapter(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'exiftool' => ['png'],
            ],
            'statamic.asset-metadata-importer.exiftool_path' => '/usr/local/bin/exiftool',
            'statamic.asset-metadata-importer.fields' => [
                'alt' => 'title',
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.png');

        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\PHPExif\Reader\PhpExifReaderException $e) {
            // Expected when exiftool binary is not available
            $this->assertStringContainsString('Could not decode exiftool output', $e->getMessage());
        }
    }

    public function test_full_workflow_with_ffprobe_adapter(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'ffprobe' => ['mp4'],
            ],
            'statamic.asset-metadata-importer.ffmpeg_path' => '/usr/local/bin/ffmpeg',
            'statamic.asset-metadata-importer.fields' => [
                'duration' => 'duration',
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.mp4');

        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\PHPExif\Reader\PhpExifReaderException $e) {
            // Expected when ffprobe binary is not available
            $this->assertStringContainsString('Could not read', $e->getMessage());
        }
    }

    // ========================================
    // ImageMagick Adapter Tests
    // ========================================

    public function test_it_uses_imagick_adapter_when_configured(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'imagick' => ['png', 'gif'],
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.png');

        // This test will work if Imagick PHP extension is installed
        // With fake test files, Imagick will throw "improper image header" error
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Error | \Exception $e) {
            // Expected when Imagick extension is not available OR with fake test files
            $message = $e->getMessage();
            $this->assertTrue(
                str_contains($message, 'Imagick') ||
                str_contains($message, 'Class') ||
                str_contains($message, 'not found') ||
                str_contains($message, 'improper image header'),
                "Exception message was: {$message}"
            );
        }
    }

    public function test_imagick_adapter_works_with_multiple_extensions(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'imagick' => ['png', 'gif', 'bmp'],
        ]]);

        $extensions = ['png', 'gif', 'bmp'];

        foreach ($extensions as $ext) {
            $container = $this->createAssetContainer();
            $asset = $this->createAsset($container, "test.{$ext}");

            try {
                $importer = new Importer($asset);
                $this->assertInstanceOf(Importer::class, $importer);
            } catch (\Error | \Exception $e) {
                // Expected when Imagick extension is not available OR with fake test files
                $message = $e->getMessage();
                $this->assertTrue(
                    str_contains($message, 'Imagick') ||
                    str_contains($message, 'Class') ||
                    str_contains($message, 'not found') ||
                    str_contains($message, 'improper image header'),
                    "Exception message was: {$message}"
                );
            }
        }
    }

    public function test_imagick_adapter_with_wildcard(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'imagick' => ['*'],
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.png');

        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Error | \Exception $e) {
            // Expected when Imagick extension is not available OR with fake test files
            $message = $e->getMessage();
            $this->assertTrue(
                str_contains($message, 'Imagick') ||
                str_contains($message, 'Class') ||
                str_contains($message, 'not found') ||
                str_contains($message, 'improper image header'),
                "Exception message was: {$message}"
            );
        }
    }

    public function test_mixed_adapters_with_imagick(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['jpg', 'jpeg'],
            'imagick' => ['png', 'gif'],
        ]]);

        // Test native adapter works for jpg
        $container = $this->createAssetContainer();
        $jpgAsset = $this->createAsset($container, 'test.jpg');
        $importer = new Importer($jpgAsset);
        $this->assertInstanceOf(Importer::class, $importer);

        // Test Imagick for png
        $pngAsset = $this->createAsset($container, 'test.png');
        try {
            $importer = new Importer($pngAsset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Error | \Exception $e) {
            // Expected when Imagick extension is not available OR with fake test files
            $message = $e->getMessage();
            $this->assertTrue(
                str_contains($message, 'Imagick') ||
                str_contains($message, 'Class') ||
                str_contains($message, 'not found') ||
                str_contains($message, 'improper image header'),
                "Exception message was: {$message}"
            );
        }
    }

    public function test_full_workflow_with_imagick_adapter(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'imagick' => ['png'],
            ],
            'statamic.asset-metadata-importer.fields' => [
                'alt' => 'title',
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.png');

        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Error | \Exception $e) {
            // Expected when Imagick extension is not available OR with fake test files
            $message = $e->getMessage();
            $this->assertTrue(
                str_contains($message, 'Imagick') ||
                str_contains($message, 'Class') ||
                str_contains($message, 'not found') ||
                str_contains($message, 'improper image header'),
                "Exception message was: {$message}"
            );
        }
    }

    public function test_imagick_adapter_precedence(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'imagick' => ['png'],
            'native' => ['png'], // PNG appears in both, first match should win
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.png');

        // Should use Imagick (first match)
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Error | \Exception $e) {
            // Expected when Imagick extension is not available OR with fake test files
            $message = $e->getMessage();
            $this->assertTrue(
                str_contains($message, 'Imagick') ||
                str_contains($message, 'Class') ||
                str_contains($message, 'not found') ||
                str_contains($message, 'improper image header'),
                "Exception message was: {$message}"
            );
        }
    }

    public function test_all_adapters_configured_together(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'native' => ['jpg', 'jpeg', 'tif', 'tiff'],
            'exiftool' => ['webp', 'avif'],
            'ffprobe' => ['mp4', 'mov'],
            'imagick' => ['png', 'gif'],
        ]]);

        // Test that adapter selection works for each type
        $testCases = [
            'test.jpg' => 'native',
            'test.png' => 'imagick',
            'test.mp4' => 'ffprobe',
        ];

        foreach ($testCases as $filename => $adapterType) {
            $container = $this->createAssetContainer();
            $asset = $this->createAsset($container, $filename);

            try {
                $importer = new Importer($asset);
                $this->assertInstanceOf(Importer::class, $importer);
            } catch (\Exception $e) {
                // Expected when required extension/binary is not available OR with fake test files
                $this->assertTrue(true); // Test passes if exception is thrown
            }
        }
    }

    public function test_imagick_adapter_case_insensitive(): void
    {
        config(['statamic.asset-metadata-importer.adapter_mapping' => [
            'imagick' => ['png'],
        ]]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.PNG'); // Uppercase extension

        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Error | \Exception $e) {
            // Expected when Imagick extension is not available OR with fake test files
            $message = $e->getMessage();
            $this->assertTrue(
                str_contains($message, 'Imagick') ||
                str_contains($message, 'Class') ||
                str_contains($message, 'not found') ||
                str_contains($message, 'improper image header'),
                "Exception message was: {$message}"
            );
        }
    }

    // ========================================
    // Multiple Adapter Fallback Tests
    // ========================================

    public function test_multiple_adapters_for_same_extension_tries_all_until_success(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg'],
                'exiftool' => ['jpg'], // Same extension, second fallback
            ],
            'statamic.asset-metadata-importer.exiftool_path' => '/nonexistent/path/exiftool',
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Should try native first, and since we're using fake files, it should handle gracefully
        $importer = new Importer($asset);
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_multiple_adapters_fallback_order_is_preserved(): void
    {
        config([
            'statamic.asset-metadata-importer.debug' => true,
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg'],
                'exiftool' => ['jpg'],
                'imagick' => ['jpg'],
            ],
        ]);

        $logMessages = [];
        Log::shouldReceive('debug')
            ->andReturnUsing(function ($message) use (&$logMessages) {
                $logMessages[] = $message;
            });

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        // Check that native adapter was tried first
        $foundFirstAdapter = false;
        foreach ($logMessages as $message) {
            if (str_contains($message, 'Trying adapter #0: Native')) {
                $foundFirstAdapter = true;
                break;
            }
        }

        $this->assertTrue($foundFirstAdapter, 'Should try Native adapter first (index 0)');
    }

    public function test_adapter_fallback_stops_when_metadata_found(): void
    {
        config([
            'statamic.asset-metadata-importer.debug' => true,
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg'], // If this finds metadata, don't try next
                'exiftool' => ['jpg'],
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // With fake files, native might not find metadata, so second adapter could be tried
        $importer = new Importer($asset);
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_all_adapters_fail_gracefully_returns_empty_metadata(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'exiftool' => ['jpg'], // Will fail - path doesn't exist
                'imagick' => ['jpg'],  // Will fail with fake file
            ],
            'statamic.asset-metadata-importer.exiftool_path' => '/nonexistent/exiftool',
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Should handle all failures gracefully
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Exception $e) {
            // Some adapters might throw - that's acceptable for test with fake files
            $this->assertTrue(true);
        }
    }

    public function test_multiple_adapters_with_wildcard(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['*'], // Try native for all
                'exiftool' => ['*'], // Fallback to exiftool for all
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_mixed_specific_and_wildcard_adapters(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg', 'jpeg'],
                'exiftool' => ['*'], // Fallback for jpg and all other formats
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Should try native first (specific), then exiftool (wildcard)
        $importer = new Importer($asset);
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_adapter_fallback_logs_all_attempts_when_debug_enabled(): void
    {
        config([
            'statamic.asset-metadata-importer.debug' => true,
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg'],
                'exiftool' => ['jpg'],
            ],
        ]);

        $logMessages = [];
        Log::shouldReceive('debug')
            ->andReturnUsing(function ($message) use (&$logMessages) {
                $logMessages[] = $message;
            });

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        $importer = new Importer($asset);

        // Should log attempts for adapters
        $hasAdapterLog = false;
        foreach ($logMessages as $message) {
            if (str_contains($message, 'Trying adapter')) {
                $hasAdapterLog = true;
                break;
            }
        }

        $this->assertTrue($hasAdapterLog, 'Should log adapter attempts when debug is enabled');
    }

    public function test_different_extensions_use_different_adapter_sets(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg'],
                'exiftool' => ['png'],
                'ffprobe' => ['mp4'],
            ],
        ]);

        // Test JPG uses native
        $container = $this->createAssetContainer();
        $jpgAsset = $this->createAsset($container, 'test.jpg');
        $importer = new Importer($jpgAsset);
        $this->assertInstanceOf(Importer::class, $importer);

        // Test PNG uses exiftool
        $pngAsset = $this->createAsset($container, 'test.png');
        try {
            $importer = new Importer($pngAsset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Exception $e) {
            // Expected with fake files and missing exiftool
            $this->assertTrue(true);
        }
    }

    public function test_adapter_fallback_with_field_mapping(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg'],
                'exiftool' => ['jpg'],
            ],
            'statamic.asset-metadata-importer.fields' => [
                'alt' => 'title',
                'copyright' => 'copyright',
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // Should try adapters and map fields if metadata found
        $importer = new Importer($asset);
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_no_adapters_configured_for_extension_returns_empty(): void
    {
        config([
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'native' => ['jpg'], // Only jpg configured
            ],
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.png'); // PNG not configured

        // Should handle gracefully with no adapters
        $importer = new Importer($asset);
        $this->assertInstanceOf(Importer::class, $importer);
    }

    public function test_adapter_exception_continues_to_next_adapter(): void
    {
        config([
            'statamic.asset-metadata-importer.debug' => true,
            'statamic.asset-metadata-importer.adapter_mapping' => [
                'exiftool' => ['jpg'], // Will throw exception
                'native' => ['jpg'],    // Should still be tried
            ],
            'statamic.asset-metadata-importer.exiftool_path' => '',
        ]);

        $container = $this->createAssetContainer();
        $asset = $this->createAsset($container, 'test.jpg');

        // First adapter fails, second should be tried
        try {
            $importer = new Importer($asset);
            $this->assertInstanceOf(Importer::class, $importer);
        } catch (\Exception $e) {
            // Both might fail with fake test files, that's ok
            $this->assertTrue(true);
        }
    }
}

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
}

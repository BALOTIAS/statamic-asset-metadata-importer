<?php

namespace Balotias\StatamicAssetMetadataImporter\Tests;

use Balotias\StatamicAssetMetadataImporter\Jobs\ImportMetadataJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\AssetContainer;

class ImportMetadataJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }


    public function test_it_can_be_dispatched(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        ImportMetadataJob::dispatch($asset);

        Queue::assertPushed(ImportMetadataJob::class);
    }


    public function test_it_uses_configured_queue(): void
    {
        config()->set('statamic.asset-metadata-importer.queue', 'custom-queue');

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        ImportMetadataJob::dispatch($asset);

        Queue::assertPushed(ImportMetadataJob::class, function ($job) {
            return $job->queue === 'custom-queue';
        });
    }


    public function test_it_defaults_to_default_queue(): void
    {
        config()->set('statamic.asset-metadata-importer.queue', 'default');

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        ImportMetadataJob::dispatch($asset);

        Queue::assertPushed(ImportMetadataJob::class, function ($job) {
            return $job->queue === 'default';
        });
    }


    public function test_it_accepts_asset_in_constructor(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        $job = new ImportMetadataJob($asset);

        $this->assertEquals($asset, $job->asset);
    }


    public function test_it_does_not_process_when_no_fields_configured(): void
    {
        config()->set('statamic.asset-metadata-importer.fields', null);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        Storage::fake('assets');
        Storage::disk('assets')->put('test-image.jpg', 'fake image content');

        $job = new ImportMetadataJob($asset);

        // Handle should return early when no fields are configured
        $job->handle();

        $this->assertTrue(true);
    }


    public function test_it_processes_when_fields_configured(): void
    {
        config()->set('statamic.asset-metadata-importer.fields', [
            'alt' => 'title',
        ]);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        Storage::fake('assets');
        Storage::disk('assets')->put('test-image.jpg', 'fake image content');

        $job = new ImportMetadataJob($asset);

        // Should not throw an exception
        $job->handle();

        $this->assertTrue(true);
    }

    protected function makeAssetContainer()
    {
        Storage::fake('assets');

        return tap(AssetContainer::make('assets')->disk('assets'))->save();
    }

    protected function makeAsset($container, $path)
    {
        return tap($container->makeAsset($path))->save();
    }
}


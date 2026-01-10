<?php

namespace Balotias\StatamicAssetMetadataImporter\Tests;

use Balotias\StatamicAssetMetadataImporter\Jobs\ImportMetadataJob;
use Balotias\StatamicAssetMetadataImporter\Listeners\AssetUploadedListener;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Statamic\Events\AssetUploaded;
use Statamic\Facades\AssetContainer;

class AssetUploadedListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        config()->set('statamic.asset-metadata-importer.extensions', [
            'jpg', 'jpeg', 'png', 'tiff', 'tif',
        ]);
    }


    public function test_it_dispatches_job_for_supported_extensions(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        $event = new AssetUploaded($asset, 'test-image.jpg');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class, function ($job) use ($asset) {
            return $job->asset->id() === $asset->id();
        });
    }


    public function test_it_dispatches_job_for_jpeg_extension(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpeg');

        $event = new AssetUploaded($asset, 'test-image.jpeg');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }


    public function test_it_dispatches_job_for_png_extension(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.png');

        $event = new AssetUploaded($asset, 'test-image.png');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }


    public function test_it_dispatches_job_for_tiff_extension(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.tiff');

        $event = new AssetUploaded($asset, 'test-image.tiff');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }


    public function test_it_does_not_dispatch_job_for_unsupported_extensions(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-file.pdf');

        $event = new AssetUploaded($asset, 'test-file.pdf');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertNotPushed(ImportMetadataJob::class);
    }


    public function test_it_does_not_dispatch_job_for_svg_files(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.svg');

        $event = new AssetUploaded($asset, 'test-image.svg');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertNotPushed(ImportMetadataJob::class);
    }


    public function test_it_does_not_dispatch_job_for_gif_files(): void
    {
        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.gif');

        $event = new AssetUploaded($asset, 'test-image.gif');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertNotPushed(ImportMetadataJob::class);
    }


    public function test_it_respects_configured_extensions(): void
    {
        config()->set('statamic.asset-metadata-importer.extensions', ['jpg']);

        $container = $this->makeAssetContainer();

        // JPG should be processed
        $jpgAsset = $this->makeAsset($container, 'test-image.jpg');
        $jpgEvent = new AssetUploaded($jpgAsset, 'test-image.jpg');
        $listener = new AssetUploadedListener();
        $listener->handle($jpgEvent);

        Queue::assertPushed(ImportMetadataJob::class, 1);

        // PNG should not be processed (not in config)
        $pngAsset = $this->makeAsset($container, 'test-image.png');
        $pngEvent = new AssetUploaded($pngAsset, 'test-image.png');
        $listener->handle($pngEvent);

        // Still only 1 job should be pushed (from JPG)
        Queue::assertPushed(ImportMetadataJob::class, 1);
    }


    public function test_it_is_case_insensitive_for_extensions(): void
    {
        $container = $this->makeAssetContainer();

        // Test uppercase extension
        $asset = $this->makeAsset($container, 'test-image.JPG');

        $event = new AssetUploaded($asset, 'test-image.JPG');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }

    // ========================================
    // Wildcard Extension Tests
    // ========================================

    public function test_it_dispatches_job_for_wildcard_extension(): void
    {
        config()->set('statamic.asset-metadata-importer.extensions', ['*']);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-file.xyz'); // Any extension

        $event = new AssetUploaded($asset, 'test-file.xyz');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }

    public function test_wildcard_allows_all_extensions(): void
    {
        config()->set('statamic.asset-metadata-importer.extensions', ['*']);

        $extensions = ['jpg', 'png', 'mp4', 'pdf', 'doc', 'xyz'];

        foreach ($extensions as $ext) {
            Queue::fake(); // Reset queue for each test

            $container = $this->makeAssetContainer();
            $asset = $this->makeAsset($container, "test.{$ext}");

            $event = new AssetUploaded($asset, "test.{$ext}");
            $listener = new AssetUploadedListener();

            $listener->handle($event);

            Queue::assertPushed(ImportMetadataJob::class);
        }
    }

    public function test_wildcard_mixed_with_specific_extensions(): void
    {
        // When wildcard is present, it should match everything
        config()->set('statamic.asset-metadata-importer.extensions', ['jpg', '*', 'png']);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-file.pdf');

        $event = new AssetUploaded($asset, 'test-file.pdf');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }

    public function test_wildcard_with_uppercase_extension(): void
    {
        config()->set('statamic.asset-metadata-importer.extensions', ['*']);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-file.PDF');

        $event = new AssetUploaded($asset, 'test-file.PDF');
        $listener = new AssetUploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
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


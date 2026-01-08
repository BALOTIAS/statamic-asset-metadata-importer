<?php

namespace Balotias\StatamicAssetMetadataImporter\Tests;

use Balotias\StatamicAssetMetadataImporter\Jobs\ImportMetadataJob;
use Balotias\StatamicAssetMetadataImporter\Listeners\AssetReuploadedListener;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Statamic\Events\AssetReuploaded;
use Statamic\Facades\AssetContainer;

class AssetReuploadedListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        config()->set('statamic.asset-metadata-importer.extensions', [
            'jpg', 'jpeg', 'png', 'tiff', 'tif',
        ]);
    }


    public function test_it_dispatches_job_when_overwrite_is_enabled(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class, function ($job) use ($asset) {
            return $job->asset->id() === $asset->id();
        });
    }


    public function test_it_does_not_dispatch_job_when_overwrite_is_disabled(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', false);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        Queue::assertNotPushed(ImportMetadataJob::class);
    }


    public function test_it_dispatches_job_for_supported_extensions_only(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }


    public function test_it_does_not_dispatch_job_for_unsupported_extensions(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-file.pdf');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        Queue::assertNotPushed(ImportMetadataJob::class);
    }


    public function test_it_does_not_dispatch_job_for_svg_files_even_when_overwrite_enabled(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.svg');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        Queue::assertNotPushed(ImportMetadataJob::class);
    }


    public function test_it_checks_overwrite_config_before_extension(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', false);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpg');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        // Even though extension is supported, should not dispatch because overwrite is disabled
        Queue::assertNotPushed(ImportMetadataJob::class);
    }


    public function test_it_dispatches_job_for_jpeg_extension(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.jpeg');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }


    public function test_it_dispatches_job_for_png_extension(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.png');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }


    public function test_it_dispatches_job_for_tiff_extension(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);

        $container = $this->makeAssetContainer();
        $asset = $this->makeAsset($container, 'test-image.tiff');

        $event = new AssetReuploaded($asset, $asset->filename());
        $listener = new AssetReuploadedListener();

        $listener->handle($event);

        Queue::assertPushed(ImportMetadataJob::class);
    }


    public function test_it_respects_configured_extensions(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);
        config()->set('statamic.asset-metadata-importer.extensions', ['jpg']);

        $container = $this->makeAssetContainer();

        // JPG should be processed
        $jpgAsset = $this->makeAsset($container, 'test-image.jpg');
        $jpgEvent = new AssetReuploaded($jpgAsset, $jpgAsset->filename());
        $listener = new AssetReuploadedListener();
        $listener->handle($jpgEvent);

        Queue::assertPushed(ImportMetadataJob::class, 1);

        // PNG should not be processed (not in config)
        $pngAsset = $this->makeAsset($container, 'test-image.png');
        $pngEvent = new AssetReuploaded($pngAsset, "test-image.png");
        $listener->handle($pngEvent);

        // Still only 1 job should be pushed (from JPG)
        Queue::assertPushed(ImportMetadataJob::class, 1);
    }


    public function test_it_is_case_insensitive_for_extensions(): void
    {
        config()->set('statamic.asset-metadata-importer.overwrite_on_reupload', true);

        $container = $this->makeAssetContainer();

        // Test uppercase extension
        $asset = $this->makeAsset($container, 'test-image.JPG');

        $event = new AssetReuploaded($asset, 'original-name.JPG');
        $listener = new AssetReuploadedListener();

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


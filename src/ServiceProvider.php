<?php

namespace Balotias\StatamicAssetMetadataImporter;

use Balotias\StatamicAssetMetadataImporter\Listeners\AssetReuploadedListener;
use Balotias\StatamicAssetMetadataImporter\Listeners\AssetUploadedListener;
use Statamic\Events\AssetReuploaded;
use Statamic\Events\AssetUploaded;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $listen = [
        AssetUploaded::class => [
            AssetUploadedListener::class,
        ],
        AssetReuploaded::class => [
            AssetReuploadedListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        $this->bootAddonConfig();
    }

    protected function bootAddonConfig(): self
    {
        $this->mergeConfigFrom(__DIR__.'/../config/asset-metadata-importer.php', 'statamic.asset-metadata-importer');

        $this->publishes([
            __DIR__.'/../config/asset-metadata-importer.php' => config_path('statamic/asset-metadata-importer.php'),
        ], 'statamic-asset-metadata-importer-config');

        return $this;
    }
}

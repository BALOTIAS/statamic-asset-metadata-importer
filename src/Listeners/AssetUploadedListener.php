<?php

namespace Balotias\StatamicAssetMetadataImporter\Listeners;

use Balotias\StatamicAssetMetadataImporter\Jobs\ImportMetadataJob;
use Statamic\Events\AssetUploaded;

class AssetUploadedListener
{
    public function handle(AssetUploaded $event): void
    {
        if (!$event->asset->extensionIsOneOf(config('statamic.asset-metadata-importer.extensions'))) {
            return;
        }

        ImportMetadataJob::dispatch($event->asset);
    }
}

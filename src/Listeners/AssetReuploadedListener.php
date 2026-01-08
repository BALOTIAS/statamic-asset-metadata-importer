<?php

namespace Balotias\StatamicAssetMetadataImporter\Listeners;

use Balotias\StatamicAssetMetadataImporter\Jobs\ImportMetadataJob;
use Statamic\Events\AssetReuploaded;

class AssetReuploadedListener
{
    public function handle(AssetReuploaded $event): void
    {
        if (!config('statamic.asset-metadata-importer.overwrite_on_reupload')) {
            return;
        }

        if (!$event->asset->extensionIsOneOf(config('statamic.asset-metadata-importer.extensions'))) {
            return;
        }

        ImportMetadataJob::dispatch($event->asset);
    }
}

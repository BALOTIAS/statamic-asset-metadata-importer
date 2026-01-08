<?php

namespace Balotias\StatamicAssetMetadataImporter\Listeners;

use Balotias\StatamicAssetMetadataImporter\Jobs\ImportMetadataJob;
use Statamic\Events\AssetReuploaded;

class AssetReuploadedListener
{
    public function handle(AssetReuploaded $event): void
    {
        if (! config('statamic.metadata-importer.overwrite_on_reupload')) {
            return;
        }

        if (!$event->asset->extensionIsOneOf(config('statamic.metadata-importer.extensions'))) {
            return;
        }

        ImportMetadataJob::dispatch($event->asset);
    }
}

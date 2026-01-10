<?php

namespace Balotias\StatamicAssetMetadataImporter\Listeners;

use Balotias\StatamicAssetMetadataImporter\Jobs\ImportMetadataJob;
use Statamic\Events\AssetReuploaded;

class AssetReuploadedListener
{
    public function handle(AssetReuploaded $event): void
    {
        if (! config('statamic.asset-metadata-importer.overwrite_on_reupload')) {
            return;
        }

        $extensions = config('statamic.asset-metadata-importer.extensions');

        // If wildcard is used, allow all extensions
        if (in_array('*', $extensions)) {
            ImportMetadataJob::dispatch($event->asset);

            return;
        }

        if (! $event->asset->extensionIsOneOf($extensions)) {
            return;
        }

        ImportMetadataJob::dispatch($event->asset);
    }
}

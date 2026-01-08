<?php

namespace Balotias\StatamicAssetMetadataImporter\Jobs;

use Balotias\StatamicAssetMetadataImporter\Importer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Statamic\Assets\Asset;

class ImportMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public Asset $asset)
    {
        $this->queue = config('statamic.asset-metadata-importer.queue', 'default');
    }

    public function handle(): void
    {
        if (!config('statamic.asset-metadata-importer.fields')) {
            return;
        }

        new Importer($this->asset);
    }
}

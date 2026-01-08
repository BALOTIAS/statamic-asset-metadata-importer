<?php

namespace Balotias\StatamicAssetMetadataImporter\Tests;

use Balotias\StatamicAssetMetadataImporter\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}

<?php

namespace Balotias\StatamicAssetMetadataImporter\Tests;

use Balotias\StatamicAssetMetadataImporter\ServiceProvider;

class ServiceProviderTest extends TestCase
{

    public function test_it_registers_the_service_provider(): void
    {
        $this->assertInstanceOf(
            ServiceProvider::class,
            $this->app->getProvider(ServiceProvider::class)
        );
    }


    public function test_it_merges_config(): void
    {
        $this->assertIsArray(config('statamic.asset-metadata-importer'));
        $this->assertArrayHasKey('debug', config('statamic.asset-metadata-importer'));
        $this->assertArrayHasKey('fields', config('statamic.asset-metadata-importer'));
        $this->assertArrayHasKey('overwrite_on_reupload', config('statamic.asset-metadata-importer'));
        $this->assertArrayHasKey('extensions', config('statamic.asset-metadata-importer'));
        $this->assertArrayHasKey('queue', config('statamic.asset-metadata-importer'));
    }


    public function test_it_has_correct_default_config_values(): void
    {
        $config = config('statamic.asset-metadata-importer');

        $this->assertIsBool($config['debug']);
        $this->assertIsBool($config['overwrite_on_reupload']);
        $this->assertTrue($config['overwrite_on_reupload']);
        $this->assertIsArray($config['extensions']);
        $this->assertContains('jpg', $config['extensions']);
        $this->assertContains('jpeg', $config['extensions']);
        $this->assertEquals('default', $config['queue']);
    }


    public function test_it_registers_event_listeners(): void
    {
        $listeners = $this->app['events']->getListeners('Statamic\Events\AssetUploaded');
        $this->assertNotEmpty($listeners);

        $listeners = $this->app['events']->getListeners('Statamic\Events\AssetReuploaded');
        $this->assertNotEmpty($listeners);
    }
}

<?php

namespace Balotias\StatamicAssetMetadataImporter;

use Illuminate\Support\Facades\Log;
use PHPExif\Adapter\Exiftool as ExiftoolAdapter;
use PHPExif\Adapter\FFprobe as FFprobeAdapter;
use PHPExif\Adapter\ImageMagick as ImageMagickAdapter;
use PHPExif\Adapter\Native as NativeAdapter;
use PHPExif\Reader\Reader as MetadataReader;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Statamic\Assets\Asset;

class Importer
{
    protected array $metadata = [
        'data' => [],
        'rawData' => [],
    ];

    protected bool $hasDirtyData = false;

    public function __construct(public Asset $asset)
    {
        $this->readMetadata();
        $this->mapToAssetField();
        $this->save();
    }

    public function readMetadata(): void
    {
        $path = $this->asset->path();
        $resolvedPath = $this->asset->resolvedPath();

        // For local files, use the resolved path directly
        if (file_exists($resolvedPath)) {
            $filePath = $resolvedPath;
        } else {
            // For remote disks (S3, etc), create a temporary directory and download the file - will be deleted automatically
            $resource = $this->asset->stream();
            $tempDirectory = (new TemporaryDirectory)->deleteWhenDestroyed()->create();
            $tempPath = $tempDirectory->path(basename($path));

            // Copy the stream resource to the temporary file
            $tempFile = fopen($tempPath, 'w');
            stream_copy_to_stream($resource, $tempFile);
            fclose($tempFile);
            fclose($resource);

            $filePath = $tempPath;
        }

        // Read metadata from the file
        $this->metadata = $this->readFileMetadata($filePath);
        $this->log('Metadata read', $this->metadata);
    }

    private function readFileMetadata(string $filePath): array
    {
        $adapters = $this->getAdaptersForFile($filePath);

        if (empty($adapters)) {
            $this->log('No adapter configured for file type', $filePath);

            return [
                'data' => [],
                'rawData' => [],
            ];
        }

        // Try each adapter until we get metadata
        foreach ($adapters as $index => $adapter) {
            $adapterName = $this->getAdapterName($adapter);
            $this->log("Trying adapter #{$index}: {$adapterName}", $filePath);

            try {
                $reader = new MetadataReader($adapter);
                $exifMetadata = $reader->read($filePath);

                // If metadata found, return it
                if ($exifMetadata && (! empty($exifMetadata->getData()) || ! empty($exifMetadata->getRawData()))) {
                    $this->log("Metadata found using adapter: {$adapterName}");

                    return [
                        'data' => $exifMetadata->getData(),
                        'rawData' => $exifMetadata->getRawData(),
                    ];
                }

                $this->log("No metadata found with adapter: {$adapterName}", $filePath);
            } catch (\Exception $e) {
                $this->log("Adapter {$adapterName} failed: {$e->getMessage()}", $filePath);
                // Continue to next adapter
            }
        }

        // No adapter found metadata
        $this->log('Metadata not found with any adapter!', $filePath);

        return [
            'data' => [],
            'rawData' => [],
        ];
    }

    private function getAdaptersForFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $adapterMapping = config('statamic.asset-metadata-importer.adapter_mapping', []);
        $adapters = [];

        foreach ($adapterMapping as $adapterType => $extensions) {
            // Check if wildcard is used or if extension matches
            if (in_array('*', $extensions) || in_array($extension, $extensions)) {
                $adapter = $this->createAdapter($adapterType);
                if ($adapter) {
                    $adapters[] = $adapter;
                }
            }
        }

        return $adapters;
    }

    private function createAdapter(string $adapterType): ?object
    {
        return match ($adapterType) {
            'native' => new NativeAdapter,
            'exiftool' => new ExiftoolAdapter(
                [],
                config('statamic.asset-metadata-importer.exiftool_path', '') ?? ''
            ),
            'ffprobe' => new FFprobeAdapter(
                [],
                config('statamic.asset-metadata-importer.ffmpeg_path', '') ?? ''
            ),
            'imagick' => new ImageMagickAdapter,
            default => null,
        };
    }

    private function getAdapterName(object $adapter): string
    {
        return match (get_class($adapter)) {
            NativeAdapter::class => 'Native',
            ExiftoolAdapter::class => 'Exiftool',
            FFprobeAdapter::class => 'FFprobe',
            ImageMagickAdapter::class => 'ImageMagick',
            default => get_class($adapter),
        };
    }

    public function mapToAssetField(): void
    {
        $blueprint = $this->asset->container->blueprint();

        foreach (config('statamic.asset-metadata-importer.fields', []) as $field => $sources) {
            if (! $blueprint->hasField($field)) {
                continue;
            }

            $value = $this->getValueBySources($sources);
            if ($value) {
                $this->asset->set($field, $value);
                $this->hasDirtyData = true;
            }
        }
    }

    private function getValueBySources(string|array $sources): ?string
    {
        $sources = is_array($sources) ? $sources : explode(',', $sources);

        foreach ($sources as $source) {
            $value = $this->getValueBySource(str($source)->trim());

            if ($value) {
                return $value;
            }
        }

        return null;
    }

    private function getValueBySource(string $source): ?string
    {
        // Try exact match first
        $value = data_get($this->metadata['data'], $source) ?? data_get($this->metadata['rawData'], $source);

        if ($value) {
            return $value;
        }

        // If loose mapping is enabled and no exact match, try partial match
        if (config('statamic.asset-metadata-importer.loose_mapping', false)) {
            $value = $this->getValueByLooseMatch($source);
        }

        return $value;
    }

    private function getValueByLooseMatch(string $source): ?string
    {
        // Search in data array
        foreach ($this->metadata['data'] as $key => $value) {
            if (str_contains(mb_strtolower($key), mb_strtolower($source))) {
                return $value;
            }
        }

        // Search in rawData array
        foreach ($this->metadata['rawData'] as $key => $value) {
            if (str_contains(mb_strtolower($key), mb_strtolower($source))) {
                return $value;
            }
        }

        return null;
    }

    public function save(): void
    {
        if (! $this->hasDirtyData) {
            return;
        }

        $this->asset->saveQuietly();

        $this->hasDirtyData = false;
    }

    private function log(string $message, mixed $context = []): void
    {
        if (config('statamic.asset-metadata-importer.debug')) {
            $prettyContext = print_r($context, true);
            $text = "[Statamic Metadata Importer] Asset ID {$this->asset->id()}: {$message}";
            if (str($prettyContext)->trim()->isNotEmpty()) {
                $text .= " \n{$prettyContext}";
            }

            Log::debug($text);
        }
    }
}

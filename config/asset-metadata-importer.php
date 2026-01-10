<?php

return [
    /*
    | -------------------------------------------------------------------------
    | Debug
    | -------------------------------------------------------------------------
    |
    | Enable debug mode to log detailed information during the metadata
    | import process. Useful for troubleshooting.
    |
    */

    'debug' => env('ASSET_METADATA_IMPORTER_DEBUG', env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    |
    | Map your asset fields to metadata tags.
    |
    | The keys are field handles of the asset container blueprint. The values
    | are a comma separated list of php-exif or Exiftool tags.
    |
    | Example: 'your_field' => ['mapped_php_exif_field', 'raw_php_exif_field']
    |
    | php-exif mapped fields: https://github.com/LycheeOrg/php-exif/blob/v1.0.4/lib/PHPExif/Exif.php
    | NOTE: php-exif DOES NOT ensure that every tag is mapped correctly!
    | For more reliable results on additional formats, consider using Exiftool tags, e.g:
    | 'credit' => ['credit' # mapped value, 'XMP-photoshop:Credit' # raw value]
    */

    'fields' => [
        'alt' => 'title',
        'copyright' => ['copyright', 'XMP-photoshop:Copyright'],
        'credit' => ['credit', 'XMP-photoshop:Credit'],
    ],

    /*
    | --------------------------------------------------------------------------
    | Loose Mapping
    | --------------------------------------------------------------------------
    |
    | When enabled, the importer will attempt to map fields even if the exact
    | field handle does not exist in the asset container blueprint. This can be useful
    | for more flexible setups, but may lead to unexpected results.
    |
    | Fields example: 'credit' => ['credit', 'd']
    |  This would map 'credit' exactly first, but if not found,
    |  it would look for any metadata key containing 'credit' (e.g. XMP-photoshop:Credit) and then for any key containing 'd'.
    |
    */

    'loose_mapping' => false,

    /*
    |--------------------------------------------------------------------------
    | Overwrite on Re-upload
    |--------------------------------------------------------------------------
    |
    | When an image is re-uploaded, the metadata will be overwritten with
    | those of the new image. This can be disabled by setting
    | reupload to 'false'.
    |
    */

    'overwrite_on_reupload' => true,

    /*
    |--------------------------------------------------------------------------
    | Extensions
    |--------------------------------------------------------------------------
    |
    | Define the file extensions for which metadata should be imported.
    |
     */

    'extensions' => [
        'jpg', 'jpeg', 'tif', 'tiff',
        // Add more extensions if needed - you may need to configure Exiftool and/or FFMpeg paths as well,
        // and adjust adapter mapping.
        // 'png', 'webp', 'avif', 'mp4', 'mov',

        // Use '*' to support all extensions - requires Exiftool and/or FFMpeg configuration
        // '*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exiftool
    |--------------------------------------------------------------------------
    |
    | If you want to support additional image formats like PNG, WEBP, AVIF or more,
    | you need to provide the path to the exiftool binary.
    | See: https://exiftool.org/
    |
     */

    'exiftool_path' => env('ASSET_METADATA_IMPORTER_EXIFTOOL_PATH', null), // e.g. '/usr/local/bin/exiftool', 'C:\\exiftool\\exiftool.exe'

    /*
    |--------------------------------------------------------------------------
    | FFMpeg
    |--------------------------------------------------------------------------
    |
    | If you want to extract metadata from video files using ffmpeg, you need to provide the
    | path to the ffmpeg binary.
    | See: https://ffmpeg.org/
    |
     */

    'ffmpeg_path' => env('ASSET_METADATA_IMPORTER_FFMPEG_PATH', null), // e.g. '/usr/local/bin/ffmpeg', 'C:\\ffmpeg\\bin\\ffmpeg.exe'

    /*
    |--------------------------------------------------------------------------
    | Adapter Mapping
    |--------------------------------------------------------------------------
    |
    | Define file extensions to use specific metadata adapters.
    | Supported adapters: 'native' (PHPExif Native), 'exiftool' (PHPExif Exiftool), 'ffprobe' (PHPExif FFprobe), 'imagick' (PHPExif ImageMagick)
    | Comment out adapters you don't want to use.
    |
    | Example: 'native' => ['jpg', 'jpeg'], 'exiftool' => ['png', 'webp'], 'ffprobe' => ['mp4', 'mov'], 'imagick' => ['gif']
    |
     */
    'adapter_mapping' => [
        'native' => ['jpg', 'jpeg', 'tif', 'tiff'], // Use native PHPExif for common image formats - supports only jpg, jpeg, tif, tiff
        // 'exiftool' => ['*'], // Use exiftool for all formats
        // 'exiftool' => ['jpg', 'jpeg', 'tif', 'tiff', 'png', 'webp', 'avif'], // Use exiftool for certain image formats
        // 'ffprobe' => ['mp4', 'mov', 'avi', 'mkv'], // Use ffprobe for video files
        // 'imagick' => ['png', 'gif'], // Use ImageMagick adapter for certain formats (less reliable)
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | If the import metadata job is being queued, specify the name of the
    | target queue. This falls back to the 'default' queue.
    |
    */

    'queue' => 'default',

];

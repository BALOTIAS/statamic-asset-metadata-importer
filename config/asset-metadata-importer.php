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
    | php-exif mapped fields: https://github.com/PHPExif/php-exif/blob/master/lib/PHPExif/Exif.php
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
        # NOTE: To support PNG, WEBP, and AVIF, you must provide the exiftool binary, see below.
        # Exiftool supports many more formats, see: https://exiftool.org/#supported
        # 'png', 'webp', 'avif'
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
    | Queue
    |--------------------------------------------------------------------------
    |
    | If the import metadata job is being queued, specify the name of the
    | target queue. This falls back to the 'default' queue.
    |
    */

    'queue' => 'default',

];

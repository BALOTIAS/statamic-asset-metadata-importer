# Statamic Asset Metadata Importer

> Automatically import and map EXIF/IPTC metadata from images to your Statamic asset fields on upload.

## Overview

This addon automatically extracts metadata from uploaded images and maps it to your asset fields. When you upload an image with embedded metadata (like copyright information, credits, descriptions, etc.), this addon will read that data and populate your asset fields accordingly.

**Why use this addon?**  
Managing image metadata manually can be time-consuming and error-prone. Professional photographers and content creators often embed important information directly into their images using tools like Adobe Lightroom or Photoshop. This addon ensures that metadata travels with your images, automatically populating fields like alt text, copyright, credits, and more—saving you time and maintaining consistency across your asset library.

## Features

- **Automatic metadata extraction** on asset upload and re-upload
- **Flexible field mapping** - Map any asset field to EXIF/IPTC metadata tags
- **Multiple metadata sources** - Define fallback sources for each field
- **Queue support** - Process metadata import asynchronously for better performance
- **Exiftool integration** - Support for PNG, WEBP, AVIF and many more formats
- **Local and cloud storage** - Works with both local filesystems and remote storage (S3, etc.)
- **Configurable file extensions** - Choose which file types should be processed
- **Debug mode** - Detailed logging for troubleshooting

## Requirements

- Statamic 5.0 or higher
- PHP 8.1 or higher (PHP 8.2+ required for Laravel 11+)
- (Optional) Exiftool binary for extended format support

## Installation

Install the addon via Composer:

``` bash
composer require balotias/statamic-asset-metadata-importer
```

Publish the configuration file:

``` bash
php artisan vendor:publish --tag=statamic-asset-metadata-importer-config
```

This will create a `config/statamic/metadata-importer.php` configuration file.

## Configuration

### Basic Setup

Open `config/statamic/asset-metadata-importer.php` and configure your field mappings:

```php
'fields' => [
    'alt' => 'title',
    'copyright' => ['copyright', 'XMP-photoshop:Copyright'],
    'credit' => ['credit', 'XMP-photoshop:Credit'],
],
```

The keys are your asset field handles (defined in your asset container blueprint), and the values are the metadata tags to extract from the image.

### Field Mapping

You can map fields in two ways:

**Single source:**
```php
'alt' => 'title',
```

**Multiple sources (with fallback):**
```php
'copyright' => ['copyright', 'XMP-photoshop:Copyright'],
```

When using multiple sources, the addon will try each one in order and use the first value it finds.

### Available Metadata Tags

The addon uses [php-exif](https://github.com/PHPExif/php-exif) for metadata extraction. You can use:

- **Mapped fields** - Normalized field names from php-exif ([see list](https://github.com/PHPExif/php-exif/blob/master/lib/PHPExif/Exif.php))
- **Raw tags** - Direct EXIF/IPTC/XMP tags (e.g., `XMP-photoshop:Copyright`, `IPTC:Caption-Abstract`)

Common mappings:
- `title` - Image title/headline
- `description` - Image description
- `keywords` - Keywords/tags
- `copyright` - Copyright information
- `credit` - Photo credit/attribution
- `creator` - Creator/photographer name

### Supported File Extensions

By default, the addon processes JPG and TIFF files:

```php
'extensions' => [
    'jpg', 'jpeg', 'tif', 'tiff',
],
```

To support additional formats like PNG, WEBP, and AVIF, you need to install Exiftool (see below).

### Using Exiftool (Optional)

For better metadata support and additional file formats, install [Exiftool](https://exiftool.org/):

**macOS (via Homebrew):**
```bash
brew install exiftool
```

**Linux (Debian/Ubuntu):**
```bash
apt-get install libimage-exiftool-perl
```

**Windows:**  
Download from [exiftool.org](https://exiftool.org/) and extract to a directory.

Then configure the path in your `.env` file:

```env
ASSET_METADATA_EXIFTOOL_PATH=/usr/local/bin/exiftool
```

Or directly in the config file:

```php
'exiftool_path' => '/usr/local/bin/exiftool',
```

Once configured, you can add more extensions:

```php
'extensions' => [
    'jpg', 'jpeg', 'tif', 'tiff', 'png', 'webp', 'avif'
],
```

### Additional Options

**Overwrite on re-upload:**
```php
'overwrite_on_reupload' => true, // Set to false to preserve existing data
```

**Queue configuration:**
```php
'queue' => 'default', // Specify which queue to use
```

**Debug mode:**
```php
'debug' => env('ASSET_METADATA_IMPORTER_DEBUG', false),
```

Or in your `.env`:
```env
ASSET_METADATA_IMPORTER_DEBUG=true
```

## Usage

Once configured, the addon works automatically:

1. **Upload an image** through the Statamic control panel
2. **Metadata is extracted** and mapped to your configured asset fields
3. **Fields are automatically populated** based on your mapping configuration

That's it! No additional action required.

### Setting Up Asset Blueprint

Make sure your asset container blueprint includes the fields you want to populate. For example:

1. Go to **Assets** in the control panel
2. Select your asset container
3. Edit the **Blueprint**
4. Add fields like:
   - `alt` (Text field)
   - `copyright` (Text field)
   - `credit` (Text field)
   - etc.

## Example Workflow

1. Photographer exports images from Lightroom with embedded metadata
2. Images are uploaded to Statamic
3. The addon automatically extracts:
   - Title → `alt` field (for SEO and accessibility)
   - Copyright notice → `copyright` field
   - Photo credit → `credit` field
   - Description → `description` field
4. All metadata is immediately available without manual entry

## Troubleshooting

**No metadata is imported:**
- Verify your images actually contain metadata (check with an EXIF viewer)
- Ensure field handles in the config match your blueprint exactly
- Enable debug mode to see what's happening
- Check the Laravel logs at `storage/logs/laravel.log`

**Specific file types not working:**
- For PNG, WEBP, AVIF: Install and configure Exiftool
- Verify the file extension is listed in the `extensions` config

**Queue issues:**
- Ensure your queue worker is running: `php artisan queue:work`
- Check the queue configuration matches your setup

## License

This addon is licensed under the MIT License.

## Credits

* Inspired by [Image Metadata Importer](https://statamic.com/addons/heidkaemper/import-image-metadata)
* Developed by [Balotias](https://github.com/balotias)  
* Powered by [php-exif](https://github.com/PHPExif/php-exif)

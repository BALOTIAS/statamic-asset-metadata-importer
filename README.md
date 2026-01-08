# Statamic Asset Metadata Importer

> Automatically import and map EXIF/IPTC metadata from images to your Statamic asset fields on upload.

## 📖 Overview

This addon automatically extracts metadata from uploaded images and maps it to your asset fields. When you upload an image with embedded metadata (like copyright information, credits, descriptions, etc.), this addon will read that data and populate your asset fields accordingly.

**Why use this addon?**  
Managing image metadata manually can be time-consuming and error-prone. Professional photographers and content creators often embed important information directly into their images using tools like Adobe Lightroom or Photoshop. This addon ensures that metadata travels with your images, automatically populating fields like alt text, copyright, credits, and more, saving you time and maintaining consistency across your asset library.

## ✨ Features

- **Automatic metadata extraction** on asset upload and re-upload
- **Flexible field mapping** - Map any asset field to EXIF/IPTC metadata tags
- **Multiple metadata sources** - Define fallback sources for each field
- **Loose mapping mode** - Partial matching for flexible metadata extraction
- **Queue support** - Process metadata import asynchronously for better performance
- **Exiftool integration** - Support for PNG, WEBP, AVIF and many more formats
- **Local and cloud storage** - Works with both local filesystems and remote storage (S3, etc.)
- **Configurable file extensions** - Choose which file types should be processed
- **Debug mode** - Detailed logging for troubleshooting

## 📋 Requirements

- Statamic 5.0 or higher
- PHP 8.1 or higher (PHP 8.2+ required for Laravel 11+)
- (Optional) Exiftool binary for extended format support

## 📦 Installation

Install the addon via Composer:

``` bash
composer require balotias/statamic-asset-metadata-importer
```

Publish the configuration file:

``` bash
php artisan vendor:publish --tag=statamic-asset-metadata-importer-config
```

This will create a `config/statamic/asset-metadata-importer.php` configuration file.

## ⚙️ Configuration

### 🔧 Basic Setup

Open `config/statamic/asset-metadata-importer.php` and configure your field mappings:

```php
'fields' => [
    'alt' => 'title',
    'copyright' => ['copyright', 'XMP-photoshop:Copyright'],
    'credit' => ['credit', 'XMP-photoshop:Credit'],
],
```

The keys are your asset field handles (defined in your asset container blueprint), and the values are the metadata tags to extract from the image.

### 🗺️ Field Mapping

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

### 🔍 Loose Mapping

By default, the addon requires exact matches between your configured field sources and the metadata keys. However, you can enable **loose mapping** for more flexible matching:

```php
'loose_mapping' => true,
```

When loose mapping is enabled:

1. **Exact matches are tried first** - The addon always attempts to find exact matches before using loose matching
2. **Partial matches as fallback** - If no exact match is found, it searches for metadata keys that *contain* your search string (case-insensitive)
3. **Works with multiple sources** - Each source in your array is tried in order, using both exact and loose matching

**Example:**

```php
'fields' => [
    'credit' => ['credit', 'photoshop'],
],
'loose_mapping' => true,
```

With loose mapping enabled:
- First tries to find `credit` exactly
- If not found, searches for any key containing "credit" (e.g., `XMP-photoshop:Credit`, `IPTC:Credit`)
- Then tries `photoshop` exactly
- If not found, searches for any key containing "photoshop" (e.g., `XMP-photoshop:Copyright`)

**Use case:** This is particularly useful when:
- You want to capture metadata from various XMP/IPTC namespaces without knowing the exact tag names
- Different image sources use slightly different metadata structures
- You want more forgiving metadata extraction without specifying every possible variant

**Note:** While loose mapping provides flexibility, it may lead to unexpected results if your search terms are too generic (e.g., searching for "d" would match many keys). Always test with your actual images to ensure the desired metadata is being extracted.

### 🏷️ Available Metadata Tags

The addon uses [miljar/php-exif](https://github.com/PHPExif/php-exif) for metadata extraction. You can use:

- **Mapped fields** - Normalized field names from php-exif (common fields like `title`, `description`, `keywords`, etc.)
- **Raw tags** - Direct EXIF/IPTC/XMP tags (only in combination with Exiftool) (e.g., `XMP-photoshop:Copyright`, `IPTC:Caption-Abstract`)

Common mappings:
- `title` - Image title/headline
- `description` - Image description
- `keywords` - Keywords/tags
- `copyright` - Copyright information
- `credit` - Photo credit/attribution
- `creator` - Creator/photographer name

### 📄 Supported File Extensions

By default, the addon processes JPG and TIFF files:

```php
'extensions' => [
    'jpg', 'jpeg', 'tif', 'tiff',
],
```

To support additional formats like PNG, WEBP, and AVIF, you need to install Exiftool (see below).

### 🔧 Using Exiftool (Optional)

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
ASSET_METADATA_IMPORTER_EXIFTOOL_PATH=/usr/local/bin/exiftool
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

### ⚡ Additional Options


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

## 🚀 Usage

Once configured, the addon works automatically:

1. **Upload an image** through the Statamic control panel
2. **Metadata is extracted** and mapped to your configured asset fields
3. **Fields are automatically populated** based on your mapping configuration

That's it! No additional action required.

### ⚙️ How It Works

**Metadata Extraction:**  
The addon uses [miljar/php-exif](https://github.com/PHPExif/php-exif) to extract metadata from image files. By default, it uses PHP's native EXIF functions, which work well for JPG and TIFF files. For enhanced metadata support and additional formats (PNG, WEBP, AVIF), you can optionally configure Exiftool, which provides comprehensive metadata extraction capabilities across a wider range of file formats.

**Local Storage:**  
For assets stored on local filesystems, the addon reads metadata directly from the file path.

**Remote Storage (S3, etc.):**  
For assets stored on remote storage systems, the addon needs to temporarily download the file to read its metadata. This is because the underlying metadata extraction library requires a local file path to access the binary EXIF/IPTC data.

The temporary download process:
- Creates a temporary directory
- Downloads the file from your remote storage
- Extracts the metadata
- Automatically cleans up the temporary file after processing

This happens automatically and transparently during the upload process, so no additional configuration is needed.

### 📝 Setting Up Asset Blueprint

Make sure your asset container blueprint includes the fields you want to populate. For example:

1. Go to **Assets** in the control panel
2. Select your asset container
3. Edit the **Blueprint**
4. Add fields like:
   - `alt` (Text field)
   - `copyright` (Text field)
   - `credit` (Text field)
   - etc.

## 💡 Example Workflow

1. Photographer exports images from Lightroom with embedded metadata
2. Images are uploaded to Statamic
3. The addon automatically extracts:
   - Title → `alt` field (for SEO and accessibility)
   - Copyright notice → `copyright` field
   - Photo credit → `credit` field
   - Description → `description` field
4. All metadata is immediately available without manual entry

## 🔧 Troubleshooting

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

## 📄 License

This addon is licensed under the MIT License.

## 🙏 Credits

* Inspired by [Image Metadata Importer](https://statamic.com/addons/heidkaemper/import-image-metadata)
* Developed by [Balotias](https://github.com/balotias)  
* Powered by [miljar/php-exif](https://github.com/PHPExif/php-exif)


![Statamic Asset Metadata Importer](./statamic-asset-metadata-importer.jpeg)
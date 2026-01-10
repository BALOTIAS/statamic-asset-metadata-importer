# Statamic Asset Metadata Importer

[![Latest Version](https://img.shields.io/packagist/v/balotias/statamic-asset-metadata-importer.svg?style=flat-square)](https://packagist.org/packages/balotias/statamic-asset-metadata-importer) [![Total Downloads](https://img.shields.io/packagist/dt/balotias/statamic-asset-metadata-importer.svg?style=flat-square)](https://packagist.org/packages/balotias/statamic-asset-metadata-importer) [![License](https://img.shields.io/packagist/l/balotias/statamic-asset-metadata-importer.svg?style=flat-square)](https://packagist.org/packages/balotias/statamic-asset-metadata-importer) [![Statamic](https://img.shields.io/badge/Statamic-5.0+-FF269E?style=flat-square&logo=statamic)](https://statamic.com) [![Tests](https://github.com/BALOTIAS/statamic-asset-metadata-importer/workflows/Tests/badge.svg)](https://github.com/BALOTIAS/statamic-asset-metadata-importer/actions)

![Statamic Asset Metadata Importer](./statamic-asset-metadata-importer.jpeg)

> Automatically import and map EXIF/IPTC metadata from your media to your Statamic asset fields on upload.

## 📖 Overview

This addon automatically extracts metadata from uploaded images, videos and any supported file types, mapping it to your asset fields. When you upload media with embedded metadata (copyright information, credits, descriptions, etc.), this addon reads that data and populates your asset fields accordingly—saving you time and maintaining consistency across your asset library.

**Perfect for photographers, content creators, and anyone managing large media libraries.**

## ✨ Features

- **Automatic metadata extraction** on asset upload and re-upload
- **Multiple adapter support** - Choose the right tool for each file type
- **Multiple adapter fallback** - Try multiple adapters sequentially for better coverage
- **Video file support** - Extract metadata from MP4, MOV, AVI, and more
- **Wildcard support** - Process all file types or use pattern matching
- **Flexible field mapping** - Map any asset field to metadata tags with fallback options
- **Loose mapping mode** - Partial matching for flexible extraction
- **Queue support** - Async processing for better performance
- **Local and cloud storage** - Works with S3, DigitalOcean Spaces, etc.
- **29,000+ metadata tags** - Comprehensive support via Exiftool

## 📋 Requirements

- Statamic 5.0 or higher
- PHP 8.2 or higher
- (Optional) [Exiftool](https://exiftool.org/) for comprehensive format support ⭐ Recommended
- (Optional) [FFmpeg](https://ffmpeg.org/) for video file support
- (Optional) PHP Imagick extension

## 📦 Installation

Install via Composer:

```bash
composer require balotias/statamic-asset-metadata-importer
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=statamic-asset-metadata-importer-config
```

## 🚀 Quick Start

1. **Configure field mappings** in `config/statamic/asset-metadata-importer.php`:

```php
'fields' => [
    'alt' => 'title',
    'copyright' => ['copyright', 'XMP-photoshop:Copyright'],
    'credit' => ['credit', 'XMP-photoshop:Credit'],
],
```

2. **Set up your adapters** (optional, for best results):

```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg', 'tif', 'tiff'],
    'exiftool' => ['png', 'webp', 'avif'],      // Requires Exiftool
    'ffprobe' => ['mp4', 'mov', 'avi', 'mkv'],  // Requires FFmpeg
],
```

3. **Add fields to your asset blueprint:**
   - Navigate to Assets → Your Container → Blueprint
   - Add fields: `alt`, `copyright`, `credit`, etc.

4. **Upload your images!** Metadata will be extracted automatically.

## 📚 Documentation

- **[Configuration Guide](docs/configuration.md)** - Complete configuration options
- **[Adapter System](docs/adapters.md)** - Understanding adapters and installation
- **[Troubleshooting](docs/troubleshooting.md)** - Common issues and solutions

## 🎯 Adapter System

The addon supports multiple metadata extraction adapters:

| Adapter | Formats | Best For | Requirements |
|---------|---------|----------|--------------|
| **Native** | JPG, TIFF | Common images | None (built-in) |
| **Exiftool** ⭐ | [100+ formats](https://exiftool.org/#supported) | Maximum coverage | [Install Exiftool](docs/adapters.md#installing-exiftool) |
| **FFprobe** | [Video files](https://www.ffmpeg.org/ffprobe-all.html#File-Formats) | Videos | [Install FFmpeg](docs/adapters.md#installing-ffmpeg) |
| **ImageMagick** | [200+ formats](https://imagemagick.org/script/formats.php#supported) | Additional formats | PHP Imagick extension |

> 💡 **Recommendation:** Install [Exiftool](https://exiftool.org/) for the best experience. It supports [29,000+ metadata tags](https://exiftool.org/TagNames/index.html) across virtually all image and video formats.

[Learn more about adapters →](docs/adapters.md)

## ⚙️ How It Works

1. **Asset Upload** - You upload an image or video to Statamic
2. **Metadata Extraction** - The addon reads embedded metadata using your configured adapter
3. **Field Mapping** - Metadata is mapped to asset fields based on your configuration
4. **Automatic Population** - Fields are populated automatically

Works seamlessly with both local storage and remote storage (S3, DigitalOcean Spaces, etc.).

## 💡 Example Use Case

A photographer exports images from Lightroom with embedded metadata:

```
Image.jpg contains:
- Title: "Sunset over Mountains"
- Copyright: "© 2025 Jane Doe"
- Credit: "Jane Doe Photography"
- Description: "Beautiful sunset in the Alps"
```

After upload to Statamic, the asset fields are automatically populated:

```php
alt: "Sunset over Mountains"
copyright: "© 2025 Jane Doe"
credit: "Jane Doe Photography"
description: "Beautiful sunset in the Alps"
```

No manual data entry required! 🎉

## 🔧 Configuration Examples

### Basic Setup (Native Adapter)
```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg', 'tif', 'tiff'],
],
```

### Recommended Setup (with Exiftool)
```php
'adapter_mapping' => [
    'exiftool' => ['*'], // Exiftool for everything
],
```

### Different Adapters Setup (All Media Types)
```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg', 'tif', 'tiff'],
    'exiftool' => ['png', 'webp', 'avif', 'heic'],
    'ffprobe' => ['mp4', 'mov', 'avi', 'mkv'],
    'imagick' => ['gif', 'bmp'],
],
```

### Multiple Adapter Fallback (NEW)
```php
'adapter_mapping' => [
    'native' => ['jpg'],    // Try native first (fast)
    'exiftool' => ['jpg'],  // Fallback to exiftool if no metadata
],
```

When multiple adapters are configured for the same extension, they are tried sequentially until metadata is found. This provides better coverage and fallback options.

[View full configuration guide →](docs/configuration.md)

## 🐛 Troubleshooting

**No metadata imported?**
- Enable debug mode: `ASSET_METADATA_IMPORTER_DEBUG=true`
- Check logs at `storage/logs/laravel.log`
- Verify field handles match your blueprint

**File type not working?**
- PNG/WEBP/AVIF: Install [Exiftool](docs/adapters.md#installing-exiftool)
- Videos: Install [FFmpeg](docs/adapters.md#installing-ffmpeg)
- Check [adapter mapping](docs/adapters.md)

[Full troubleshooting guide →](docs/troubleshooting.md)

## 📄 License

MIT License - see [LICENSE.md](LICENSE.md)

## 🙏 Credits

- Inspired by [Image Metadata Importer](https://statamic.com/addons/heidkaemper/import-image-metadata)
- Developed by [Balotias](https://github.com/balotias)
- Powered by [lychee-org/php-exif](https://github.com/LycheeOrg/php-exif)


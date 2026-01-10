# Adapter System Guide

The addon supports multiple metadata extraction adapters, each optimized for different file formats.

## Table of Contents
- [Overview](#overview)
- [Available Adapters](#available-adapters)
- [Configuration](#configuration)
- [Installation Guides](#installation-guides)
- [Wildcard Support](#wildcard-support)
- [Performance Tips](#performance-tips)

## Overview

> 💡 **Recommendation:** We strongly recommend using **Exiftool** as your primary adapter. It supports [100+ file types](https://exiftool.org/#supported) and can read, write, and edit [29,000+ metadata tags](https://exiftool.org/TagNames/index.html), providing the most comprehensive metadata extraction across virtually all image and video formats.

Configure which adapter to use for which file types:

```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg', 'tif', 'tiff'],
    'exiftool' => ['png', 'webp', 'avif'],
    'ffprobe' => ['mp4', 'mov', 'avi', 'mkv'],
    'imagick' => ['gif', 'bmp'],
],
```

### Multiple Adapter Fallback

You can specify multiple adapters for the same file extension. If the first adapter fails or returns no metadata, the system automatically tries the next adapter. This provides fallback options for better metadata extraction coverage:

```php
'adapter_mapping' => [
    'native' => ['jpg'],    // Try native first (fast)
    'exiftool' => ['jpg'],  // Fallback to exiftool if no metadata found
],
```

Adapters are tried in the order they appear in your configuration. Once an adapter successfully returns metadata, the process stops and doesn't try remaining adapters.

## Available Adapters

### 1. Native (Default)
- **Requirements:** None (built-in PHP)
- **Formats:** JPG, JPEG, TIF, TIFF
- **Best for:** Common image formats, fastest performance
- **Note:** Limited format support but no external dependencies

### 2. Exiftool ⭐ Recommended
- **Requirements:** Exiftool binary
- **Formats:** [100+ file types](https://exiftool.org/#supported) including all major image formats
- **Metadata Support:** [29,000+ tags](https://exiftool.org/TagNames/index.html) (EXIF, IPTC, XMP, and more)
- **Best for:** Maximum format coverage and metadata extraction
- **Installation:** [See below](#installing-exiftool)

### 3. FFprobe
- **Requirements:** FFmpeg/FFprobe binary
- **Formats:** [Video files](https://www.ffmpeg.org/ffprobe-all.html#File-Formats) (MP4, MOV, AVI, MKV, etc.)
- **Best for:** Extracting metadata from video files
- **Installation:** [See below](#installing-ffmpeg)

### 4. ImageMagick (Imagick)
- **Requirements:** PHP Imagick extension
- **Formats:** [200+ formats](https://imagemagick.org/script/formats.php#supported) including PNG, GIF, BMP
- **Best for:** When Imagick extension is already installed
- **Note:** Less reliable than Exiftool but doesn't require external binary

## Configuration

### Basic Example
```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg', 'tif', 'tiff'],
],
```

### Recommended Setup
```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg'],        // Fast for common formats
    'exiftool' => ['png', 'webp', 'avif'], // Comprehensive support
],
```

### Complete Setup
```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg', 'tif', 'tiff'],
    'exiftool' => ['png', 'webp', 'avif', 'heic'],
    'ffprobe' => ['mp4', 'mov', 'avi', 'mkv'],
    'imagick' => ['gif', 'bmp'],
],
```

## Installation Guides

### Installing Exiftool

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

**Find the path:**
```bash
which exiftool  # macOS/Linux
# or
where exiftool  # Windows
```

**Configure in `.env`:**
```env
ASSET_METADATA_IMPORTER_EXIFTOOL_PATH=/usr/local/bin/exiftool
```

**Update adapter mapping:**
```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg'],
    'exiftool' => ['png', 'webp', 'avif'],
],
```

### Installing FFmpeg

**macOS (via Homebrew):**
```bash
brew install ffmpeg
```

**Linux (Debian/Ubuntu):**
```bash
apt-get install ffmpeg
```

**Windows:**  
Download from [ffmpeg.org](https://ffmpeg.org/) and add to PATH.

**Configure in `.env`:**
```env
ASSET_METADATA_IMPORTER_FFMPEG_PATH=/usr/local/bin/ffmpeg
```

**Update adapter mapping:**
```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg'],
    'ffprobe' => ['mp4', 'mov', 'avi', 'mkv'],
],
```

### Installing ImageMagick

**Check if already installed:**
```bash
php -m | grep imagick
```

**macOS (via Homebrew):**
```bash
brew install imagemagick
pecl install imagick
```

**Linux (Debian/Ubuntu):**
```bash
apt-get install php-imagick
```

**Update adapter mapping:**
```php
'adapter_mapping' => [
    'imagick' => ['png', 'gif', 'bmp'],
],
```

## Wildcard Support

You can use wildcards in adapter mappings to process all file types with a specific adapter:

```php
'adapter_mapping' => [
    'exiftool' => ['*'], // Use Exiftool for all file types
],
```

Or mix wildcards with specific extensions:

```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg'], // Fast processing for common formats
    'exiftool' => ['*'],          // Exiftool for everything else
],
```

**Note:** With multiple adapter fallback, adapters are tried in order. The first adapter that matches the file extension is tried first.

## Multiple Adapter Fallback Strategies

### Strategy 1: Fast First, Comprehensive Fallback
Try a fast adapter first, then fall back to a more comprehensive one if no metadata is found:

```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg'],   // Try fast native first
    'exiftool' => ['jpg', 'jpeg'], // Fallback to comprehensive exiftool
],
```

### Strategy 2: Mixed Format Coverage
Use different adapters for the same extension to maximize metadata extraction:

```php
'adapter_mapping' => [
    'imagick' => ['png'],   // Try ImageMagick first
    'exiftool' => ['png'],  // Fallback to Exiftool if needed
    'native' => ['png'],    // Last resort for PNG
],
```

### Strategy 3: Universal Fallback
Try specific adapters first, then use exiftool as a catch-all:

```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg', 'tif', 'tiff'],  // Fast for common formats
    'imagick' => ['png', 'gif'],                  // Imagick for these
    'ffprobe' => ['mp4', 'mov'],                  // FFprobe for video
    'exiftool' => ['*'],                          // Exiftool fallback for ALL types
],
```

### Strategy 4: Robustness Over Speed
Prioritize successful metadata extraction over performance:

```php
'adapter_mapping' => [
    'exiftool' => ['*'],    // Try exiftool first for everything
    'native' => ['*'],      // Fallback to native
    'imagick' => ['*'],     // Last resort: imagick
],
```

### How It Works

1. The system identifies all adapters that match the file extension
2. Adapters are tried **in the order they appear** in your configuration
3. If an adapter **fails** (throws an exception) or **returns no metadata**, the next adapter is tried
4. If an adapter **successfully returns metadata** (even if empty arrays), the process **stops**
5. If **all adapters fail**, an empty metadata array is returned

### Debugging Adapter Fallback

Enable debug mode to see which adapters are being tried:

```php
'debug' => true,
```

The log will show:
```
[Statamic Metadata Importer] Asset ID ...: Trying adapter #0: Native
[Statamic Metadata Importer] Asset ID ...: No metadata found with adapter: Native
[Statamic Metadata Importer] Asset ID ...: Trying adapter #1: Exiftool
[Statamic Metadata Importer] Asset ID ...: Metadata found using adapter: Exiftool
```

## Performance Tips

### Adapter Performance
- **Native:** Fastest (built-in PHP, no external processes)
- **Imagick:** Fast (PHP extension, no external processes)
- **Exiftool:** Moderate (spawns external process)
- **FFprobe:** Moderate (spawns external process, optimized for video)

### Recommendations
1. Use **Native** adapter for JPG/TIFF for best performance
2. Use **Exiftool** for comprehensive format support when needed
3. Use **FFprobe** specifically for video files
4. Use **Imagick** when the extension is available and you want to avoid external binaries

### Example Performance-Optimized Setup
```php
'adapter_mapping' => [
    'native' => ['jpg', 'jpeg', 'tif', 'tiff'], // Fastest for common formats
    'imagick' => ['png', 'gif'],                 // Fast for these formats
    'exiftool' => ['webp', 'avif', 'heic'],     // Comprehensive for modern formats
    'ffprobe' => ['mp4', 'mov', 'avi'],         // Specialized for video
],
```

---

**Back to:** [Configuration Guide](configuration.md)  
**Next:** [Troubleshooting Guide](troubleshooting.md)


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

**Important:** The first matching adapter is used, so order matters!

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


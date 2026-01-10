# Configuration Guide

This guide covers all configuration options for the Statamic Asset Metadata Importer.

## Table of Contents
- [Basic Setup](#basic-setup)
- [Field Mapping](#field-mapping)
- [Loose Mapping](#loose-mapping)
- [Available Metadata Tags](#available-metadata-tags)
- [Supported File Extensions](#supported-file-extensions)
- [Additional Options](#additional-options)

## Basic Setup

Open `config/statamic/asset-metadata-importer.php` and configure your field mappings:

```php
'fields' => [
    'alt' => 'title',
    'copyright' => ['copyright', 'XMP-photoshop:Copyright'],
    'credit' => ['credit', 'XMP-photoshop:Credit'],
],
```

The keys are your asset field handles (defined in your asset container blueprint), and the values are the metadata tags to extract from the image.

## Field Mapping

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

## Loose Mapping

By default, the addon requires exact matches between your configured field sources and the metadata keys. However, you can enable **loose mapping** for more flexible matching:

```php
'loose_mapping' => true,
```

When loose mapping is enabled:

1. **Exact matches are tried first** - The addon always attempts to find exact matches before using loose mapping
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

## Available Metadata Tags

The addon uses [lychee-org/php-exif](https://github.com/LycheeOrg/php-exif) for metadata extraction. You can use:

- **Mapped fields** - Normalized field names from php-exif (common fields like `title`, `description`, `keywords`, etc.)
- **Raw tags** - Direct EXIF/IPTC/XMP tags (only in combination with Exiftool) (e.g., `XMP-photoshop:Copyright`, `IPTC:Caption-Abstract`)

Common mappings:
- `title` - Image title/headline
- `description` - Image description
- `keywords` - Keywords/tags
- `copyright` - Copyright information
- `credit` - Photo credit/attribution
- `creator` - Creator/photographer name

## Supported File Extensions

Configure which file extensions should trigger metadata extraction:

```php
'extensions' => [
    'jpg', 'jpeg', 'tif', 'tiff', 'png', 'webp', 'avif'
],
```

You can also use a wildcard to process all file types:

```php
'extensions' => ['*'], // Process all uploaded files
```

## Additional Options

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

---

**Next:** [Adapter System Guide](adapters.md)


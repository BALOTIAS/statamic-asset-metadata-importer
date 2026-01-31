# Troubleshooting Guide

Common issues and solutions for the Statamic Asset Metadata Importer.

## Table of Contents
- [No Metadata is Imported](#no-metadata-is-imported)
- [Specific File Types Not Working](#specific-file-types-not-working)
- [Adapter-Specific Issues](#adapter-specific-issues)
- [Queue Issues](#queue-issues)
- [Remote Storage Issues](#remote-storage-issues)
- [Debug Mode](#debug-mode)

## No Metadata is Imported

### Check Your Images
Verify your images actually contain metadata:
- Use an EXIF viewer tool (e.g., ExifTool, Adobe Bridge, or online tools)
- Some image optimization tools strip metadata
- Social media platforms often remove metadata when images are uploaded/downloaded

### Verify Configuration
1. **Field handles must match exactly:**
   ```php
   // In your config
   'fields' => [
       'alt' => 'title',  // 'alt' must match your blueprint field handle
   ],
   ```

2. **Check blueprint setup:**
   - Go to Assets → Your Container → Blueprint
   - Ensure fields like `alt`, `copyright`, `credit` exist
   - Field handles are case-sensitive

3. **Verify file extensions:**
   ```php
   'extensions' => ['jpg', 'jpeg', 'png'], // Must include your file type
   ```

4. **Check adapter mapping:**
   ```php
   'adapter_mapping' => [
       'native' => ['jpg', 'jpeg'], // Your file extension must be mapped
   ],
   ```

### Enable Debug Mode
Add to your `.env`:
```env
ASSET_METADATA_IMPORTER_DEBUG=true
```

Then check `storage/logs/laravel.log` for detailed information about:
- Which metadata was found
- Which fields were mapped
- Any errors that occurred

## Specific File Types Not Working

### PNG, WEBP, AVIF Files
The native PHP adapter only supports JPG/TIFF. For other formats:

1. **Install Exiftool:**
   ```bash
   # macOS
   brew install exiftool
   
   # Linux
   apt-get install libimage-exiftool-perl
   ```

2. **Configure the path in `.env`:**
   ```env
   ASSET_METADATA_IMPORTER_EXIFTOOL_PATH=/usr/local/bin/exiftool
   ```

3. **Update adapter mapping:**
   ```php
   'adapter_mapping' => [
       'native' => ['jpg', 'jpeg'],
       'exiftool' => ['png', 'webp', 'avif'],
   ],
   ```

### Video Files (MP4, MOV, AVI, MKV)
Video files require FFmpeg/FFprobe:

1. **Install FFmpeg:**
   ```bash
   # macOS
   brew install ffmpeg
   
   # Linux
   apt-get install ffmpeg
   ```

2. **Configure the path in `.env`:**
   ```env
   ASSET_METADATA_IMPORTER_FFMPEG_PATH=/usr/local/bin/ffmpeg
   ```

3. **Update adapter mapping:**
   ```php
   'adapter_mapping' => [
       'ffprobe' => ['mp4', 'mov', 'avi', 'mkv'],
   ],
   ```

### GIF, BMP Files
These formats can use ImageMagick:

1. **Check if Imagick is installed:**
   ```bash
   php -m | grep imagick
   ```

2. **Install if needed:**
   ```bash
   # macOS
   brew install imagemagick
   pecl install imagick
   
   # Linux
   apt-get install php-imagick
   ```

3. **Update adapter mapping:**
   ```php
   'adapter_mapping' => [
       'imagick' => ['gif', 'bmp'],
   ],
   ```

## Adapter-Specific Issues

### Native Adapter
**Problem:** "Metadata not found or unsupported file type"

**Solution:**
- Native adapter only supports JPG, JPEG, TIF, TIFF
- Use Exiftool for other formats

### Exiftool Adapter
**Problem:** "Given path to the exiftool binary is invalid"

**Solutions:**
1. **Verify the binary exists:**
   ```bash
   which exiftool
   # or
   /usr/local/bin/exiftool -ver
   ```

2. **Check the path in your config:**
   ```env
   ASSET_METADATA_IMPORTER_EXIFTOOL_PATH=/usr/local/bin/exiftool
   ```

3. **Ensure binary is executable:**
   ```bash
   chmod +x /usr/local/bin/exiftool
   ```

4. **Windows users:** Use full path with `.exe`:
   ```env
   ASSET_METADATA_IMPORTER_EXIFTOOL_PATH=C:\exiftool\exiftool.exe
   ```

### FFprobe Adapter
**Problem:** "Could not read the video"

**Solutions:**
1. **Verify FFmpeg is installed:**
   ```bash
   ffmpeg -version
   ffprobe -version
   ```

2. **Check the path:**
   ```env
   ASSET_METADATA_IMPORTER_FFMPEG_PATH=/usr/local/bin/ffmpeg
   ```

3. **Ensure the video file is valid:**
   ```bash
   ffprobe your-video.mp4
   ```

### ImageMagick Adapter
**Problem:** "Class 'Imagick' not found"

**Solutions:**
1. **Verify Imagick extension is installed:**
   ```bash
   php -m | grep imagick
   ```

2. **Install the extension:**
   ```bash
   # macOS
   pecl install imagick
   
   # Linux
   apt-get install php-imagick
   ```

3. **Restart your web server after installation**

**Problem:** "improper image header"

**Solution:**
- This happens with fake/corrupted image files
- Verify your image file is valid
- Try opening it with an image viewer

## Queue Issues

### Jobs Not Processing
**Problem:** Metadata import jobs are queued but not processing

**Solutions:**
1. **Start the queue worker:**
   ```bash
   php artisan queue:work
   ```

2. **Check your queue configuration** in `config/statamic/asset-metadata-importer.php`:
   ```php
   'queue' => 'default',
   ```

3. **Verify queue connection** in `config/queue.php`

4. **For production, use a process manager** (Supervisor, systemd):
   ```bash
   # Example Supervisor config
   [program:laravel-worker]
   command=php /path/to/artisan queue:work
   autostart=true
   autorestart=true
   ```

### Jobs Failing Silently
**Solutions:**
1. **Check failed jobs table:**
   ```bash
   php artisan queue:failed
   ```

2. **View failed job details:**
   ```bash
   php artisan queue:failed --id=1
   ```

3. **Enable debug mode** to see detailed errors:
   ```env
   ASSET_METADATA_IMPORTER_DEBUG=true
   ```

## Remote Storage Issues

### S3 or Cloud Storage
**Problem:** "File not found" or slow processing

**Solutions:**
1. **Verify storage configuration** in `config/filesystems.php`

2. **Check credentials** for your cloud storage provider

3. **Ensure proper permissions:**
   - Read access to assets
   - Temporary directory write access

4. **Note:** Remote files are downloaded temporarily for metadata extraction, then cleaned up automatically

### Temporary Directory Issues
**Problem:** "Failed to create temporary directory"

**Solutions:**
1. **Check disk space:**
   ```bash
   df -h
   ```

2. **Verify temp directory permissions:**
   ```bash
   ls -la /tmp
   ```

3. **Ensure PHP can write to temp:**
   ```bash
   php -r "echo sys_get_temp_dir();"
   ```

## Debug Mode

### Enabling Debug Mode
Add to your `.env`:
```env
ASSET_METADATA_IMPORTER_DEBUG=true
```

Or in config:
```php
'debug' => true,
```

### What Debug Mode Shows
Debug mode logs detailed information to `storage/logs/laravel.log`:

- Asset ID being processed
- Metadata extraction results
- Which fields were mapped
- Any errors or exceptions
- Adapter selection decisions

### Example Debug Output
```
[Statamic Metadata Importer] Asset ID abc123: Metadata read
Array
(
    [data] => Array
        (
            [title] => My Photo
            [copyright] => © 2025 John Doe
        )
    [rawData] => Array
        (
            [EXIF:Copyright] => © 2025 John Doe
        )
)
```

### Analyzing Debug Logs
1. **Look for "Metadata read"** - Shows what metadata was extracted
2. **Check field mappings** - Verifies your configuration
3. **Look for errors** - Shows any exceptions or problems
4. **Verify adapter selection** - Confirms which adapter was used

---

**Still having issues?** 

- Check the [GitHub Issues](https://github.com/BALOTIAS/statamic-asset-metadata-importer/issues)
- Review the [adapter documentation](adapters.md)
- Enable debug mode and check the logs

**Back to:** [Main README](../README.md)


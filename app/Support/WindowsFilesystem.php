<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;

class WindowsFilesystem extends Filesystem
{
    /**
     * Write the contents of a file, replacing it atomically if possible.
     * On Windows, handles NTFS rename locking (Access is denied code: 5) gracefully.
     *
     * @param  string  $path
     * @param  string  $content
     * @param  int|null  $mode
     * @return void
     */
    public function replace($path, $content, $mode = null)
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        if (! is_null($mode)) {
            @chmod($tempPath, $mode);
        } else {
            @chmod($tempPath, 0777 - umask());
        }

        file_put_contents($tempPath, $content);

        try {
            // Attempt standard atomic rename
            rename($tempPath, $path);
        } catch (\Throwable $e) {
            // Fallback for Windows NTFS locking where rename fails if destination is being read
            if (@copy($tempPath, $path)) {
                @unlink($tempPath);
            } else {
                // If copy also fails, wait 50ms and try once more
                usleep(50000);
                if (@copy($tempPath, $path)) {
                    @unlink($tempPath);
                } else {
                    @unlink($tempPath);
                    throw $e;
                }
            }
        }
    }
}

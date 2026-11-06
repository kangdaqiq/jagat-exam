<?php

namespace Tests\Unit;

use App\Support\WindowsFilesystem;
use Tests\TestCase;

class WindowsFilesystemTest extends TestCase
{
    public function test_windows_filesystem_can_replace_file_atomically_without_locking_error(): void
    {
        $fs = app('files');
        $this->assertInstanceOf(WindowsFilesystem::class, $fs);

        $path = storage_path('framework/views/test_unit_view.tmp');

        // Initial write
        $fs->replace($path, 'initial content');
        $this->assertFileExists($path);
        $this->assertEquals('initial content', file_get_contents($path));

        // Overwrite / replace while file exists
        $fs->replace($path, 'updated content');
        $this->assertEquals('updated content', file_get_contents($path));

        // Cleanup
        $fs->delete($path);
        $this->assertFileDoesNotExist($path);
    }
}

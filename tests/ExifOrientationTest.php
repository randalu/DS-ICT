<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

class ExifOrientationTest extends TestCase
{
    public function testApplyExifOrientationErrorPath(): void
    {
        $img = imagecreatetruecolor(10, 10);

        // When using a non-existent file or empty string, exif_read_data will trigger an error
        // On PHP 8 it throws a ValueError when the string is empty
        // The catch block in gd_apply_exif_orientation intercepts this and returns null
        $result = gd_apply_exif_orientation($img, "");
        $this->assertNull($result, "Expected null when throwing ValueError on empty path");

        // Also test with a path that isn't a real file, just to be sure it returns null
        $resultNotExists = gd_apply_exif_orientation($img, "/does/not/exist/fake_file.jpg");
        $this->assertNull($resultNotExists, "Expected null when exif_read_data emits a warning on non-existent file");

        imagedestroy($img);
    }
}

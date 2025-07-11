<?php

namespace Tests\Unit\Modules;

require_once __DIR__ . '/../../../../phive/phive.php';

use PHPUnit\Framework\TestCase;

//  php vendor/bin/phpunit --bootstrap vendor/autoload.php --testdox tests/Unit/Modules/FilerTest.php
class FilerTest extends TestCase
{
    private $filer;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->filer = phive('Filer');
    }

    private function generateFiles(): array
    {
        $image_path = __DIR__ . '/../Mock/Filer/green.png'; // 3359 bytes
        $csv_file = __DIR__ . '/../Mock/Filer/green.csv'; // 46 bytes
        $malicious_exe = __DIR__ . '/../Mock/Filer/malicious.exe';
        $malicious_png = __DIR__ . '/../Mock/Filer/malicious.png';

        return [
            [
                'name' => basename($image_path),
                'type' => mime_content_type($image_path),
                'tmp_name' => $image_path,
                'error' => 0,
                'size' => filesize($image_path)
            ],
            [
                'name' => basename($csv_file),
                'type' => mime_content_type($csv_file),
                'tmp_name' => $csv_file,
                'error' => 0,
                'size' => filesize($csv_file)
            ],
            [
                'name' => basename($malicious_exe),
                'type' => mime_content_type($malicious_exe),
                'tmp_name' => $malicious_exe,
                'error' => 0,
                'size' => filesize($malicious_exe)
            ],
            [
                'name' => basename($malicious_png),
                'type' => mime_content_type($malicious_png),
                'tmp_name' => $malicious_png,
                'error' => 0,
                'size' => filesize($malicious_png)
            ]
        ];
    }

    private function addNullByteToString($string): string
    {
        return "\0" . $string;
    }

    private function generateLongFileName($characters = 255): string
    {
        return str_repeat('a', $characters);
    }

    // basic test which should pass if files are valid
    public function testValidFileUpload(): void
    {
        [$image, $csv, $m_exe, $m_png] = $this->generateFiles();

        $result = $this->filer->validateFileObject($image);
        $this->assertTrue($result['success']);
        $this->assertSame($result['filename'], $image['name']);

        $result = $this->filer->validateFileObject($csv);
        $this->assertTrue($result['success']);
        $this->assertSame($result['filename'], $csv['name']);

        $result = $this->filer->validateFileObject($m_exe);
        $this->assertFalse($result['success']);

        $result = $this->filer->validateFileObject($m_png);
        $this->assertFalse($result['success']);
    }

    // basic test which should pass if files are valid
    public function testValidFileUploadOriginalName(): void
    {
        [$image, $csv] = $this->generateFiles();

        $result = $this->filer->validateFileObject($image, false);
        $this->assertTrue($result['success']);
        $this->assertSame($result['filename'], $image['name']);

        $result = $this->filer->validateFileObject($csv, false);
        $this->assertTrue($result['success']);
        $this->assertSame($result['filename'], $csv['name']);
    }

    // Validate file extension -  file extension must match one of the predefined list
    public function testValidFileExtension(): void
    {
        [$image, $csv] = $this->generateFiles();

        $image['name'] = 'green.php';
        $result = $this->filer->validateFileObject($image);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_extension');

        $csv['name'] = 'green.php';
        $result = $this->filer->validateFileObject($csv);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_extension');
    }

    // Validate file type
    public function testValidFileType(): void
    {
        [$image, $csv, $m_exe, $m_png] = $this->generateFiles();
        $result = $this->filer->validateFileObject($m_png);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_mime_type');
    }

    // Validate Content-Type header
    public function testValidContentType(): void
    {
        [$image, $csv, $m_exe, $m_png] = $this->generateFiles();
        $image['type'] = $m_exe['type'];
        $result = $this->filer->validateFileObject($image);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_content_type');

        [$image, $csv] = $this->generateFiles();
        $csv['type'] = $m_png['type'];
        $result = $this->filer->validateFileObject($csv);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_content_type');
    }

    // Validate file size
    public function testValidFileSize(): void
    {
        // provided file size is greater than the allowed size
        [$image, $csv] = $this->generateFiles();

        $image['size'] = 12 * 1024 * 1024;
        $result = $this->filer->validateFileObject($image);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_size');

        $csv['size'] = 12 * 1024 * 1024;
        $result = $this->filer->validateFileObject($csv);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_size');

        // provided file size is greater than the provided allowed size parameter
        [$image, $csv] = $this->generateFiles();

        $result = $this->filer->validateFileObject($image, true, 1);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_size');

        $result = $this->filer->validateFileObject($csv, true, 1);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_size');

        // provided file size is fine but the file itself is greater than the allowed size
        $image['size'] = 1;
        $result = $this->filer->validateFileObject($image, true, 2);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_size_internal');

        $csv['size'] = 1;
        $result = $this->filer->validateFileObject($csv, true, 2);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_size_internal');

        // size 0 not allowed
        $image['size'] = 0;
        $result = $this->filer->validateFileObject($image, true, 2);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_size');

        $csv['size'] = 0;
        $result = $this->filer->validateFileObject($csv, true, 2);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_size');
    }

    // Prevent Null bytes
    public function testValidFileNameNullByte(): void
    {
        [$image, $csv] = $this->generateFiles();

        // name to start with a null byte
        $image['name'] = $this->addNullByteToString($image['name']);

        $result = $this->filer->validateFileObject($image);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_name_null');

        $result = $this->filer->validateFileObject($image, true);
        $this->assertTrue($result['success']);

        // name to start with a null byte
        $csv['name'] = $this->addNullByteToString($csv['name']);

        $result = $this->filer->validateFileObject($csv);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_name_null');

        $result = $this->filer->validateFileObject($csv, true);
        $this->assertTrue($result['success']);
    }

    // Prevent multiple extensions
    public function testValidFileNameMultipleExtensions(): void
    {
        [$image, $csv] = $this->generateFiles();

        $image['name'] .= ".png";
        $result = $this->filer->validateFileObject($image);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_multiple_extensions');

        $result = $this->filer->validateFileObject($image, true);
        $this->assertTrue($result['success']);

        $csv['name'] .= ".csv";
        $result = $this->filer->validateFileObject($csv);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_multiple_extensions');

        $result = $this->filer->validateFileObject($csv, true);
        $this->assertTrue($result['success']);
    }

    // Prevent filename size exceeding configured number of characters
    public function testValidFileNameSize(): void
    {
        [$image, $csv] = $this->generateFiles();

        $image['name'] = $this->generateLongFileName(500) . $image['name'];
        $result = $this->filer->validateFileObject($image);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_name_size');

        $csv['name'] = $this->generateLongFileName(500) . $csv['name'];
        $result = $this->filer->validateFileObject($csv);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_name_size');
    }

    // Prevent blacklisted characters in file name
    public function testValidFileNameBlacklist(): void
    {
        [$image, $csv] = $this->generateFiles();

        $image['name'] = '{' . $image['name'];
        $result = $this->filer->validateFileObject($image);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_name_blocked');

        $csv['name'] = '{' . $csv['name'];
        $result = $this->filer->validateFileObject($csv);
        $this->assertFalse($result['success']);
        $this->assertSame($result['error'], 'file_upload.invalid_file_name_blocked');
    }

}

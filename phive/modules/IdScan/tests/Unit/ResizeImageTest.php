<?php

use IdScan\IdScanImage;
use Intervention\Image\ImageManager;
use Tests\Unit\Mock\StdoutLogger;

uses(Tests\Unit\PestPhiveBase::class);

it('image resize', function ($file_path, $output) {
    // load image from tests/assets directory and base64 encode it
    $logger = new StdoutLogger();

    try {
        $original = (new ImageManager(['driver' => 'imagick']))->make($file_path);
        $result = new IdScanImage($file_path);
    } catch (\Exception $e) {
        echo "Wrong image path: $file_path".PHP_EOL;
        $this->fail($e->getMessage());
    }
    $file_size = phive('IdScan')->getSetting('max_upload_filesize');
    // check that the image is not empty
    $result_image_size = $result->size();
    if ($output == 'higher') {
        expect($result_image_size)->toBeGreaterThan(0)->toBeLessThan($original->filesize())->toBeLessThan($file_size);
    } elseif ($output == 'lower') {
        expect($result_image_size)->toBeGreaterThan(0)->toBeLessThan($file_size);
    }

    // store the image in the tests/assets directory with a different name to check it manually
    $path =  '/tmp/' . $original->filename . rand() .'.jpg';
    $result->save($path);

})->with([
   'a very big jpg photo' => [ __DIR__ . '/assets/image.jpg', 'higher'],
   'a smaller jpg photo' => [ __DIR__.'/assets/img.png', 'lower'],
   'a smaller png photo' => [ __DIR__.'/assets/image3.png', 'lower'],
   'a larger png photo' => [ __DIR__.'/assets/image4.png', 'higher'],
]);


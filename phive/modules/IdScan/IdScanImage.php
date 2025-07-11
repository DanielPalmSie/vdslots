<?php

namespace IdScan;

use IdScan\Exceptions\MissingSettingException;
use IdScan\lib\FittedImage;

class IdScanImage extends FittedImage
{
    /**
     * @throws MissingSettingException
     * @throws Exceptions\NotReadableImageException
     */
    public function __construct($raw_image)
    {
        $max_file_size = phive('IdScan')->getSetting('max_upload_filesize');
        if (empty($max_file_size)) {
            throw new MissingSettingException('max_upload_filesize');
        }

        parent::__construct($max_file_size, $raw_image);
    }

}
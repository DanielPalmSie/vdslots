<?php

namespace IdScan\lib;

use IdScan\Exceptions\ImageTooBigException;
use IdScan\Exceptions\NotReadableImageException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class FittedImage
{
    private const EXTENSION = 'jpg';
    private const MAX_LENGTH = 1500;
    private Image $image;
    private int $maxFileSize;
    private bool $is_wither_than_taller = false;

    /**
     * @throws NotReadableImageException
     */
    public function __construct(int $max_file_size, $raw_image)
    {
        $this->maxFileSize = $max_file_size;

        try {
            $this->image = (new ImageManager(['driver' => 'imagick']))->make($raw_image);
            $this->fitImage();
        } catch (\Exception $e) {
            throw new NotReadableImageException($e->getMessage());
        }
    }

    /**
     * This will constrain the image to the maximum size allowed and set the extension to jpg
     *
     * @return void
     * @throws ImageTooBigException
     */
    private function fitImage(): void
    {
        $this->image->encode(self::EXTENSION);
        $length = min($this->image->getWidth(), $this->image->getHeight(), self::MAX_LENGTH);

        while ($this->isTooBig($length)) {
            $this->resizeImage($length);
            $length = $length - 100;
        }

        if ($this->size() > $this->maxFileSize) {
            throw new ImageTooBigException();
        }
    }

    private function isTooBig(int $length): bool
    {
        return $this->size() > $this->maxFileSize && $length > 0;
    }

    private function resizeImage($length)
    {
        $this->isWitherThanTaller() ?
            $this->image->widen($length) :
            $this->image->heighten($length);
    }

    public function size(): int
    {
        return strlen($this->image->encode()->encoded);
    }

    public function mime(): string
    {
        return $this->image->mime();
    }

    public function extension(): string
    {
        return self::EXTENSION;
    }

    public function encoded(): string
    {
        return base64_encode($this->image->encode()->encoded);
    }

    public function save($path)
    {
        $this->image->save($path);
    }

    private function isWitherThanTaller(): bool
    {
        if (empty($this->is_wither_than_taller)) {
            $this->is_wither_than_taller = $this->image->getWidth() > $this->image->getHeight();
        }
        return $this->is_wither_than_taller;
    }
}
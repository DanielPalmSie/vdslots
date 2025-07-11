<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

/**
 * Seeder to fix image size settings for specific casino images in dbet brand
 * This seeder updates the 'original' flag for specific image IDs to ensure proper display
 */
class FixDbetCasinoImageSize extends Seeder
{
    private const BRAND_DBET = 'dbet';
    private const TABLE_IMAGE_DATA = 'image_data';
    private const IMAGE_IDS_CASINO = [73142, 73143];

    private Connection $connection;
    private string $brand;
    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = self::TABLE_IMAGE_DATA;
    }

    public function up()
    {
        if ($this->brand !== self::BRAND_DBET) {
            return;
        }

        foreach (self::IMAGE_IDS_CASINO as $imageId) {
            $image = $this->connection
                ->table($this->table)
                ->where('image_id', '=', $imageId)
                ->first();

            if ($image) {
                $this->connection
                    ->table($this->table)
                    ->where('image_id', '=', $imageId)
                    ->where('width', '=', $image->width)
                    ->where('height', '=', $image->height)
                    ->update(['original' => 1]);
            }
        }
    }

    public function down()
    {
        if ($this->brand !== self::BRAND_DBET) {
            return;
        }

        foreach (self::IMAGE_IDS_CASINO as $imageId) {
            $image = $this->connection
                ->table($this->table)
                ->where('image_id', '=', $imageId)
                ->first();

            if ($image) {
                $this->connection
                    ->table($this->table)
                    ->where('image_id', '=', $imageId)
                    ->where('width', '=', $image->width)
                    ->where('height', '=', $image->height)
                    ->update(['original' => 0]);
            }
        }
    }
}

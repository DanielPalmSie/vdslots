<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class CreateCarouselBannerRotationBox extends Seeder
{
    protected string $tablePages = 'pages';
    protected string $tableBoxes = 'boxes';
    protected string $boxesAttributesTable = 'boxes_attributes';

    private Connection $connection;
    private array $pageData = [];
    private string $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        if ($this->isTargetBrand()) {
            $this->pageData = [
                [
                    'parent_id' => $this->getDesktopPageParentID(),
                    'alias' => '.',
                    'filename' => 'diamondbet/generic.php',
                    'cached_path' => '/.',
                    'priority' => 24,

                ],
                [
                    'parent_id' => $this->getMobilePageParentID(),
                    'alias' => 'mobile',
                    'filename' => 'diamondbet/mobile.php',
                    'cached_path' => '/mobile',
                    'priority' => 3, // for other branch is 0
                ],
            ];
        }
    }

    public function up()
    {
        $this->init();
        if (!$this->isTargetBrand()) {
            return;
        }

        foreach ($this->pageData as $data) {
            $pageId = $this->getPageID($data['cached_path'], $data['alias']);
            $boxClass = 'CarouselBannerRotationBox';

            $boxExists = $this->connection
                ->table($this->tableBoxes)
                ->where('box_class', $boxClass)
                ->where('page_id', $pageId)
                ->exists();

            if (!$boxExists) {
                $this->connection
                    ->table($this->tableBoxes)
                    ->insert([
                        'container' => 'full',
                        'box_class' => $boxClass,
                        'page_id' => $pageId,
                        'priority' => $data['priority'] ?? 0,
                    ]);
            }

            // Fetch inserted (or existing) box ID
            $boxId = $this->getBoxID($boxClass, $pageId);

            // Check if attributes already exist
            $attrExists = $this->connection
                ->table($this->boxesAttributesTable)
                ->where('box_id', $boxId)
                ->where('attribute_name', 'check_perm')
                ->first();

            if (empty($attrExists)) {
                $this->createBoxAttributes($boxId, $data);
            }
        }
    }

    public function down()
    {
        $this->init();
        if (!$this->isTargetBrand()) {
            return;
        }

        $boxClass = 'CarouselBannerRotationBox';

        foreach ($this->pageData as $data) {
            $pageId = $this->getPageID($data['cached_path'], $data['alias']);

            $box = $this->connection
                ->table($this->tableBoxes)
                ->where('container', 'full')
                ->where('box_class', $boxClass)
                ->where('priority', $data['priority'] ?? 0)
                ->where('page_id', $pageId)
                ->first();

            if ($box) {
                $boxId = (int) $box->box_id;

                $this->connection
                    ->table($this->boxesAttributesTable)
                    ->where('box_id', $boxId)
                    ->delete();

                $this->connection
                    ->table($this->tableBoxes)
                    ->where('box_id', $boxId)
                    ->delete();
            }
        }
    }

    private function getDesktopPageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', 0)
            ->where('alias', '.')
            ->where('filename', 'diamondbet/generic.php')
            ->where('cached_path', '/.')
            ->first();

        return (int) $page->page_id;
    }

    private function getMobilePageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', 0)
            ->where('alias', 'mobile')
            ->where('filename', 'diamondbet/mobile.php')
            ->where('cached_path', '/mobile')
            ->first();

        return (int) $page->page_id;
    }

    private function getPageID(string $cachedPath, string $alias): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', $alias)
            ->where('cached_path', $cachedPath)
            ->first();

        return (int) $page->page_id;
    }

    private function getBoxID(string $boxClass, int $pageId): int
    {
        $box = $this->connection
            ->table($this->tableBoxes)
            ->where('box_class', $boxClass)
            ->where('page_id', $pageId)
            ->first();

        return (int) $box->box_id;
    }

    private function isTargetBrand(): bool
    {
        return in_array($this->brand, [phive('BrandedConfig')::BRAND_DBET], true);
    }

    private function createBoxAttributes(int $boxId, array $data)
    {
        // Build complete fields array based on actual partners value
        $fields = [
            'check_perm',
            'enable_carousel',
            'background_image', 
            'partners',
            'excluded_countries',
        ];
        
        // Set background image based on page type
        $backgroundImage = $data['alias'] === 'mobile' 
            ? 'partners_carousel_banner_mobile.jpg'
            : 'partners_carousel_banner_desktop.jpg';
            
        $defaults = [
            'check_perm' => 0,
            'enable_carousel' => 1,
            'background_image' => $backgroundImage,
            'partners' => 7,
            'excluded_countries' => '',
        ];

        $currentPartners = $defaults['partners'];
        
        // Define partner logo images
        $partnerLogos = [
            'Elitserien_carousel.png',
            'SvenskaBandyforbundet_carousel.png',
            'SvenskaBasket_carousel.png',
            'SvenskaBasketligan_carousel.png',
            'PropertyLinkoping_carousel.png',
            'PixboWallenstam_carousel.png',
            'HandbollsLigan_carousel.png',
        ];
        
        for ($i = 1; $i <= $currentPartners; $i++) {
            $fields[] = "partner_logo_$i";
            $defaults["partner_logo_$i"] = $partnerLogos[$i - 1] ?? '';  
            $fields[] = "partner_link_$i";
            $defaults["partner_link_$i"] = '';
        }

        // Prepare attributes data for insertion
        $attributesData = [];
        foreach ($fields as $field) {
            $attributesData[] = [
                'box_id' => $boxId,
                'attribute_name' => $field,
                'attribute_value' => $defaults[$field],
            ];
        }

        $this->connection->table($this->boxesAttributesTable)->insert($attributesData);
    }
}

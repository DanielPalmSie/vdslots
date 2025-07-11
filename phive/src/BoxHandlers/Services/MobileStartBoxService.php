<?php

declare(strict_types=1);

namespace Videoslots\BoxHandlers\Services;

use MobileStartBoxBase;

require_once __DIR__ . '/../../../modules/BoxHandler/boxes/diamondbet/MobileStartBoxBase.php';

final class MobileStartBoxService
{
    /**
     * @var String
     */
    private const PAGE_ALIAS_MOBILE = 'mobile';

    /**
     * @var String
     */
    private const BOX_CLASS = 'MobileStartBox';


    /**
     * @var \MobileStartBoxBase|null
     */
    private ?MobileStartBoxBase $mobile_start_box_base = null;

    /**
     * @var string
     */
    private string $category;

    /**
     * @param string $category
     */
    public function __construct(string $category)
    {
        $this->category = $category;
        $box_id = $this->findBoxId();

        if (! is_null($box_id)) {
            $this->mobile_start_box_base = new MobileStartBoxBase($box_id);
            $this->mobile_start_box_base->init(true);
        }
    }

    /**
     * @api
     *
     * @return array
     */
    public function getBanners(): array
    {
        if (is_null($this->mobile_start_box_base)) {
            return [];
        }

        return $this->mobile_start_box_base->getDynamicData(true, $this->mobile_start_box_base->cur_lang);
    }

    /**
     * @return int|null
     */
    private function findBoxId(): ?int
    {
        $page_id = $this->getPageId();

        if (is_null($page_id)) {
            return null;
        }

        $query = sprintf(
            "SELECT box_id FROM boxes WHERE page_id = %s AND box_class = '%s'",
            $page_id,
            self::BOX_CLASS
        );

        return (int) phive('SQL')->getValue($query);
    }

    /**
     * @return int|null
     */
    private function getPageId(): ?int
    {
        $pager = phive('Pager');
        $page = $pager->getPageByAlias(self::PAGE_ALIAS_MOBILE);

        if (is_null($page)) {
            return null;
        }

        if ($this->category !== self::PAGE_ALIAS_MOBILE) {
            $parent_id = (int) $page['page_id'];
            $page = $pager->getPageByAlias($this->category, $parent_id);

            if (is_null($page)) {
                return null;
            }
        }

        return (int) $page['page_id'];
    }
}

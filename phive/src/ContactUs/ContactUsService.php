<?php

declare(strict_types=1);

namespace Videoslots\ContactUs;

use EmailFormBoxBase;
use Laraphive\Domain\Content\DataTransferObjects\ContactUs\ContactUsData;
use Videoslots\ContactUs\Factories\ContactUsFactory;

require_once __DIR__ . '/../../modules/BoxHandler/boxes/diamondbet/EmailFormBoxBase.php';

final class ContactUsService
{
    /**
     * @var String
     */
    private const PARENT_PAGE_ALIAS = 'customer-service';

    /**
     * @var String
     */
    private const PAGE_ALIAS = 'contact-us';

    /**
     * @var String
     */
    private const BOX_CLASS = 'EmailFormBox';

    /**
     * @var int
     */
    public const MAP_IMAGE_WIDTH = 300;

    /**
     * @var int
     */
    public const MAP_IMAGE_HEIGHT = 125;

    /**
     * @var bool
     */
    private bool $is_api;

    /**
     * @var \EmailFormBoxBase|null
     */
    private ?EmailFormBoxBase $box;

    /**
     * @param bool $is_api
     * @param \EmailFormBoxBase|null $box
     */
    public function __construct(bool $is_api, ?EmailFormBoxBase $box = null)
    {
        $this->is_api = $is_api;
        $this->box = is_null($box) ? $this->getInstance() : $box;
    }

    /**
     * @return \EmailFormBoxBase|null
     */
    private function getInstance(): ?EmailFormBoxBase
    {
        $box_id = $this->findBoxId();

        if (! is_null($box_id)) {
            return new EmailFormBoxBase($box_id);
        }

        return null;
    }

    /**
     * @return \Laraphive\Domain\Content\DataTransferObjects\ContactUs\ContactUsData|null
     */
    public function getContactUsData(): ?ContactUsData
    {
        if (is_null($this->box)) {
            return null;
        }

        return ContactUsFactory::create($this->is_api, $this->box);
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
        $page = $pager->getPageByAlias(self::PARENT_PAGE_ALIAS);

        if (is_null($page)) {
            return null;
        }

        $parent_id = (int) $page['page_id'];
        $page = $pager->getPageByAlias(self::PAGE_ALIAS, $parent_id);

        if (is_null($page)) {
            return null;
        }


        return (int) $page['page_id'];
    }
}

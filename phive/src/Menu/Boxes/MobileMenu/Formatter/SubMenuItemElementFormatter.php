<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData;
use Videoslots\Menu\Boxes\MobileMenu\Helper;

final class SubMenuItemElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "sub-menu-item";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData
     */
    private SubMenuItemData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData $data
     */
    public function __construct(SubMenuItemData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        ?>

        <li>
            <a <?= $this->data->getHref() ?>
                <?= $this->data->isCurrent() ? 'class="sub-menu-active"' : '' ?>
            >
                <span class="icon <?= $this->data->getIcon() ?>"></span>
                <?= t($this->data->getContent()->getValue()) ?>
            </a>
        </li>

        <?php
    }

    /**
     * @return array
     */
    public function toJson(): array
    {
        $content = $this->data->getContent();

        return [
            'content' => [
                'value' => Helper::formatAliases($content->getValue()),
                'type' => $content->getType(),
            ],
            'graphic' => [
                'source' => $this->data->getIcon(),
                'type' => 'icon',
            ],
            'operation' => [
                'source' => $this->data->getHref(),
                'type' => 'navigation',
            ],
            'page_id' => $this->data->getPageId(),
        ];
    }
}

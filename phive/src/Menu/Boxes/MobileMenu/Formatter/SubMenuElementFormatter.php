<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuData;

final class SubMenuElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "sub-menu-element";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuData
     */
    private SubMenuData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuData $data
     */
    public function __construct(SubMenuData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        ?>

        <ul>

        <?php

        foreach ($this->data->getItems() as $item) {
            (new SubMenuItemElementFormatter($item))->toHtml();
        }

        ?>

        </ul>

        <?php
    }

    /**
     * @return array
     */
    public function toJson(): array
    {
        $items = [];
        foreach ($this->data->getItems() as $item) {
            $items[] = (new SubMenuItemElementFormatter($item))->toJson();
        }

        return $items;
    }
}

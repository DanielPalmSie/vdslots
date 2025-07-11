<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\ChatItemData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemDepositData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemWithdrawalData;
use Videoslots\Menu\Boxes\MobileMenu\Helper;

final class MenuElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "menu-element";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\MenuData
     */
    private MenuData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\MenuData $data
     */
    public function __construct(MenuData $data)
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
            if ($item instanceof ChatItemData) {
                (new ChatItemElementFormatter($item))->toHtml();
            } elseif ($item instanceof MenuItemWithdrawalData) {
                (new MenuItemElementWithdrawalFormatter($item))->toHtml();
            } elseif ($item instanceof MenuItemDepositData) {
                (new MenuItemElementDepositFormatter($item))->toHtml();
            } elseif ($item instanceof MenuItemData) {
                (new MenuItemElementFormatter($item))->toHtml();
            }
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
            if ($item instanceof ChatItemData) {
                $items[] = (new ChatItemElementFormatter($item))->toJson();
            } elseif ($item instanceof MenuItemData) {
                $items[] = (new MenuItemElementFormatter($item))->toJson();
            }
        }

        $content = $this->data->getContent();

        return [
            'content' => [
                'value' => Helper::formatAliases($content->getValue()),
                'type' => $content->getType(),
            ],
            'items' => $items,
        ];
    }
}

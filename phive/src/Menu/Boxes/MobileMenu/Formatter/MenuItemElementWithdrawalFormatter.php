<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemDataInterface;
use Videoslots\Menu\Boxes\MobileMenu\Helper;

final class MenuItemElementWithdrawalFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "menu-item";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData
     */
    private MenuItemDataInterface $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData $data
     */
    public function __construct(MenuItemDataInterface $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        $translation = phive('Localizer')->getPotentialString($this->data->getContent()->getValue());
        ?>

        <li>
            <a onclick="<?= withdrawalGo() ?>">
                <img src="<?= Helper::menuImagePath($this->data->getIcon()) ?>" alt=""/>
                <?= $this->data->isCurrent() ? '&raquo;' : '' ?>
                <?= $translation ?>
                <?= $this->data->isCurrent() ? '&laquo;' : '' ?>
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
        ];
    }
}

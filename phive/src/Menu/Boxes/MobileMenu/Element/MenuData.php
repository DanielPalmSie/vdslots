<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class MenuData
{
    /**
     * @var array<\Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData>
     */
    private array $items;

    /**
     * @var ContentData
     */
    private ContentData $content;

    /**
     * @param array<\Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData> $items
     * @param ContentData $content
     */
    public function __construct(array $items, ContentData $content)
    {
        $this->items = $items;
        $this->content = $content;
    }

    /**
     * @return array<\Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return ContentData
     */
    public function getContent(): ContentData
    {
        return $this->content;
    }
}

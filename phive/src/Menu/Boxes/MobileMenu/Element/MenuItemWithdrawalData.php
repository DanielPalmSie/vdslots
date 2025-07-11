<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class MenuItemWithdrawalData implements MenuItemDataInterface
{
    /**
     * @var string
     */
    private string $icon;

    /**
     * @var ContentData
     */
    private ContentData $content;

    /**
     * @var bool
     */
    private bool $current;

    /**
     * @var string
     */
    private string $href;

    /**
     * @param string $icon
     * @param ContentData $content
     * @param bool $current
     * @param string $href
     */
    public function __construct(
        string $icon,
        ContentData $content,
        bool $current,
        string $href
    ) {
        $this->icon = $icon;
        $this->content = $content;
        $this->current = $current;
        $this->href = $href;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @return ContentData
     */
    public function getContent(): ContentData
    {
        return $this->content;
    }

    /**
     * @return bool
     */
    public function isCurrent(): bool
    {
        return $this->current;
    }

    /**
     * @return string
     */
    public function getHref(): string
    {
        return $this->href;
    }
}

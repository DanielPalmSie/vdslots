<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class MenuItemData implements MenuItemDataInterface
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
     * @var int
     */
    private int $priority;

    /**
     * @param string $icon
     * @param ContentData $content
     * @param bool $current
     * @param string $href
     * @param int $priority
     */
    public function __construct(
        string $icon,
        ContentData $content,
        bool $current,
        string $href,
        int $priority
    ) {
        $this->icon = $icon;
        $this->content = $content;
        $this->current = $current;
        $this->href = $href;
        $this->priority = $priority;
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

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}

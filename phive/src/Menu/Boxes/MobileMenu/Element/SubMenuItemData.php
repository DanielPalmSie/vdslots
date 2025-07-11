<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class SubMenuItemData
{
    /**
     * @var string
     */
    private string $params;

    /**
     * @var string
     */
    private string $icon;

    /**
     * @var ContentData
     */
    private ContentData $content;

    /**
     * @var bool|string
     */
    private bool $current;

    /**
     * @var string
     */
    private string $href;

    /**
     * @var int
     */
    private int $pageId;

    /**
     * @param string $params
     * @param string $icon
     * @param bool $current
     * @param ContentData $content
     * @param string $href
     * @param int $pageId
     */
    public function __construct(
        string $params,
        string $icon,
        bool $current,
        ContentData $content,
        string $href,
        int $pageId
    ) {
        $this->params = $params;
        $this->icon = $icon;
        $this->current = $current;
        $this->content = $content;
        $this->href = $href;
        $this->pageId = $pageId;
    }

    /**
     * @return string
     */
    public function getParams(): string
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @return bool
     */
    public function isCurrent(): bool
    {
        return $this->current;
    }

    /**
     * @return ContentData
     */
    public function getContent(): ContentData
    {
        return $this->content;
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
    public function getPageId(): int
    {
        return $this->pageId;
    }
}

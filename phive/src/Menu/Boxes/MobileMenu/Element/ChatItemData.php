<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class ChatItemData
{
    /**
     * @var string
     */
    private string $url;

    /**
     * @var ContentData
     */
    private ContentData $content;

    /**
     * @var string
     */
    private string $imgPath;

    /**
     * @var int
     */
    private int $priority;

    /**
     * @param string $url
     * @param ContentData $content
     * @param string $imgPath
     * @param int $priority
     */
    public function __construct(string $url, ContentData $content, string $imgPath, int $priority)
    {
        $this->url = $url;
        $this->content = $content;
        $this->imgPath = $imgPath;
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
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
    public function getImgPath(): string
    {
        return $this->imgPath;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}

<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class HeadLineData
{
    /**
     * @var ContentData
     */
    private ContentData $content;

    /**
     * @param ContentData $content
     */
    public function __construct(ContentData $content)
    {
        $this->content = $content;
    }

    /**
     * @return ContentData
     */
    public function getContent(): ContentData
    {
        return $this->content;
    }
}

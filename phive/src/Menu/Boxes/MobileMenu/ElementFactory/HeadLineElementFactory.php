<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\ContentData;
use Videoslots\Menu\Boxes\MobileMenu\Element\HeadLineData;

final class HeadLineElementFactory
{
    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\ContentData $contentData
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\HeadLineData
     */
    public function create(ContentData $contentData): HeadLineData
    {
        return new HeadLineData($contentData);
    }
}

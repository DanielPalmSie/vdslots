<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\HeadLineData;
use Videoslots\Menu\Boxes\MobileMenu\Helper;

final class HeadLineElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "headline";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\HeadLineData
     */
    private HeadLineData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\HeadLineData $data
     */
    public function __construct(HeadLineData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        ?>

        <div class="acc-left-headline"><?= t($this->data->getContent()->getValue()) ?></div>

        <?php
    }

    /**
     * @deprecated Will not be returned on API
     *
     * @return array
     */
    public function toJson(): array
    {
        $content = $this->data->getContent();

        return [
            'element-type' => self::ELEMENT_TYPE,
            'content' => [
                'value' => Helper::formatAliases($content->getValue()),
                'type' => $content->getType(),
            ],
        ];
    }
}

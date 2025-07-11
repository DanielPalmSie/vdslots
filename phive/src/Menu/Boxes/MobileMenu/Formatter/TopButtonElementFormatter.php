<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\TopButtonData;

final class TopButtonElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "top-button";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\TopButtonData
     */
    private TopButtonData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\TopButtonData $data
     */
    public function __construct(TopButtonData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        btnDefaultXxl(t($this->data->getAlias()), '', depGo());
    }

    /**
     * @deprecated Will not be returned on API
     *
     * @return array
     */
    public function toJson(): array
    {
        return [
            'element-type' => self::ELEMENT_TYPE,
            'alias' => $this->data->getAlias(),
            'url' => $this->data->getUrl(),
        ];
    }
}

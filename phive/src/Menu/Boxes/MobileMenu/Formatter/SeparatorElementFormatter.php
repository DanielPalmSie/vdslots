<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\SeparatorData;

final class SeparatorElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "separator";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\SeparatorData
     */
    private SeparatorData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\SeparatorData $data
     */
    public function __construct(SeparatorData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        ?>

        <div class="menu-separator">
            <span class="medium-bold"><?php et($this->data->getAlias()) ?></span>
        </div>

        <?php
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
        ];
    }
}

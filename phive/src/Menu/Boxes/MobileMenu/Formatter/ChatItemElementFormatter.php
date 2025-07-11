<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\ChatItemData;
use Videoslots\Menu\Boxes\MobileMenu\Helper;

final class ChatItemElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ACTION_OPEN_ZENDESK = "open-zendesk";

    /**
     * @var string
     */
    public const ELEMENT_TYPE = "chat-item";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\ChatItemData
     */
    private ChatItemData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\ChatItemData $data
     */
    public function __construct(ChatItemData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        ?>

        <li class="pointer" onclick="<?= $this->data->getUrl() ?>">
            <a>
                <img src="<?= $this->data->getImgPath() ?>" alt="" />
                <?php et($this->data->getContent()->getValue()) ?>
            </a>
        </li>

        <?php
    }

    /**
     * @return array
     */
    public function toJson(): array
    {
        $content = $this->data->getContent();

        return [
            'content' => [
                'value' => Helper::formatAliases($content->getValue()),
                'type' => $content->getType(),
            ],
            'graphic' => [
                'source' => $this->data->getImgPath(),
                'type' => 'icon',
            ],
            'operation' => [
                'source' => self::ACTION_OPEN_ZENDESK,
                'type' => 'action',
            ],
        ];
    }
}

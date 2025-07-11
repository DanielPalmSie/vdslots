<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\ChatItemData;
use Videoslots\Menu\Boxes\MobileMenu\Element\ContentData;
use Videoslots\Menu\Boxes\MobileMenu\Helper;

final class ChatItemFactory
{
    /**
     * @param bool $isApi
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\ChatItemData
     */
    public function create(bool $isApi): ChatItemData
    {
        $data = phive()->getSetting('chat_support_disabled') ? 'chat.offline' : 'help.start.live.chat.headline';

        return new ChatItemData(
            phive('Localizer')->getChatUrl(),
            new ContentData($data, 'alias'),
            Helper::getImgPath($isApi),
            $this->getChatItemPriority()
        );
    }

    /**
     * @return int
     */
    private function getChatItemPriority(): int
    {
        return (int) phive('SQL')->getValue("SELECT priority FROM menus WHERE alias = 'live-chat'");
    }
}

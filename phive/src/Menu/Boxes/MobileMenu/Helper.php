<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu;

final class Helper
{
    /**
     * @var string
     */
    public const MENU_MOBILE_MAIN = "mobile-main-menu";

    /**
     * @var string
     */
    public const CHAT_IMAGE_PATH = "topmost-menu-chat";

    /**
     * @var string
     */
    public const CHAT_DISABLED_IMAGE_PATH = "topmost-menu-chat-disabled";

    /**
     * @var string
     */
    public const DEFAULT_MENU_ALIAS = "mobile.menu";

    /**
     * @param string $alias
     *
     * @return string
     */
    public static function menuImagePath(string $alias): string
    {
        return '/diamondbet/images/' . brandedCss() . 'mobile/' . $alias . '.png';
    }

    /**
     * @param string $params
     *
     * @return string
     */
    public static function parseHref(string $params): string
    {
        if (str_contains($params, '/profile')) {
            $params = str_replace($params, 'profile', 'profile/');
        }

        $format = explode('/', $params);

        array_pop($format);

        $result = end($format);

        return ($result === 'mobile') ? 'home' : (string)$result;
    }

    /**
     * @param bool $isApi
     *
     * @return string
     */
    public static function getImgPath(bool $isApi): string
    {
        $isDisabled = phive()->getSetting('chat_support_disabled');
        $disabledImagePath = '/diamondbet/images/' . self::CHAT_DISABLED_IMAGE_PATH . '.png';
        $useBrandLivechatIcon = phive('DBUserHandler')->getSetting('use_brand_livechat_icon');

        if ($isApi) {
            $imgPath = $isDisabled ? self::CHAT_DISABLED_IMAGE_PATH : self::CHAT_IMAGE_PATH;
        } else if(phive()->isMobile()) {
                $imgPath = $isDisabled ? $disabledImagePath : ($useBrandLivechatIcon
                    ? self::menuImagePath('livechat')
                    : fupUri(self::CHAT_IMAGE_PATH . '.png', true));
        } else {
            $imgPath = $isDisabled ? $disabledImagePath : fupUri(self::CHAT_IMAGE_PATH . '.png', true);
        }

        return $imgPath;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public static function getType(string $data): string
    {
        $formattedData = self::formatAliases($data);
        // This check is done because our alias column in the database supports only ASCII characters
        $stringCollation = mb_detect_encoding($formattedData, 'ASCII', true);

        if ($stringCollation === 'ASCII' && ! empty(phive('Localizer')->getStringLanguages($formattedData))) {
            return 'alias';
        }

        return 'string';
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public static function formatAliases(string $data): string
    {
        if (self::isAlias($data)) {
            return str_replace('#', '', $data);
        }

        return $data;
    }

    /**
     * @param string $data
     *
     * @return bool
     */
    public static function isAlias(string $data): bool
    {
        return substr($data, 0, 1) === '#';
    }
}

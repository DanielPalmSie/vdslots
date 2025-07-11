<?php

declare(strict_types=1);

namespace Videoslots\ContactUs\Factories;

use EmailFormBoxBase;
use Laraphive\Domain\Content\DataTransferObjects\ContactUs\CaptchaData;
use Laraphive\Domain\Content\DataTransferObjects\ContactUs\ContactInformationData;
use Laraphive\Domain\Content\DataTransferObjects\ContactUs\ContactUsData;
use Laraphive\Domain\Content\DataTransferObjects\ContactUs\FormData;
use Laraphive\Domain\Content\DataTransferObjects\ContactUs\InputData;
use Videoslots\ContactUs\ContactUsService;

final class ContactUsFactory
{
    /**
     * @param bool $is_api
     * @param \EmailFormBoxBase $box
     *
     * @return @return \Laraphive\Domain\Content\DataTransferObjects\ContactUs\ContactUsData
     */
    public static function create(bool $is_api, EmailFormBoxBase $box): ContactUsData
    {
        $user = cu();
        $box_id = $box->getId();
        $headline = "eform.headline.$box_id";
        $description = "eform.description.$box_id.html";
        $map_alias = 'contact.map.right';
        $domain = phive('DBUserHandler')->getSiteUrl();
        $captcha = null;

        if ($is_api) {
            list($map) = phive("ImageHandler")->img(
                $map_alias,
                ContactUsService::MAP_IMAGE_WIDTH,
                ContactUsService::MAP_IMAGE_HEIGHT
            );

            $captcha_session_key = 'captcha_contact_us_' . \Str::uuid()->toString();
            $captcha_image = base64_encode(file_get_contents(
                $domain .
                '/phive/modules/Former/captcha/simple-php-captcha.php?_CAPTCHA&reset=true&t=false&captcha_session_key='
                . $captcha_session_key
            ));
            $captcha = new CaptchaData($captcha_image, $captcha_session_key);
        } else {
            $map = $map_alias;
        }

        $iconPath = ($is_api ? $domain : '') . '/diamondbet/images/' . brandedCss() . 'support/';
        $emailIcon = $iconPath . 'Mail.png';
        $addressIcon = $iconPath . 'Home.png';

        $form = new FormData(
            new InputData('from', 'text', empty($user) ? '' : $user->getAttr('email'), 'email.from'),
            new InputData('subject', 'text', '', 'email.subject'),
            new InputData('message', 'textarea', '', 'email.message'),
            new InputData('captcha', 'text', '', ''),
            new InputData('', 'submit', 'submit', ''),
            $captcha
        );

        $contactInformation = new ContactInformationData(
            'contact.info.headline',
            'contact.us.right.html',
            'you.can.contact.us.at',
            $map,
            'actual.support.email',
            $emailIcon,
            'our.address',
            $addressIcon
        );

        return new ContactUsData($headline, $description, $form, $contactInformation);
    }
}

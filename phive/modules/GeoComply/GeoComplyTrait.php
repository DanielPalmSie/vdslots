<?php

namespace GeoComply;

use GeoComply;

trait GeoComplyTrait
{

    public function loadGeoComplyCSS()
    {
        if (!$this->getLicSetting('geocomply')['ENABLED']) {
            return;
        }

        loadCss("/diamondbet/css/" . brandedCss() . "geo-comply.css");
    }

    public function loadGeoComplyJs($context = 'login')
    {
        if (!$this->getLicSetting('geocomply')['ENABLED']) {
            return;
        }

        $geoComplyAuth = phive('GeoComply')->getSetting('auth');

        loadJs($geoComplyAuth['libUrl']);
        loadJs('/phive/modules/GeoComply/js/gc-helper.js');
        loadJs('/phive/modules/GeoComply/js/gc-lib.js');

        if ($context == 'global' && isLogged()) {
            loadJs('/phive/modules/GeoComply/js/gc-global-checks.js');
        } else if (phive()->isMobile()) {
            loadJs('/phive/modules/GeoComply/js/gc-mobile.js');
        } else {
            loadJs('/phive/modules/GeoComply/js/gc-desktop.js');
        }
    }

    /**
     * Prevents bets from users with expired geolocation
     *
     * @param \DBUser $user
     * @return bool
     */
    public function geoComplyPlayBlock(\DBUser $user): bool
    {
        if (!$this->getLicSetting('geocomply')['ENABLED']) {
            return false;
        }
        $geoComply = phive('GeoComply');
        $geoComply->setSkipChangeIpCheck(); // IP is from the game provider (we can't check remIp)
        return !$geoComply->hasVerifiedIp($user);
    }

    /**
     * Logs out any user that is playing with expired geolocation
     *
     * @return array
     */
    public function geoComplyCron(): array
    {
        // If user GeoComply data is expired and it's currently playing a game, we have to force a logout (fe tampering)
        /** @var GeoComply $geoComply */
        $geoComply = phive('GeoComply');
        $logout = [];
        // Set an expiration threshold as we don't want to log out by error users that leave games after a bet
        // needed as checkPlayerIsPlayingAGame is renewed after every bet and lasts 60 secs, could cause false positives
        $geoComply->setSkipChangeIpCheck(); // IP is from the cron (we can't check remIp)
        $geoComply->setSkipSessionMismatchCheck(); // IP is from the cron (we can't check current session)

        foreach ($geoComply->getExpiredUsers() as $user_id) {
            if (phive("Casino")->checkPlayerIsPlayingAGame($user_id)) {
                phive('DBUserHandler')->logoutUser($user_id, 'wrong location');
                $logout[] = $user_id;
            }
        }
        phive('Logger')->getLogger('geocomply')->debug('cron-logout', $logout);
        return $logout;
    }
}

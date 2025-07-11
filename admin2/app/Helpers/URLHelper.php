<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 17/03/16
 * Time: 12:21
 */

namespace App\Helpers;

use Silex\Application;

class URLHelper
{
    public static function generateUserProfileLink(Application $app, $username = null)
    {
        if ($app['vs.config']['use.legacy.user.profile.link']) {
            return "/admin/userprofile/?username=$username";
        } else {
            return empty($username) ? $app['url_generator']->generate('admin.userprofile.direct') : $app['url_generator']->generate('admin.userprofile', ['user' => $username]);
        }
    }

    public static function printUserProfileLink(Application $app, $username)
    {
        return "<a href='" . self::generateUserProfileLink($app, $username) . "'>{$username}</a>";
    }
    
    /**
     * Return last part of an url
     * @param Application $app
     * @return type
     */
    public static function getUrlLastSegment(Application $app) {
        $currentPath = $app['request_stack']->getCurrentRequest()->getPathInfo();
        $pathArray = explode('/',rtrim($currentPath,"/"));
        return end($pathArray);
    }
    
}

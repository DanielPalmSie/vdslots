<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Helpers\DownloadHelper;
use App\Models\Config;
use App\Models\IpLog;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

class AdminMainController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var ControllerCollection $factory */
        $factory = $app['controllers_factory'];
        $factory->get('/', 'App\Controllers\AdminMainController::dashboard')->bind('home');
        $factory->get('/debug/', 'App\Controllers\AdminMainController::debug')->bind('debug');

        $factory->get('/404-tk/', 'App\Controllers\AdminMainController::notFoundPage')->bind('404');

        $factory->get('/show-flash-messages/', 'App\Controllers\AdminMainController::showAjaxFlashMessages')
            ->bind('show-flash-messages')
            ->before(function () use ($app) {
                if (!p('admin')) {
                    $app->abort(403);
                }
            });

        $factory->get('/storage/{folder}/{file}/', 'App\Controllers\AdminMainController::serveStorageFile')
            ->bind('download-file')
            ->before(function (Request $request) use ($app) {
                /**
                 * NOTE: holds permissions for any folder that we'll download from
                 */
                $folders_permissions = [
                    "contacts_list" => 'messaging.contacts.download',
                    "offline_campaigns" => 'messaging.offline-campaigns.download',
                    "offline_campaigns_get_excluded" => 'messaging.offline-campaigns.download',
                    "all_user_data" => 'user.account.all_user_data.download'
                ];
                $folder = $request->get('folder');
                if (!p($folders_permissions[$folder])) {
                    $app->abort(403);
                }

                $actor_id = UserRepository::getCurrentId();
                $tag = "download_file";
                $description = "Downloaded file {$request->get('file')} from folder {$folder}";

                ActionRepository::logAction($actor_id, $description, $tag, false, $actor_id);
                IpLog::logIp($actor_id, $actor_id, $tag, $description);
            });

        return $factory;
    }

    /**
     * Return admin dashboard
     *
     * @param Application $app
     *
     * @return mixed
     */
    public function dashboard(Application $app)
    {
        try {
            Config::getValue('wiraya-language-map', 'crm', '', Config::TYPE_GROUP_LIST_WIRAYA, true);
        } catch (\Exception $e) {
            $app['monolog']->addError(implode(',', [$e->getFile(), $e->getLine(), $e->getMessage()]));
        }

        return $app['blade']->view()->make('admin.dashboard', compact('app'))->render();
    }

    public function debug(Application $app, Request $request)
    {
        return $app['blade']->view()->make('admin.dashboard', compact('app'))->render();
    }

    public function notFoundPage(Application $app, Request $request)
    {
        $app->abort(404, $request->get('msg'));
    }


    public function showAjaxFlashMessages(Application $app, Request $request)
    {
        return $app['blade']->view()
            ->make('admin.partials.flash', compact('app'))
            ->render();
    }

    public function serveStorageFile(Request $request)
    {
        return (new DownloadHelper())->fromStorage($request->get('folder'), $request->get('file'));
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 14/03/16
 * Time: 14:38
 */

namespace App\Controllers;

use App\Extensions\Database\FManager as DB;
use App\Helpers\DateHelper;
use App\Models\User;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class NotificationController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        return $factory;
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return mixed
     */
    public function listUserNotificationHistory(Application $app, User $user, Request $request)
    {
        $date_range = DateHelper::validateDateRange($request, 8);

        $notifications_query = DB::shTable($user->getKey(), 'users_notifications as un')
            ->selectRaw('un.*, u.currency')
            ->leftJoin('users as u', 'u.id', '=', 'un.user_id')
            ->where('un.user_id', $user->getKey())->where('un.created_at', '>=', $date_range['start_date'])
            ->orderBy('un.created_at', 'DESC');

        if (!empty($date_range['end_date'])) {
            $notifications_query->where('un.created_at', '<=', $date_range['end_date']);
        }

        $notifications_list = $notifications_query->get();

        $sort = ['column' => 1, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.notifications', compact('app', 'user', 'sort', 'notifications_list'))->render();
    }

}


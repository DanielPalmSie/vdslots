<?php

/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.07.19
 * Time: 9:29
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Helpers\PaginationHelper;
use App\Models\User;
use App\Repositories\ActionRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Carbon;

class ActionsController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        return $factory;
    }

    private function getPage(Application $app, Request $request, $params, $user, $date_range, $is_initial = true)
    {
        $repo = new ActionRepository();
        $by_admin = empty($request->get('by-admin')) ? false : true;
        $default_pag = ['length' => 100, 'order' => ['column' => 'created_at', 'order' => 'DESC']];
        $has_secret_comment_permission = p('view.account.comments.hidden');

        $query = $repo->getUserActionsQuery($params, $date_range, $user, $has_secret_comment_permission, false, $by_admin);

        if ($app['vs.config']['archive.db.support.actions'] && isArchived('actions', $date_range)) {
            $query_archive = $repo->getUserActionsQuery($params, $date_range, $user, $has_secret_comment_permission, true, $by_admin);
        }


        if ($query->count() > 0 && $app['vs.config']['archive.db.support.actions'] && isset($query_archive) && $query_archive->count() > 0) {
            $total_records = $query->count() + $query_archive->count();

            if ($is_initial) {
                $order_column = $default_pag['order']['column'];
                $order_dir = $default_pag['order']['order'];
                $start = 0;
                $length = $total_records < $default_pag['length'] ? $total_records : $default_pag['length'];
            } else {
                $order = $request->get('order')[0];
                $order_column = $request->get('columns')[$order['column']]['data'];
                $order_dir = $order['dir'];
                $start = $request->get('start');
                $length = $request->get('length');
            }

            $query->orderBy($order_column, $order_dir);
            $query->limit($start + $length);

            $query_archive->orderBy($order_column, $order_dir);
            $query_archive->limit($start + $length);

            $actions = $query->get()->merge($query_archive->get());
            $actions = $actions->sortBy($order_column, SORT_REGULAR, strtolower($order_dir) == 'desc');

            $actions = $actions->slice($start, $length);

            $page = [
                "draw" => intval($request->get('draw')),
                "recordsTotal" => intval($total_records),
                "recordsFiltered" => intval($total_records),
                "data" => $actions->values()
            ];

        } else {
            if ($app['vs.config']['archive.db.support.actions'] && isset($query_archive) && $query_archive->count() > 0) {
                $query = $query_archive;
            }

            $order_column = $default_pag['order']['column'];
            $order_dir = $default_pag['order']['order'];

            $query->orderBy($order_column, $order_dir);

            $paginator = new PaginationHelper($query, $request, $default_pag);
            $page = $paginator->getPage($is_initial);

        }
        return $page;
    }

    /**
     * Show user actions
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return mixed
     */
    public function userActions(Application $app, User $user, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $params = [];
            foreach ($request->get('form') as $form_elem) {
                $params[$form_elem['name']] = $form_elem['value'];
            }
            $date_range = DateRange::rangeFromRawDate(
                $params['date-range'],
                empty($params['tag-like']) ? DateRange::DEFAULT_LAST_30_DAYS : DateRange::DEFAULT_LAST_YEAR
            );

            return $app->json($this->getPage($app, $request, $params, $user, $date_range, false));
        } else {
            $by_admin = $request->get('by-admin', false);

            if (empty($request->get('tag-like'))) {
                $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_30_DAYS);
                $date_range->validate($app);
            } else {
                $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_YEAR);
            }

            $params = [
                'actor' => $request->get('actor'),
                'tag' => $request->get('tag'),
                'tag-like' => $request->get('tag-like')
            ];

            $page = $this->getPage($app, $request, $params, $user, $date_range);

            $repo = new ActionRepository();
            $actors = $repo->getUserActorsList($user);
            $tags = $repo->getUserTagsList($user, $app, $date_range, $by_admin);
            $show_admin = p('admin_top', cu($user->id)) && p('view.account.admin.actions');

            return $app['blade']->view()->make('admin.user.actions', compact('app', 'user', 'page', 'date_range', 'actors', 'tags', 'show_admin', 'by_admin'))->render();
        }
    }
}

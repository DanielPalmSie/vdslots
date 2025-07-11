<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.11.01.
 * Time: 9:03
 */

namespace App\Controllers;

use App\Helpers\DataFormatHelper;
use App\Models\TournamentTemplate;
use App\Models\BoAuditLog;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repositories\TrophyAwardsRepository;
use App\Models\TrophyAwards;
use App\Extensions\Database\FManager as DB;

class TrophyAwardsController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\TrophyAwardsController::index')
            ->bind('trophyawards.index')
            ->before(function () use ($app) {
                if (!p('trophyawards.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/new/', 'App\Controllers\TrophyAwardsController::newTrophyAward')
            ->bind('trophyawards.new')
            ->before(function () use ($app) {
                if (!p('trophyawards.new')) {
                    $app->abort(403);
                }
            });

        $factory->match('/edit/{trophyaward}/', 'App\Controllers\TrophyAwardsController::editTrophyAward')
            ->convert('trophyaward', $app['trophyAwardProvider'])
            ->bind('trophyawards.edit')
            ->before(function () use ($app) {
                if (!p('trophyawards.edit')) {
                    $app->abort(403);
                }
            });

        $factory->match('/delete/{trophyaward}/', 'App\Controllers\TrophyAwardsController::deleteTrophyAward')
            ->convert('trophyaward', $app['trophyAwardProvider'])
            ->bind('trophyawards.delete')
            ->before(function () use ($app) {
                if (!p('trophyawards.delete')) {
                    $app->abort(403);
                }
            });

        $factory->get('/filter/', 'App\Controllers\TrophyAwardsController::filter')
            ->bind('trophyawards.ajaxfilter')
            ->before(function () use ($app) {
                if (!p('trophyawards.link')) {
                    $app->abort(403);
                }
            });

        $factory->match('/search/', 'App\Controllers\TrophyAwardsController::searchTrophyAwards')
            ->bind('trophyawards.search')
            ->before(function () use ($app) {
                if (!p('trophyawards.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/file-upload/', 'App\Controllers\TrophyAwardsController::fileUpload')
            ->bind('trophyawards.fileupload')
            ->before(function () use ($app) {
                if (!p('trophyawards.fileupload')) {
                    $app->abort(403);
                }
            });

        $factory->get('/search/tournament-templates/', 'App\Controllers\TrophyAwardsController::searchTournamentTemplates')
            ->bind('trophyawards.search.tournament-templates')
            ->before(function () use ($app) {
                if (!p('trophyawards.search.tournament-templates')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * Response for AJAX filtering requests. On the client side it needs to be polished a little bit because
     * select2 accepts this format: ["results": {"id": 32, "text": "sometext"}, .....]
     *
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function filter(Application $app, Request $request)
    {
        return TrophyAwards::where('description', 'LIKE', '%' . $request->query->get('q') . '%')
            ->orWhere('alias', 'LIKE', '%' . $request->query->get('q') . '%')
            ->get();
    }

    public function index(Application $app, Request $request, $users_list = null)
    {
        $repo = new TrophyAwardsRepository($app);
        $columns = $repo->getTrophyAwardsSearchColumnsList();

        if (!isset($_COOKIE['trophyawards-search-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('trophyawards-search-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['trophyawards-search-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['trophyawards-search-no-visible'], true);
        }

        $res = $this->getTrophyAwardsList($request, $app, [
            'ajax'         => false,
            'length'       => 25,
            'sendtobrowse' => $request->get('sendtobrowse', 0),
            'users_list'   => $users_list
        ]);

        $pagination = [
            'data'           => $res['data'],
            'defer_option'   => $res['recordsTotal'],
            'initial_length' => 25
        ];

        $breadcrumb = 'List and Search';

        $view = ["new" => "Trophy Award", 'title' => 'Trophy Awards', 'variable' => 'trophyawards', 'variable_param' => 'trophyaward'];

        return $app['blade']->view()->make('admin.gamification.trophyawards.index', compact('app', 'columns', 'pagination', 'breadcrumb', 'view'))->render();
    }

    public function getAllDistinct()
    {
        $trophyawards = new TrophyAwards();
        $all_distinct['type'] = array_merge([""], $trophyawards->getDistinct('type'));
        $all_distinct['action'] = array_merge([""], $trophyawards->getDistinct('action'));
        $all_distinct['excluded_countries'] = DataFormatHelper::getSelect2FormattedData(DataFormatHelper::getCountryList(), [
            "id" => 'iso',
            "text" => 'printable_name'
        ]);
        return $all_distinct;
    }

    public function fixTrophyAwardData(&$trophyaward_data)
    {
        // Special treatment for bonus_id, as if this is cleared in the form,
        // it's not showing up in the data, and thus not "clearing" it.
        // Doing that here then.
        if (!isset($trophyaward_data['bonus_id'])) {
            $trophyaward_data['bonus_id'] = 0;
        }

        // Checkboxes are also special.
        // TODO: Better solution?
        if (isset($trophyaward_data['mobile_show'])) {
            $trophyaward_data['mobile_show'] = 1;
        } else {
            $trophyaward_data['mobile_show'] = 0;
        }
        if ($trophyaward_data['excluded_countries']) {
            $trophyaward_data['excluded_countries'] = implode(' ', $trophyaward_data['excluded_countries']);
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return string|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws
     */
    public function newTrophyAward(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $t = new TrophyAwards($data);
            if (!$t->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
            }

            $this->fixTrophyAwardData($data);
            $new_trophy_award = TrophyAwards::create($data);

            BoAuditLog::instance()
                ->setTarget($new_trophy_award->getTable(), $new_trophy_award->getAttribute('id'))
                ->registerCreate($new_trophy_award->getAttributes());

            if (!$new_trophy_award) {
                return $app->json(['success' => false, 'error' => 'Unable to create a Trophy Award.']);
            }

            return $app->json(['success' => true, 'trophyaward' => $new_trophy_award]);
        }

        $trophyawards_all_distinct = $this->getAllDistinct();

        $buttons['save'] = "Create New Trophy Award";

        $breadcrumb = 'New';

        return $app['blade']->view()->make('admin.gamification.trophyawards.new', compact('app', 'buttons', 'trophyawards_all_distinct', 'breadcrumb'))->render();
    }

    public function searchTournamentTemplates(Application $app, Request $request) {
        $results = TournamentTemplate::with('game')
            ->where('tournament_tpls.id', 'like', "{$request->get('search')}%")
            ->get()
            ->map(function ($el) {
                /** @var TournamentTemplate $el */
                return $el->toSearchResult();
            });

        return $app->json($results);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param TrophyAwards $trophyaward
     * @return string|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws
     */
    public function editTrophyAward(Application $app, Request $request, TrophyAwards $trophyaward)
    {
        if (!$trophyaward) {
            return $app->json(['success' => false, 'Trophy not found.']);
        }

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->fixTrophyAwardData($data);

            $old = $trophyaward->getAttributes();
            if (!$trophyaward->update($data)) {
                return $app->json(['success' => false, 'error' => 'Internal error']);
            }

            BoAuditLog::instance()
                ->setTarget($trophyaward->getTable(), $trophyaward->getAttribute('id'))
                ->registerUpdate($old, $trophyaward->getAttributes());

            return $app->json(['success' => true]);
        }

        $trophyawards_all_distinct = $this->getAllDistinct();

        $repo = new TrophyAwardsRepository($app);

        $trophyaward_bonus = null;
        if ($trophyaward->bonus_id > 0) {
            $trophyaward_bonus = $repo->getBonusById($trophyaward->bonus_id);
        }

        $repo->setTrophyAwardImage($trophyaward, null);

        $buttons['save'] = "Save";
        $buttons['save-as-new'] = "Save As New...";
        //$buttons['delete']      = "Delete";

        $breadcrumb = 'Edit';

        if ($trophyaward->isTournamentTicket()) {
            /** @var TournamentTemplate $t */
            $t = TournamentTemplate::with('game')->find($trophyaward->bonus_id);

            if (!empty($t)) {
                $tournament = $t->toSearchResult();
            }
        }

        return $app['blade']->view()->make('admin.gamification.trophyawards.edit', compact('app', 'buttons', 'trophyaward', 'trophyaward_bonus', 'trophyawards_all_distinct', 'breadcrumb', 'tournament'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param TrophyAwards $trophyaward
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteTrophyAward(Application $app, Request $request, TrophyAwards $trophyaward)
    {
        // TODO: Make sure user permision check works if you ever enable this.
        // TODO: Log the action with BoAuditLog if you enable this
        return $app->json(['success' => false, 'error' => 'Delete is disabled']);

        /* // Disabled delete for now.
        DB::shBeginTransaction(true);
        try {
            // TODO: Also do something with Trophies that point to this award?
            $result = $trophyaward->delete();
            if (!$result) {
                DB::shRollback(true);
                return $app->json(['success' => false]);
            }
        } catch (\Exception $e) {
            DB::shRollback(true);
            return $app->json(['success' => false, 'error' => $e]);
        }

        DB::shCommit(true);
        return $app->json(['success' => true]);
        */
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function fileUpload(Application $app, Request $request)
    {
        $base_destination = phive('Filer')->getSetting('UPLOAD_PATH') . '/events';

        foreach ($_FILES as $file) {
            $color_file_destination = $base_destination . '/' . $file['name'];
            if (phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                phive('Dmapi')->uploadPublicFile($file['tmp_name'], 'file_uploads', $file['name'], 'events');
            } else {
                if (move_uploaded_file($file['tmp_name'], $color_file_destination)) {
                    chmod($color_file_destination, 0777);
                }
            }

        }

        return $app->json(['success' => true, 'files', $_FILES]);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param array $attributes
     * @return array
     */
    private function getTrophyAwardsList($request, $app, $attributes)
    {
        $repo = new TrophyAwardsRepository($app);
        $search_query = null;
        $archived_count = 0;
        $total_records = 0;
        $length = 25;
        $order_column = "alias";
        $start = 0;
        $order_dir = "ASC";

        if ($attributes['sendtobrowse'] != 1) {
            $search_query = $repo->getTrophyAwardsSearchQuery($request);
        } else {
            $search_query = $repo->getTrophyAwardsSearchQuery($request, false, $attributes['users_list']);
        }

        // Search column-wise too.
        foreach ($request->get('columns') as $value) {
            if (strlen($value['search']['value']) > 0) {
                $words = explode(" ", $value['search']['value']);
                foreach ($words as $word) {
                    $search_query->where($value['data'], 'LIKE', "%" . $word . "%");
                }
            }
        }

        $search = $request->get('search')['value'];
        if (strlen($search) > 0) {
            $s = explode(' ', $search);
            foreach ($s as $q) {
                $search_query->where('alias', 'LIKE', "%$q%");
                //$search_query->orWhere('description', 'LIKE', "%$q%");
            }
        }

        $non_archived_count = DB::table(DB::raw("({$search_query->toSql()}) as a"))
            ->mergeBindings($search_query)
            ->count();

        if ($attributes['sendtobrowse'] != 1 && $app['vs.config']['archive.db.support'] && $repo->not_use_archived == false) {
            $archived_search_query = $repo->getTrophyAwardsSearchQuery($request, true);
            try {
                $archived_count = DB::connection('videoslots_archived')->table(DB::raw("({$archived_search_query->toSql()}) as b"))
                    ->mergeBindings($search_query)
                    ->count();
            } catch (\Exception $e) {
            }
            $total_records = $non_archived_count + $archived_count;
        } else {
            $total_records = $non_archived_count;
        }

        if ($attributes['ajax'] == true) {
            $start = $request->get('start');
            $length = $request->get('length');
            $order = $request->get('order')[0];
            $order_column = $request->get('columns')[$order['column']]['data'];
            $order_dir = $order['dir'];
        } else {
            $length = $total_records < $attributes['length'] ? $total_records : $attributes['length'];
        }

        if ($attributes['sendtobrowse'] !== 1 && $app['vs.config']['archive.db.support'] && $archived_count > 0) {
            $non_archived_records = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
            $non_archived_slice_count = count($non_archived_records);
            if ($non_archived_slice_count < $length) {
                $next_length = $length - $non_archived_slice_count;
                $next_start = $start - $non_archived_count;
                if ($next_start < 0) {
                    $next_start = 0;
                }
                $archived_records = $archived_search_query->orderBy($order_column, $order_dir)->limit($next_length)->skip($next_start)->get();
                if ($non_archived_slice_count > 0) {
                    $data = array_merge($non_archived_records, $archived_records);
                } else {
                    $data = $archived_records;
                }
            } else {
                $data = $non_archived_records;
            }
        } else {
            $data = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
        }

        return [
            "draw" => intval($request->get('draw')),
            "recordsTotal" => intval($total_records),
            "recordsFiltered" => intval($total_records),
            "data" => $data
        ];
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function searchTrophyAwards(Application $app, Request $request)
    {
        return $app->json($this->getTrophyAwardsList($request, $app, ['ajax' => true]));
    }

}

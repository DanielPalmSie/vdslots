<?php

namespace App\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repositories\RaceTemplatesRepository;
use App\Models\RaceTemplate;
use App\Models\TrophyAwards;
use App\Extensions\Database\FManager as DB;

class RaceTemplatesController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\RaceTemplatesController::index')
            ->bind('racetemplates.index')
            ->before(function () use ($app) {
                if (!p('racetemplates.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/new/', 'App\Controllers\RaceTemplatesController::newRaceTemplate')
            ->bind('racetemplates.new')
            ->before(function () use ($app) {
                if (!p('racetemplates.new')) {
                    $app->abort(403);
                }
            });

        $factory->match('/edit/{racetemplate}/', 'App\Controllers\RaceTemplatesController::editRaceTemplate')
            ->convert('racetemplate', $app['raceTemplateProvider'])
            ->bind('racetemplates.edit')
            ->before(function () use ($app) {
                if (!p('racetemplates.edit')) {
                    $app->abort(403);
                }
            });

        $factory->match('/delete/{racetemplate}/', 'App\Controllers\RaceTemplatesController::deleteRaceTemplate')
            ->convert('racetemplate', $app['raceTemplateProvider'])
            ->bind('racetemplates.delete')
            ->before(function () use ($app) {
                if (!p('racetemplates.delete')) {
                    $app->abort(403);
                }
            });

        $factory->get('/filter/', 'App\Controllers\RaceTemplatesController::filter')
            ->bind('racetemplates.ajaxfilter')
            ->before(function () use ($app) {
                if (!p('racetemplates.link')) {
                    $app->abort(403);
                }
            });

        $factory->match('/search/', 'App\Controllers\RaceTemplatesController::searchRaceTemplate')
            ->bind('racetemplates.search')
            ->before(function () use ($app) {
                if (!p('racetemplates.section')) {
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
        return RaceTemplate::where('description', 'LIKE', '%' . $request->query->get('q') . '%')
            ->orWhere('alias', 'LIKE', '%' . $request->query->get('q') . '%')
            ->get();
    }

    public function index(Application $app, Request $request, $users_list = null)
    {
        $repo = new RaceTemplatesRepository($app);
        $columns = $repo->getRaceTemplateSearchColumnsList();

        if (!isset($_COOKIE['racetemplates-search-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('racetemplates-search-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['racetemplates-search-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['racetemplates-search-no-visible'], true);
        }

        $res = $this->getRaceTemplateList($request, $app, [
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

        $view = ["new" => "Race Template", 'title' => 'Race Templates', 'variable' => 'racetemplates', 'variable_param' => 'racetemplate'];

        return $app['blade']->view()->make('admin.gamification.racetemplates.index', compact('app', 'columns', 'pagination', 'breadcrumb', 'view'))->render();
    }

    public function getAllDistinct()
    {
        //$racetemplates = new RaceTemplate();
        //$all_distinct['race_type'] = array_merge([""], $racetemplates->getDistinct('race_type'));
        $all_distinct['race_type'] = ["spins", "bigwin"];
        $all_distinct['display_as'] = ["race"];
        $all_distinct['prize_type'] = ["cash", "award"];
        $all_distinct['game_categories'] = ["slots", "videoslots"];
        $all_distinct['recur_type'] = ["weekly"];
        $all_distinct['recurring_days'] = ["Monday" => 1, "Tuesday" => 2, "Wednesday" => 3, "Thursday" => 4, "Friday" => 5, "Saturday" => 6, "Sunday" => 7];
        $all_distinct['levels_threshold'] = ["Big Win (15x)" => 15, "Mega Win (30x)" => 30, "Super Mega Win (60x)" => 60];

        return $all_distinct;
    }

    public function fixRaceTemplateData(&$racetemplates_data)
    {
        if (isset($racetemplates_data['recurring_days'])) {
            $racetemplates_data['recurring_days'] = implode(",", $racetemplates_data['recurring_days']);
        }

        if (isset($racetemplates_data['game_categories'])) {
            $racetemplates_data['game_categories'] = implode(",", $racetemplates_data['game_categories']);
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return string|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function newRaceTemplate(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $new_racetemplate = null;

            $data = $request->request->all();
            $t = new RaceTemplate($data);
            if (!$t->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
            }

            $this->fixRaceTemplateData($data);

            $new_racetemplate = RaceTemplate::create($data);

            if (!$new_racetemplate) {
                return $app->json(['success' => false, 'error' => 'Unable to create a Race Template.']);
            }

            return $app->json(['success' => true, 'racetemplate' => $new_racetemplate]);
        }

        $racetemplates_all_distinct = $this->getAllDistinct();

        $buttons['save'] = "Create New Race Template";

        $breadcrumb = 'New';

        return $app['blade']->view()->make('admin.gamification.racetemplates.new', compact('app', 'buttons', 'racetemplates_all_distinct', 'breadcrumb'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param RaceTemplate $racetemplate
     * @return string|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editRaceTemplate(Application $app, Request $request, RaceTemplate $racetemplate)
    {
        if (!$racetemplate) {
            return $app->json(['success' => false, 'Race Template not found.']);
        }

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->fixRaceTemplateData($data);

            if (!$racetemplate->update($data)) {
                return $app->json(['success' => false, 'error' => 'Internal error']);
            }

            return $app->json(['success' => true]);
        }

        $racetemplates_all_distinct = $this->getAllDistinct($app);

        $trophy_awards = [];

        if (strlen($racetemplate["prizes"]) > 0) {
            $prizes = explode(":", $racetemplate["prizes"]);
            $counted_prizes = array_count_values($prizes);
            $fixed = [];
            foreach ($counted_prizes as $key => $value) {
                if ($racetemplate->prize_type == "award") {
                    list($award_id, $award_alt_id) = explode(",", $key);
                    $tropyaward = TrophyAwards::where('id', $award_id)->first();
                    $award_alt_array = null;

                    if ($tropyaward) {

                        $award_array = ['id' => $tropyaward->id, 'alias' => $tropyaward->alias, 'description' => $tropyaward->description];

                        if ($award_alt_id != null && strlen($award_alt_id) > 0) {
                            $tropyaward_alt = TrophyAwards::where('id', $award_alt_id)->first();
                            if ($tropyaward_alt) {
                                $award_alt_array = ['id' => $tropyaward_alt->id, 'alias' => $tropyaward_alt->alias, 'description' => $tropyaward_alt->description];
                            }
                        }
                    } else {
                        $award_array = ['id' => $award_id, 'alias' => "Id ".$award_id." not found in DB.", 'description' => "Id ".$award_id." not found in DB."];
                        if ($award_alt_id != null && strlen($award_alt_id) > 0) {
                            $award_alt_array = ['id' => $award_alt_id, 'alias' => "Id ".$award_alt_id." not found in DB.", 'description' => "Id ".$award_alt_id." not found in DB."];
                        }
                    }

                    $trophy_awards[] = ['award' => $award_array, 'award_alt' => $award_alt_array];
                }

                $fixed[$key." "] = $value; // " " added or else the browser will sort on the key. We want to keep the order.
            }
            $racetemplate["counted_prizes"] = $fixed;
        }

        if (isset($racetemplate['recurring_days'])) {
            $racetemplate['recurring_days'] = explode(",", $racetemplate['recurring_days']);
        }

        if (isset($racetemplate['game_categories'])) {
            $racetemplate['game_categories'] = explode(",", $racetemplate['game_categories']);
        }

        $buttons['save'] = "Save";
        $buttons['save-as-new'] = "Save As New...";
        //$buttons['delete']      = "Delete";

        $breadcrumb = 'Edit';

        return $app['blade']->view()->make('admin.gamification.racetemplates.edit', compact('app', 'buttons', 'racetemplate', 'trophy_awards', 'racetemplate_bonus', 'racetemplates_all_distinct', 'breadcrumb'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param RaceTemplate $racetemplates
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteRaceTemplate(Application $app, Request $request, RaceTemplate $racetemplates)
    {
        // TODO: Make sure user permision check works if you ever enable this.
        return $app->json(['success' => false, 'error' => 'Delete is disabled']);

        /* // Disabled delete for now.
        DB::shBeginTransaction(true);
        try {
            $result = $racetemplates->delete();
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
    private function getRaceTemplateList($request, $app, $attributes)
    {
        $repo = new RaceTemplatesRepository($app);
        $search_query = null;
        $archived_count = 0;
        $total_records = 0;
        $length = 25;
        $order_column = "id";
        $start = 0;
        $order_dir = "ASC";

        if ($attributes['sendtobrowse'] != 1) {
            $search_query = $repo->getRaceTemplateSearchQuery($request);
        } else {
            $search_query = $repo->getRaceTemplateSearchQuery($request, false, $attributes['users_list']);
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
            $archived_search_query = $repo->getRaceTemplateSearchQuery($request, true);
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
    public function searchRaceTemplate(Application $app, Request $request)
    {
        return $app->json($this->getRaceTemplateList($request, $app, ['ajax' => true]));
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 01/06/17
 * Time: 16:29
 */

namespace App\Repositories;

use App\Classes\Dmapi;
use App\Controllers\GameOverrideController;
use App\Extensions\Database\FManager as DB;
use App\Helpers\DataFormatHelper;
use App\Helpers\PaginationHelper;
use App\Helpers\FileUploadHelper;
use App\Models\BoAuditLog;
use App\Models\Config;
use App\Models\Game;
use App\Models\GameFeatures;
use App\Models\GameOverride;
use App\Models\Operator;
use Illuminate\Support\Collection;
use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class GamesRepository
{
    /** @var Application $app */
    protected $app;
    private $file_type_map = [
        'background' => 'backgrounds/',
        'screen_shot' => 'screenshots/',
        'thumbnail' => 'thumbs/',
        'mobileGameBannerImage' => 'thumbs/',
        'desktopGameBannerImage' => 'thumbs/',
    ];
    private $upload_path;
    private $upload_uri;
    public $request;
    /**
     * TrophiesRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->request = $app['request_stack']->getCurrentRequest();
        $this->upload_path = phive('Filer')->getSetting('UPLOAD_PATH').'/';
        $this->upload_uri = phive('Filer')->getSetting('UPLOAD_PATH_URI').'/';
    }

    public static function instance($app) {
        return new self($app);
    }

    public static function getLanguageCodes($id)
    {
        $languages = DB::table('micro_games')->select('languages')->where('id', $id)->first();

        return explode(',', $languages->languages);
    }

    /**
     * @param $id
     *
     * @return mixed|static
     * @throws \Exception
     */
    public function getGameById($id)
    {
        return Game::query()->find($id);
    }

    /**
     * @param string $name
     * @param array $col_search
     * @param int $start
     * @param int $length
     * @param bool $overrides
     * @return array
     * @throws \Exception
     */
    public function getGames($name='', $col_search=[], $start=0, $length=50, $overrides=false)
    {
        $games_query = DB::table('micro_games as mg');

        $games_query->selectRaw("count(*) as amount");

        foreach ($games_query->get() as $elem) {
            $games_count = $elem->amount;
        }

        $games = DB::table('micro_games as mg');

        $games->selectRaw("*")
            ->orderBy('game_name', 'ASC')
            ->get();


        if (!empty($overrides)) {
            $game_overrides = DB::raw("(
                SELECT game_id, count(*) as overrides_count
                FROM game_country_overrides
                GROUP BY game_id
            ) AS gco");
            $games->leftJoin($game_overrides, 'gco.game_id', '=', 'mg.id');
        }

        if (!empty($name)) {
            $games->where('game_name', 'LIKE', '%'.$name.'%');
        }

        if (count($col_search) > 0) {
            if (!empty($col_search[0]['search']['value'])) {
                $games->where('game_name', 'LIKE', '%'.$col_search[0]['search']['value'].'%');
            }

            if (!empty($col_search[1]['search']['value'])) {
                $games->where('device_type', 'LIKE', '%'.$col_search[1]['search']['value'].'%');
            }

            if (!empty($col_search[2]['search']['value'])) {
                $games->where('game_id', 'LIKE', '%'.$col_search[2]['search']['value'].'%');
            }

            if (!empty($col_search[3]['search']['value'])) {
                $games->where('operator', 'LIKE', '%'.$col_search[3]['search']['value'].'%');
            }

            if (!empty($col_search[4]['search']['value'])) {
                $games->where('network', 'LIKE', '%'.$col_search[4]['search']['value'].'%');
            }

            if (!empty($col_search[5]['search']['value'])) {
                $games->where('payout_percent', 'LIKE', $col_search[5]['search']['value']);
            }

            if (!empty($col_search[6]['search']['value']) || (isset($col_search[6]['search']['value']) && $col_search[6]['search']['value'] == '0')) {
                $games->where('min_bet', $col_search[6]['search']['value']);
            }

            if (!empty($col_search[7]['search']['value']) || (isset($col_search[7]['search']['value']) && $col_search[7]['search']['value'] == '0')) {
                $games->where('max_bet', $col_search[7]['search']['value']);
            }
            if (!empty($overrides)) {
                if (!empty($col_search[8]['search']['value'])) {
                    $games->where('gco.overrides_count', 'LIKE', '%'.$col_search[8]['search']['value'].'%');
                }
            }
        }

        $filtered_count = $games->count();

        return [
            //'debug' => $games->toSql(),
            'data' => $games->skip($start)->take($length)->get(),
            'recordsTotal' => $games_count,
            'recordsFiltered' => $filtered_count
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getNetworks()
    {
        return DB::table('micro_games')
            ->pluck('network')
            ->unique()
            ->toArray();
    }

    /**
     * @param $id
     *
     * @return array
     * @throws \Exception
     */
    public function getTags($id)
    {
        $tag =  DB::table('micro_games')->find($id)->tag;
        return explode(",", $tag);
    }

    /**
     * @param $network
     *
     * @return array
     * @throws \Exception
     */
    public function getOperators($network)
    {
        if (empty($network)) {
            return [];
        }

        return DB::table('micro_games')
            ->where('network', $network)
            ->orderBy('operator')
            ->pluck('operator', 'id')
            ->unique()
            ->toArray();
    }


    public function getDevices($flip = false)
    {
        return  phive()->getDeviceMap($flip);
    }

    /**
     * @param      $game_id
     * @param bool $ids_only
     * @param null $current_themes
     *
     * @return array
     * @throws \Exception
     */
    public function getGameThemes($game_id, $ids_only = false, $current_themes = null)
    {
        if ($current_themes) {
            return DB::table('themes AS t')
                ->whereIn('id', $current_themes)
                ->get()
                ->mapWithKeys(function ($theme, $i) use ($ids_only) {
                    return $ids_only
                        ? [$i => $theme->id]
                        : [$theme->id => $theme->name];
                })
                ->all();
        }

        return DB::table('game_themes')
            ->selectRaw("game_themes.*, t.name")
            ->where('game_id', $game_id)
            ->leftJoin('themes AS t', 'game_themes.theme_id', '=', 't.id')
            ->get()
            ->mapWithKeys(function ($row, $i) use ($ids_only) {
                return $ids_only
                    ? [$i => $row->theme_id]
                    : [$row->theme_id => $row->name];
            })
            ->all();
    }

    /**
     * @param $game_id
     * @param $themes
     *
     * @return mixed
     * @throws \Exception
     */
    public function saveThemes($game_id, $themes)
    {
        if (is_string($themes)) {
            $themes = explode(',', trim($themes, ', '));
        }

        try {
            DB::table('game_themes')
                ->where('game_id', $game_id)
                ->delete();

            return collect($themes)
                ->unique()
                ->filter(function ($theme) {
                    return $theme != 0;
                })
                ->map(function ($theme) use ($game_id) {
                    return ['game_id' => $game_id, 'theme_id' => $theme];
                })
                ->tap(function ($themes) {
                    /** @var Collection $themes */
                    return $themes->count() > 0
                        ? DB::table('game_themes')->insert($themes->all())
                        : null;
                });
        } catch (\Exception $e) {}
    }

    private function getFileLocation($type, $name) {
        return $this->upload_path . $this->file_type_map[$type] . $name;
    }

    private function getFileUrl($type, $name) {
        return $this->upload_uri . $this->file_type_map[$type] . $name;
    }

    /**
     * @param $game_id
     * @return mixed
     * @throws \Exception
     */
    public function getImagesView($game_id)
    {
        $game = $this->getGameById($game_id);

        $breadcrumb = 'Edit';
        $breadcrumb_elms = [
            $this->app['url_generator']->generate('settings.games.edit') . '?id=' . $game->id => $game->game_name
        ];

        $bkg_pic = $game->bkg_pic;

        $map = [
            'background' => [
                $this->getFileLocation('background', $bkg_pic),
                $this->getFileUrl('background', $bkg_pic)
            ],
            'screen_shot' => [
                $this->getFileLocation('screen_shot', $game->game_id.'_big.jpg'),
                $this->getFileUrl('screen_shot', $game->game_id.'_big.jpg')
            ],
            'thumbnail' => [
                $this->getFileLocation('thumbnail', $game->game_id.'_c.jpg'),
                $this->getFileUrl('thumbnail', $game->game_id.'_c.jpg')
            ],
            'mobileGameBannerImage' => [
                $this->getFileLocation('thumbnail', $game->game_id.'_mb.jpg'),
                $this->getFileUrl('thumbnail', $game->game_id.'_mb.jpg')
            ],
            'desktopGameBannerImage' => [
                $this->getFileLocation('thumbnail', $game->game_id.'_db.jpg'),
                $this->getFileUrl('thumbnail', $game->game_id.'_db.jpg')
            ],
        ];

        foreach(range(1, 4) as $i){
            $map["sr$i"] = [
                $this->getFileLocation('thumbnail', $game->game_id."_sr$i.jpg"),
                $this->getFileUrl('thumbnail', $game->game_id."_sr$i.jpg")
            ];
        }

        $images = [];
        foreach ($map as $type => $path) {
            $images[$type] = file_exists($path[0]) && is_file($path[0])
                ? [$path[1] . '?' . time(), filesize($path), $bkg_pic]
                : '';
        }
        $app = $this->app;
        return $this->app['blade']->view()->make('admin.settings.games.images.edit', compact('app', 'breadcrumb', 'breadcrumb_elms', 'game', 'images'))->render();
    }

    /**
     * @param $game_id
     * @return mixed
     * @throws \Exception
     */
    public function getGameTagView($game_id)
    {
        $game = $this->getGameById($game_id);

        $data = DB::table('game_tags')->get();

        $datatag = DB::table("game_tag_con")
            ->where('game_tag_con.game_id', $game->id)
            ->get();

        $app = $this->app;
        return $this->app["blade"]
            ->view()
            ->make("admin.settings.games.gametag.edit", compact("app", "game", "data", "datatag"))
            ->render();
    }

    /**
     * @param $game_id
     * @return mixed
     * @throws \Exception
     */
    public function getFeaturesView($game_id)
    {
        $game = $this->getGameById($game_id);

        $breadcrumb = 'Edit';
        $breadcrumb_elms = [
            $this->app['url_generator']->generate('settings.games.edit') . '?id=' . $game->id => $game->game_name
        ];

        $features = GameFeatures::where('game_id', $game->id)->orderBy('name', 'ASC')->get();

        $app = $this->app;
        return $app['blade']->view()->make('admin.settings.games.features.edit', compact('app', 'breadcrumb', 'breadcrumb_elms', 'game', 'features'))->render();
    }

    public function sendDatatablesResponse($query, $request, $columns)
    {
        $order = $request->get('order')[0] ?? ['column' => 0, 'dir' => 'ASC'];
        $order['column'] = array_keys($columns)[$order['column']];
        $paginator = new PaginationHelper($query, $request, [
            'length' => $request->get('report-table_length', 25),
            'order' => $order
        ]);

        return $this->app->json($paginator->getPage(false));
    }

    /**
     * @param $game_id
     * @return mixed
     * @throws \Exception
     */
    public function getChangesLogView($game_id)
    {
        $app = $this->app;
        $request = $this->request;

        $columns = [
            'actor_id' => 'Actor',
            'timestamp' => 'When',
            'human_readable_desc' => 'Description',
            'target_table' => 'Section',
            'action' => 'Action',
        ];

        if (!$request->isXmlHttpRequest()) {
            return $app['blade']->view()->make('admin.settings.games.changes-log.index', compact('app', 'columns', 'request', 'game_id'))->render();
        }

        foreach ($this->request->get('form') as $form_elem) {
            $this->request->request->set($form_elem['name'], $form_elem['value']);
        }

        $query = BoAuditLog::query()
            ->select(['actor_id', 'timestamp', 'changes', 'target_table', 'action'])
            ->selectRaw("
                CASE
                    WHEN target_table = 'micro_games' THEN 'Game'
                    WHEN target_table = 'game_features' THEN 'Game features'
                    WHEN target_table = 'game_country_overrides' THEN 'Game overrides'
                    WHEN target_table = 'game_tag_con' THEN 'Game tag connections'
                END AS target_table
            ")
            ->where(function($q) {
                return $q->where('target_table', '=', 'micro_games')->orWhere('context', '=', 'micro_games');
            })
            ->where(function($q) use ($game_id) {
                return $q->where('target_id', '=', $game_id)->orWhere('context_id', '=', $game_id);
            });

        $order = $this->request->get('order');
        $column_dict = [
            0 => 'actor_id',
            1 => 'timestamp',
            2 => 'changes',
            3 => 'target_table',
            4 => 'action'
        ];

        $columns_to_sort = [];
        foreach ($order as $o){
            array_push($columns_to_sort, $column_dict[$o["column"]]);
        }

        if (!is_null($columns_to_sort)) {
            for($x = 0; $x < sizeof($columns_to_sort); $x++){
                $query->orderBy($columns_to_sort[$x], $order[$x]['dir']);
            }
        }

        return $this->sendDatatablesResponse($query, $this->request, $columns);
    }

    /**
     * @param $game_id
     * @return mixed
     * @throws \Exception
     */
    public function getOverridesView($game_id)
    {
        $overrides     = GameOverride::where('game_id', $game_id)->orderBy('id', "desc")->get();
        $game          = Game::find($game_id)->toArray();

        $prepops = [];
        $controller = new GameOverrideController();

        foreach($controller->form_info as $key => $val){
            $prepops[$key] = $game[$val[0]];
        }

        $app = $this->app;
        return $app['blade']->view()->make('admin.settings.games.overrides.edit', array_merge(
            $controller->getCommonViewVars($app),
            [
                'overrides' => $overrides,
                'game'      => $game,
                'prepops'   => $prepops,
                'action'    => 'Create'
            ]))->render();
    }

    /**
     * @param $game
     * @param $type
     * @return string|null
     */
    public function getPath($game, $type) {
        $path = null;
        $game_name = str_replace(' ', '', $game->game_name);

        switch ($type) {
            case 'background':
                $path = $this->upload_path . 'backgrounds/' . $game_name . '_BG.jpg';
                break;
            case 'screen_shot':
                $path = $this->upload_path . 'screenshots/' . $game->game_id . '_big.jpg';
                break;
            case 'thumbnail':
                $path = $this->upload_path . 'thumbs/' . $game->game_id . '_c.jpg';
                break;
            case 'mobileGameBannerImage':
                $path = $this->upload_path . 'thumbs/' . $game->game_id . '_mb.jpg';
                break;
            case 'desktopGameBannerImage':
                $path = $this->upload_path . 'thumbs/' . $game->game_id . '_db.jpg';
                break;
            case 's1':
            case 's2':
            case 's3':
            case 's4':
                $path = $this->upload_path . "thumbs/{$game->game_id}_{$type}.jpg";
                break;
            default:
                $path = $this->upload_path . 'thumbs/' . $game->game_id . '_' . $type . '.jpg';
        }

        return $path;
    }

    /**
     * @param Game $game
     * @throws \Exception
     */
    public function newGameLogs($game) {

        $all = collect(explode(',', $game->blocked_countries));
        $current = collect(explode(' ', $game->getOldValue('blocked_countries')));

        $to_insert = $all->filter(function($country) use ($current) {
            return !$current->contains($country);
        })->implode(" ");

        $to_delete = $current->filter(function($country) use ($all) {
            return !$all->contains($country);
        })->implode(" ");

        if ($game->getOldValue('blocked_countries') != $game->blocked_countries) {
            if (!empty($to_delete)) {
                BoAuditLog::instance()->setTarget($game->getTable(), $game->id)->registerDeactivated($to_delete);
            }
            if (!empty($to_insert)) {
                BoAuditLog::instance()->setTarget($game->getTable(), $game->id)->registerActivated($to_insert);
            }
        }

        if ($game->getOldValue('active') != $game->active) {
            if ($game->active === 1) {
                BoAuditLog::instance()->setTarget($game->getTable(), $game->id)->registerActivated("Activated for all countries");
            } else {
                BoAuditLog::instance()->setTarget($game->getTable(), $game->id)->registerDeactivated("Deactivated for all countries");
            }
        }

        if ($game->getOldValue('id') === 0) {
            // create
            BoAuditLog::instance()
                ->setTarget($game->getTable(), $game->getAttribute('id'))
                ->registerCreate($game->getAttributes());
        } else {
            // update
            BoAuditLog::instance()
                ->setTarget($game->getTable(), $game->getAttribute('id'))
                ->registerUpdate($game->old_entry, $game->getAttributes());
        }
    }

    /**
     * @param Game $game
     * @return array
     * @throws
     */
    public function unblocksLicensedCountry($game) {
        $licensed_countries = Config::getValue('countries', 'countries_with_certified_games', 'se,dk', Config::TYPE_COUNTRIES_LIST, true);
        $licensed_countries = array_map('strtoupper', $licensed_countries);

        $all = explode(' ', $game->blocked_countries);
        $current = explode(' ', $game->getOldValue('blocked_countries'));
        $unblocked_countries = [];

        foreach ($current as $country) {
            $should_unblock = !in_array($country, $all);
            $licensed_country = in_array($country, $licensed_countries);

            if ($should_unblock && $licensed_country) {
                $unblocked_countries[] = $country;
            }
        }

        return $unblocked_countries;
    }


    /**
     * @param Game $game
     * @param Request $request
     * @return bool|array
     */
    public function handleLicensedCountryDocument($game, $request) {
        $errors = [];

        if ($game->active !== 1) {
            return [null];
        }

        $unblocked_countries = $this->unblocksLicensedCountry($game);

        if (empty($unblocked_countries)) {
            return [null];
        }

        $errors['required_certificates_issues'] = 0;
        $errors['required_certificates'] = $unblocked_countries;
        $certificates=$versions=[];

        foreach ($unblocked_countries as $country) {
            $request_data = $request->get($country);
            $errors[$country] = [];

            if (empty($certificate = trim($request_data['i-certificate']))) {
                $errors[$country]['certificate_missing'] = true;
                $errors[$country]['certificate'] = ["Certificate is required to unblock $country."];
            }

            if (empty(trim($version = $request_data['i-certificate_version']))) {
                $errors[$country]['certificate_version'] = ["Version is required."];
            }

            $versions[$country] = $version;

            if ($certificate === 'new') {
                if (empty($_FILES[$country])) {
                    $errors[$country]['certificate'] = ['File is required.'];
                } elseif (($code = $_FILES[$country]['error']) !== UPLOAD_ERR_OK) {
                    $errors[$country]['certificate'] = [FileUploadHelper::codeToMessage($code)];
                }
                if (empty(trim($request_data['i-certificate_subtag']))) {
                    $errors[$country]['certificate_subtag'] = ['Subtag is required.'];
                }
                if (empty(trim($request_data['i-certificate_tag']))) {
                    $errors[$country]['certificate_tag'] = ['Tag is required.'];
                }
                if (empty(trim($request_data['i-certificate_ref']))) {
                    $errors[$country]['certificate_ref'] = ['Ref is required.'];
                }

                if (!empty($errors[$country]) && empty($errors[$country]['certificate'])) {
                    $errors[$country]['certificate'] = ['Upload the file again and fix the other issues.'];
                }
            }

            $errors['required_certificates_issues'] += (int)!empty($errors[$country]);
        }

        if ($errors['required_certificates_issues'] > 0) {
            $this->app['flash']->add('danger', 'Changes not saved. Please check the form for errors.');
            return [$errors];
        }

        /**
            "data" => [
                "type" => "certificate"
                "id" => "4"
                "attributes" => [
                    "certificate_ref" => "string"
                    "tag" => "string"
                    "subtag" => "string"
                    "uploaded_name" => "string.extension"
                    "version_number" => "string"
                    "deleted_at" => null
                    "created_at" => "timestamp"
                    "updated_at" => "timestamp"
                ]
            ]
         */

        foreach ($unblocked_countries as $country) {
            $request_data = $request->get($country);

            $certificate = trim($request_data['i-certificate']);

            if ($certificate === 'new') {
                try {
                    $res = (new Dmapi($this->app))->uploadDocument($_FILES[$country], [
                        'tag' => $request_data['i-certificate_tag'],
                        'subtag' => $request_data['i-certificate_subtag'],
                        'version_number' => $request_data['i-certificate_version'],
                        'certificate_ref' => $request_data['i-certificate_ref']
                    ]);

                    $certificates[$country] = @$res['data']['attributes']['certificate_ref'];

                    $this->app['monolog']->addError("Game wizard certificate:", [$res]);
                } catch (\Exception $e) {
                    $this->app['monolog']->addError("Game wizard certificate upload failed:", [$e->getMessage()]);
                    $cert_upload_err = true;
                }
            } else {
                $certificates[$country] = $certificate;
            }

            if (empty($certificate) || !empty($cert_upload_err)) {
                $this->app['monolog']->addError("Game wizard certificate:", [$res]);
                $this->app['flash']->add('danger', 'There was an internal error while uploading the file.');
                return [$errors];
            }
        }

        return [null, $unblocked_countries, $certificates, $versions];
    }

    /**
     * @param Request $request
     * @param array $data
     * @param integer $status
     * @return \Symfony\Component\HttpFoundation\JsonResponse|RedirectResponse
     */
    public function redirectBackWithData($request, $data, $status = 200) {

        if ($request->isXmlHttpRequest()) {
            return $this->app->json($data, $status);
        }

        $_SESSION['edit_game_errors'] = $data;
        $_SESSION['edit_game_data'] = $request->request->all();

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function getSettingsGamesSearchColumnsList()
    {
        $columns = [];

        $select = [
            'id' => 'ID',
            'game_name' => 'Name',
            'game_id' => 'Game ID',
            'ext_game_name' => 'External Game Name',
            'game_url' => 'URL',
            'network' => 'Network',
            'operator' => 'Operator',
            'tag' => 'Tag',
            'device_type' => 'Device Type',
            'payout_percent' => 'RTP',
            'min_bet' => 'Min Bet',
            'max_bet' => 'Max Bet',
            'max_win' => 'Max Win Multiplier',
            'volatility' => 'Volatility',
            'num_lines' => 'Number Of Lines',
            'jackpot_contrib' => 'Jackpot Contribution',
            'branded' => 'Branded',
            'ribbon_pic' => 'Ribbon Pic',
            'included_countries' => 'Included Countries',
            'mobile_id' => 'Mobile ID',
            'width' => 'Width(px)',
            'height' => 'Height(px)',
            'enabled' => 'Enabled',
            'multi_channel' => 'Multi Channel',
            'stretch_bkg' => 'Stretch Background',
            'active' => 'Active',
            'blocked_countries' => 'Blocked Countries',
            'blocked_provinces' => 'Blocked Provinces'
        ];

        $columns['list']               = array_merge($select);
        $columns['select']             = array_merge($select);
        $columns['default_visibility'] = ['name', 'game_id', 'ext_game_name', 'network', 'operator', 'tag', 'device_type'];

        return $columns;
    }

}

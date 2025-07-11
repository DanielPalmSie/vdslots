<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Classes\Dmapi;
use App\Extensions\Database\FManager as DB;
use App\Helpers\DataFormatHelper as Help;
use App\Models\BoAuditLog;
use App\Models\Game;
use App\Models\GameBlockedCountries;
use App\Models\GameCountryVersions;
use App\Models\GameFeatures;
use App\Models\GameTagConnection;
use App\Models\Operator;
use App\Models\Theme;
use App\Repositories\GamesRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Valitron\Validator;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\GameTagsUpdateHistoryMessage;
use Videoslots\HistoryMessages\GameUpdateHistoryMessage;

class SettingsGamesController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->post('/save/', 'App\Controllers\SettingsGamesController::save')
            ->bind('settings.games.save')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/saveas/', 'App\Controllers\SettingsGamesController::saveas')
            ->bind('settings.games.saveas')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/edit/features/', 'App\Controllers\SettingsGamesController::editFeatures')
            ->bind('settings.games.features.edit')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/save/features/', 'App\Controllers\SettingsGamesController::saveFeatures')
            ->bind('settings.games.features.save')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/save/gametag/', 'App\Controllers\SettingsGamesController::saveGameTag')
            ->bind('settings.games.gametag.save')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/edit/images/', 'App\Controllers\SettingsGamesController::editImages')
            ->bind('settings.games.images.edit')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/edit/images/upload/', 'App\Controllers\SettingsGamesController::fileUpload')
            ->bind('games.images.upload')
            ->before(function () use ($app) {
                if (!p('settings.games.fileupload')) {
                    $app->abort(403);
                }
            });

        $factory->post('/add/features/', 'App\Controllers\SettingsGamesController::addFeature')
            ->bind('settings.games.features.add')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/remove/features/', 'App\Controllers\SettingsGamesController::removeFeature')
            ->bind('settings.games.features.remove')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/changes-log/', 'App\Controllers\SettingsGamesController::getChangesLog')
            ->bind('settings.games.changes-log')
            ->before(function () use ($app) {
                if (!p('settings.games.section.changes-log')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/search/', 'App\Controllers\SettingsGamesController::search')
            ->bind('settings.games.search')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/search-country/', 'App\Controllers\SettingsGamesController::searchCountry')
            ->bind('settings.games.search-country')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/search-province/', 'App\Controllers\SettingsGamesController::searchProvinces')
            ->bind('settings.games.search-provinces')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/format-provinces/', 'App\Controllers\SettingsGamesController::helpFormatProvinces')
            ->bind('settings.games.format-provinces')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/search-theme/', 'App\Controllers\SettingsGamesController::searchTheme')
            ->bind('settings.games.search-theme')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/search-mobile/', 'App\Controllers\SettingsGamesController::searchMobile')
            ->bind('settings.games.search-mobile')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/get-operators/', 'App\Controllers\SettingsGamesController::getOperators')
            ->bind('settings.games.get-operators')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/load-default-features/', 'App\Controllers\SettingsGamesController::loadDefaultFeatures')
            ->bind('settings.games.features.load-defaults')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/load-countries-include/', 'App\Controllers\SettingsGamesController::loadCountriesInclude')
            ->bind('settings.games.load-countries-include')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/load-themes/', 'App\Controllers\SettingsGamesController::loadThemes')
            ->bind('settings.games.load-themes')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/edit/images/delete/', 'App\Controllers\SettingsGamesController::deleteImages')
            ->bind('settings.games.images.delete')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/search-games/', 'App\Controllers\SettingsGamesController::searchGamesSettings')
            ->bind('settingsgametemplates.search')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param null $users_list
     * @return mixed
     * @throws \Exception
     */
    public function index(Application $app, Request $request, $users_list = null)
    {
        $repository = new GamesRepository($app);
        $columns = $repository->getSettingsGamesSearchColumnsList();

        if (!isset($_COOKIE['settingsgametemplates-search-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('settingsgametemplates-search-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['settingsgametemplates-search-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['settingsgametemplates-search-no-visible'], true);
        }

        $res = $this->getGamesSettingsList($app, $request, [
            'ajax'         => false,
            'length'       => 25,
        ]);

        $pagination = [
            'data'           => $res['data'],
            'defer_option'   => $res['recordsTotal'],
            'initial_length' => 25
        ];

        $breadcrumb = "List and Search";
        $view = [
            'new' => 'Game',
            'title' => 'Games',
            'variable' => 'settingsgametemplates',
            'variable_param' => 'id',
            'edit_route' => 'settings.games.edit',
            'create_route' => 'settings.games.edit',
        ];

        return $app['blade']->view()
            ->make('admin.settings.games.index', compact('app', 'columns', 'pagination', 'breadcrumb', 'view'))
            ->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function searchGamesSettings(Application $app, Request $request)
    {
        return $app->json(
            $this->getGamesSettingsList($app, $request, [
                'ajax' => true
            ])
        );
    }


    /**
     * @param Request $request
     * @param Application $app
     * @param array $attributes
     * @return array
     * @throws \Exception
     */
    private function getGamesSettingsList(Application $app, Request $request, array $attributes = [])
    {
        $start = 0;
        $order_dir = "ASC";
        $order_column = "game_name";
        $repository = new GamesRepository($app);
        $fields = implode(", ", array_keys($repository->getSettingsGamesSearchColumnsList()['select']));

        $search_query = DB::table('micro_games AS mg');
        $search_query->selectRaw("{$fields}");

        foreach($request->get('columns') as $value) {
            if (strlen($value['search']['value']) <= 0)
                continue;

            $words = explode(" ", $value['search']['value']);
            foreach($words as $word) {
                $search_query->where($value['data'], 'LIKE', "%".$word."%");
            }
        }

        $search = $request->get('search')['value'];
        if (strlen($search) > 0) {
            foreach (explode(' ', $search) as $q) {
                $search_query->where('game_id', 'LIKE', "%$q%");
                $search_query->orWhere('network', 'LIKE', "%$q%");
                $search_query->orWhere('game_url', 'LIKE', "%$q%");
                $search_query->orWhere('operator', 'LIKE', "%$q%");
                $search_query->orWhere('game_name', 'LIKE', "%$q%");
                $search_query->orWhere('ext_game_name', 'LIKE', "%$q%");
            }
        }

        $total_records = $search_query->count();
        if ($attributes['ajax']) {
            $start        = $request->get('start');
            $length       = $request->get('length');
            $order        = $request->get('order')[0];
            $order_column = $request->get('columns')[$order['column']]['data'];
            $order_dir    = $order['dir'];
        } else {
            $length = min($total_records, $attributes['length']);
        }

        $data = $search_query->orderBy($order_column, $order_dir)
            ->limit($length)
            ->skip($start)
            ->get();

        return [
            "draw"            => intval($request->get('draw')),
            "recordsTotal"    => $total_records,
            "recordsFiltered" => $total_records,
            "data"            => $data
        ];
    }

    public function fileUpload(Application $app, Request $request)
    {
        if (empty($_FILES['file']) || empty($request->get('id', 0))) {
            return $app->json('File or game ID is empty.', 403);
        }

        $games_repo = new GamesRepository($app);
        try {
            $game = $games_repo->getGameById($request->get('id', 0));
        } catch (\Exception $e) {}

        if (!$game) {
            return $app->json('Game with ID '.$request->get('id').' not found.', 403);
        }

        $file = $_FILES['file'];
        $src_file = $file['tmp_name'];
        list($width, $height, $type, $attr) = getimagesize($src_file);

        $image_type = $request->get('type');

        switch ($image_type) {
            case 'background':
                if ($width < 2000 || $height < 1000) {
                    return $app->json('Image size can not be less than 2000x1000px.', 403);
                }

                $game->bkg_pic = str_replace(' ', '', $game->game_name) . '_BG.jpg';
                $game->save();
                break;
            case 'screen_shot':
                if ($width < 630 || $height < 470) {
                    return $app->json('Image size can not be less than 630x470px.', 403);
                }
                break;
            case 'thumbnail':
                if ($width != 155 || $height != 130) {
                    return $app->json('Image size can not be differ than 155x130px.', 403);
                }
                break;
            case 'mobileGameBannerImage':
                if ($width != 780 || $height != 380) {
                    return $app->json('Image size can not be differ than 780x380px.', 403);
                }
                break;
            case 'desktopGameBannerImage':
                if ($width != 590 || $height != 274) {
                    return $app->json('Image size can not be differ than 590x274px.', 403);
                }
                break;
            default:
                break;
        }

        $destination_file = $games_repo->getPath($game, $image_type);
        if (phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
            throw new Exception('This function is not working properly yet due to send_public_files_to_dmapi setting');
            //phive('Dmapi')->uploadPublicFile($src_file, 'file_uploads', $destination_file, '');
        } else {
            @unlink($destination_file);
            if (move_uploaded_file($src_file, $destination_file)) {
                chmod($destination_file, 0777);
            }
            else {
                return $app->json('Could not be saved.', 403);
            }
        }

        return $app->json(['success' => true]);
    }

    public function saveFeatures(Application $app, Request $request)
    {
        $params = $request->request->all();

        $v = new Validator($params);

        $v->rules(['required' => [
            ['name'],
            ['type'],
            ['sub_type'],
            ['value']
        ]]);

        if ($v->validate()) {
            $game_features = GameFeatures::query()->whereIn('id', array_keys($params['name']))->get();
            $game_features->each(function ($feature) use ($params) {
                $feature->update([
                    'type' => $params['type'][$feature->id],
                    'sub_type' => $params['sub_type'][$feature->id],
                    'value' => $params['value'][$feature->id]
                ]);
            });

            return $app->json(['ok' => 1]);
        }

        return $app->json($v->errors(), 400);
    }

    public function addFeature(Application $app, Request $request)
    {
        $params = $request->request->all();

        $params['name'] = str_replace(' ', '_', strtolower($params['name']));
        //$params['name'] = strtolower($params['name']);

        $v = new Validator($params);

        $v->addRule('nameExists', function($field, $value, array $params, array $fields) {

            if ($value == '') {
                return true;
            }

            $row = GameFeatures::where('game_id', (int) $params[0])->where('name', $value)->first();

            if (!$row) {
                return true;
            }
            return false;
        }, ' already exists. Please choose another.');

        $v->rule('nameExists', 'name', $request->get('game_id', 0));
        $v->rule('regex', 'name', '/^([a-z0-9%_ -])+$/i');

        $v->rules([
            'required' => [
                ['game_id'],
                ['name'],
                ['value']
            ]
        ]);

        if ($v->validate()) {

            $game_feature = GameFeatures::create($params);

            BoAuditLog::instance()
                ->setTarget($game_feature->getTable(), $game_feature->id)
                ->setContext('micro_games', $game_feature->game_id)
                ->registerCreate($game_feature->getAttributes());

            return $app->json(['id' => $game_feature->id]);
        }

        return $app->json($v->errors(), 400);
    }

    public function loadDefaultFeatures(Application $app, Request $request)
    {
        $game_id = (int) $request->get('game_id', 0);

        foreach (GameFeatures::getDefaults() as $name => $val) {

            $name = str_replace(' ', '_', strtolower($name));

            $game_feature = GameFeatures::where('game_id', $game_id)->where('name', $name)->first();

            if (!$game_feature) {
                // add if doesnt exists
                $game_feature = GameFeatures::create([
                    'game_id' => $game_id,
                    'name' => $name,
                    'type' => 'info',
                    'sub_type' => '',
                    'value' => $val
                ]);

                BoAuditLog::instance()
                    ->setTarget($game_feature->getTable(), $game_feature->id)
                    ->setContext('micro_games', $game_id)
                    ->registerCreate($game_feature->getAttributes());
            }
        }

        return $app->json(['success' => true]);
    }

    public function removeFeature(Application $app, Request $request)
    {
        $items = $request->get('items', [$request->get('id', 0)]);

        $features = GameFeatures::query()->whereIn('id', $items)->get();

        $features->each(function($feature) {
            BoAuditLog::instance()
                ->setContext('micro_games', $feature->game_id)
                ->setTarget($feature->getTable(), $feature->id)
                ->registerDelete($feature->getAttributes());

            $feature->delete();
        });

        return $app->json(['ok' => 1, 'items' => $features->pluck('id')]);
    }

    /**
     * @param Application $app
     * @param Request     $request
     *
     * @return mixed
     * @throws
     */
    public function edit(Application $app, Request $request)
    {
        $games_repo = new GamesRepository($app);

        if (($id = (int)$request->get('id', 0)) !=  0) {
            $item = Game::query()->find($id);
            $game_id = $id;
        } else {
            $old_data = $request->get('results');
            $old_data = Help::htmlEntityToObject($old_data);
            $item = new Game((array)$old_data);
        }

        $game_tags = DB::table("micro_games")
            ->selectRaw('distinct tag')
            ->get()
            ->pluck('tag')
            ->values();

        $network_module_id = ['netent','evolution','microgaming'];
        $ribbon_pictures = [
            'live-casino-AuthenticGaming_icon',
            'live-casino-betgamestv_icon',
            'live-casino-evolution_icon',
            'live-casino-ezugi_icon',
            'live-casino-netent_icon',
            'live-casino-playtech_icon',
            'live-casino-pragmatic_icon',
            'live-casino-raw_icon',
            'iconexclusive',
            'newgameicon',
            'rtg-dailyjackpot_icon',
            'DropsandWin_icon'
            ];

        $languages = DB::table("micro_games")
            ->select('languages')
            ->where('id', $game_id)
            ->pluck('languages')
            ->toArray();

        $all_languages = phive('Localizer')->getLangSelect();

        $chosen_languages = explode(',', $languages[0]);

        $certificates = DB::table('game_country_versions')
            ->select(['game_country_versions.game_certificate_ref'])
            ->leftJoin('micro_games', 'micro_games.id', '=', 'game_country_versions.game_id')
            ->where('micro_games.network', '=', $item->network)
            ->where('game_country_versions.game_certificate_ref', '!=', '')
            ->groupBy(['micro_games.network', 'game_country_versions.game_certificate_ref'])
            ->get();


        return $app['blade']->view()->make('admin.settings.games.edit', compact(
            'app', 'item', 'id', 'games_repo', 'old_data', 'game_tags', 'network_module_id',
            'ribbon_pictures', 'game_id', 'certificates', 'chosen_languages', 'all_languages'
            ))->render();
    }

    public function loadCountriesInclude(Request $request)
    {
        $countries = $request->get('countries');
        $countries = Help::formatCountries($countries);
        return json_encode($countries);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function save(Application $app, Request $request)
    {
        $games_repo = new GamesRepository($app);

        $game = Game::findOrNewFromRequest($request);
        $is_new = is_null($game->id);

        $operator = Operator::query()
            ->where('name', $request->request->get("operator"))
            ->where('network', $request->request->get("network"))
            ->first();

        if (!$operator) {
            $operator =  Operator::query()->create([
                'name' => $request->request->get("operator"),
                'network' => $request->request->get("network"),
            ]);
            $request->request->set("operator", $operator->name);
            $request->request->set("network", $operator->network);
            // these items currentl
            // blocked_countries_non_branded
            // blocked_countries_jackpot
            // branded_op_fee
        }
        $request_tag = $request->request->get("tag")[0];
        $request->request->set("tag", $request_tag);
        $tag_args = [];
        if ($request_tag != $game->tag) {
            $tag_args = [
                'game_id' => (int)$game->id,
                'tags' => [$request_tag],
                'event_timestamp' => time(),
            ];
        }

        $game->customFillAttributes($request->request->all(), $games_repo);

        list($err, $countries, $crt, $v) = $games_repo->handleLicensedCountryDocument($game, $request);

        if (!empty($err)) {
            return $games_repo->redirectBackWithData($request, $err, 400);
        }

        //validating that the mobile game id was not modified forcefully through HTML modification for html5 games
        if($game->device_type === 'html5' && (isset($game->mobile_id) && $game->mobile_id !== 0)){
            $reqData = $request->request->all();
            unset($reqData['mobile_id']);

            // remove the mobile_id, otherwise it will be returned and overwrite the mobile_id value that should not be changed
            $request->request->replace($reqData);
            $app['flash']->add('danger', 'Game ID cannot be modified for html5 games');
            return $games_repo->redirectBackWithData($request, $game->getErrors(), 400);
        }

        if (!$game->save()) {
            $app['flash']->add('danger', 'Please check the form for errors.');
            return $games_repo->redirectBackWithData($request, $game->getErrors(), 400);
        }

        try {
            $args = [
                'id' => (int)$game->id,
                'is_new' => $is_new,
                'mobile_id' => (int)($game->mobile_id ?? 0),
                'tag' => $game->tag,
                'game_name' => $game->game_name,
                'game_id' => $game->game_id,
                'operator' => $game->operator,
                'provider_game_id' => $game->ext_game_name,
                'device_type_number' => (int)$game->device_type_num,
                'enabled' => (int)$game->enabled,
                'active' => (int)$game->active,
                'event_timestamp' => time(),
            ];
            lic('addRecordToHistory', [
                    'game_update',
                    new GameUpdateHistoryMessage($args)
                ]
            );
            if (!empty($tag_args)) {
                lic('addRecordToHistory', [
                        'game_tags_update',
                        new GameTagsUpdateHistoryMessage($tag_args)
                    ]
                );
            }
        } catch (InvalidMessageDataException $e) {
            $app['monolog']->addError("Invalid message data on SettingsGamesController", [
                'args' => $args,
                'validation_errors' => $e->getErrors()
            ]);
        }

        // licensed $countries are being unblocked, store data in game_country_versions
        if (!empty($countries)) {
            foreach ($countries as $country) {
                 GameCountryVersions::query()
                    ->where('game_id', $game->id)
                    ->where('country', $country)
                    ->firstOrCreate([
                        'game_id' => $game->id,
                        'country' => $country,
                        'game_version' => $v[$country],
                        'game_certificate_ref' => $crt[$country]
                    ]);
            }
        }

        $games_repo->newGameLogs($game);

        $games_repo->saveThemes($game->id, $request->get('themes'));

        $app['flash']->add('success', 'Changes saved successfully');

        return $games_repo->redirectBackWithData($request, ['id' => $game->id]);
    }

    public function saveas(Application $app, Request $request)
    {
        $id = $request->get('id', 0);
        $values = $request->request->all();

        $values['id'] = $id;
        $values['languages'] = implode($request->get('languages', []), ',');
        $values['enabled'] = $request->get('enabled', 0);
        $values['multi_channel'] = $request->get('multi_channel', 0);
        $values['stretch_bkg'] = $request->get('stretch_bkg', 0);
        $values['active'] = $request->get('active', 0);
        $values['blocked_countries'] = str_replace(',', ' ', trim($values['blocked_countries'], ', '));
        $values['included_countries'] = str_replace(',', ' ', trim($values['included_countries'], ', '));
        $values['blocked_provinces'] = str_replace(',', ' ', trim($values['blocked_provinces'], ', '));

        if($values['mobile_id'] === ''){
            $values['mobile_id'] = '0';
        }

        return json_encode($values);
    }

    /**
     * @param Request $request
     *
     * @return string
     * @throws \Exception
     */
    public function searchCountry(Request $request)
    {
        return DB::table("bank_countries")
            ->where("printable_name","like","%{$request->get("search", "")}%")
            ->get()
            ->map(function ($bankCountry) {
                return [
                    "text" => $bankCountry->printable_name,
                    "value" => $bankCountry->iso
                ];
            })
            ->toJson();
    }

    public function searchProvinces(Request $request)
    {
        return DB::table('license_config')
            ->where('config_tag', '=', 'provinces')
            ->where('license', '=', 'ca')
            ->where('config_name', 'like', "%{$request->get('search', '')}%")
            ->get()
            ->map(function ($result) {
                $data = json_decode($result->config_value, true);
                return [
                    'value' => strtoupper($result->license) . '-' . ($data['iso_code'] ?? $data['iso_province']),
                    'text' => $result->config_name,
                ];
            })
            ->pipe(function ($rows) {
                return json_encode($rows);
            });
    }

    public function helpFormatProvinces(Application $app, Request $request)
    {
        $provinces = $request->get('blocked_provinces');
        $provinces = Help::formatProvince($provinces);
        return json_encode($provinces);
    }

    /**
     * @param Request $request
     *
     * @return string
     * @throws \Exception
     */
    public function searchTheme(Request $request)
    {
        return DB::table("themes")
            ->selectRaw("*")
            ->where("name", "like","%{$request->get("search", "")}%")
            ->get()
            ->map(function ($theme) {
                return [
                    'text' => $theme->name,
                    'value' => $theme->id
                ];
            })
            ->pipe(function ($rows) {
                return json_encode($rows);
            });
    }
    /**
     * @param Request $request
     *
     * @return string
     * @throws \Exception
     */
    public function searchMobile(Request $request)
    {
        return DB::table("micro_games")
            ->selectRaw("*")
            ->where('device_type_num', 1)
            ->where(function ($query) use ($request) {
                $query->where("game_name", "like", "%{$request->get("search", "")}%")
                    ->orWhere("id", "like", "%{$request->get("search", "")}%");
            })
            ->get()
            ->map(function ($microGame) {
                return [
                    'text' => $microGame->game_name,
                    'id' => $microGame->id,
                    'game_url'=> phive('MicroGames')->getUrl($microGame->id, (array)$microGame, false, true)
                ];
            })
            ->pipe(function ($rows) {
                return json_encode($rows);
            });
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function loadThemes(Request $request)
    {
        $themes = $request->get('themes');

        return Theme::query()
            ->whereIn('id', explode(',', $themes[0]))
            ->get()
            ->mapWithKeys(function ($theme) {
                return [$theme->id => $theme->name];
            })
            ->pipe(function ($rows) {
                return json_encode($rows);
            });
    }

    /**
     * @param Application $app
     * @param Request     $request
     *
     * @return string
     * @throws
     */
    public function search(Application $app, Request $request)
    {
        $edit_games_url = $app['url_generator']
            ->generate('settings.games.edit');

        $games_data = (new GamesRepository($app))
            ->getGames(
                $request->get('search')['value'],
                $request->get('columns', []),
                (int)$request->get('start', 0),
                (int)$request->get('length', 50),
                true
            );

        $games_data['data'] = collect($games_data['data'])
            ->map(function ($game) use ($edit_games_url) {
                $game->overrides_count = empty($game->overrides_count) ? 0 : $game->overrides_count;
                return [
                    "<a href='{$edit_games_url}?id={$game->id}'>
                        {$game->game_name}
                    </a>",
                    $game->device_type,
                    $game->game_id,
                    $game->operator,
                    $game->network,
                    $game->payout_percent,
                    $game->min_bet,
                    $game->max_bet,
                    "<button class='expand-overrides btn btn-default' data-id='{$game->id}'>{$game->overrides_count}</button>"
                ];
            })
            ->all();

        return json_encode($games_data);
    }

    /**
     * @param Application $app
     * @param Request     $request
     *
     * @return RedirectResponse|JsonResponse
     * @throws \Exception
     */
    public function saveGameTag(Application $app, Request $request)
    {
        $game_id = $request->get("id", 0);

        $all_tags = collect($tags_arr = explode(',', $request->get('tag', '')))
            ->map(function ($tag_id) use ($game_id) {
                $tag_id = intval($tag_id);
                return compact('tag_id', 'game_id');
            })
            ->filter(function($conn) {
                return !empty($conn['tag_id']);
            });

        $current_tags = GameTagConnection::query()->where('game_id', $game_id)->get();

        $to_delete = $current_tags->filter(function($tag) use ($all_tags) {
            return !$all_tags->pluck('tag_id')->contains($tag['tag_id']);
        });

        $to_insert = $all_tags->filter(function($tag) use ($current_tags) {
            return !$current_tags->pluck('tag_id')->contains($tag['tag_id']);
        });

        $to_delete->each(function($conn) use ($game_id) {
            try {
                $conn->delete();

                BoAuditLog::instance()
                    ->setTarget($conn->getTable(), $conn->id)
                    ->setContext('micro_games', $game_id)
                    ->registerDelete($conn->getAttributes());
            } catch (\Exception $e) {

            }
        });

        $to_insert->each(function($conn) use ($game_id) {
            $game_tag_connection = GameTagConnection::create($conn);

            BoAuditLog::instance()
                ->setTarget($game_tag_connection->getTable(), $game_tag_connection->id)
                ->setContext('micro_games', $game_id)
                ->registerCreate($game_tag_connection->getAttributes());
        });

        if ($request->isXmlHttpRequest()) {
            return $app->json([]);
        }

        // redirect back
        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteImages(Application $app, Request $request) {

        $game = Game::query()->find($request->get('game_id'));
        $image_type = $request->get('image');

        if (!$game) {
            return $app->redirect($request->headers->get('referer'));
        }

        $file = GamesRepository::instance($app)->getPath($game, $image_type);

        if ($image_type == 'background') {
            $game->bkg_pic = '';
            $game->save();
        }

        unlink($file);

        return $app->redirect($request->headers->get('referer'));
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function getChangesLog(Application $app, Request $request) {
        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            }
        }
        return GamesRepository::instance($app)->getChangesLogView($request->get('id'));
    }

    public function getOperators(Application $app, Request $request)
    {
        $games_repo = new GamesRepository($app);

        $data = $games_repo->getOperators($request->get('network', ''));

        return json_encode($data);
    }
}

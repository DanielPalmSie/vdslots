<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Helpers\PaginationHelper;
use App\Models\BankCountry;
use App\Models\Game;
use App\Models\GameTypes;
use App\Models\Operator;
use App\Repositories\GamesRepository;
use Illuminate\Support\Collection;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;
use Valitron\Validator;

class SettingsOperatorsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\SettingsOperatorsController::index')
            ->bind('settings.operators.index')
            ->before(function () use ($app) {
                if (!p('settings.operators.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/edit/', 'App\Controllers\SettingsOperatorsController::edit')
            ->bind('settings.operators.edit')
            ->before(function () use ($app) {
                if (!p('settings.operators.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/save/', 'App\Controllers\SettingsOperatorsController::save')
            ->bind('settings.operators.save')
            ->before(function () use ($app) {
                if (!p('settings.operators.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/update-games/', 'App\Controllers\SettingsOperatorsController::updateGames')
            ->bind('settings.operators.update-games')
            ->before(function () use ($app) {
                if (!p('settings.operators.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/search/', 'App\Controllers\SettingsOperatorsController::search')
            ->bind('settings.operators.search')
            ->before(function () use ($app) {
                if (!p('settings.operators.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/search-country/', 'App\Controllers\SettingsOperatorsController::searchCountry')
            ->bind('settings.operators.search-country')
            ->before(function () use ($app) {
                if (!p('settings.operators.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/get-formatted-countries/', 'App\Controllers\SettingsOperatorsController::getFormattedCountries')
            ->bind('settings.operators.countries.formatted')
            ->before(function () use ($app) {
                if (!p('settings.operators.section')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * @param Application $app
     */
    public function index(Application $app, Request $request)
    {
        $breadcrumb = 'List and Search';

        return $app['blade']->view()->make('admin.settings.operators.index', compact('app', 'breadcrumb'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function edit(Application $app, Request $request)
    {
        $item = Operator::query()->find($request->get('id', ''));
        if(empty($item) && empty($request->get('id'))) {
            $item = new Operator();
        }

        $games_repo = new GamesRepository($app);

        $breadcrumb = ($request->get('id', 0) != 0) ? 'Edit' : 'New';

        $breadcrumb_elms = [
            $app['url_generator']->generate('settings.operators.edit').'?id='.$item->id => $item->id
        ];

        $networks = $games_repo->getNetworks();
        $operators = $games_repo->getOperators($item->network);
        $blocked_countries = $this->getCountriesFormatted($item->blocked_countries);
        $blocked_countries_jackpot = $this->getCountriesFormatted($item->blocked_countries_jackpot);
        $blocked_countries_non_branded = $this->getCountriesFormatted($item->blocked_countries_non_branded);

        return $app['blade']->view()->make('admin.settings.operators.edit',
            compact('blocked_countries_jackpot', 'blocked_countries_non_branded', 'blocked_countries', 'app', 'breadcrumb', 'breadcrumb_elms', 'networks', 'item', 'operators')
        )->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function getFormattedCountries(Application $app, Request $request)  {
        $data = [];
        foreach ($request->request->all() as $key => $val) {
            $data[$key] = $this->getCountriesFormatted($val);
        }
        return $app->json($data);
    }

    /**
     * @param $countries
     * @param bool $ignore_invalid
     * @return array
     */
    private function getCountriesFormatted($countries, $ignore_invalid = true)
    {
        $countries_list = array_flip(array_unique(explode(" ", $countries)));

        foreach ($countries_list as $k => $v) {
            $countries_list[$k] = $k;
        }

        return BankCountry::query()
            ->whereIn('iso',  array_keys($countries_list))
            ->get()
            ->mapWithKeys(function($bank_country) {
                return [$bank_country->iso => $bank_country->printable_name];
            })
            ->tap(function($countries) use ($countries_list, $ignore_invalid) {
                /** @var Collection $countries */
                if ($ignore_invalid) {
                    return $countries;
                }

                // make sure that no country is left out
                return $countries->union($countries_list);
            })
            ->all();
    }

    public function updateGames(Application $app, Request $request)
    {
        $operator = Operator::find($request->get('id', 0));

        if ($operator) {
            // add non_branded and branded countries here!
            // add jackpot blocked countries
            $num_affected_branded = Game::where('network', $operator->network)->where('operator', $operator->name)->where('branded', GameTypes::Branded)->update([
                'op_fee' => $operator->branded_op_fee,
                'blocked_countries' => $operator->blocked_countries
            ]);
            $num_affected_non_branded = Game::where('network', $operator->network)->where('operator', $operator->name)->where('branded', GameTypes::NonBranded)->update([
                'op_fee' => $operator->non_branded_op_fee,
                'blocked_countries' => $operator->blocked_countries_non_branded
            ]);
            $num_affected_jackpot = Game::where('network', $operator->network)->where('operator', $operator->name)->where('jackpot_contrib', '>', 0)->update([
                'blocked_countries' => $operator->blocked_countries_jackpot
            ]);
        }

        return json_encode(['result' => [
            'branded'       => $num_affected_branded ?? 0,
            'non_branded'   => $num_affected_non_branded ?? 0,
            'jackpot'       => $num_affected_jackpot ?? 0
        ]]);
    }

    public function save(Application $app, Request $request)
    {
        $values = $request->request->all();
        $v = new Validator($values);

        $v->addRule('operatorExists', function($field, $value, array $params, array $fields) {

            if ($value == '') {
                return true;
            }

            $operator = Operator::query()
                ->where('name', 'like', $value)
                ->where('id', '!=', (int) $params[0])
                ->where('network', $params[1])
                ->first();

            if (!$operator) {
                return true;
            }
            return false;
        }, ' for selected network already exists. Please choose another.');

        $v->addRule('countriesListCheck', function($field, $value, array $params, array $fields) {

            $value = trim($value, ', ');

            if (strpos($value, ',')) {
                $items = explode(',', $value);
            }
            else {
                $items = explode(' ', $value);
            }

            foreach ($items as $item) {
                if (strlen($item) != 2) {
                    return false;
                }
            }
            return true;
        }, ' wrong format.');

        $v->addRule('minMax', function($field, $value, array $params, array $fields) {
            if ($value < 0 || $value >= 1) {
                return false;
            }
            return true;
        }, ' must be more than 0 and less than 1.');

        $v->rule('operatorExists', 'name', $request->get('name'), $request->get('id'), $request->get('network'));

        $v->rules([
            'required' => [
                ['branded_op_fee'],
                ['non_branded_op_fee'],
                ['name'],
                ['network']
            ],
            'numeric' => [
                ['branded_op_fee'],
                ['non_branded_op_fee']
            ],
            'minMax' => [
                ['branded_op_fee'],
                ['non_branded_op_fee']
            ],
            'lengthMin' => [
                ['name', 2]
            ],
            'countriesListCheck' => [
                ['blocked_countries'],
                ['blocked_countries_non_branded'],
                ['blocked_countries_jackpot']
            ]
        ]);

        if ($v->validate()) {

            $id = $request->get('id', 0);

            $values['blocked_countries'] = str_replace(',', ' ', trim($values['blocked_countries'], ', '));
            $values['blocked_countries_jackpot'] = str_replace(',', ' ', trim($values['blocked_countries_jackpot'], ', '));
            $values['blocked_countries_non_branded'] = str_replace(',', ' ', trim($values['blocked_countries_non_branded'], ', '));

            if ($id == 0) {
                $row = new Operator($values);
                $row->save();
                $id = $row->id;
            }
            else {
                $row = Operator::query()->find($id);
                if ($row) {
                    $row->fill($values);
                    $row->save();
                }
            }

            return $app->json(['id' => $id]);
        }

        return $app->json($v->errors(), 400);
    }

    public function searchCountry(Application $app, Request $request)
    {
        $result = DB::table('bank_countries')
            ->selectRaw("*")
            ->where('printable_name', 'like', "%{$request->get('search', '')}%");

        $data = [];

        foreach($result->get() as $row) {
            $data[] = [
                'text' => $row->printable_name,
                'value' => $row->iso
            ];
        }

        return json_encode($data);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return string
     * @throws \Exception
     */
    public function search(Application $app, Request $request)
    {
        $search = $request->get('search', '')['value'];

        $list = Operator::query()
            ->selectRaw("id, name, network, concat(branded_op_fee,', ', non_branded_op_fee) as fees")
            ->where('name', 'LIKE', "%{$search}%")
            ->orWhere('network', 'LIKE', "%{$search}%");

        $paginator = new PaginationHelper($list, $request, [
            'length' => $request->get('length', 25),
            'order' => ['column' => 'name', 'order' => 'ASC']
        ]);

        $result = $paginator->getPage(false);

        $result['data'] = array_map(function($operator) use ($app) {
            return [
                'name' => '<a href="'.$app['url_generator']->generate('settings.operators.edit').'?id='.$operator->id.'">'.$operator->name.'</a>',
                'network' => $operator->network,
                'fees' => $operator->fees,
            ];
        }, $result['data']);

        return json_encode($result);
    }
}

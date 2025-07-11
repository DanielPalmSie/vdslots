<?php

namespace App\Controllers;

use App\Models\BoAuditLog;
use Illuminate\Http\RedirectResponse;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Models\Game;
use App\Models\GameOverride;
use App\Extensions\Database\FManager as DB;
use App\Repositories\GameRepository;
use App\Helpers\Common;

class GameOverrideController implements ControllerProviderInterface
{

    public array $form_info;

    public function __construct(){
        $this->form_info = [
            'ext_game_id'          => ['ext_game_name',        'External GP ID',        'The identifier at the GP'],
            'ext_launch_id'        => ['game_id',              'External GP Launch ID', 'The launch identifier for the GP'],
            'payout_percent'       => ['payout_percent',       'RTP',                   'The RTP of the game for this jurisdiction'],
            'payout_extra_percent' => ['payout_extra_percent', 'RTP Modifier',          'The RTP modifier for this game and jurisdiction (leave empty or 0, without decimals, and the system will ignore it)']
        ];
    }
    
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $routes = [['url'  => '/',                            'm' => 'index',               'bind' => 'games-override',                 'p' => 'game.override'],
                   ['url'  => '/list-games/',                 'm' => 'listGames',           'bind' => 'list-games-for-game-overrides',  'p' => 'game.override'],
                   ['url'  => '/list-game-overrides/',        'm' => 'listOverrides',       'bind' => 'games-list-overrides',           'p' => 'game.override'],
                   ['url'  => '/create-game-override/',       'm' => 'createOverride',      'bind' => 'games-create-override',          'p' => 'game.override'],
                   ['url'  => '/update-game-override/',       'm' => 'updateOverride',      'bind' => 'games-update-override',          'p' => 'game.override'],
                   ['url'  => '/update-game-override-start/', 'm' => 'updateOverrideStart', 'bind' => 'games-update-override-start',    'p' => 'game.override'],
                   ['url'  => '/delete-game-override/',       'm' => 'deleteOverride',      'bind' => 'games-delete-override',          'p' => 'game.override']];

        Common::doRoutes($app, $factory, $routes, 'game.override', 'GameOverrideController');
        
        return $factory;
    }

    public function index(Application $app, Request $request)
    {
        $overrides = DB::table('micro_games as mg')->selectRaw('mg.*, COUNT(gco.id) as total_overridden')
            ->leftJoin('game_country_overrides as gco', 'mg.id', '=', 'gco.game_id')
            ->whereNotNull('gco.id')
            ->groupBy(['mg.id', 'mg.device_type'])
            ->get();

        return Common::view($app, 'admin.gameoverrides.index', array_merge(
            $this->getCommonViewVars($app),
            [
                'sort' => ['column' => 0, 'type' => "desc"],
                'games' => $overrides,
                'show_group' => true
            ]));
    }

    public function listGames(Application $app, Request $request){
        $partial = $request->get('partial');
        $games   = empty($partial) ? [] : Game::where('ext_game_name', 'LIKE', "%$partial%")->orWhere('game_name', 'LIKE', "%$partial%")->get();
        return Common::view($app, 'admin.gameoverrides.listgames', [
            'games' => $games,
            'app'   => $app
        ]);
        
    }

    public function getLabelsAndDescriptions(){
        $labels = [];
        $descrs = [];
        foreach($this->form_info as $key => $val){
            $labels[$key] = $val[1];
            $descrs[$key] = $val[2];
        }
        return [$labels, $descrs];
    }

    public function getCommonViewVars($app){

        list($labels, $descrs) = $this->getLabelsAndDescriptions();
        
        return [
            'app'       => $app,
            'form_info' => $this->form_info,
            'labels'    => $labels,
            'descrs'    => $descrs,
        ];
    }
    
    public function listOverrides(Application $app, Request $request){
        $overrides     = GameOverride::where('game_id', $request->get('game_id'))->get();
        $game          = Game::find($request->get('game_id'))->toArray();

        $create_prepop = [];
        foreach($this->form_info as $key => $val){
            if ($key === 'payout_extra_percent') { // RTP Modifier
                $create_prepop[$key] = 0;
            } else {
                $create_prepop[$key] = $game[$val[0]];
            }
        }

        if (!empty($request->get('only_data'))) {
            return $app->json($overrides);
        }

        return Common::view($app, 'admin.gameoverrides.listoverrides', array_merge(
            $this->getCommonViewVars($app),
            [
                'overrides' => $overrides,
                'game'      => $game,
                'prepops'   => $create_prepop,
                'action'    => 'Create'
            ]));
    }

    public function updateOverrideStart(Application $app, Request $request){
        $override     = GameOverride::find($request->get('id'));
        $prepops      = array_intersect_key($override->toArray(), $this->form_info);

        return Common::view($app, 'admin.gameoverrides.update_start', array_merge(
            $this->getCommonViewVars($app),
            [
                'prepops'      => $prepops,
                'jurisdiction' => $override->country,
                'action'       => 'Update',
                'id'           => $override->id,
                'game'         => $override->game->toArray()
            ]));        
    }

    public function createOverride(Application $app, Request $request){
        $arr                    = array_map('trim', $request->request->all());
        $game                   = Game::find($request->get('game_id'))->toArray();
        $arr['device_type']     = $game['device_type'];
        $arr['device_type_num'] = $game['device_type_num'];

        try {
            $game_override = new GameOverride($arr);
            if (!$game_override->save()) {
                $app['flash']->add('danger', "Error: {$game_override->getFirstError()[0]}");
            }
            BoAuditLog::instance()
                ->setTarget($game_override->getTable(), $game_override->id)
                ->setContext('micro_games', $game_override->game_id)
                ->registerCreate($game_override->getAttributes());

        } catch (\Exception $e) {
            if (!empty($request->get('only_json'))) {
                return $app->json(["error" => $e->getMessage()]);
            }

            throw $e;
        }

        return $this->listOverrides($app, $request);
    }

    public function updateOverride(Application $app, Request $request){
        try {
            $game_override = GameOverride::find($request->get('id'));
            $old = $game_override->getAttributes();
            $params = array_map('trim', $request->request->all());
            if (!$game_override->fill($params)->save()) {
                $app['flash']->add('danger', "Error: {$game_override->getFirstError()[0]}");
            }
            BoAuditLog::instance()
                ->setTarget($game_override->getTable(), $game_override->id)
                ->setContext('micro_games', $game_override->game_id)
                ->registerUpdate($old, $game_override->getAttributes());
        } catch (\Exception $e) {
            if (!empty($request->get('only_json'))) {
                return $app->json(["error" => $e->getMessage()]);
            }
            throw $e;
        }

        return $this->listOverrides($app, $request);
    }

    public function deleteOverride(Application $app, Request $request){
        $game_override = GameOverride::find($request->get('id'));
        $game_override->delete();

        BoAuditLog::instance()
            ->setTarget($game_override->getTable(), $game_override->id)
            ->setContext('micro_games', $game_override->game_id)
            ->registerDelete($game_override->getAttributes());

        return $this->listOverrides($app, $request);
    }
}

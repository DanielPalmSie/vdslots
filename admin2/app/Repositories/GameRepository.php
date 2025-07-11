<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 16/03/16
 * Time: 16:48
 */

namespace App\Repositories;

use App\Models\Game;
use App\Models\Operator;

class GameRepository
{
    public static function getGameList()
    {
        return Game::select('game_name', 'game_id', 'active', 'device_type', 'ext_game_name')->where('active', 1)->orderBy('game_name')->get();
    }

    public static function getGameSelectList($search = '')
    {
        return Game::select('id', 'game_name', 'game_id', 'active', 'device_type', 'ext_game_name')
            ->where('active', 1)
            ->where(function ($sub_query) use ($search) {
                $sub_query->where('game_name', 'LIKE', "%$search%")
                    ->orWhere('device_type', 'LIKE', "%$search%");
            })
            ->orderBy('game_name')
            ->paginate();
    }
    public static function getAllGameList()
    {
        return Game::select('game_name', 'game_id', 'active', 'ext_game_name', 'operator', 'network', 'device_type', 'device_type_num')
            ->groupBy('ext_game_name','device_type')
            ->orderBy('game_name')->get();
    }
    /**
     * @return Game
     */
    public function getGameByExtGameName($ext_game_name)
    {
        return Game::where('ext_game_name', '=', $ext_game_name)->first();
    }

    public function getOperatorList()
    {
        return Game::select('network')->distinct()->where('active', 1)->orderBy('network')->pluck('network');
    }

    public function getGameByGameRef($game_ref, $device = null)
    {
        $builder = Game::where('ext_game_name', $game_ref);

        if($device !== null && $device !== '' && $device !== false) {
            $column = is_numeric($device) ? 'device_type_num' : 'device_type';
            $builder->where($column, $device);
        }

        $game = $builder->first();

        return $game;
    }

    public function getMobileGame($game)
    {
        if (empty($game->device_type_num)) {
            return Game::where('id', '=', $game->mobile_id)->first();
        }
        return $game;
    }

    public function getDesktopGame($game)
    {
        if (!empty($game->device_type_num)) {
            return Game::where('mobile_id', '=', $game->id)->first();
        }
        return $game;
    }

    /**
     * Determine if a game has a version for desktop or mobile or both
     *
     * @param string $game_ref
     * @return string
     */
    public function isGameForDesktopOrMobile($game_ref)
    {
        $desktop_compatible = false;
        $mobile_compatible  = false;

        $game        = $this->getGameByGameRef($game_ref, 0);
        if(!empty($game)) {
            // We have a desktop game
            $desktop_compatible = true;
            $mobile_game = $this->getMobileGame($game);
            if(!empty($mobile_game)) {
                $mobile_compatible = true;
            }
        }

        $game        = $this->getGameByGameRef($game_ref, 1);
        if(!empty($game)) {
            // We have a mobile game
            $mobile_compatible = true;
            $desktop_game = $this->getDesktopGame($game);
            if(!empty($desktop_game)) {
                $desktop_compatible = true;
            }
        }

        switch ([$desktop_compatible, $mobile_compatible]) {
            case [true, true]:
                $result = 'both';
                break;

            case [true, false]:
                $result = 'desktop';
                break;

            case [false, true]:
                $result = 'mobile';
                break;

            default:
                $result = 'no game found';
                break;
        }

        return $result;
    }

    public function getGamesByOperator($operator, $network){
        $games = Game::query()
            ->where('operator', $operator)
            ->where('network', $network)
            ->get();
        return $games;

    }

    public static function getOperatorNetworkList()
    {
        return Operator::select('name', 'network')->distinct()->orderBy('name')->get();
    }
}

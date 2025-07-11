<?php

class CasinoEventHandler
{
    private Casino $c;
    private array $game_reg_map;

    /**
     *
     */
    public function __construct()
    {
        $this->c = phive('Casino');
        $this->game_reg_map = [];
    }

    /**
     * Register a user on provider on tournament start (post request to provider UNKNOWN DURATION)
     *
     * @param $t
     * @param $uid
     * @param $new_eid
     * @param $inc_pot_with
     */
    public function onTournamentRegisterUserEvent($t, $uid, $new_eid, $inc_pot_with)
    {
        try {
            $game_ref = $t['game_ref'];
            if (is_null($this->game_reg_map[$game_ref])) {
                $this->game_reg_map[$game_ref] = $this->c->getGpFromGref($game_ref, false);
            }
            $module = $this->game_reg_map[$game_ref];
            if ($module === false) {
                throw new \Exception("Module for game_ref '$game_ref' could not be found.");
            }
            $module->registerUser($uid, $new_eid);
        } catch (\Throwable $e) { // Catch any kind of throwable, including Error and Exception
            phive('Logger')->getLogger('casino')->error("onTournamentRegisterUserEvent", [json_encode($t), $uid, $new_eid, $inc_pot_with, $e->getMessage()]);
        }
    }

}

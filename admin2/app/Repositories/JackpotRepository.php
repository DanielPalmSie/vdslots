<?php

namespace App\Repositories;


use App\Extensions\Database\FManager as DB;
use Silex\Application;;
use App\Models\Jackpot;


class JackpotRepository
{
    /** @var Application $app */
    protected $app;
    
    /**
     * Gets all the action of the wheel log
     */
    public function getWinLog()
    {
        $winLog = DB::shsSelect('jackpot_wheel_log',"SELECT jwl.created_at, 
                                                            jwl.user_id, 
                                                            jwl.user_currency, 
                                                            jwl.win_jp_amount, 
                                                            ta.description 
                                                    FROM    jackpot_wheel_log jwl, 
                                                            trophy_awards ta
                                                    WHERE   jwl.win_jp_amount <> 0
                                                    AND     jwl.win_award_id = ta.id 
                                                    ORDER BY created_at DESC");

        return $winLog;
    }
}


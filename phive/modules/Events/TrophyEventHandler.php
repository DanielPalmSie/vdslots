<?php

class TrophyEventHandler
{
    private Trophy $trophy;
    private $t;

    /**
     *
     */
    public function __construct()
    {
        $this->trophy = phive('Trophy');
        $this->t = phive('Tournament');
    }


    /**
     * Calculate the prizes given on tournament finish
     *
     * @param array $tournament
     */
    public function onTournamentFinishedEvent(array $tournament)
    {
        $t = phive('Tournament');
        $tpl = $t->getParent($tournament);
        if (empty($tpl['prize_calc_wait_minutes'])) {
            $t->calcPrizes($tournament);
            $tournament['calc_prize_stamp'] = phive()->hisNow();
            $tournament['prizes_calculated'] = 1;
        } else {
            $tournament['calc_prize_stamp'] = phive()->hisMod("+{$tpl['prize_calc_wait_minutes']} minute");
        }
        $t->save($tournament);
    }

    /**
     *  Calculates prizes on tournament 1 min cron
     */
    public function onTournamentCalcPrizesEvent() {
        $this->t->calcPrizesCron();
    }

    /**
     * Cronjob to update trophy award progression
     *
     * @param $sh_num
     * @param bool $set_progress
     * @param string $now
     * @param string $hour_ago
     * @param string $day_ago
     */
    public function onTrophyAwardProgressionEvent($sh_num, $set_progress = true, $now = '', $hour_ago = '', $day_ago = '')
    {
        $this->trophy->minuteCron($sh_num, $set_progress, $now, $hour_ago, $day_ago);
    }

    /**
     * Cronjob to expire trophy awards and progress xp
     * @return void
     */
    public function onTrophyXpCronAndExpireAwardsEvent()
    {
        $this->trophy->xpCronAndExpireAwards();
    }
}

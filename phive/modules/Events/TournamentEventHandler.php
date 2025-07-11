<?php

class TournamentEventHandler
{
    private Tournament $t;

    const SNG_REPEAT = 2;

    /**
     *
     */
    public function __construct()
    {
        $this->t = phive('Tournament');
    }

    /**
     * Closes the tournament entries, and we spawn a new sng tournament when the current one is over - only for recur = 2
     *
     * @param array $tournament
     */
    public function onTournamentFinishedEvent(array $tournament)
    {
        $this->t->setEntriesStatus($tournament, 'finished');
        if ($tournament['start_format'] == 'sng') {
            $tpl = $this->t->getParent($tournament);
            if ((int)$tpl['recur'] === self::SNG_REPEAT) {
                $this->t->insertTournament($tpl);
            } else if (!$this->t->hasExpired($tpl) && in_array((int)$tpl['recur'], array(1, 3, 4))){
                $this->t->insertSng($tournament, $tpl);
            }
        }
    }

}
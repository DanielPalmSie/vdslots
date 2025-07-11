<?php

namespace App\Observers;

use App\Models\BoAuditLog;
use App\Models\TrophyAwards;

class TrophyAwardsObserver
{
    /**
     * Handle the TrophyAwards "created" event.
     *
     * @param TrophyAwards $trophy_award
     * @return void
     */
    public function created(TrophyAwards $trophy_award)
    {
        BoAuditLog::instance()
            ->setTarget($trophy_award->getTable(), $trophy_award->id)
            ->registerCreate($trophy_award->getAttributes());
    }

    /**
     * Handle the TrophyAwards "updated" event.
     *
     * @param TrophyAwards $trophy_award
     * @return void
     */
    public function updated(TrophyAwards $trophy_award)
    {
        BoAuditLog::instance()
            ->setTarget($trophy_award->getTable(), $trophy_award->id)
            ->registerUpdate($trophy_award->getOriginal(), $trophy_award->getAttributes());
    }
}
<?php

namespace App\Observers;

use App\Models\BoAuditLog;
use App\Models\Trophy;

class TrophyObserver
{
    /**
     * Handle the Trophy "created" event.
     *
     * @param Trophy $trophy
     * @return void
     */
    public function created(Trophy $trophy)
    {
        BoAuditLog::instance()
            ->setTarget($trophy->getTable(), $trophy->id)
            ->registerCreate($trophy->getAttributes());
    }

    /**
     * Handle the Trophy "updated" event.
     *
     * @param Trophy $trophy
     * @return void
     */
    public function updated(Trophy $trophy)
    {
        BoAuditLog::instance()
            ->setTarget($trophy->getTable(), $trophy->id)
            ->registerUpdate($trophy->getOriginal(), $trophy->getAttributes());
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.23.
 * Time: 11:49
 */

namespace App\Repositories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class UserSessionRepository
{
    public function getLoginsByMonth(User $user, $months = 3)
    {
        $logins = $user->userSessions()
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%b') as month"), DB::raw('count(*) as cnt'))
            ->where('created_at', '>=', Carbon::now('Europe/Malta')->subMonths($months))
            ->groupBy('month')
            ->orderBy('created_at', 'ASC')
            ->pluck('cnt', 'month')->toArray();

        return $this->toFlotGraph($logins);
    }

    public function toFlotGraph($data)
    {
        $ret = "";
        foreach ($data as $key => $value) {
            $ret .= "['$key', $value],";
        }
        return rtrim($ret, ",");
    }

}
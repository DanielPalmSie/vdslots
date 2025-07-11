<?php

namespace App\Repositories;

use App\Classes\DateRange;
use App\Classes\Mts;
use App\Classes\PR;
use App\Helpers\DataFormatHelper;
use App\Helpers\DownloadHelper;
use App\Helpers\PaginationHelper;
use App\Models\MiscCache;
use App\Models\User;
use App\Models\UserDailyBalance;
use App\Models\UserSetting;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class MonitoringRepository
{
    public function getMonitoring(Application $app, Request $request, $params, $query, $user = null)
    {
        $where_date = " AND triggers_log.created_at between :start_date AND :end_date ";
        $trigger_type = $request->get('trigger_type');
        if (!empty($trigger_type)) {
            $where_trigger_type = " AND trigger_name LIKE '$trigger_type%'";
        }
        $aFields = ['country', 'username', 'trigger_name'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        $bindings['rating_type'] = $trigger_type;
        if ($user) {
            $where_username = $query['username'];
            $bindings['username'] = $user->username;
            if (empty($request->get('date-range'))) {
                $bindings['start_date'] = Carbon::now()->subDays(30)->startOfDay()->toDateTimeString();
                $bindings['end_date'] = Carbon::now()->endOfDay()->toDateTimeString();
            }
        }

        $sql = "SELECT
                  users.id as id,
                  users.username,
                  concat(users.firstname, ' ', users.lastname) AS fullname,
                  users.country,
                  triggers_log.created_at,
                  users.register_date,
                  indicator_name,
                  triggers.description AS trigger_description,
                  trigger_name,
                  descr,
                  data,
                  color,
                  concat(IFNULL(formus.value, 0), IFNULL(proofus.value, 0)) as declaration_proof,
                  risk_profile_rating_log.rating_tag  as rg_profile_score
            FROM triggers_log
              LEFT JOIN triggers ON trigger_name = triggers.name
              LEFT JOIN users ON users.id = triggers_log.user_id
              LEFT JOIN risk_profile_rating_log ON triggers_log.user_id = risk_profile_rating_log.user_id
              AND risk_profile_rating_log.id = (SELECT MAX(id) FROM risk_profile_rating_log
                WHERE user_id = triggers_log.user_id AND created_at <= triggers_log.created_at AND rating_type = :rating_type)
              LEFT JOIN users_settings formus ON formus.user_id = users.id AND formus.setting = 'source_of_funds_activated'
              LEFT JOIN users_settings proofus ON proofus.user_id = users.id AND proofus.setting = 'proof_of_wealth_activated'
            WHERE 1
            $where_date
            $where_country
            $where_username
            $where_trigger_name
            $where_trigger_type
            ORDER BY created_at DESC";

        if ($user) {
            return ReplicaDB::shSelect($user->id, "triggers_log", $sql, $bindings);
        }
        return ReplicaDB::shsSelect("triggers_log", $sql, $bindings);
    }


}

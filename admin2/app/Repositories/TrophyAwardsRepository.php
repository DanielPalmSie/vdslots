<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 27/09/16
 * Time: 09:48
 */

namespace App\Repositories;

use App\Models\TrophyAwards;
use App\Models\BonusType;
use Silex\Application;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\HttpFoundation\Request;

class TrophyAwardsRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * TrophyAwardsRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getTrophyAwardsSearchColumnsList()
    {
        $columns = [];

        $select = [
            'id'             => 'Id',
            'type'           => 'Type',
            'multiplicator'  => 'Multiplicator',
            'amount'         => 'Amount',
            'bonus_id'       => 'Bonus ID',
            'valid_days'     => 'Valid Days',
            'own_valid_days' => 'Own Valid Days',
            'created_at'     => 'Created At',
            'description'    => 'Description',
            'alias'          => 'Alias',
            'action'         => 'Action',
            'bonus_code'     => 'Bonus Code',
            'mobile_show'    => 'Mobile Show',
            'jackpots_id'    => 'Jackpot Id',
            'excluded_countries' => 'Excluded Countries',
        ];

        $columns['list']               = array_merge($select);
        $columns['select']             = array_merge($select);
        $columns['default_visibility'] = ['id', 'type', 'multiplicator', 'amount', 'bonus_id', 'description', 'alias', 'action'];

        return $columns;
    }

    /**
     * @param Request $request
     * @param bool $archived
     * @param null $trophies_list
     * @return Builder
     */
    public function getTrophyAwardsSearchQuery(Request $request, $archived = false, $trophies_list = null)
    {
        if ($archived) {
            $query = DB::connection('videoslots_archived')->table('trophy_awards AS ta');
        } else {
            $query = DB::table('trophy_awards AS ta');
        }

        if (!empty($trophies_list) && count($trophies_list) > 0) {
            return $query->whereIn('ta.id', $trophies_list);
        }

        $form_elem    = [];

        if (!empty($request->get('form'))) {
            foreach ($request->get('form') as $key => $val) {
                $form_elem[key($val)][key(array_values($val)[0])] = array_values(array_values($val)[0])[0];
            }
        } else {
            $form_elem = [
                'alias' => $request->get('alias'),
            ];
        }

        $grouped  = false;

        foreach ($form_elem['alias'] as $key => $val) {
            if (!empty($val)) {
                if ($key == 'id') {
                    if (strpos($val, ',')) {
                        $query->whereIn('ta.id', explode(',', $val));
                    } else {
                        $query->where('ta.id', $val);
                    }
                } elseif (in_array($key, ['description', 'mobile', 'alias'])) {
                    $query->where("ta.$key", 'LIKE', '%' . $val . '%');
                } else {
                    $query->whereRaw("ta.$key = '$val'");
                }
            }
        }

        foreach ($form_elem['since'] as $key => $val) {
            if (!empty($val)) {
                $query->where("ta.$key", '>=', $val);
            }
        }

        foreach ($form_elem['before'] as $key => $val) {
            if (!empty($val)) {
                $query->where("ta.$key", '<', $val);
            }
        }

        $columns = $this->getTrophyAwardsSearchColumnsList();
        $str = implode(", ", array_keys($columns['select']));

        if ($archived) {
            //$query->selectRaw("{$str}ta.alias AS backend, ta.id AS playcheck, 'Yes' AS archived");
            $query->selectRaw("{$str}");
        } else {
            //$query->selectRaw("{$str}ta.alias AS backend, ta.id AS playcheck, '' AS archived");
            $query->selectRaw("{$str}");
        }

        if ($grouped) {
            $query->groupBy('ta.id');
        }

        return $query;
    }

    /**
     * @return BonusType
     */
    public function getBonusById($id)
    {
        return BonusType::where('id', $id)->first();
    }

    public function setTrophyAwardImage(&$trophyaward, $user)
    {
        $trophyaward['img'] = phive('Trophy')->getAwardUri($trophyaward, $user);
    }

    public static function getTrophyAwardDescription($trophy_award_id)
    {
        return rep(phive('Trophy')->getAward($trophy_award_id)['description']);
    }

    public static function getTrophyAwardImage($trophyaward, $user)
    {
        $ta = TrophyAwards::find($trophyaward);
        if ($ta === null) {
            return "";
        }
        return phive('Trophy')->getAwardUri($ta, $user);
    }

    /**
     * @param $trophy_id
     * @param $legacy_user
     * @return string
     */
    public static function getDescriptionWithImage($trophy_id, $legacy_user) {
        $processed_description = self::getTrophyAwardDescription($trophy_id);
        if (empty($processed_description)) {
            $trophy = TrophyAwards::find($trophy_id);
            $processed_description = $trophy->description;
        }

        $imgHref = \App\Repositories\TrophyAwardsRepository::getTrophyAwardImage($trophy_id, $legacy_user);
        if (!empty($imgHref)) {
            $processed_description = '<img class="award-image" src="' . $imgHref . '" > ' . $processed_description;
        }

        return $processed_description;
    }


}

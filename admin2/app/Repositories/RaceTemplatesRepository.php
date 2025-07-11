<?php

namespace App\Repositories;

use App\Models\RaceTemplate;
use Silex\Application;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\HttpFoundation\Request;

class RaceTemplatesRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * RaceTemplatesRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getRaceTemplateSearchColumnsList()
    {
        $columns = [];

        $select = [
            'id'                 => 'Id',
            'race_type'          => 'Race Type',
            'display_as'         => 'Display As',
            'levels'             => 'Levels',
            'prizes'             => 'Prizes',
            'prize_type'         => 'Prize Type',
            'game_categories'    => 'Game Categories',
            'games'              => 'Games',
            'recur_type'         => 'Recurrance Type',
            'start_time'         => 'Start Time',
            'start_date'         => 'Start Date',
            'recurring_days'     => 'Recurring Days',
            'recurring_end_date' => 'Recurring End Date',
            'duration_minutes'   => 'Duration Minutes'
        ];

        $columns['list']               = array_merge($select);
        $columns['select']             = array_merge($select);
        $columns['default_visibility'] = ['id', 'levels', 'prize_type', 'start_time', 'start_date', 'recurring_days', 'recurring_end_date'];

        return $columns;
    }

    /**
     * @param Request $request
     * @param bool $archived
     * @param null $trophies_list
     * @return Builder
     */
    public function getRaceTemplateSearchQuery(Request $request, $archived = false, $trophies_list = null)
    {
        // TODO: Rename 'ta'
        if ($archived) {
            $query = DB::connection('videoslots_archived')->table('race_templates AS ta');
        } else {
            $query = DB::table('race_templates AS ta');
        }

        if (!empty($trophies_list) && count($trophies_list) > 0) {
            return $query->whereIn('ta.id', $trophies_list);
        }

        // TODO: This alias is not available in race_templates. Replace?
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

        $columns = $this->getRaceTemplateSearchColumnsList();
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
}

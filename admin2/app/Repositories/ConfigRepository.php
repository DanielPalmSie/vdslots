<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 27/09/16
 * Time: 09:48
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Models\Config;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Silex\Application;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\HttpFoundation\Request;

class ConfigRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * ConfigRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getConfigSearchColumnsList()
    {
        $columns = [];

        $select = [
            'id'           => 'Id',
            'config_name'  => 'Config Name',
            'config_tag'   => 'Config Tag',
            'config_value' => 'Config Value',
            'config_type'  => 'Config Type',
        ];

        $columns['list']               = array_merge($select);
        $columns['select']             = array_merge($select);
        $columns['default_visibility'] = ['id', 'config_name', 'config_tag', 'config_value'];

        return $columns;
    }

    /**
     * @param Request $request
     * @param bool $archived
     * @param null $config_list
     * @return Builder
     */
    public function getConfigSearchQuery(Request $request, $archived = false, $config_list = null)
    {
        if ($archived) {
            $query = DB::connection('videoslots_archived')->table('config AS t');
        } else {
            $query = DB::table('config AS t');
        }

        if (!empty($config_list) && count($config_list) > 0) {
            return $query->whereIn('t.id', $config_list);
        }

        $form_elem    = [];
        $extra_select = [];

        if (!empty($request->get('form'))) {
            foreach ($request->get('form') as $key => $val) {
                $form_elem[key($val)][key(array_values($val)[0])] = array_values(array_values($val)[0])[0];
            }
        } else {
            $form_elem = [
                'config_name' => $request->get('config_name'),
            ];
        }

        $uds_join = false;
        $us_join  = false;
        $grouped  = false;

        foreach ($form_elem['config_name'] as $key => $val) {
            if (!empty($val)) {
                if ($key == 'id') {
                    if (strpos($val, ',')) {
                        $query->whereIn('t.id', explode(',', $val));
                    } else {
                        $query->where('t.id', $val);
                    }
                } elseif (in_array($key, ['config_name', 'config_tag'])) {
                    $query->where("t.$key", 'LIKE', '%' . $val . '%');
                } else {
                    $query->whereRaw("t.$key = '$val'");
                }
            }
        }

        foreach ($form_elem['since'] as $key => $val) {
            if (!empty($val)) {
                $query->where("t.$key", '>=', $val);
            }
        }

        foreach ($form_elem['before'] as $key => $val) {
            if (!empty($val)) {
                $query->where("t.$key", '<', $val);
            }
        }

        $columns = $this->getConfigSearchColumnsList();
        $str = implode(", ", array_keys($columns['select']));

        if ($archived) {
            $query->selectRaw("{$str}");
        } else {
            $query->selectRaw("{$str}");
        }

        if ($grouped) {
            $query->groupBy('t.id');
        }

        return $query;
    }
}

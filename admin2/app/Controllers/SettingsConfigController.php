<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Events\ConfigUpdated;
use App\Models\BoAuditLog;
use App\Repositories\ConfigRepository;
use App\Models\Config;
use App\Models\BankCountry;
use Illuminate\View\View;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;

class SettingsConfigController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\SettingsConfigController::index')
            ->bind('settings.config.index')
            ->before(function () use ($app) {
                if (!p('config.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/new/', 'App\Controllers\SettingsConfigController::newConfig')
            ->bind('settings.config.new')
            ->before(function () use ($app) {
                if (!p('config.new')) {
                    $app->abort(403);
                }
            });

        $factory->match('/edit/{config}/', 'App\Controllers\SettingsConfigController::editConfig')
            ->convert('config', $app['configProvider'])
            ->bind('settings.config.edit')
            ->before(function () use ($app) {
                if (!p('config.edit')) {
                    $app->abort(403);
                }
            });

        $factory->match('/delete/{config}/', 'App\Controllers\SettingsConfigController::deleteConfig')
            ->convert('config', $app['configProvider'])
            ->bind('settings.config.delete')
            ->before(function () use ($app) {
                if (!p('config.delete')) {
                    $app->abort(403);
                }
            });

        $factory->match('/search/', 'App\Controllers\SettingsConfigController::searchConfig')
            ->bind('config.search')
            ->before(function () use ($app) {
                if (!p('config.section')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * @param Application $app
     * @return View
     */
    public function index(Application $app, Request $request, $users_list = null)
    {
        $repo    = new ConfigRepository($app);
        $columns = $repo->getConfigSearchColumnsList();

        if (!isset($_COOKIE['config-search-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('config-search-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['config-search-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['config-search-no-visible'], true);
        }

        $res = $this->getConfigList($request, $app, [
            'ajax'         => false,
            'length'       => 25,
            'sendtobrowse' => $request->get('sendtobrowse', 0),
            'users_list'   => $users_list
        ]);

        $pagination = [
            'data'           => $res['data'],
            'defer_option'   => $res['recordsTotal'],
            'initial_length' => 25
        ];

        $breadcrumb = 'List and Search';

        return $app['blade']->view()->make('admin.settings.config.index', compact('app', 'columns', 'pagination', 'breadcrumb'))->render();
    }

    private function getAllDistinct()
    {
        $config                      = new Config();
        //$all_distinct['config_name'] = array_merge([""], $config->getDistinct('config_name'));
        //$all_distinct['config_tag']  = array_merge([""], $config->getDistinct('config_tag'));
        $all_distinct['config_type']  = array_merge([""], $config->getDistinct('config_type'));

        return $all_distinct;
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return View
     */
    public function newConfig(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $new_config = null;

            $data = $request->request->all();

            $t = new Config($data);
            if (!$t->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
            }

            $this->configValuesToSingleLine($data['config_type'], $data);

            DB::shBeginTransaction(true);
            try {

                $new_config = Config::create($data);
                $this->storeChanges('add', $new_config);

            } catch (\Exception $e) {
                DB::shRollback(true);
                return $app->json(['success' => false, 'error' => $e->getMessage()]);
            }
            DB::shCommit(true);

            return $app->json(['success' => true, 'config' => $new_config]);
        }

        $all_distinct = $this->getAllDistinct();

        $buttons['save'] = "Create New Config";

        $breadcrumb = 'New';

        $config_type_json['delimiter'] = "::";
        $config_type_json['next_data_delimiter'] = "\n";

        return $app['blade']->view()->make('admin.settings.config.new', compact('app', 'buttons', 'config_type_json', 'all_distinct', 'breadcrumb'))->render();
    }

    private function analyzeValue($value) {
        $info['type'] = 'text';

        $value = trim($value);

        if (preg_match('/^\d+$/', $value, $matches)) {
            $info['type'] = 'number';
        } else if (preg_match('/^\d+\.(\d+)$/', $value, $matches)) {
            $info['type'] = 'number';
            $info['extras'] = ['step' => '0.'.str_repeat("0", strlen($matches[1])-1).'1'];
        } else if (preg_match('/^(?:[A-Z]{2}\s{1})+[A-Z]{2}\s?$/', $value, $matches)) {
            $info['type'] = 'iso2uppercase';
        } else if (preg_match('/^(?:[a-z]{2}\s{1})+[a-z]{2}\s?$/', $value, $matches)) {
            $info['type'] = 'iso2lowercase';
        }

        return $info;
    }

    private function getConfigTypeData($config_type_json, $config_type_value) {

        $result = [];
        $matches = null;
        preg_match_all("/<\:([\w,]+)>/" , $config_type_json['format'], $matches);
        $config_type_json['next_data_delimiter'] = isset($config_type_json['next_data_delimiter']) ? $config_type_json['next_data_delimiter'] : "\n";

        if (empty($config_type_value)) {
            $config_type_value = implode($config_type_json['delimiter'], $matches[1]);
        }

        foreach (explode($config_type_json['next_data_delimiter'], $config_type_value) as $row) {
            if (count($matches[1]) > 1) {
                /*
                $d = array_combine($matches[1], explode($config_type_json['delimiter'], $row));
                foreach ($d as $key => $value) {
                    list($name, $type) = explode(",", $key);
                    if (!$type) {
                        $type = "string";
                    }
                    $d[$key] = ['name' => $name, 'type' => 'string', 'value' => $value];
                }
                $result[] = $d;
                */
                if (strlen($config_type_json['delimiter']) > 0) {
                    $result[] = array_combine($matches[1], explode($config_type_json['delimiter'], $row));
                } else {
                    $result[] = $row;
                }
            } else {
                if (strlen($config_type_json['delimiter']) > 0) {
                    $result[][$matches[1][0]] = explode($config_type_json['delimiter'], $row);
                } else {
                    $result[][$matches[1][0]] = $row;
                }
            }
        }

        // TODO: Remove this below and insert in foreach above in a suitable way.
        foreach ($result as $i => $r) {
            foreach ($r as $key => $value) {
                $value = trim($value);
                list($name, $type) = explode(",", $key);
                unset($result[$i][$key]);

                $info = $this->analyzeValue($value);
                $result[$i][$name] = ['type' => empty($type) ? $info['type'] : $type, 'value' => $value, 'extras' => $info['extras']];
            }
        }

        return $result;
    }

    private function configValuesToSingleLine($config_type, &$data) {

        $config_type_json = json_decode($config_type, true); // Note: JSON check is done during validation. No need to do it again.
        if (in_array($data['config_value_type'], ['template', 'ISO2-template'])) {
            $data['config_value'] = "";
            preg_match_all("/<\:([\w]+)>/", $config_type_json['format'], $matches);
            if (count($matches) > 1) {
                $first_column = $matches[1][0];
                foreach (array_keys($data[$first_column]) as $index) {
                    if (strlen($data[$first_column][$index]) > 0) {
                        $temp_array = [];
                        foreach ($matches[1] as $attribute) {
                            array_push($temp_array, $data[$attribute][$index]);
                        }

                        if ($data['config_value_type'] == 'ISO2-template') {
                            $temp_array[1] = implode(" ", $temp_array[1]);
                        }
                        $data['config_value'] .= join($config_type_json['delimiter'],
                                $temp_array) . $config_type_json['next_data_delimiter'];
                    }
                }
            }
        } else if ($data['config_value_type'] == 'ISO2' || $data['config_value_type'] == 'iso2') {
            $data['config_value'] = join($config_type_json['delimiter'], $data['config_value']);
        }

        $data['config_value'] = trim($data['config_value'], $config_type_json['next_data_delimiter']);
        $data['config_value'] = trim($data['config_value']);

        return $data['config_value'];
    }

    /**
     * @param $data
     */
    private function checkForBrandId(&$data)
    {
        if (!in_array('brand_id', $data['Name'])) {
            $data['Name'][] = 'brand_id';
            $data['Value'][] = phive('Distributed')->getLocalBrandId();
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param Config $config
     * @return JSON Response
     */
    public function editConfig(Application $app, Request $request, Config $config)
    {
        if (!$config) {
            return $app->json(['success' => false, 'Config not found.']);
        }

        // Able to edit permissions on tag level.
        if (!p('config.edit.tag.'.$config['config_tag'])) {
            return $app->json(['success' => false, 'Permission denied.']);
        }

        if ($request->getMethod() == 'POST') {

            $data = $request->request->all();
            $this->checkForBrandId($data);

            if (isset($config['config_type']) && strlen($config['config_type']) > 0) {
                $data['config_type'] = $config['config_type'];
            }

            $t = new Config($data);
            if (!$t->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
            }

            $this->configValuesToSingleLine($config['config_type'], $data);

            DB::shBeginTransaction(true);
            try {

                $this->storeChanges('edit', $config, $t);
                $config->update($data);


            } catch (\Exception $e) {
                DB::shRollback(true);
                return $app->json(['success' => false, 'error' => $e->getMessage()]);
            }
            DB::shCommit(true);
            $app['dispatcher']->dispatch(ConfigUpdated::NAME, new ConfigUpdated($config));
            return $app->json(['success' => true]);
        }

        $buttons['save']        = "Save";
        $buttons['save-as-new'] = "Save As New...";
        $buttons['save-all']    = "Save All";
        //$buttons['delete']      = "Delete";

        $breadcrumb = 'Edit';

        $info = $this->analyzeValue($config['config_value']);
        $config_type_json = json_decode($config['config_type'], true);
        $config_type_json = array_merge($info, empty($config_type_json) ? [] : $config_type_json);
        $countries = [];

        if ($config_type_json['type'] == 'ISO2') {
            $countries = [];
            $bank_country = BankCountry::all();
            $delimiter = $config_type_json['delimiter'];
            $countries_db = explode($delimiter, $config['config_value']);
            foreach ($bank_country as $b) {
                $countries['all'][] = $b;
                if (in_array($b['iso'], $countries_db)) {
                    $countries['selected'][$b['iso']] = true;
                }
            }
        } else if ($config_type_json['type'] == 'iso2') {
            $countries = [];
            $bank_country = BankCountry::all();
            $delimiter = $config_type_json['delimiter'];
            $countries_db = explode($delimiter, $config['config_value']);
            foreach ($bank_country as $b) {
                $b['iso'] = strtolower($b['iso']);
                $countries['all'][] = $b;
                if (in_array($b['iso'], $countries_db)) {
                    $countries['selected'][$b['iso']] = true;
                }
            }
        }
        $config_type_json_data = $this->getConfigTypeData($config_type_json, $config['config_value']);

        if ($config_type_json['type'] == 'ISO2-template') {
            $bank_country = BankCountry::all();
            foreach($config_type_json_data as $key => $option) {
                $countries_db = explode(" ", $option['ISO2']['value']);
                foreach ($bank_country as $b) {
                    $config_type_json_data[$key]['ISO2']['all'][] = $b;
                    if (in_array($b['iso'], $countries_db)) {
                        $config_type_json_data[$key]['ISO2']['selected'][$b['iso']] = true;
                    }
                }
            }
        }

        return $app['blade']->view()->make('admin.settings.config.edit', compact('app', 'buttons', 'config', 'config_type_json', 'config_type_json_data', 'countries', 'breadcrumb'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param Config $config
     * @return JSON Response
     */
    public function deleteConfig(Application $app, Request $request, Config $config)
    {
        // TODO: Make sure user permision check works if you ever enable this.
        return $app->json(['success' => false, 'error' => 'Delete is disabled.']);

        /* // Disabled delete for now.
        DB::shBeginTransaction(true);
        try {
            $result = $config->delete();
            if (!$result) {
                DB::shRollback(true);
                return $app->json(['success' => false]);
            }

            $repo_strings      = new LocalizedStringsRepository($app);
            $localized_strings = $repo_strings->getAllByAlias('configname.'.$config->config_name);
            foreach ($localized_strings as $s) {
                $ok = $s->delete();
                if (!$ok) {
                    DB::shRollback(true);
                    return $app->json(['success' => false]);
                }
            }
        } catch (\Exception $e) {
            DB::shRollback(true);
            return $app->json(['success' => false, 'error' => $e]);
        }

        DB::shCommit(true);
        return $app->json(['success' => true]);
        */
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param array $attributes
     * @return array
     */
    private function getConfigList($request, $app, $attributes)
    {
        $repo           = new ConfigRepository($app);
        $search_query   = null;
        $archived_count = 0;
        $total_records  = 0;
        $length         = 25;
        $order_column   = "config_name";
        $start          = 0;
        $order_dir      = "ASC";

        if ($attributes['sendtobrowse'] != 1) {
            $search_query = $repo->getConfigSearchQuery($request);
        } else {
            $search_query = $repo->getConfigSearchQuery($request, false, $attributes['users_list']);
        }

        // Search column-wise too.
        foreach($request->get('columns') as $value) {
            if (strlen($value['search']['value']) > 0) {
                $words = explode(" ", $value['search']['value']);
                foreach($words as $word) {
                    $search_query->where($value['data'], 'LIKE', "%".$word."%");
                }
            }
        }

        $search = $request->get('search')['value'];
        if (strlen($search) > 0) {
            $s = explode(' ', $search);
            foreach ($s as $q) {
                $search_query->where('config_name', 'LIKE', "%$q%");
                $search_query->orWhere('config_tag', 'LIKE', "%$q%");
            }
        }

        $non_archived_count = DB::table(DB::raw("({$search_query->toSql()}) as a"))
            ->mergeBindings($search_query)
            ->count();

        if ($attributes['sendtobrowse'] != 1 && $app['vs.config']['archive.db.support'] && $repo->not_use_archived == false) {
            $archived_search_query = $repo->getConfigSearchQuery($request, true);
            try {
                $archived_count = DB::connection('videoslots_archived')->table(DB::raw("({$archived_search_query->toSql()}) as b"))
                    ->mergeBindings($search_query)
                    ->count();
            } catch (\Exception $e) {
            }
            $total_records = $non_archived_count + $archived_count;
        } else {
            $total_records = $non_archived_count;
        }

        if ($attributes['ajax'] == true) {
            $start        = $request->get('start');
            $length       = $request->get('length');
            $order        = $request->get('order')[0];
            $order_column = $request->get('columns')[$order['column']]['data'];
            $order_dir    = $order['dir'];
        } else {
            $length = $total_records < $attributes['length'] ? $total_records : $attributes['length'] ;
        }

        if ($attributes['sendtobrowse'] !== 1 && $app['vs.config']['archive.db.support'] && $archived_count > 0) {
            $non_archived_records     = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
            $non_archived_slice_count = count($non_archived_records);
            if ($non_archived_slice_count < $length) {
                $next_length = $length - $non_archived_slice_count;
                $next_start  = $start - $non_archived_count;
                if ($next_start < 0) {
                    $next_start = 0;
                }
                $archived_records = $archived_search_query->orderBy($order_column, $order_dir)->limit($next_length)->skip($next_start)->get();
                if ($non_archived_slice_count > 0) {
                    $data = array_merge($non_archived_records, $archived_records);
                } else {
                    $data = $archived_records;
                }
            } else {
                $data = $non_archived_records;
            }
        } else {
            $data = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
        }

        return [
            "draw"            => intval($request->get('draw')),
            "recordsTotal"    => intval($total_records),
            "recordsFiltered" => intval($total_records),
            "data"            => $data
        ];
    }


    /**
     * @param Application $app
     * @param Request $request
     * @return JSON Response
     */
    public function searchConfig(Application $app, Request $request)
    {
        return $app->json($this->getConfigList($request, $app, ['ajax' => true]));
    }

    private function storeChanges($method, $values, $request = '') {
        if (isset($request['config_value']) && is_array($request['config_value'])) {
            $config_value_string = implode(" ", $request['config_value']);
            $request['config_value'] = $config_value_string;
        }
        $this->logConfigChanges($method, $values, $request);
        $this->sendConfigEmail($method, $values, $request);
    }

    private function logConfigChanges($method, $values, $request) {
        if ($method === 'edit') {
            BoAuditLog::instance()
                ->setTarget('config', $values->id)
                ->setContext('config', $values->id)
                ->registerUpdate($values->getAttributes(), $request->getAttributes());
        } else {
            BoAuditLog::instance()
                ->setTarget('config', $values->id)
                ->setContext('config', $values->id)
                ->registerCreate($values->getAttributes());
        }
    }

    private function sendConfigEmail($method, $values, $request) {
        $email = phive('MailHandler2')->getSetting('CONFIG_MAIL');
        $replacers = $this->getMailReplacers($method, $values, $request);
        $mailTrigger = $this->getMailTrigger($method);

        phive('MailHandler2')->sendMailToEmail($mailTrigger, $email, $replacers);
    }

    private function getMailReplacers($method, $values, $request): array
    {
        $replacers = ['_TIMESTAMP_' => phive()->hisNow()];

        if ($method === 'edit') {
            return array_merge($replacers,
                [
                '__OLD-CONFIG-NAME__' => $values['config_name'],
                '__OLD-CONFIG-TAG__' => $values['config_tag'],
                '__OLD-CONFIG-VALUE__' => $values['config_value'],
                '__NEW-CONFIG-NAME__' => $request['config_name'],
                '__NEW-CONFIG-TAG__' => $request['config_tag'],
                '__NEW-CONFIG-VALUE__' => $request['config_value'],
                '__MADE-BY__' => cu()->getUsername(),
                 ]);
        } else {
            return array_merge($replacers,
                [
                '__NEW-CONFIG-NAME__' => $values['config_name'],
                '__NEW-CONFIG-TYPE__' => $values['config_type'],
                '__NEW-CONFIG-TAG__' => $values['config_tag'],
                '__MADE-BY__' => cu()->getUsername(),
                ]);
        }
    }

    private function getMailTrigger($method): string
    {
        return $method === 'edit' ? 'config.change' : 'config.add';
    }
}

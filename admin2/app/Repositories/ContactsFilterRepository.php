<?php
/**
 * Created by PhpStorm.
 * User: joel
 * Date: 20/09/17
 * Time: 16:46
 */

namespace App\Repositories;

use App\Classes\Filters;
use App\Helpers\DataFormatHelper;
use App\Models\NamedSearch;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use App\Classes\Filter\FilterClass;
use App\Classes\Filter\FilterData;
use App\Extensions\Database\FManager as DB;

class ContactsFilterRepository
{

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    |
    */
        /**
         * @return array
         */
        public static function getFilterFields() {
            return FilterData::getFilterFields();
        }

        /**
         * @param $field_id
         * @return mixed
         */
        public function getFieldData($field_id)
        {
            $field = new FilterData($field_id);

            if ($field->needsOptions()) {
                $field->setOptions(DataFormatHelper::{$field->get('options_source')}());

                $format_options = self::getFormatField($field_id);

                if ($format_options) {
                    $field->setOptions(
                        call_user_func_array(
                            [$this, 'mapFields'],
                            [$field->get('data'), $format_options[0], $format_options[1]]
                        )
                    );
                }
            }

            return $field->get();
        }

        /**
         * @param FilterClass $contacts
         * @param array $data
         *
         * @return array
         */
        public function beautifyResult($contacts, $data)
        {
            $alter_fields = collect($contacts->selected_fields)
                ->map(function($el) use ($contacts) {
                    return [
                        'key' => $el,
                        'options' => $contacts->fields_map[$el]['options_source']
                    ];
                })
                ->filter(function($el) {
                    return $el['options'] !== null;
                })
                ->map(function($el) {
                    $el['options'] = DataFormatHelper::{$el['options']}();

                    $format_options = self::getFormatField($el['key']);

                    if ($format_options) {

                        $el['options'] = call_user_func_array(
                            [$this, 'mapFields'],
                            [$el['options'], $format_options[0], $format_options[1]]
                        );
                    }

                    return $el;
                });

            $data = array_map(function($el) use ($alter_fields){
                foreach ($alter_fields as $field) {
                    $el->{$field['key']} = $field['options'][$el->{$field['key']}] ?? $el->{$field['key']};
                }
                return $el;
            },$data);

            return $data;
        }

        /**
         * @param Request $request
         *
         * @return array|mixed
         * @throws \Exception
         */
        public function getFilteredContacts(Request $request)
        {
            $query_data = null;
            $named_search = null;
            $saved_filter_id = $request->get('filter-id');
            $selected_fields = [
                'id',
                'name',
                'email',
                'mobile',
                'country',
                'language',
                'username',
                'currency',
                'register_date',
                'is_bonus_fraud_flagged',
            ];

            if ($saved_filter_id) {
                $named_search = NamedSearch::find($saved_filter_id);
                $query_data = (array) json_decode($named_search->form_params, true);
            }

            if (!$request->get('order')) {
                $filter_data = new FilterData();
                return [
                    'named_search' => $named_search,
                    'selected_fields' => array_reduce($selected_fields, function($carry, $el) use ($filter_data) {
                        $carry[$el] = $filter_data->solveTitleKey($el);
                        return $carry;
                    }, [])
                ];
            }

            $contacts = (new FilterClass(
                'users',
                $request,
                new FilterData(),
                $query_data,
                $selected_fields,
                true,
                $named_search->getAttribute('language')
            ))->setup();

            $page = $contacts->getResults();

            $page['data'] = $this->beautifyResult($contacts, $page['data']);
            $page['data'] = $this->censorSensibleFields($page['data']);

            return $page;
        }

        /**
         * To be used with rest api.
         *
         * @param Request $request
         *
         * @return array
         */
        public function getContactByFilter(Request $request)
        {
            $contacts = (new FilterClass(
                'users',
                $request,
                new FilterData(),
                null,
                null,
                true,
                $request->get('language')
            ))->setup();

            $page = $contacts->getResults();

            $page['data'] = $this->beautifyResult($contacts, $page['data']);
            $page['data'] = $this->censorSensibleFields($page['data']);

            return $page;
        }

        /**
         * @param $key
         * @return mixed
         */
        private function getFormatField($key)
        {
            return [
                'currency'  => ['code', 'code'],
                'country'   => ['iso', 'printable_name'],
                'deposit_method' => ['dep_type', 'dep_type']
            ][$key];
        }

        /**
         * @param $contacts
         * @return array
         */
        public function censorSensibleFields($contacts, $hide = false)
        {
            return array_map(function ($contact) use ($hide) {
                $contact->mobile = (!p('users.search.mobile') or $hide) ? '**************' : $contact->mobile;
                $contact->email  = (!p('users.search.email')  or $hide) ? '**************' : $contact->email;
                return $contact;
            }, $contacts);
        }

        /**
         * This will map array of objects to object with key_attr => value_attr
         * @param array|\Doctrine\Common\Collections\Collection $arr
         * @param string $key_attr
         * @param string $value_attr
         * @return array
         */
        private function mapFields($arr, $key_attr, $value_attr)
        {
            return array_reduce($arr[0] instanceof  Model ? $arr->toArray() : $arr,
                function ($carry, $item) use ($key_attr, $value_attr)
                {
                    $carry[$item[$key_attr]] = $item[$value_attr];

                    return $carry;
                },
                array()
            );
        }

        /**
         * @param Request $request
         * @param NamedSearch|null $named_search
         * @return array
         */
        public function saveNamedSearch(Request $request, NamedSearch $named_search = null)
        {
            $sql_query = (new FilterClass(
                'users',
                $request,
                new FilterData(),
                null,
                json_decode($request->get('selected_fields')),
                false,
                $request->get('language')
            ))->setup();

            try {
                $count = $sql_query->toQueryBuilder()->count('users.id');
            } catch (Exception $e) {
                return ['success' => false];
            }

            $data = [
                'name'          => empty($request->request->get('name')) ? 'Contacts filter - ' . date("Y-m-d H:i:s", time()) : $request->request->get('name'),
                'form_params'   => json_encode($request->request->get('query_data')),
                'output_fields' => $request->get('selected_fields'),
                'language'      => $request->get('language'),
                'sql_statement' => $sql_query->getSql(),
                'result'        => $count
            ];

            if (empty($named_search)) {
                NamedSearch::create($data);
            } else {
                $named_search->update($data);
            }

            return ['success' => true];
        }
}

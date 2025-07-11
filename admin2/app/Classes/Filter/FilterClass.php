<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 17/10/2017
 * Time: 15:29
 */

namespace App\Classes\Filter;

use \Illuminate\Database\Query\Builder;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;
use App\Helpers\PaginationHelper;

/*
|--------------------------------------------------------------------------
| Filter Class
|--------------------------------------------------------------------------
|
| It will filter any users table using the rules from filter data class
|
*/
class FilterClass
{
    /*
    |--------------------------------------------------------------------------
    | Class keys
    |--------------------------------------------------------------------------
    */
        /**
         * @var Builder $base_query
         */
        private $base_query;
        private $queued_keys_for_join;
        private $query_data;
        private $order_col;
        private $source;
        private $request;
        private $table;
        private $page;
        private $paginated_ids = [];
        private $paginated;

        public  $selected_fields;
        public  $fields_map;

    /**
     * Magic constructor.
     * @param $table
     * @param Request $request
     * @param FilterData $source_class_instance
     * @param $query_data array
     * @param $selected_fields array
     * @param bool $paginated
     * @param string|bool $language
     */
    function __construct($table, Request $request, $source_class_instance, $query_data = null, $selected_fields = null, $paginated = false, $language = false)
    {
        $this->queued_keys_for_join = [];
        $this->table                = $table;

        $this->request              = $request;
        $this->source               = $source_class_instance;
        $this->paginated            = $paginated;
        $this->fields_map           = $source_class_instance->getFieldsMap();

        $this->query_data           = $query_data ?? $request->get('query_data');
        $this->selected_fields      = $selected_fields ?? $request->get('selected_fields');

        $this->order        = $request->get('order')[0] ?? ['dir' => 'desc'];
        $this->order_col    = $request->get('columns')[$this->order['column']]['data'] ?? 'register_date';
        $this->language     = $language;

        return $this->initQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | Private methods
    |--------------------------------------------------------------------------
    */
        /**
         * Initialize base query
         * @param null $table
         * @return Builder
         * @throws
         */
        private function initBaseQuery($table = null)
        {
            if (!$table)
            {
                $table = $this->table;
            }
            $this->base_query = DB::table($table);
            if (!empty($this->language)) {
                $this->base_query->where("{$this->table}.preferred_lang", $this->language);
            }
            return $this->base_query;
        }

        /**
         * Append conditions to base query
         * @return $this
         */
        private function initQuery()
        {
            $this->initBaseQuery();
            $this->appendSubQueryData($this->base_query, (array) $this->query_data);
            return $this;
        }

        /**
         * Append join statements on base query
         * @param bool $paginated
         * @return Builder
         */
        private function appendJoin($paginated = false)
        {
            foreach (collect(array_keys($this->queued_keys_for_join))->unique() as $alias)
            {
                $join_data = $this->fields_map[$alias]['join'];

                if (!$join_data) continue;

                foreach ($this->queued_keys_for_join[$alias] as $key => $join)
                {
                    $this->base_query = $join_data['callback'](
                        $this->base_query,
                        $join['values'],
                        $paginated ? $this->paginated_ids : null,
                        $join['table']
                    );
                }
            }
            return $this->base_query;
        }

        /**
         * Apply pagination and get page
         * @return array
         */
        private function getPage()
        {
            $selected_keys = [ 'id', $this->order_col ];

            $this->base_query->selectRaw($this->generateSelectQuery($selected_keys));

            $this->base_query = $this->appendJoin();

            $paginator = new PaginationHelper($this->toQueryBuilder(), $this->request, [
                'length' => $this->request->get('length') ?? 25,
                'order'  => [
                    'column' => $this->order_col,
                    'order'  => $this->order['dir']
                ]
            ]);

            $page = $paginator->getPage(false);

            $this->initBaseQuery();

            $this->paginated_ids = collect($page['data'])->pluck('id')->filter(function($el)
            {
                return !is_null($el);
            })->toArray();

            $this->base_query = $this->base_query->whereIn("{$this->table}.id", $this->paginated_ids);

            return $page;
        }

        /**
         * @param array $arrays
         * @return bool|string
         */
        private function generateSelectQuery($arrays = [])
        {
            return collect($arrays)->flatten(1)->unique()->map(function($key) {
                $this->queued_keys_for_join[$key] = $this->queued_keys_for_join[$key] ?? [[
                    "table" => $key,
                    "values"=> []
                ]];

                $field = $this->fields_map[$key];

                $select = "CASE ";

                foreach ($this->queued_keys_for_join[$key] as $queued) {
                    $select_statement   = is_callable($field['select'])
                                        ? $field['select']($queued['table'])
                                        : $field['select'];

                    $select .= " WHEN {$select_statement} is not null THEN {$select_statement} ";
                }

                return $select . " END as {$key}";
            })->implode(',');
        }

        /**
         * Append condition or operand.
         * @param $query
         * @param array $params
         * @return Builder|null
         */
        private function appendSubQueryData($query, $params)
        {
            if ($params['expr_type'] == 'condition')
            {
                return $this->appendQuery(new Condition($params['fields']), $query);
            }

            foreach ($params['operands'] as $operand)
            {
                $query->{
                    $this->expressionToMethodName($params['expr_type'])
                }(function($q) use ($operand)
                {
                    $this->appendSubQueryData($q, (array) $operand);
                });
            }
            return $query;
        }

        /**
         * @param Condition $condition
         * @param Builder $query
         * @return Builder
         */
        private function appendQuery($condition, $query)
        {
            if (!$condition->has('value'))
            {
                return $query;
            }

            $table = $condition->get('key');

            $this->queued_keys_for_join[$table] = $this->queued_keys_for_join[$table] ?? [];

            $fake_table = $table . count($this->queued_keys_for_join[$table]);

            $this->queued_keys_for_join[$table][] = [
                "table" => $fake_table,
                "values"=> $condition->get()
            ];

            $params = $this->source->comparatorParams($condition->get(), $fake_table);

            foreach (array_keys($params) as $key)
            {
                if ($params["raw"] === true)
                {
                    $query->whereRaw($params["sql"]);
                    continue;
                }

                call_user_func_array([$query, $key], $params[$key]);
            }

            return $query;
        }

        /**
         * Returns: where|orWhere
         * @param string $query_data_expr_type
         * @return string
         */
        private function expressionToMethodName($query_data_expr_type)
        {
            return $query_data_expr_type === 'or' ? 'orWhere' : 'where';
        }

    /*
    |--------------------------------------------------------------------------
    | Public methods
    |--------------------------------------------------------------------------
    */
        /**
         * Get the query builder
         * @return Builder
         */
        public function toQueryBuilder()
        {
            return $this->base_query;
        }

        /**
         * Get single page
         * @return mixed
         */
        public function getResults()
        {
            return $this->page;
        }

        /**
         * Setup base query with lazy loading when pagination is required.
         * Otherwise setup select and join on base query.
         * @return $this
         */
        public function setup()
        {
            $fields_array = array_merge(['id', $this->order_col], $this->selected_fields);


            $this->page = $this->paginated ? $this->getPage() : null;

            $this->base_query->selectRaw($this->generateSelectQuery($fields_array));

            $this->base_query = $this->appendJoin($this->paginated);

            if($this->paginated)
            {
                $this->page['data'] = $this->base_query
                    ->orderBy($this->order_col, $this->order['dir'])
                    ->get()
                    ->toArray();
            }

            return $this;
        }

        /**
         * Convert query builder to raw sql
         * @param Builder $model
         * @return mixed
         */
        public function getSql()
        {
            $sql        = $this->base_query->toSql();
            $bindings   = $this->base_query->getBindings();
            $needle     = '?';
            foreach ($bindings as $replace)
            {
                $pos = strpos($sql, $needle);
                if ($pos !== false)
                {
                    if (gettype($replace) === "string")
                    {
                        $replace = ' "'.addslashes($replace).'" ';
                    }
                    $sql = substr_replace($sql, $replace, $pos, strlen($needle));
                }
            }
            return $sql;
        }
}
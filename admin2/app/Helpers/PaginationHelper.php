<?php

namespace App\Helpers;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpFoundation\Request;

class PaginationHelper
{

    /** @var  Builder $query */
    protected $query;

    /** @var int $total_records */
    protected $total_records;

    /** @var Request $request */
    protected $request;

    /** @var array $default */
    protected $default;

    //todo default order on get Initial page

    /**
     * PaginationHelper constructor.
     * @param Builder|EloquentBuilder|array|Collection|EloquentCollection $query
     * @param Request $request
     * @param array $default
     * @param int $total
     * @throws \Exception
     */
    public function __construct($query, $request, $default, $total = null)
    {
        $this->request = $request;
        $this->default = $default;
        /**
         * if query object is array create collection that
         * after is processed by PaginationCollectionHelper class
         */
        if (is_array($query)) {
            $query = collect($query);
        }

        if ($query instanceof EloquentBuilder || $query instanceof Builder) {
            $this->query = $query;
            $this->total_records = is_null($total) ? $query->count() : $total;
        } elseif ($query instanceof Collection || $query instanceof EloquentCollection) {
            $this->query = new PaginationCollectionHelper($query);
            $this->total_records = is_null($total) ? $query->count() : $total;
        } else {
            throw new \Exception('Pagination helper error. First parameter needs to be a Builder or a Collection, instance of '. get_class($query). ' given.');
        }
    }

    /**
     * @param Builder $query1
     * @param Builder|Builder[] $query2
     * @param Request $request
     * @param $default
     * @return PaginationHelper
     */
    public static function makeFromUnion($query1, $query2, Request $request, $default)
    {
        if (is_array($query2)) {
            $count = $query1->count();
            foreach ($query2 as $q) {
                $count += $q->count();
                $query1 = $query1->union($q);
            }
            return new self($query1, $request, $default, $count);
        } else {
            $count = $query1->count() + $query2->count();
            return new self($query1->union($query2), $request, $default, $count);
        }
    }

    public function getPage($initial = true)
    {
        if ($initial) {
            $order_column = $this->default['order']['column'];
            $order_dir = $this->default['order']['dir'];
            $start = 0;
            $length = $this->total_records < $this->default['length'] ? $this->total_records : $this->default['length'];
        } else {
            $order = $this->request->get('order')[0];
            $order_column = $this->request->get('columns')[$order['column']]['data'];
            $order_dir = $order['dir'];
            $start = $this->request->get('start');
            $length = $this->request->get('length');
        }
        if (!is_null($order_dir)) {
            if($order_column == "human_readable_desc"){
                $order_column = "timestamp";
            }
            $this->query->orderBy($order_column, $order_dir);
        }

        if ($length != -1) {
            $this->query->limit($length)->skip($start);
        }

        return [
            "draw" => intval($this->request->get('draw')),
            "recordsTotal" => intval($this->total_records),
            "recordsFiltered" => intval($this->total_records),
            "data" => $this->query->get()->all()
        ];
    }

}

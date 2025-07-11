<?php

namespace App\Helpers;

/**
 * Paginate Laravel Collection
 */
class PaginationCollectionHelper
{
    private $collection;

    private $limit;

    private $skip;

    private $sortBy;

    private string $sort = 'sortBy';

    public function __construct($query)
    {
        $this->collection = $query;
    }

    /**
     * @param $sortBy
     * @param string $sortDir
     * @return $this
     */
    public function orderBy($sortBy, string $sortDir = 'asc'): self
    {
        $this->sortBy = $sortBy;
        if (strtolower($sortDir) === 'desc') {
            $this->sort = "sortByDesc";
        }
        return $this;
    }

    /**
     *
     * @param mixed $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     *
     * @param mixed $skip
     * @return $this
     */
    public function skip($skip)
    {
        $this->skip = $skip;
        return $this;
    }

    /**
     * Return array of data
     * @return $this
     */
    public function get()
    {
        return $this;
    }

    public function all()
    {
        $sort = $this->sort;
        return $this->collection->$sort($this->sortBy)->splice($this->skip, $this->limit)->toArray();
    }

}

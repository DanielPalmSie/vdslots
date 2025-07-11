<?php
/**
 * MysqlAsync provides a pool mechanism to do asynchronous queries to Mysql using mysqli API
 *
 * This class is used by Laravel core to loop asynchronously through a number of configured shards
 * but it is made to be used as an standalone class. No use of any function from external packages
 *
 * Example usage:
 * MysqlAsync::executeQuery($nodes_list, $query, $bindings);
 *
 * TODO cache support
 * TODO paginate and sorting over cache support
 *
 * @package  MysqlAsync
 * @author   Ricardo Ruiz
 * @version  $Revision: 2017/02/27 $
 */

namespace App\Extensions\Database\MysqlAsync;

use mysqli;
use PDO;

class MysqlAsync
{
    /**
     * mysql connection resource
     *
     * @var mysqli[]
     */
    protected $links = [];

    protected $query;

    protected $length;

    protected $offset;

    protected $sort = [];

    protected $fetch_mode = PDO::FETCH_OBJ;

    protected $debug = false;

    const RES_STATEMENT = 2;

    const RES_AGGREGATE = 3;

    /**
     * MysqlAsync constructor.
     *
     * It is possible to create the object empty, or initialize its parts.
     *
     * @param array $nodes
     * @param null|string $query
     * @param null|array $bindings
     * @param null|array $params
     * @throws \Exception
     */
    public function __construct($nodes = null, $query = null, $bindings = [], $params = [])
    {
        $this->loadParams($params);

        if (!is_null($nodes)) {
            $this->loadNodes($nodes, $query, $bindings, $params);
        }
    }

    /**
     * Main method to execute a query statically.
     *
     * @param $nodes
     * @param $query
     * @param $bindings
     * @param $mode
     * @param bool|\Closure|string $closure
     * @param array $params
     * @return array
     */
    public static function executeQuery($nodes, $query, $bindings = [], $mode = null, $params = [], $closure = false)
    {
        $new_instance = new self($nodes, $query, $bindings, $params);

        if (!is_null($mode)) {
            $new_instance->setFetchMode($mode);
        }
        return $new_instance->execute($closure);
    }


    /**
     * Nodes can be reloaded (connection established without configuring the query, to be able to reuse the object
     * after initialization.
     *
     * Charset is always utf8 as I don't see the point of parametrize this in the config, so is hardcoded here to utf8
     *
     * @param array $nodes
     * @param null|string $query
     * @param array $bindings
     * @param array $params
     */
    public function loadNodes($nodes, $query = null, $bindings = [], $params = [])
    {
        $this->loadParams($params);

        foreach ($nodes as $index => $node) {
            if (isOnDisabledNode($index)) {
                continue;
            }
            $link = mysqli_connect($node['host'], $node['username'], $node['password'], $node['database']);
            if ($link === false) {
                throw new \RuntimeException(mysqli_connect_error(), mysqli_connect_errno());
            }
            $link->set_charset('utf8');
            if (!is_null($query)) {
                $link->query($this->prepare($link, $query, $bindings), MYSQLI_ASYNC);
            }
            $this->links[] = $link;
        }
    }

    public function loadParams($params)
    {
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                $this->{$k} = $v;
            }
        }
    }

    /**
     * Once nodes are loaded, queries can be run several times using the same links configuration.
     *
     * @param $query
     * @param array $bindings
     * @param array $params
     * @return array
     */
    public function runQuery($query, $bindings = [], $params = [])
    {
        return $this->loadQuery($query, $bindings, $params)->execute();
    }

    public function setFetchMode($mode)
    {
        if (in_array($mode, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ])) {
            $this->fetch_mode = $mode;
        }
        return $this;
    }


    /**
     * Adds a new query to the previously established links
     *
     * TODO need to test if this links reuse can be done safely
     *
     * @param $query
     * @param array $bindings
     * @param array $params
     * @return MysqlAsync
     */
    protected function loadQuery($query, $bindings = [], $params = [])
    {
        $this->loadParams($params);

        foreach ($this->links as $link) {
            $link->query($this->prepare($link, $query, $bindings), MYSQLI_ASYNC);
        }

        return $this;
    }

    /**
     * Prepare the query replacing all the bindings to generate a final raw query.
     *
     * TODO add support for stuff different than string like int (keep another stuff like float, double or whatever for later)
     *
     * @param mysqli $link
     * @param string $query
     * @param array $bindings
     * @return bool|mixed|string
     * @throws \Exception
     */
    protected function prepare($link, $query, $bindings = [])
    {
        if (is_null($query)) {
            throw new AsyncQueryException("Query not supplied", $query);
        }

        if (!empty($bindings)) {
            foreach ($bindings as $key => $binding) {
                $query = preg_replace(
                    is_string($key) ? "/:\b{$key}\b/" : "/\?/",
                    is_int($binding) ? sprintf("%d", $this->sanitize($link->real_escape_string($binding))) :
                        sprintf("'%s'", $this->sanitize($link->real_escape_string($binding))),
                    $query,
                    1
                );
            }
        }
        if (empty($this->query)) {
            $this->query = $query;
        }
        return $query;
    }

    /**
     * Sanitize the binding variable depending on the type
     *
     * @param $var
     * @return mixed
     */
    protected function sanitize($var)
    {
        if (is_int($var)) {
            return filter_var($var, FILTER_SANITIZE_NUMBER_INT);
        } else {
            return filter_var($var, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
    }

    protected function fetch(\mysqli_result $result, $force_assoc = false)
    {
        if ($force_assoc !== false) {
            return $result->fetch_assoc();
        }

        switch ($this->fetch_mode) {
            case PDO::FETCH_OBJ:
                return $result->fetch_object();
                break;
            case PDO::FETCH_ASSOC:
                return $result->fetch_assoc();
                break;
            default:
                throw new AsyncQueryException("Fetch mode not supported", $this->query);
        }
    }

    /**
     * Do the magic with mysqli_poll to execute asynchronously the prepared query.
     * TODO should I close the connection before returning results? I think best approach is to keep it option
     * TODO Processing returning data set needs to be improved with generators for example to reduce the RAM usage
     *
     * @param $closure
     * @return array
     * @throws AsyncQueryException
     */
    protected function execute($closure = false)
    {
        $processed = 0;
        $result = [];
        $affected_rows = 0;
        do {
            $links = $errors = $reject = [];
            foreach ($this->links as $link) {
                $links[] = $errors[] = $reject[] = $link;
            }
            if (!mysqli_poll($links, $errors, $reject, 1)) {
                continue;
            }
            foreach ($links as $link) {
                if ($q_result = $link->reap_async_query()) {
                    if (is_object($q_result)) {
                        $temp = [];
                        while (($row = $this->fetch($q_result)) && $temp[] = $row) ;
                        $result = array_merge($result, $temp);
                        mysqli_free_result($q_result);
                    } else { //INSERT/UPDATE/DELETE/STATEMENT
                        $affected_rows += $link->affected_rows;
                    }
                } else {
                    throw new AsyncQueryException(mysqli_error($link), $this->query);
                }
                $processed++;
            }
        } while ($processed < count($this->links));

        return $this->processResult($result, $affected_rows, $closure);
    }

    /**
     * Process the array so the result is paginated/sorted as configured.
     * //todo implement key support on aggregation...
     * @param bool|\Closure|string $closure
     * @param $result
     * @param $affected_rows
     * @return array|float|int
     * @throws \Exception
     */
    protected function processResult($result, $affected_rows, $closure = false)
    {
        if (!empty($this->groups)) {
            $result = $this->doGroupBy($result, $this->groups);
        }

        if (!empty($this->sort)) {
            foreach ($this->sort as $sort) {
                $result = $this->doSort($result, $sort['column'], $sort['direction']);
            }
        }

        if (!is_null($this->offset)) {
            $result = array_slice($result, $this->offset, $this->length);
        }

        if (!empty($closure)) {
            if (is_int($closure)) {
                if ($closure === self::RES_STATEMENT) {
                    return $affected_rows;
                } elseif ($closure === self::RES_AGGREGATE) {
                    return $this->sum($result);
                } else {
                    throw new AsyncQueryException("Result type not supported", $this->query);
                }
            } elseif ($closure instanceof \Closure) {
                return $closure($result);
            } else {
                throw new AsyncQueryException("Not a closure or int", $this->query);
            }
        } else {
            return $result;
        }
    }

    protected function doGroupBy($items, $groups)
    {
        //todo this is a temporal exception, aggregations on group by must be implemented
        if (strpos(strtolower($this->query), 'sum(') !== false || strpos(strtolower($this->query), 'count(') !== false) {
            throw new \Exception("Aggregations not supported on grouping mysqlasync");
        }

        //todo this is a temporal exception, multiple grouping must be implemented
        if (is_array($groups) && count($groups) > 1) {
            throw new \Exception("Multiple grouping not supported yet.");
        }

        if ($this->fetch_mode == PDO::FETCH_ASSOC) {
            $callback = function ($item, $key) {
                return $item[$key];
            };
        } elseif ($this->fetch_mode == PDO::FETCH_OBJ) {
            $callback = function ($item, $key) {
                return $item->{$key};
            };
        } else {
            throw new \Exception("Group by not supported using this fetch mode.");
        }

        $dictionary = [];
        if ($items) {
            foreach ($items as $item) {
                $dictionary[$callback($item, $groups[0])][] = $item;
            }
        }
        return $dictionary;
    }

    /**
     * It is done using foreach as all the array_reduce/map functions are slower
     * @param array $items Set of items to sum
     * @param null|string $key If you need to aggregate by key, this is the alias in the Select
     * @return array
     */
    protected function sum($items, $key = null)
    {
        $res = [];
        foreach ($items as $k => $v) {
            foreach ($v as $ks => $vs) {
                $res[$ks] += $vs;
            }
        }

        if ($this->fetch_mode == PDO::FETCH_OBJ) {
            $res = [(object)$res];
        }

        return is_null($key) ? $res : $res[$key];
    }

    /**
     * Do the sorting magic.
     * This function is dependant of the Laravel helper function data_get
     *
     * @param array $data
     * @param string $column
     * @param string $direction
     * @return array
     */
    protected function doSort($data, $column, $direction = 'desc')
    {
        $results = [];

        $callback = function ($item) use ($column) {
            return data_get($item, $column);
        };

        foreach ($data as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        strtolower($direction) == 'desc' ? arsort($results, SORT_NATURAL | SORT_FLAG_CASE) : asort($results, SORT_NATURAL | SORT_FLAG_CASE);

        foreach (array_keys($results) as $key) {
            $results[$key] = $data[$key];
        }

        return array_values(array_filter($results));
    }

    /**
     * Setup the limit.
     *
     * @param int $i
     * @return $this
     */
    public function take($i = 0)
    {
        $this->length = $i;

        return $this;
    }

    /**
     * Setup the offset.
     *
     * @param int $i
     * @return $this
     */
    public function skip($i = 0)
    {
        $this->offset = $i;

        return $this;
    }

    /**
     * Sort by column.
     *
     * @param string $column
     * @param bool $descending
     * @return $this
     */
    public function sortBy($column, $descending = false)
    {
        $this->sort = [
            'column' => $column,
            'order' => $descending
        ];

        return $this;
    }

    /**
     * Is done ?
     *
     * @param int $seconds
     * @param int $microseconds
     * @return bool
     */
    public function isDone($seconds = 0, $microseconds = 1000)
    {
        $links = $errors = $reject = [];
        foreach ($this->links as $link) {
            $links[] = $errors[] = $reject[] = $link;
        }
        if (!mysqli_poll($links, $errors, $reject, $seconds, $microseconds)) {
            return false;
        }
        return true;
    }


    /**
     * Close all connections
     */
    public function close()
    {
        foreach ($this->links as $link) {
            mysqli_close($link);
        }
    }

    /**
     * Destroy the object closing all the connections
     */
    public function __destruct()
    {
        $this->close();
    }

}
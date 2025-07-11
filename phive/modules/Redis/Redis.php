<?php
require_once __DIR__.'/../../api/PhModule.php';
require_once __DIR__.'/../../vendor/autoload.php';

use Predis\Collection\Iterator;

/**
 * This is the Redis wrapper around Predis
 *
 * It's main use is to group various Redis nodes into clusters and load balance
 * the nodes within that cluster, default usage is just one "cluster" with one node
 * on port 6379.
 *
 * @author Henrik Sarvell <henrik.sarvell@videoslots.com>
 * @link https://github.com/nrk/predis Predis on github.
 */
final class PhiveRedis extends PhModule
{
    protected \Predis\Client $cluster_client;

    /**
     * @var array The clusters of nodes.
     */
    protected array $clusters = [];

    /**
     * @var array|null
     */
    protected ?array $nodes = [];
    protected string $prefix;
    protected array $clients = [];

    public function getModuleName()
    {
        return "Redis";
    }

    /**
     * The constructor which accepts the cluster key to be used as its sole argument
     *
     * $this->clients will contain the node connections for the cluster in question (default phive('Redis')->getSetting('hosts')).
     *
     * @param string|null $config_key The cluster key to be used to fetch the correct config
     * defined in Redis.config.php.
     *
     * @throws Exception
     */
    public function __construct(?string $config_key = '')
    {
        if ($this->inClusterMode()) {
            $config_key = empty($config_key) ? 'hosts' : $config_key;
            $this->prefix = $this->getSetting('prefix', 'default') . ':' . $config_key . ':';
            $this->setupClusterClient($config_key);
        } else {
            $this->prefix = $this->getSetting('prefix', 'default') . ':';
            $this->_setup($config_key);
        }
    }

    public function inClusterMode(): bool
    {
        $clusterMode = $this->getSetting('cluster_mode', false);
        return $clusterMode === true;
    }

    /**
     * Gets the client that will manage all the nodes in the cluster.
     * Before we had multiple clusters, now we will have a single cluster, but we will still have one client per cluster.
     * Note that there's no option to lazy load as with a single client, we will always need to connect to the cluster.
     *
     * @throws Exception
     */
    private function setupClusterClient(?string $config_key = '')
    {
        $config_key = empty($config_key) ? 'hosts' : $config_key;
        $nodes = $this->getSetting($config_key);
        try {
            $this->cluster_client = new Predis\Client(
                $nodes,
                [
                    'cluster' => 'redis',
                    'prefix' => $this->prefix,
                    'exceptions' => true,
                    'profile' => $this->getSetting('profile', '3.0'),
                ]
            );
        } catch (Exception $e) {
            phive('Logger')->error('setup-redis-cluster-error', ['error' => $e->getMessage(), 'script' => basename($_SERVER["SCRIPT_FILENAME"]) . '.php']);
            throw $e;
        }
    }

    /**
     * @param $fresh
     *
     * @return \Predis\Client
     * @throws Exception
     */
    private function getClusterClient($fresh): \Predis\Client
    {
        if ($fresh) {
            $this->setupClusterClient();
        }

        return $this->cluster_client;
    }


    /**
     * Responsible for connecting to a specific Redis node.
     *
     * Wrapper around Predis\Client
     *
     * @param int $box_num The node number (the numeric key in the array in Redis.config.php)
     * @param bool $fresh If set to true we initiate the Clients or connection infos in case lazy loading is true.
     * $fresh should be true on first initialization and false on subsequent calls.
     * @param bool $lazy_load If set to true we only return the connection info for this node,
     * if false we connect and return the Predis Client. This to avoid connecting unnecessarily to nodes which we won't
     * end up getting or setting any data from / on.
     *
     * @return mixed The connection options or the connected Predis Client object.
     */
    protected function _getClient(
        int   $box_num,
        ?bool $fresh = false,
        ?bool $lazy_load = false
    )
    {
        $profile = $this->getSetting('profile');
        if ($fresh) {
            if (empty($this->nodes)) {
                // No point in respecting lazy load here as we're only talking one client anyway that for sure will be utilized.
                $host = $this->getSetting('host');
                if (!empty($host)) {
                    $parameters = [
                        'scheme' => $this->getSetting('scheme'),
                        'host' => $host,
                        'port' => $this->getSetting('port'),
                        'timeout' => 5.0,
                        'profile' => $profile,
                        'read_write_timeout' => -1,
                    ];
                } else {
                    $parameters = ['profile' => $profile];
                }

                return new Predis\Client($parameters, ['prefix' => $this->prefix]);
            } else {
                $setting = $this->nodes[$box_num];
                $parameters = [
                    'scheme' => $setting['scheme'],
                    'host' => $setting['host'],
                    'port' => $setting['port'],
                    'timeout' => 5.0,
                    'profile' => $profile,
                    'read_write_timeout' => -1,
                ];
                if ($lazy_load) {
                    return $parameters;
                }

                return new Predis\Client($parameters, ['prefix' => $this->prefix]);
            }
        } else {
            if (empty($this->nodes)) {
                $box_num = 0;
            }
            $client = $this->clients[$box_num];
            if (is_array($client)) {
                $client = new Predis\Client($client, ['prefix' => $this->prefix]);
                $this->clients[$box_num] = $client;
            }

            return $client;
        }
    }

    /**
     * Wrapper around the $clusters class variable.
     *
     * This method will return a certain instance of this class which is a wrapper around a group of nodes (a cluster).
     *
     * @param string $config_key The cluster key to be used to fetch the correct config
     * defined in Redis.config.php.
     *
     * @return $this The instance of this class.
     */
    function getCluster($config_key){
        if(empty($this->clusters[$config_key]))
            $this->clusters[$config_key] = new PhiveRedis($config_key);
        return $this->clusters[$config_key];
    }

    /**
     * Sets up the cluster.
     *
     * Used in the constructor to setup the cluster, will loop all configured nodes and call
     * _getClient on them with fresh set to true and lazy loading true in order to avoid unnecessary
     * connections.
     *
     * @param string|null $config_key The config key to the connection values as defined in Redis.config.php.
     *
     * @return void
     */
    public function _setup(?string $config_key = '')
    {
        $config_key = empty($config_key) ? 'hosts' : $config_key;
        $this->nodes = $this->getSetting($config_key);
        if (!empty($this->nodes)) {
            foreach ($this->nodes as $box_num => $config) {
                $this->clients[] = $this->_getClient($box_num, true, true);
            }
        } else {
            $this->clients[0] = $this->_getClient(0, true, true);
        }
    }

    /**
     * Applies a closure to all nodes in a cluster.
     *
     * Used to loop all nodes in a cluster and apply custom logic to them.
     *
     * @param callable $func The closure / function to apply, each node will the the argument to it.
     *
     * @return array The results recieved after applying the custom logic.
     */
    function doAll($func){
        if ($this->inClusterMode()) {
            return $this->doAllCluster($func);
        }

        $res = [];
        foreach($this->clients as $box_num => $client){
            $client = $this->_getClient($box_num);
            $res[] = $func($client);
        }
        return phive()->flatten($res);
    }

    /**
     * Applies a closure to all nodes in a cluster **when redis is setup in cluster mode**.
     *
     * Used to loop all nodes in a cluster and apply custom logic to them.
     *
     * @param callable $func The closure / function to apply, each node will the the argument to it.
     *
     * @return array The results recieved after applying the custom logic.
     */
    private function doAllCluster(callable $callback)
    {
        $nodes = $this->getClient();
        $result = [];

        foreach ($nodes as $x => $nodeClient) {
            $result[] = $callback($nodeClient);
        }

        return phive()->flatten($result);
    }

    /**
     * This is the cluster load balancer.
     *
     * This method is responsible for picking the correct node to get / set data from / on,
     * either randomly by applying crc32 (followed by modulo) to the key or by using the nutcracker format which looks like this: namespace:[int]:key.
     * If the Nutcracker format is used and we have 10 nodes in the cluster and for instance looks like this: user:[56]:abc
     * then we will look for the data in node 6.
     *
     * @link https://github.com/twitter/twemproxy twemproxy (AKA "Nutcracker") on github.
     * @uses Redis::_getClient()
     * @see  Redis::_getClient()
     *
     * @param mixed $key A string or number, all numbers will be marshalled to strings.
     * @param bool $fresh Will be false if we don't need to reconnect which we don't unless we're testing something.
     *
     * @return Predis\Client The Predis connection object.
     * @throws Exception
     */

    public function getClient($key = '', ?bool $fresh = false)
    {
        if (!empty($this->do_only_client)) {
            return $this->do_only_client;
        }
        if (!empty($this->cluster_client)) {
            return $this->getClusterClient($fresh);
        }
        $key = (string)$key;
        $uid = getMuid($key);
        $num = empty($uid) ? crc32($key) : $uid;
        $box_num = $num % count($this->clients);
        $client = $this->_getClient($box_num, $fresh);
        if (empty($client)) {
            phive()->dumpTbl(
                "redis-box-num-" . $box_num,
                [$key, $box_num, count($this->clients)]
            );
        }

        return $client;
    }

    /**
     * Disconnects from all nodes.
     *
     * Will loop all nodes in the custer and disconnect from them, is rarely used as this happens automatically
     * upon PHP execution stop.
     *
     * @return void
     */
    function disconnect(){
        foreach($this->clients as $client)
            $client->disconnect();
    }

    /**
     * Can be used to connect directly to a specific Redis node.
     *
     * If used this method will bypass the whole cluster logic and connect to a specific node.
     *
     * @param string $cmd The command to run on the node, example: set
     * @param array $args The arguments to use with the command, example: ['foo', 'bar'] if we want to set the foo key to bar.
     * @param string $host The host.
     * @param string $prefix The connection prefix to use.
     * @param int $port The port to connect to.
     *
     * @return mixed The result from running the Redis command
     */
    function exec($cmd, $args = [], $host = 'localhost', $prefix = '', $port = 6379){
        $port            = empty($port) ? 6379 : $port;
        $client          = $this->runtime_clients[$host.$port];
        $wrapper         = new PhiveRedis();
        $wrapper->prefix = $prefix;

        if(empty($client)){
            $con_opts = array('scheme' => 'tcp', 'host' => $host, 'port' => $port, 'read_write_timeout' => -1);
            $client = new Predis\Client($con_opts, array('prefix' => $prefix));
            $this->runtime_clients[$host.$port] = $client;
        }

        $wrapper->do_only_client = $client;

        try{
            return call_user_func_array(array($wrapper, $cmd), $args);
        }catch(Exception $e){
            error_log("Fatal error: Redis timed out, host: $host, cmd: $cmd, port: $port");
            return '';
        }
    }

    /**
     * Returns the key count of the selected node.
     *
     * @param string $key The key used in order to fetch the node via the sharding logic.
     *
     * @return int The count.
     */
    function dbsize($key = ''){
        return $this->getClient($key)->dbsize();
    }

    /**
     * Returns the remaining time to live of a key that has a timeout. This introspection capability allows a
     * Redis client to check how many seconds a given key will continue to be part of the dataset.
     *
     * @link https://redis.io/commands/ttl The redis command.
     *
     * @param string $key The key to check the TTL on.
     *
     * @return int The amount of seconds before purge.
     */
    function ttl($key){
        return $this->getClient($key)->ttl($key);
    }

    /**
     * Set the expire on a certain key, example: expire('foo', 10) will set the key foo to expire in 10 seconds.
     *
     * @link https://redis.io/commands/expire The redis command.
     *
     * @param string $key The key.
     * @param int $expire The amount of seconds the key should exist.
     *
     * @return void
     */
    function expire($key, $expire = 0){
        if(!empty($expire))
            $this->getClient($key)->expire((string)$key, (int)$expire);
    }

    /**
     * Sets a certain key to a value with an optional expire. NOTE that "updating" a key / value by setting an existing
     * key to a new value WITHOUT an expiry will remove any prior expire value and make the key stay forever.
     * This logic applies to ALL various set commands.
     *
     * @link https://redis.io/commands/expire The redis command.
     *
     * @param string $key The key.
     * @param string $value The value.
     * @param int $expire The expire time, if this value is empty the key will never expire.
     *
     * @return void
     */
    function set($key, $value, $expire = 0){
        if(!empty($expire)){
            $this->getClient($key)->set((string)$key, (string)$value, 'EX', $expire);
        }else{
            $this->getClient($key)->set((string)$key, (string)$value);
        }
    }

    /**
     * Sets a certain key to a value only if it doesn't exist.
     * It will return the result of te operation
     *  1 - Write success
     *  0 - Key already exists
     *
     * @link https://redis.io/commands/setnx The redis command.
     *
     * @param string $key The key.
     * @param string $value The value.
     * @param int $expire Optional expiry.
     *
     * @return bool if the new key was created
     */
    function setNx($key, $value, $expire){
        if ($this->getClient($key)->setnx((string)$key, (string)$value)) {
            $this->expire($key, $expire);
            return true;
        }
        return false;
    }

    /**
     * Sets the specified fields to their respective values in the hash stored at key. This command overwrites any
     * specified fields already existing in the hash. If key does not exist, a new key holding a hash is created.
     * Used with hgetall to retrieve the data / values.
     *
     * Example CLI:
     * ```php
     * phM('hmset', 'foo', ['hej' => 'hello', 'apa' => 'monkey']);
     * phM('hmset', 'foo', ['hej' => 'greeting', 'jo' => 'yo']);
     * $res = phM('hgetall', 'foo');
     * print_r($res);
     * ```
     *
     * Will print:
     * ```
     *  Array
     *  (
     *    [hej] => greeting
     *    [apa] => monkey
     *    [jo] => yo
     *  )
     * ```
     *
     * @link https://redis.io/commands/hmset The redis command.
     *
     * @param string $key The key to use.
     * @param array $arr The values to stre.
     * @param int $expire Optional expiry.
     *
     * @return void
     */
    function hmset($key, $arr, $expire = 0){
        $this->getClient($key)->hmset((string)$key, (array)$arr);
        $this->expire($key, $expire);
    }

    /**
     * From the link: Insert all the specified values at the head of the list stored at key.
     * If key does not exist, it is created as empty list before performing the push operations.
     * When key holds a value that is not a list, an error is returned.
     *
     * @link https://redis.io/commands/lpush The redis command.
     *
     * @param string $key The key to use.
     * @param string $value The value to push.
     * @param int $expire Optional expiry.
     *
     * @return void
     */
    function lpush($key, $value, $expire = 0){
        $this->getClient($key)->lpush((string)$key, (string)$value);
        $this->expire((string)$key, $expire);
    }

    /**
     * Returns the specified elements of the list stored at key. The offsets start and stop are zero-based indexes,
     * with 0 being the first element of the list (the head of the list), 1 being the next element and so on.
     * These offsets can also be negative numbers indicating offsets starting at the end of the list.
     * For example, -1 is the last element of the list, -2 the penultimate, and so on.
     *
     * Example:
     * ```php
     * phM('lpush', 'alist', 'foo');
     * phM('lpush', 'alist', 'bar');
     * $res = phM('lrange', 'alist', 0, 2);
     * print_r($res);
     * ```
     *
     * @link https://redis.io/commands/lrange The redis command.
     *
     * @param string $key The key to use.
     * @param int $start The start position to use.
     * @param int $offset The offset to use.
     * @param int $expire An optional expiry.
     *
     * @return array
     */
    function lrange($key, $start, $offset, $expire = 0){
        $this->expire((string)$key, $expire);
        return $this->getClient($key)->lrange((string)$key, (int)$start, (int)$offset);
    }

    /**
     * This command works exactly like lrange but instead of returning the values it will set the list to only contain those values.
     *
     * @link https://redis.io/commands/ltrim The redis command.
     *
     * @param string $key The key to use.
     * @param int $start The start position.
     * @param int $offset The offset to use.
     * @param int $expire Optional expire.
     *
     * @return void
     */
    function ltrim($key, $start, $offset, $expire = 0){
        $this->expire((string)$key, $expire);
        $this->getClient($key)->ltrim((string)$key, (int)$offset, (int)$stop);
    }

    /**
     * Returns the length of the list in key.
     *
     * @link https://redis.io/commands/llen The redis command.
     *
     * @param string $key The key to use.
     *
     * @return int The length.
     */
    function llen($key){
        return $this->getClient($key)->llen((string)$key);
    }

    /**
     * Decreases a value by 1.
     *
     * @link https://redis.io/commands/decr The redis command.
     *
     * @param string $key The key to use.
     * @param int $expire Optional expiry.
     *
     * @return void
     */
    function decr($key, $expire = 0){
        $this->getClient($key)->decr((string)$key);
        $this->expire((string)$key, $expire);
    }

    /**
     * Increases a value by 1.
     *
     * @link https://redis.io/commands/incr The redis command.
     *
     * @param string $key The key to use.
     * @param int $expire Optional expire.
     *
     * @return void
     */
    function incr($key, $expire = 0){
        $this->getClient($key)->incr((string)$key);
        $this->expire((string)$key, $expire);
    }

    /**
     * Increases a value by an arbitrary amount.
     *
     * @link https://redis.io/commands/incrby The redis command.
     *
     * @param string $key The key to use.
     * @param int $num The number to increase with.
     * @param int $expire Optional expire.
     *
     * @return void
     */
    function incrby($key, $num, $expire = 0){
        $this->getClient($key)->incrby((string)$key, (int)$num);
        $this->expire((string)$key, $expire);
    }

    /**
     * Stores a value in a specific key in data initiated with hmset, example:
     *
     * ```php
     * phM('hmset', 'foo', ['hej' => 'hello', 'apa' => 'monkey']);
     * phM('hset', 'foo', 'hej', 'greeting');
     * $res = phM('hgetall', 'foo');
     * print_r($res);
     * ```
     *
     * Result:
     * ```
     * Array
     *  (
     *    [hej] => greeting
     *    [apa] => monkey
     *    [jo] => yo
     *  )
     * ```
     *
     * @link https://redis.io/commands/hset The redis command.
     *
     * @param string $key The main key to use.
     * @param string $fkey The field / secondary key to use.
     * @param string $val The value.
     * @param int $expire Optional expire.
     *
     * @return void
     */
    function hset($key, $fkey, $val, $expire = 0){
        $this->getClient($key)->hset((string)$key, (string)$fkey, (string)$val);
        $this->expire((string)$key, $expire);
    }

    /**
     * Deletes all sub keys / fields in $arr in the hash at $key.
     *
     * Example:
     * ```php
     * phM('hmset', 'foo', ['hej' => 'hello', 'apa' => 'monkey', 'key' => 'value']);
     * phM('hdel', 'foo', ['hej', 'apa']);
     * $res = phM('hgetall', 'foo');
     * print_r($res);
     * ```
     *
     * Result:
     * ```
     * Array
     * (
     *   [key] => value
     * )
     * ```
     *
     * @link https://redis.io/commands/hdel The redis command.
     *
     * @param string $key Main key for the hash.
     * @param array $arr The keys to delete.
     *
     * @return void
     */
    function hdel($key, $arr){
        $this->getClient($key)->hdel((string)$key, $arr);
    }

    /**
     * Gets all keys in the hash with $key as an associative array.
     *
     * @link https://redis.io/commands/hgetall The redis command.
     *
     * @param string $key The main key for the hash.
     * @param int $expire Optional expire.
     *
     * @return array The associative array.
     */
    function hgetall($key, $expire = 0){
        if (empty($key)) {
            return [];
        }
        $this->expire((string)$key, $expire);
        return $this->getClient($key)->hgetall((string)$key);
    }

    /**
     * Gets the value for a specific sub key / field in a hash.
     *
     * @link https://redis.io/commands/hget The redis command.
     *
     * @param string $key The main hash key.
     * @param string $field The hash sub key / field.
     * @param int $expire Optional expire.
     *
     * @return string The value under the sub key / field.
     */
    function hget($key, $field, $expire = 0){
        $this->expire((string)$key, $expire);
        return $this->getClient($key)->hget((string)$key, (string)$field);
    }

    /**
     * Simply gets the value under $key.
     *
     * @link https://redis.io/commands/get The redis command.
     *
     * @param string $key The key.
     * @param string $expire Optional expiry.
     *
     * @return string The value.
     */
    function get($key, $expire = 0){
        $this->expire($key, $expire);
        return $this->getClient($key)->get((string)$key);
    }

    /**
     * Deletes the value under $key as well as the key itself.
     *
     * @link https://redis.io/commands/del The redis command.
     *
     * @param string $key The key.
     *
     * @return int The number of keys that were removed.
     */
    function del($key){
        return $this->getClient($key)->del((string)$key);
    }


    /**
     * Gets all keys that matches a certain pattern in the whole cluster. NOTE that this command will cause a LOCK on the node(s) it is being run on.
     *
     * @link https://redis.io/commands/keys The redis command.
     *
     * @param string $pat The pattern.
     * @param bool $strip_prefix Set to true if the prefix should be stripped in the result set.
     *
     * @return array The keys that match the pattern.
     */
    function keys($pat = '*', $strip_prefix = true){
        if ($this->inClusterMode()) {
            return $this->clusterScan($pat, $strip_prefix);
        }

        $pat  = (string)$pat;
        $id   = getMuid($pat);
        $orig = $pat;
        $pat  = phive()->escapeChars('[]', '\\', $pat);
        if(!empty($id)){
            $client = $this->getClient($orig);
            $res    = $this->getSetting('use_scan') === true ? iterator_to_array(new Iterator\Keyspace($client, $this->prefix.$pat, 10000)) : $client->keys($pat);
        } else {
            $res = $this->doAll(function($client) use ($pat){
                if($this->getSetting('use_scan') === true){
                    return iterator_to_array(new Iterator\Keyspace($client, $this->prefix.$pat, 10000));
                }else{
                    return $client->keys($pat);
                }
            });
        }
        return $strip_prefix ? str_replace($this->prefix, '', $res) : $res;
    }



    private function clusterScan($pattern = '*', $strip_prefix = true)
    {
        $res = $this->doAllCluster(function ($client) use ($pattern) {
            return iterator_to_array(new Iterator\Keyspace($client, $this->prefix . $pattern, 10000));
        });
        return $strip_prefix ? str_replace($this->prefix, '', $res) : $res;
    }


    /**
     * Works like keys but will not cause a lock.
     *
     * NOTE that this command has been performing badly when used,
     * it needs to be tested thoroughly before it can be used in production.
     *
     * @link https://redis.io/commands/scan The redis command.
     *
     * @param string $pat The pattern to use
     * @param string $strip_prefix Set to true if prefix should be stripped in the result set.
     *
     * @return array The keys that match the pattern.
     */
    function scan($pat = '*', $strip_prefix = true){
        $pat = (string)$pat;

        if ($this->inClusterMode()) {
            return $this->clusterScan($pat, $strip_prefix);
        }

        $id = getMuid($pat);

        $orig = $pat;
        $pat = phive()->escapeChars('[]', '\\', $pat);

        if(!empty($id)){
            $client = $this->getClient($orig);
            $res = iterator_to_array(new Iterator\Keyspace($client, $this->prefix.$pat, 10000));
        } else {
            $res = $this->doAll(function($client) use ($pat){
                if($this->getSetting('use_scan') === true){
                    return iterator_to_array(new Iterator\Keyspace($client, $this->prefix.$pat, 10000));
                }else{
                    return $client->keys($pat);
                }
            });
        }
        return $strip_prefix ? str_replace($this->prefix, '', $res) : $res;
    }

    /**
     * Posts a message to a give channel. Used in the websocket logic to push messages to the client(s) from the server.
     *
     * @link https://redis.io/commands/publish The redis command.
     *
     * @param string $channel The channel to post to.
     * @param string $msg The message to post to the channel.
     *
     * @return int The number of clients that received the message.
     */
    function publish($channel, $msg){
        return $this->getClient($channel)->publish((string)$channel, (string)$msg);
    }

    /**
     * Can be used in order to achieve asynchronous execution with ability to fetch the result, see Phive->pexecRes().
     *
     * The below will have to be configured in /etc/redist/redis.conf, it controls limits related to redis pub / sub:
     * ```
     * client-output-buffer-limit pubsub 4048mb 4048mb 600
     * ```
     * @link https://redis.io/commands/subscribe The redis command.
     *
     * @param string $channel The channel to setup.
     * @param int $node_count The amount of forked processes that are supposed to run simultaneously.
     *
     * @return void
     */
    function subscribeRes($channel, $node_count = 1){
        $client = $this->getClient($channel, true);
        $pubsub = $client->pubSubLoop();
        $pubsub->subscribe($channel);
        $key    = "rchannel$channel";
        foreach($pubsub as $message) {
            //We can't have any PHP variable assignments at all in this area due to the lack of proper Mutexes.
            //Script will crash without even segfaulting.
            if($message->kind == 'subscribe')
                continue;
            if(phM('llen', $key) == $node_count){
                $pubsub->unsubscribe();
                unset($pubsub);
                $client->disconnect();
                unset($client);
                return;
            }
        }
    }

    /**
     * Get all values in under many keys.
     *
     * This method is using keys under the hood to get all values under the keys that are matched. NOTE: this LOCKs the whole node(s) when it is running.
     *
     * @param string $pat The key pattern to use.
     *
     * @return array The values.
     */
    function asArr($pat = '*'){
        $rarr = array();
        foreach($this->keys($pat) as $key){
            try{
                $rarr[$key] = $this->get($key);
            }catch(Predis\ServerException $e){
                $rarr[$key] = $this->hgetall($key);
            }
        }

        return $rarr;
    }

    /**
     * Delete all values in under many keys, and the keys.
     *
     * This method is using keys under the hood. NOTE: this LOCKs the whole node(s) when it is running.
     *
     * @param string $pat The key pattern to use.
     *
     * @return array The keys that were deleted.
     */
    function delAll($pat = '*'){
        $ret = array();
        foreach($this->keys($pat) as $key){
            $ret[] = $key;
            $this->del($key);
        }
        return $ret;
    }

    /**
     * Gets all values in a list.
     *
     * Gets all values in a list, tries to JSON decode all values if $decode is set to true.
     *
     * @param string $key The key to use.
     * @param bool $decode JSON decode each value in result set if true.
     *
     * @return array The values.
     */
    function getRange($key, $decode = true){
        $arr = $this->lrange($key, 0, -1);
        if($decode)
            return array_map(function($el){ return json_decode($el, true); }, $arr);
        return $arr;
    }

    /**
     * Nothing but a combination of set and json_encode, for convenience.
     *
     * @param string $key The key to set.
     * @param array $arr The value to set.
     * @param int $expire Optional expire.
     *
     * @return void
     */
    function setJson($key, $arr, $expire = 0){
        $this->set($key, json_encode($arr), $expire);
    }

    /**
     * Nothing but a combination of get and json_decode.
     *
     * @param string $key The key to use.
     * @param int $expire Optional expire.
     *
     * @return array The value.
     */
    function getJson($key, $expire = 0){
        return json_decode($this->get($key, $expire), true);
    }

    /**
     * Checks if we have a key in the Nutcracker format, example: user[1234]:session
     *
     * @param string $key The key.
     *
     * @return bool True if it is, false if not.
     */
    function keyIsPartitioned($key){
        if(!is_string($key))
            return false;
        return preg_match('|(.+):\[(\d+)\]:(.+)|', $key);
    }

    /**
     * This method is used to make setting up Redis simpler with all the clusters, example usage:
     *
     * ```php
     * $arr1 = [
     *     'hosts'   => [6380, 6467], // 87
     *     'mp'      => [6468, 6480], // 12
     *     'pexec'   => [6481, 6489], // 8
     *     'uaccess' => [6490, 6498]  // 8
     * ];
     *
     * $arr2 = [
     *     'qcache'    => [6380, 6400], // 20
     *     'localizer' => [6401, 6421]  // 20
     * ];
     *
     * $this->setClusterConfigs($arr1, 'redisgameplay');
     * $this->setClusterConfigs($arr2, 'rediscontent');
     * ```
     *
     * @param array $clusters The config array, see above for how it should look like.
     * @param string $host The host to use for these particular clusters.
     *
     * @return null
     */
    function setClusterConfigs($clusters, $host){
        foreach($clusters as $cluster => $port_range){
	    $arr = [];
	    foreach(range($port_range[0], $port_range[1]) as $port){
		$arr[] = ['host' => $host, 'scheme' => 'tcp', 'port' => $port];
	    }
            $this->setSetting($cluster, $arr);
        }
    }

    /**
     * Removes and returns the last elements of the list stored at key.
     *
     * @param $key
     * @return string|null
     */
    function rpop($key){
        return $this->getClient($key)->rpop((string)$key);
    }
}

/**
* Global convenience wrapper around Redis->getCluster
*
* @param string $config_key The cluster key.
*
* @return object The Redis object.
*/
function mCluster($config_key){
    return phive('Redis')->getCluster($config_key);
}

<?php
require_once __DIR__ . '/PhConfigurable.php';
require_once __DIR__ . '/PhLog.php';
require_once __DIR__ . '/PhModule.php';

use Illuminate\Container\Container;

/**
 * The Phive utility class used for misc. basic functionality.
 */
class Phive extends PhConfigurable{
    /**
     * @var bool Variable that caches all instances of loaded modules to achieve the singleton pattern.
     */
    public $modules;

    // TODO henrik remove.
    private $dependencies;

    // TODO henrik remove.
    private $installed = false;

    /**
     * Flattens an array, potentially recursively.
     *
     * @param array $array The array to flatten.
     * @param bool $shallow A boolean, if true (default) we only do one level. If false we do fully recursively.
     *
     * @return array The resultant array.
     */
    function flatten($array, $shallow = false) {
        $ret = [];
        if(!empty($array)){
            foreach($array as $value){
                if(is_array($value)){
                    $ret = array_merge($ret, $shallow ? $value : $this->flatten($value));
                }else{
                    $ret[] = $value;
                }
            }
        }
        return $ret;
    }

    /**
     * Convenience function to return HTTP 503 error with a retry after 300 seconds.
     *
     * @return void
     */
    function http503(){
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 300');
    }

    /**
     * Decodes all HTML entities to their appplicable characters.
     *
     * @link http://php.net/manual/en/function.html-entity-decode.php
     *
     * @param string $str The HTML to decode.
     *
     * @return string The decoded HTML.
     */
    function decHtml($str){
        $str = html_entity_decode($str, ENT_QUOTES, "ISO-8859-1");
        $str = preg_replace('/&#(\d+);/me',"chr(\\1)", $str);
        return preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)", $str);
    }

    /**
     * Removes whitespaces, including spaces, supports utf8.
     *
     * @link https://stackoverflow.com/questions/1176904/php-how-to-remove-all-non-printable-characters-in-a-string
     * @param string $str The string to remove wihte spaces from.
     *
     * @return string The string with white spaces removed.
     */
    function rmWhiteSpace($str){
        return preg_replace('/[\x00-\x20\x7F\xA0]/u', '', $str);
    }

    /**
     * Removes all non alpha numerical characters from the input string, respects UTF-8 characters.
     *
     * @param string $str The string ot remove characters from.
     *
     * @return string The string without any non alpha numericals.
     */
    function rmNonAlphaNums($str){
        return preg_replace('/[^[:alnum:]]/u', '', $str);
    }

    /**
     * Removes all non numbers, NOTE that this function might NOT return a valid number as it doesn't care about leading 0s.
     *
     * @param string $value The string we want to remove non-numbers from.
     *
     * @return string The new string without numbers.
     */
    function rmNonNums($value){
        return preg_replace('/[^0-9]/', '', (string)$value);
    }

    /**
     * Removes all non alpha numerical characters, but not spaces, from the input string, respects UTF-8 characters.
     *
     * @param string $str The string ot remove characters from.
     * @param string $str Optional partial regex containing extra characters to keep, typically the underscore with '|_'
     *
     * @return string The string without any non alpha numericals.
     */
    function rmNonAlphaNumsNotSpaces($str, $keep_extra = ''){
        return preg_replace("/[^[:alnum:]|\s{$keep_extra}]/u", '', $str);
    }

    /**
     * Decodes a string of URL params.
     *
     * Converts all key -> value parameters to a PHP array, example:
     * foo=bar&bar=foo results in ['foo' => 'bar', 'bar' => 'foo']
     *
     * @param string $url The string with the URL encoded data.
     * @param bool $urldecode If true it we will url decode each key and value.
     *
     * @return array The resultant array.
     */
    function decUrl($url, $urldecode = true){
        $res = array();
        foreach (explode('&', $url) as $chunk) {
            $param = explode("=", $chunk);
            if($param){
                if($urldecode)
                    $res[urldecode($param[0])] = urldecode($param[1]);
                else
                    $res[$param[0]] = $param[1];
            }
        }
        return $res;
    }

    /**
     * The constructor, initiates the member variable $modules in order to manage the singleton logic.
     *
     * @return void
     */
    function __construct(){
        $this->modules = array();

        $this->me = $this; // Do not remove this line!
    }

    /**
     * Used in order to put things i a queue.
     *
     * We execute in a fork so execution in the main process does not get blocked. NOTE, if $uuid is false the $args need to be
     * filtered / escaped for the command line, primarily for security reasons but also to avoid weird bugs.
     *
     * @link https://www.rabbitmq.com/ Any backend can be used but currently we're using RabbitMQ.
     * @link http://php.net/manual/en/function.escapeshellarg.php Escape shell arg on PHP.net.
     *
     * @param string $channel The named queue / channel we want to queue something in.
     * @param string $module The Phive module we want to execute a method on.
     * @param string $method The method on the Phive module / class we want to execute.
     * @param array $args If we have complex values which we can't put on the command line then $uuid needs to be true.
     * @param bool $uuid If false we just implode the args and put them on the command line, if true we store them in a
     * Redis variable and fetch them in the q.php script, ie they are too complicated to pass on the command line.
     * @param int $timeout The amount of time the PHP script can spend executing once the queue server executes the logic.
     *
     * @return void
     *
     * @deprecated Use Site/Publisher and Site/Consumer
     */
    function q($channel, $module, $method, $args = array(), $uuid = false, $timeout = 0){
        // TODO: Delete this deprecated function after release of TA-554.
        $args = $uuid ? uuidSet($args) : implode(' ', $args);
        $str = 'php '.__DIR__."/q.php $timeout $channel $module $method $args >> /dev/null 2>&1 &";
        pclose(popen($str, 'r'));
    }

    /**
     * This allows us to call functions in an asynchronous way.
     *
     * Use this with simple arguments like strings and integers to make it not break if not using uuid,
     * good: [1231, 'foo'],
     * bad: ['yo bar foo', '{"foo": ["bar"]}']
     *
     * If $uuid is set to true we store args in Redis and hijack the whole process, the uuid check happens at all stages in the flow.
     * In that case any arguments are OK.
     *
     * @param string $module    The name of the module
     * @param string $method    Method name without brackets
     * @param array $args       The arguments for the method
     * @param int|string $sleep Pass in 500-60 to sleep for 500 microseconds and force execution timeout to be 60 seconds,
     * pass in 500-0 to set timeout to infinity.
     * @param bool $uuid If false we just implode the args and put them on the command line, if true we store them in a
     * Redis variable and fetch them in the pexec.php script, ie they are too complicated to pass on the command line.
     * @param string $channel If we want to be able to get the result of multiple async calls we need to pass in the
     * Redis pub / sub channel name here.
     *
     * @return void
     *
     * @deprecated on 10/04/2024 please don't use pexec any more in favour of queue events.
     */
    function pexec($module, $method, $args = array(), $sleep = 500, $uuid = false, $channel = ''){
        if($uuid === false){
            // No complicated args, we just implode and do straight CLI.
            $args = implode(' ', $args);
        }else if(is_numeric($uuid)){
            // We have a user id, so we want to use a specific Redis node for the intermediate storage.
            $args = uuidSet($args, 60, mKey($uuid, $this->uuid()));
        }else if($uuid === true){
            // We just want to pass along complex args, using a random Redis node for intermediate storage.
            $args = uuidSet($args);
        }

        // If we want the result
        if(!empty($channel))
            $args = "$args $channel";
        $str = 'php '.__DIR__."/pexec.php $sleep $module $method $args >> /dev/null 2>&1 &";
        pclose(popen($str, 'r'));
    }

    /**
     * Fire an event.
     *
     * @param string $topic The Feature that fired the event
     * @param string $event Event fired in the form of {Feature.Event}
     * @param array $data Data passed to the event handlers
     * @param int $delay Milliseconds!!
     * @param mixed $fallback to be able
     * @param DBUser $user
     */
    public function fire($topic, $event, $data, $delay, $fallback, $user = null)
    {
        if (!phive('Events/EventPublisher')->useEventQueue($event, $user) && is_callable($fallback)) {
            return call_user_func($fallback);
        }

        $result = phive('Events/EventPublisher')->single($topic, $event, $data, $delay, $user);

        if(!$result && is_callable($fallback)){
            return call_user_func($fallback);
        }

        return $result;
    }


    /**
     * Used when we want to execute logic in parallel and get the result.
     *
     * @param string $module The Phive module to execute method on.
     * @param string $method The method to execute.
     * @param array $argss 2D array of args to pass to each method, eg: [['foo', 'bar'], ['hi', 'hello']]
     * @param bool $flatten True if we want to merge all the results from each execution into one array, false otherwise.
     *
     * @return array The resultant array.
     */
    function pexecRes($module, $method, $argss = [], $flatten = false){
        $channel = uniqid();
        $node_count = count($argss);
        foreach($argss as $args)
            $this->pexec($module, $method, $args, 100, true, $channel);
        //$this->rmqSubscribe($channel, $node_count);
        phM('subscribeRes', $channel, $node_count);
        $key = "rchannel{$channel}";
        $res = array_map(
            function($r){
                $res = json_decode($r, true);
                if(empty($res))
                    $res = [];
                return $res;
            },
            phM('lrange', $key, 0, -1)
        );
        phMdel($key);
        return $flatten ? $this->flatten($res, true) : $res;
    }

    /**
     * Takes some logic to execute and suppresses all output and returns said output instead.
     *
     * @link http://php.net/manual/en/function.call-user-func-array.php This logic is using this useful base function.
     *
     * @param string|array|Closure $source Either the PHP script to execute or an array to use with call_user_func_array.
     * @param array $params Optional arguments to the method / function to run.
     *
     * @return string The captured output.
     */
    function ob($source, $params = array()){
        ob_start();
        if(is_string($source) && strpos($source, '.php') !== false)
            include $source;
        else{
            //If $func is array($obj, 'func') then a method can be called on an instance
            call_user_func_array($source, $params);
        }
        return ob_get_clean();
    }
    /**
     * Replace all non-alphanumeric chars for all languages supported.
     *
     * When the u flag is used on a regular expression, \p{L} (and \p{Letter}) matches any character in any of the Unicode letter categories.
     *
     * @param $string
     * @return string|string[]|null
     */
    public function cleanUpString($string)
    {
        return preg_replace('/[^\p{L}0-9\s]+/u', '', $string);
    }

    /**
     * Escapes all characters in a string with the character(s) in the escape sequence.
     *
     * To escape with a \ just use '\\'.
     *
     * @param string $charsToEscape The string with characters to escape, eg '[]'
     * @param string $escapeSeq The character(s) to escape with, eg '\\'
     * @param string $string The string with chars to escape, eg 'Hi, this is an array: []'
     *
     * @return string The string with replaced characters, eg: 'Hi, this is an array: \[\]'
     */
    function escapeChars($charsToEscape, $escapeSeq, $string){
        $charsToEscape = preg_quote($charsToEscape, '/');
        $regexSafeEscapeSeq = preg_quote($escapeSeq, '/');
        $escapeSeq = preg_replace('/([$\\\\])/', '\\\$1', $escapeSeq);
        return(preg_replace('/(?<!'.$regexSafeEscapeSeq.')(['.$charsToEscape.'])/', $escapeSeq.'$1', $string));
    }

    /**
     * Used to replace all instances of ' with &#39; to avoid SQL many injection shenanigans.
     *
     * @param array &$arr The array passed by reference.
     * @param object $user The currently logged in user (if any), if it is an admin we don't escape.
     *
     * @return void
     */
    function htmlQuotes(&$arr, $user){
        if(!empty($arr)){
            if(empty($_SESSION['user_id']) || !p('admin_top', $user)){
                foreach($arr as $key => &$value){
                    if(strpos($key, 'password') === false)
                        $value = str_replace("'", "&#39;", $value);
                    $value = str_replace("\x00","",$value);
                }
            }
        }
    }

    /**
     * Initialization logic, loops all modules, installs them too and sets up all module aliases.
     *
     * @return void
     */
    public function install(){
        mb_internal_encoding("UTF-8");
        if (phive('BrandedConfig')->newContainer()) {
            return;
        }
        foreach ($this->modules as $key => $module){
            if(!is_a($module, 'PhModule')){
                // It's not a real Phive module so we skip it.
                continue;
            }
            $module->phInstall();
            $aliases = $module->phAliases();
            if (is_array($aliases) && !empty($aliases)){
                foreach ($aliases as $alias){
                    if (!isset($this->modules[$alias])) {
                        $this->modules[$alias] = $module;
                    }
                }
            }
        }
    }

    /**
     * Simple conveniace wrapper around call_user_func_array.
     *
     * @param string $module The Phive module to call a method on.
     * @param string $func The method to run.
     * @param array $params The parameters to pass to the method.
     *
     * @return mixed The result returned from the method to run.
     */
    function apply($module, $func, $params){
        $module = phive(ucfirst($module));
        if(is_object($module))
            return call_user_func_array(array($module, $func), $params);
    }

    /**
     * Part of the singleton logic.
     *
     * Note: on new container mode, the $module argument will be the concrete class name, and the $key will be the abstract class name.
     * For existing modules this doesn't matter since we don't support for interfaces.
     *
     * @param object|string $module The object to add to the singleton array.
     * @param string $key The key / alias to use in order to retrieve the object.
     *
     * @return void
     */
    public function addModule($module, $key = '', $alias = "")
    {
        $key = empty($key) ? $module->getModuleName() : $key;
        if ($this->isNewContainer($module)) {
            $abstract = $key;
            $concrete = $module;
            if (is_object($concrete)) {
                $concrete = function () use ($concrete) {
                    return $concrete;
                };
            }
            $app = Container::getInstance();
            $app->singleton($abstract, $concrete);
            if (!empty($alias)) {
                $app->alias($abstract, $alias);
            }

            // When we get the instance we call the phInstall method on the module. Note on singletons this is only called once.
            $app->afterResolving($abstract, function ($concrete) {
                if (is_a($concrete, 'PhModule')) {
                    $concrete->phInstall();
                }
            });
        } else {
            $this->modules[$key] = $module;
        }
    }

    /**
     * Allows to bind a concrete class to an abstract class.
     *
     * @param string $abstract The abstract class name. Can be a class name or an interface name.
     * @param string|object $concrete $concrete. The concrete class name or an instance of the concrete class.
     * @param string $alias The alias to use for the abstract class.
     * @param bool $shared Whether the binding should be singleton or not.
     * @return void
     */
    public function bind($abstract, $concrete, $alias = "", $shared = true)
    {
        if ($this->isNewContainer($concrete)) {
            $app = Container::getInstance();
            if ($shared) {
                $this->addModule($concrete, $abstract, $alias);
            } else {
                $app->bind($abstract, $concrete);
                if (!empty($alias)) {
                    $app->alias($abstract, $alias);
                }
            }
        } else {
            $this->modules[$abstract] = $concrete;
        }
    }

    public function isNewContainer($module = '') {

        if(is_a($module, 'BrandedConfig', true)) {
            // Must return true to avoid a circular reference when calling bootstrap method
            return true;
        }
        return phive('BrandedConfig')->newContainer();
    }

    /**
     * Resolves a class from the container and automatically injects dependencies from it.
     * If the class is not bound in the container, it will be resolved using the default container.
     * You can pass parameters to the constructor using the second parameter.
     *
     * p.e.  Phive::make(TestClass:class) will resolve the class from the container and inject dependencies.
     *       Phive::make(TestClass:class, ['user_id' => 1234])
     * @param string $class
     * @param array $params Optional parameters to pass to the constructor.
     * @return Closure|mixed|object|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function make($class, $params = []) {
        if (!empty($params)) {
            return Container::getInstance()->makeWith($class, $params);
        }
        return Container::getInstance()->make($class);
    }

    /**
     * Simple utility for loading files in the api folder or sub folders in it.
     *
     * @param string $file The file body name.
     * @param string $sub_dir An optional sub directory of the api folder.
     *
     * @return void
     */
    function loadApi($file, $sub_dir = ''){
        if(!empty($sub_dir))
            $sub_dir .= '/';
        require_once __DIR__ . "/$sub_dir$file.php";
    }

    /**
     * Simple utility to load and return instances of classes inside module folders.
     *
     * @param string $module The module folder, eg: Cashier
     * @param string $class The class / file name eg: Fraud
     *
     * @return object
     */
    function loadClass($module, $class){
        $to_include = __DIR__ . "/../modules/{$module}/{$class}.php";
        if(is_file($to_include)){
            require_once $to_include;
            return new $class();
        }else
            die('Can not find Class: '.$class);
    }

    /**
     * Gets a module instance from the modules array by alias or class name (default).
     * TODO - extract the container logic to a separate class that handles all bootstrapping
     *
     * @param string $name The alias / class name.
     *
     * @return object The module instance.
     */
    public function getModule($name) {
        if (empty($this->modules[$name]) && $this->isNewContainer($name)) {
            $abstract = $name;
            $app = Container::getInstance();

            if (!$app->bound($abstract)) {
                return null;
            }
            // We use rebinding to make the moudule refreshable in case we dynamically change the binding.
            $this->modules[$abstract] = $app->rebinding($abstract, function ($app, $concrete) use ($abstract) {
                $this->modules[$abstract] = $concrete;
            });
        }

        return $this->modules[$name];
    }
    /**
     * Checks if a certain module exists and is loaded.
     *
     * @param string $module The module class name.
     *
     * @return bool True if the module exists, false otherwise.
     */
    public function moduleExists($module){
        if ($this->isNewContainer($module)) {
            return Container::getInstance()->bound($module);
        }

        foreach($this->modules as $mod){
            if(get_class($mod) == $module)
                return get_class($mod);
        }
        return false;
    }

    /**
     * Checks if a method exists on an existing module.
     *
     * @param string $name The module name.
     * @param string $method The method name.
     *
     * @return bool True if the module and method exist, false otherwise.
     */
    function methodExists($name, $method){
        if($this->moduleExists($name))
            return method_exists($this->getModule($name), $method);
        return false;
    }

    /**
     * Simple wrapper around a Phive.config.php setting.
     *
     * @return string The base path of the project.
     */
    public function getPath(){ return $this->getSetting("phive_dir"); }

    /**
     * Wrapper around PHP's array_multisort.
     *
     * This method takes the array to be sorted, which column(s) to sort by and which flag(s) to use.
     *
     * Example from the Tournament class:
     * ```php
     * function sortLeaderboard(&$t, $entries){
     *     $sort_by = $t['win_format'] == 'thw' ? array('win_amount', 'cash_balance', 'highest_score_at') : array('win_amount', 'biggest_win', 'updated_at');
     *     return phive()->sort2d($entries, $sort_by, array('desc', 'desc', 'asc'));
     * }
     * ```
     *
     * @link http://php.net/manual/en/function.array-multisort.php PHP.net's array_multisort page.
     *
     * @param array $arr The 2D array to sort.
     * @param string|array $by_col The column(s) to sort by.
     * @param string|array $flags The flag(s) to sort by, they have to correspond to the columns, amount and order.
     *
     * @return array The sorted array.
     */
    function sort2d($arr, $by_col, $flags = null){
        if(is_array($by_col) && is_array($flags)){
            $params = array();
            for($i = 0; $i < count($by_col); $i++){
                $cur_col = $by_col[$i];
                $tmp = array();
                foreach($arr as $key => $sub)
                    $tmp[] = $sub[$cur_col];
                $params[] = $tmp;
                $params[] = strtolower($flags[$i]) == 'desc' ? SORT_DESC : SORT_ASC;
            }
            $params[] = &$arr;
            call_user_func_array('array_multisort', $params);
        }else{
            if(is_array($by_col))
                $s_col = $by_col;
            else{
                foreach($arr as $sub)
                    $s_col[] = $sub[$by_col];
            }
            $s_type   = is_numeric($s_col[0]) ? SORT_NUMERIC : SORT_STRING;
            $flag     = strtolower($flags) == 'desc' ? SORT_DESC : SORT_ASC;
            if($s_type == SORT_STRING)
                $s_col = array_map('strtolower', $s_col);
            array_multisort($s_col, $s_type, $flag, $arr);
        }
        return $arr;
    }

    /**
     * Gets a sub array by matching against all sub arrays by the passed key.
     *
     * @param array $arr The array to search.
     * @param string $key The key whose value we want to compare.
     * @param mixed $val The value we want to look for.
     *
     * @return array|null The sub array we find or null if no array could be found.
     */
    public function search2d($arr, $key, $val){
        foreach($arr as $sub){
            if($sub[$key] == $val){
                return $sub;
            }
        }
        return null;
    }

    /**
     * Gets the smallest or biggest sub array by column in a 2d array.
     *
     * @param array $arr The array.
     * @param string $by_col The column to use.
     * @param mixed $flags How to sort, desc or asc.
     *
     * @return array The smallest or biggest sub array.
     */
    function minMax2d($arr, $by_col, $flags){
        // We have a one dimenstional
        if(!is_array(current($arr))){
            return $arr;
        }

        $arr = $this->sort2d($arr, $by_col, $flags);

        return $arr[0];
    }

    /**
     * Gets the biggest sub array by column in a 2d array.
     *
     * @param array $arr The array.
     * @param string $by_col The column to use.
     *
     * @return array The biggest sub array.
     */
    function max2d($arr, $by_col){
        return $this->minMax2d($arr, $by_col, 'desc');
    }

    /**
     * Gets the smallest sub array by column in a 2d array.
     *
     * @param array $arr The array.
     * @param string $by_col The column to use.
     *
     * @return array The smallest sub array.
     */
    function min2d($arr, $by_col){
        return $this->minMax2d($arr, $by_col, 'asc');
    }


    /**
     * Sums a sub key of a 2d array
     *
     * The behaviour of this method depends on whether $key is empty or not. If it is not empty we return a sum of the values
     * present in that sub-key as a number. If it is empty we sum up all sub-key -> values, NOTE that in this case all non-numeric
     * information will be lost.
     *
     * @param array $arr The array whose sub-arrays we want to sum up.
     * @param string $key The key to sum.
     * @param bool $abs Whether to treat all values as signed (false) or unsigned positive (true).
     *
     * @return array|int|float The sorted array or numeric sum.
     */
    function sum2d($arr, $key = '', $abs = false){
        if(!empty($key)){
            $total = 0;
            foreach($arr as $el)
                $total += $abs ? abs($el[$key]) : $el[$key];
            return $total;
        }else{
            $rarr = array();
            foreach($arr as $el){
                foreach($el as $key => $val)
                    $rarr[$key] += is_array($val) ? 0 : $val;
            }
            return $rarr;
        }
    }

    /**
     * This method performs counts in 2D arrays based on input.
     *
     * If we have 4 rows in the wins table with starburst mobile, and one with stickers mobile, and we run:
     * ```php
     * $res = phive()->countGroup($wins, 'user_id', 'game_ref', 'spins');
     * ```
     * The result will be:
     * ```
     *     Array
     *      (
     *        [5129332] => Array
     *        (
     *          [netent_stickers_mobile_html_sw] => Array
     *          (
     *            ...
     *            [spins] => 1
     *          )
     *          [netent_starburst_mobile_html_sw] => Array
     *          (
     *            ...
     *            [spins] => 4
     *          )
     *        )
     *     )
     * ```
     *
     * @link http://wiki.videoslots.com/index.php?title=DB_table_wins The wins table wiki documentation.
     *
     * @param array &$arr The array to perform counts on, passed in as a reference for increased performance.
     * @param string $key1 The group key, in the above example user_id.
     * @param string $key2 The key to count.
     * @param string $add_key Optional key to put the counted values in, in the above example it is 'spins', if
     * omitted the logic will sum up in straight in the count key.
     *
     * @return array The counted array / data.
     */
    function countGroup(&$arr, $key1, $key2, $add_key = ''){
        $ret = array();
        foreach($arr as &$el){
            if(!empty($add_key)){
                $tmp = $ret[$el[$key1]][$el[$key2]];
                if(empty($tmp))
                    $tmp = $el;
                $tmp[$add_key]++;
                $ret[$el[$key1]][$el[$key2]] = $tmp;
            }else
                $ret[$el[$key1]][$el[$key2]]++;
        }
        return $ret;
    }


    /**
     * This method performs summing operations on 3D arrays.
     *
     * If the below array is in $arr:
     * ```
     * Array
     * (
     *    [15] => Array
     *    (
     *      [0] => Array
     *      (
     *        [user_id] => 15
     *        [type] => 11
     *        [amount] => 2
     *      )
     *      [1] => Array
     *      (
     *        [user_id] => 15
     *        [type] => 11
     *        [amount] => 2
     *      )
     *      [2] => Array
     *      (
     *        [user_id] => 15
     *        [type] => 12
     *        [amount] => 2
     *      )
     *    )
     *    [25] => Array
     *    (
     *      [0] => Array
     *      (
     *        [user_id] => 25
     *        [type] => 12
     *        [amount] => 2
     *      )
     *    )
     * )
     * ```
     *  Then the result of phive()->sum3d($arr, 'type', 'amount') will be:
     * ```
     *  Array
     *  (
     *    [15] => Array
     *    (
     *      [user_id] => 15
     *      [type] => 11
     *      [amount] => 2
     *      [sums] => Array
     *      (
     *        [11] => 4
     *        [12] => 2
     *      )
     *    )
     *    [25] => Array
     *    (
     *      [user_id] => 25
     *      [type] => 12
     *      [amount] => 2
     *      [sums] => Array
     *      (
     *        [12] => 2
     *      )
     *    )
     *  )
     * ```
     * @param array $arr The array to perform sums with / on.
     * @param string $key2 The grouping key.
     * @param string $sum_key The key -> value to sum.
     * @param bool $abs Whether or not we treat the value to sum as a positive unsigned int (true) or not (false).
     * @param bool $twod If true we just sum in the grouping key, if false we put the results into each resultant
     * 2D array under the 'sums' key.
     *
     * @return array The resultant 2D array.
     */
    function sum3d($arr, $key2, $sum_key, $abs = false, $twod = false){
        $ret = array();
        foreach($arr as $key => $sub){
            foreach($sub as $el){
                if($twod)
                    $ret[$el[$key2]] += $abs ? abs($el[$sum_key]) : $el[$sum_key];
                else{
                    if(empty($ret[$key]))
                        $ret[$key] = $el;
                    $ret[$key]['sums'][$el[$key2]] += $abs ? abs($el[$sum_key]) : $el[$sum_key];
                }
            }
        }
        return $ret;
    }

    /**
     * Sums a 3D array, optionally by a subset of columns.
     *
     * If do only is not empty it will only sum those keys in the sub-arrays. Otherwise all numerical values will be summed.
     * Non numerical values are simply copied.
     *
     * Example:
     *
     * ```php
     * $arr = [
     *     [['number' => 2, 'text' => 'abc']],
     *     [['number' => 2, 'text' => 'abc']],
     * ];
     * $res = phive()->sum3dAssoc($arr);
     * print_r($res);
     * ```
     *
     * Gives us the following result:
     *
     * ```
     * Array
     * (
     *  [0] => Array
     *     (
     *       [number] => 4
     *       [text] => abc
     *     )
     * )
     * ```
     * @param array $arr The array to work with.
     * @param array $do_only An optional array of keys that are to be summed.
     *
     * @return array The resultant array.
     */
    function sum3dAssoc($arr, $do_only = []){
        $ret = [];
        foreach($arr as $sub2d){
            foreach($sub2d as $main_key => $assoc){
                foreach($assoc as $sub_key => $val){
                    // If the value is numeric and do only is empty or sub key is in the do only array
                    if(is_numeric($val) && (empty($do_only) || in_array($sub_key, $do_only))){
                        $ret[$main_key][$sub_key] += $val;
                    }else{
                        $ret[$main_key][$sub_key] = $val;
                    }
                }
            }
        }
        return $ret;
    }

  /**
   * This method is used to group 2D arrays based on sub key.
   *
   * ```php
   * $arr = array(
   *  array('user_id' => 1, 'type' => 1),
   *  array('user_id' => 1, 'type' => 2),
   *  array('user_id' => 2, 'type' => 1));
   * $res = phive()->group2d($arr, 'user_id', false);
   * print_r($res);
   * ```
   *
   * Results in:
   *
   * ```
   * Array
   * (
   * [1] => Array
   * (
   *   [0] => Array
   *   (
   *     [user_id] => 1
   *     [type] => 1
   *   )
   *     [1] => Array
   *   (
   *     [user_id] => 1
   *     [type] => 2
   *   )
   * )
   * [2] => Array
   * (
   *   [0] => Array
   *   (
   *     [user_id] => 2
   *     [type] => 1
   *   )
   *  )
   * )
   * ```
   *
   * @param array $arr The array to work with.
   * @param string $key The string to work with.
   * @param bool $case_sensitive True is we want to compare the value under the $key in a case sensitive fashion, false if not.
   * @param bool $preserve_sub_key True if we want to preserve the current key each sub array has.
   *
   * @return array The resultant 3D array.
   */
    function group2d($arr, $key, $case_sensitive = true, $preserve_sub_key = false){
        if(empty($key))
            return $arr;
        $rarr = array();
        foreach($arr as $sub_key => $el){
            if($preserve_sub_key){
                $rarr[ $case_sensitive ? $el[$key] : strtolower($el[$key]) ][$sub_key] = $el;
            }else{
                $rarr[ $case_sensitive ? $el[$key] : strtolower($el[$key]) ][] = $el;
            }
        }
        return $rarr;
    }

    /**
     * Removes duplicates from a 2D array based on key.
     *
     * ```php
     *  $arr = array(
     *  array('user_id' => 15, 'type' => 11, 'amount' => 2),
     *  array('user_id' => 15, 'type' => 11, 'amount' => 2),
     *  array('user_id' => 15, 'type' => 12, 'amount' => 2),
     *  array('user_id' => 25, 'type' => 12, 'amount' => 2),
     *  array('user_id' => 15, 'type' => 12, 'amount' => 2),
     *  array('user_id' => 15, 'type' => 12, 'amount' => 2));
     *  $res = phive()->uniqByKey($arr, 'user_id');
     * ```
     *
     *  Will result in $res containing:
     * ```
     *   array (
     *     0 =>
     *     array (
     *       'user_id' => 15,
     *       'type' => 11,
     *       'amount' => 2,
     *     ),
     *     1 =>
     *     array (
     *       'user_id' => 25,
     *       'type' => 12,
     *       'amount' => 2,
     *     ),
     *   )
     * ```
     *
     * @param array $arr The array to work with.
     * @param string $key The key to work with.
     *
     * @return array The resultant array.
     */
    function uniqByKey($arr, $key){
        $arr = $this->group2d($arr, $key);
        $rarr = array();
        foreach($arr as $sub)
            $rarr[] = $sub[0];
        return $rarr;
    }

    /**
     * Used to serialize arrays for making hashes used in authentication with EnterCash and Trustly
     *
     * @param mixed $object The object to serialize.
     *
     * @return string The serialized object or the argument without modification in case the argument was a string.
     */
    function serializeData($object){
        if(is_object($object))
            $object = (array)$object;
        $serialized = '';
        if( is_array($object) ) {
            ksort($object); //Sort keys
            foreach($object as $key => $value){
                if(is_numeric($key)) //Array
                    $serialized .= $this->serializeData($value);
                else //Hash
                    $serialized .= $key . $this->serializeData($value);
            }
        }else
            return $object; //Scalar
        return $serialized;
    }

    /**
     * Copies a key -> value from one array to another.
     *
     * ```php
     * $to = array(array('apa' => 'gorilla', 'award_id' => 10));
     * phive()->addTo2d(array('10' => array('something')), $to, 'award_id', 'award');
     * ```
     *
     * Gives:
     *
     * ```
     *   array (
     *     0 =>
     *     array (
     *       'apa' => 'gorilla',
     *       'award_id' => 10,
     *       'award' =>
     *       array (
     *         0 => 'something',
     *       ),
     *     ),
     *   )
     * ```
     *
     * @param array $from The array to copy data from.
     * @param array &$to The array to copy to, passed as reference and works on said array by reference so no return value.
     * @param string $bykey The key to copy.
     * @param string $newkey The key to use in the to array for the new copied value.
     *
     * @return void
     */
    function addTo2d($from, &$to, $bykey, $newkey){
        foreach($to as &$sub)
            $sub[$newkey] = $from[$sub[$bykey]];
    }

    /**
     * Is used to sum or move columns from one array in another array.
     * ```php
     *  $arr1 = [['foo' => 'bar', 'apa' => 'monkey', 'amount' => 100, 'cash' => 100]];
     *  $arr2 = [];
     *  phive()->addCol2d($arr1, $arr2, ['amount' => 'amount_sum', 'cash' => 'cash_sum'], true, true);
     * ```
     *  Gives:
     *  Array
     *  (
     *    [0] => Array
     *    (
     *      [foo] => bar
     *      [apa] => monkey
     *      [amount] => 100
     *      [cash] => 100
     *      [amount_sum] => 100
     *      [cash_sum] => 100
     *    )
     *  )
     * ```php
     *  $arr1 = [['foo' => 'bar', 'apa' => 'monkey', 'amount' => 100, 'cash' => 100]];
     *  $arr2 = [['bar' => 'foo', 'amount_sum' => 100, 'cash_sum' => 100]];
     *  phive()->addCol2d($arr1, $arr2, ['amount' => 'amount_sum', 'cash' => 'cash_sum'], true, true);
     * ```
     *  Gives:
     *  Array
     *  (
     *    [0] => Array
     *    (
     *      [bar] => foo
     *      [amount_sum] => 200
     *      [cash_sum] => 200
     *    )
     *  )
     *
     * @param array $from The array to get values from.
     * @param array &$to The array to work with, to sum in. Passed as reference and worked on in a destructive fashion.
     * @param array $keys The keys to work with.
     * @param bool $abs Treat all values as unsigned positive?
     * @param bool $sum True if we want to sum values, otherwise they are just moved.
     * @param bool $do_empty Controls how we want to handle the case where the corresponding value in the $to array is empty.
     * @param bool $move True if we want to move the whole sub array.
     *
     * @return void
     */
    function addCol2d($from, &$to, $keys, $abs = false, $sum = false, $do_empty = true, $move = false){
        if(is_string($keys))
            $keys = array($keys => $keys);
        foreach($from as $mk => $sub){
            if($move && empty($to[$mk]))
                $to[$mk] = $sub;
            foreach($keys as $fk => $tk){
                if(!$do_empty && empty($to[$mk]))
                    continue;
                if($sum)
                    $to[$mk][$tk] += $abs ? abs($sub[$fk]) : $sub[$fk];
                else
                    $to[$mk][$tk] = $abs ? abs($sub[$fk]) : $sub[$fk];
            }
        }
    }

    /**
     * Grabs values out of a 2D array to create a 1D kv array.
     *
     * @param array $arr The array to work with.
     * @param string $kkey The to key.
     * @param string $vkey The from key.
     *
     * @return array The resultant array.
     */
    function to1d($arr, $kkey, $vkey){
        $rarr = array();
        foreach($this->group2d($arr, $kkey) as $key => $sub){
            if(count($sub) == 1)
                $rarr[$key] = $sub[0][$vkey];
            else if(count($sub) > 1)
                $rarr[$key] = $this->arrCol($sub, $vkey);
        }
        return $rarr;
    }

    /**
     * Grabs a column out of a 2d array.
     *
     * @param array $arr The array to work with.
     * @param string $key The key to grab.
     * @param string $kkey An optional key that will be used as a "reciepient" key in the result array for the values extracted with the main key.
     *
     * @return array The resultant array.
     */
    function arrCol($arr, $key, $kkey = ''){
        if(empty($kkey))
            return array_column($arr, $key);
        $rarr = array();
        foreach($arr as $i){
            $rarr[$i[$kkey]] = $i[$key];
        }
        return $rarr;
    }

    /**
     * Changes target key in a 2D array.
     *
     * This method will switch the main key to a value in each element, which value depends on $key. NOTE that you probably want the value under $key to be unique
     * otherwise you risk overwriting value you don't want to lose.
     *
     * @param array $arr The array to work with.
     * @param string $key The sub array key to use in order to get its value and use it for the parent key.
     * @param bool $case_sensitive Case sensitive true / false.
     *
     * @return array The resultant array.
     */
    function reKey($arr, $key, $case_sensitive = true){
        $rarr = array();
        foreach($arr as $el)
            $rarr[ $case_sensitive ? $el[$key] : strtolower($el[$key]) ] = $el;
        return $rarr;
    }

    /**
     * Copies key -> values from one 2D array to another 2D array.
     *
     * @param array $keys 1D array with the keys to copy.
     * @param array $from 2D array with the arrays to copy from.
     * @param array $to An optional 2D array to copy too, if empty we get a new array with just the passed in keys.
     *
     * @return array The resultant array.
     */
    function moveit($keys, $from, $to = array()){
        $map = array_combine($keys, $keys);
        return $this->mapit($map, $from, $to);
    }

    /**
     * Copies key -> values from one 2D array to another 2D array.
     *
     * @param array $map 1D array with the keys to copy on the form of target key => source key.
     * @param array $from 2D array with the arrays to copy from.
     * @param array $to An optional 2D array to copy too, if empty we get a new array with just the passed in keys.
     * @param bool $copy_empty Should we copy empty values? Default yes / true.
     *
     * @return array The resultant array.
     */
    function mapit($map, $from, $to = array(), $copy_empty = true){
        foreach($map as $new => $old){
            if($copy_empty || !empty($from[ $old ])){
                $to[ $new ] = $from[ $old ];
            }
        }
        return $to;
    }

    /**
     * Reorders the actual places of the key -> values in an assoc 1d array
     *
     * @param array $arr The array to work with.
     * @param array $keys The array of keys to order with.
     *
     * @return array The result array.
     */
    function orderKeysBy($arr, $keys){
        return $this->moveit($keys, $arr, array());
    }

    /**
     * Removes all empty elements from an array, optionally maintaining keys.
     *
     * @param array $assoc The array to work on.
     * @param bool $keep_keys True if we want to maintain keys, false otherwise in which case the result array will be numeric.
     *
     * @return array The resultant array.
     */
    function remEmpty($assoc, $keep_keys = true){
        $rarr = array();
        foreach($assoc as $key => $value){
            if(!empty($value)){
                if($keep_keys)
                    $rarr[$key] = $value;
                else
                    $rarr[] = $value;
            }
        }
        return $rarr;
    }

    /**
     * Creates an array with dates.
     *
     * We use a start and end date and a modificator that has to comply with PHP's strtotime() format.
     *
     * ```php
     * $days = phive()->getDateInterval('2017-12-01', '2017-12-05');
     * print_r($days);
     * ```
     *
     * Gives us:
     * ```
     *  Array
     *  (
     *    [0] => 2017-12-01
     *    [1] => 2017-12-02
     *    [2] => 2017-12-03
     *    [3] => 2017-12-04
     *    [4] => 2017-12-05
     *  )
     * ```
     *
     * @link http://php.net/manual/en/function.strtotime.php strtotime() on php.net.
     * @param string $sdate The start date / stamp, needs to be Y-m-d or Y-m-d H:i:s
     * @param string $edate The end date / stamp, needs to be Y-m-d or Y-m-d H:i:s
     * @param string $mod The modificator, ie how much we want to jump in time for each element in the result array.
     *
     * @return array The array of dates.
     */
    function getDateInterval($sdate, $edate, $mod = '+1 day'){
        $rarr 	= array($sdate);
        $cur_date 	= $sdate;
        while($cur_date < $edate){
            $cur_date = $this->modDate($cur_date, $mod);
            $rarr[] = $cur_date;
        }
        return $rarr;
    }

    /**
     * Wrapper so we don't have to write '0000-00-00 00:00:00' in misc. places.
     *
     * @return string The zero date.
     */
    function getZeroDate(){
        return '0000-00-00 00:00:00';
    }

    /**
     * date validation
     *
     * should perhaps be made stricter
     * with ! in default format string
     *
     * @param string $date_string
     * @param string $format
     * @return bool
     */
    public function validateDate($date_string, $format = 'Y-m-d'): bool
    {
        date_create_from_format($format, $date_string);
        $errors = date_get_last_errors();
        return $errors['warning_count'] === 0 && $errors['error_count'] === 0;
    }

    /**
     * Returns true if a value is deemed to be "empty"
     *
     * In this case empty means one of the following:
     * - empty as returned by empty().
     * - Every element in an array returns true when empty() is used on it.
     * - The value is 0000-00-00 00:00:00 (the zero date).
     *
     * @param mixed $val
     *
     * @return bool True if empty, false otherwise.
     */
    function isEmpty($val){
        if(is_array($val)){
            foreach($val as $el){
                if(!empty($el))
                    return false;
            }
            return true;
        }

        if($val === '0000-00-00 00:00:00')
            return true;

        return empty($val);
    }

    /**
     * Creates a 1D assoc array of sums from two 1D assoc arrays.
     *
     * @param array $base The base array we add to.
     * @param array $to_add The array with values to add.
     * @param array $map An array with keys we want to add.
     *
     * @return array The array of sums.
     */
    function addArrays($base, $to_add, $map = []){
        $map = empty($map) ? array_keys($to_add) : $map;
        foreach($map as $key){
            if(!is_numeric($base[$key]) && !is_numeric($to_add[$key]))
                continue;
            $base[$key] += $to_add[$key];
        }
        return $base;
    }

    /**
     * Creates a random alphanumerical code.
     *
     * @param int $length The length of the code.
     *
     * @return string The code.
     */
    function randCode($length = 5){
        $ranges = array(range('a', 'z'), range('A', 'Z'), range(1, 9));
        $code = '';
        for($i = 0; $i < $length; $i++){
            $rkey = array_rand($ranges);
            $vkey = array_rand($ranges[$rkey]);
            $code .= $ranges[$rkey][$vkey];
        }
        return $code;
    }

    /**
     * Dumps info into the trans_log table.
     *
     * @link http://wiki.videoslots.com/index.php?title=DB_table_trans_log trans_log table wiki entry.
     * @param string $tag Tag for the dumped data.
     * @param mixed $var The dumped data.
     * @param string|int $uid Optional users.user_id.
     *
     * @return void
     */
    function dumpTbl($tag, $var = '', $uid = 0){
        if ($this->getSetting('dump_log', true)) {
            $uid = uid($uid);
            $clear_var = phive('Logger')->clearSensitiveFields($var);
            phive('SQL')->insertArray('trans_log', array('user_id' => $uid, 'tag' => $tag, 'dump_txt' => var_export($clear_var, true)));
        }
        if($this->getSetting('log_to_file', false)){
            $uid = uid($uid);
            phive('Logger')->info("DUMP_TBL | TAG: $tag | UID: $uid: ", $var);
        }
    }

    /**
     * To be used for the api/webhook monitoring with prometheus
     *
     * @param $tag
     * @param $status_code
     * @param int $response_time
     */
    public function monitorLog($tag, $status_code, $response_time = 0)
    {
        $mt = explode(' ', microtime());
        $current_time_ms = ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
        $this->dumpTbl("monitor-{$tag}", json_encode(compact('status_code', 'response_time', 'current_time_ms')));
    }

    /**
     * Inserts data into the misc_cache table.
     *
     * @link http://wiki.videoslots.com/index.php?title=DB_table_misc_cache misc_cache table wiki entry.
     * @param string $key Unique key for the data.
     * @param string $data The data (typically serialized).
     * @param bool $update True if we want to update / save the value, otherwise false, default false.
     *
     * @return void
     */
    function miscCache($key, $data, $update = false){
        if(is_array($data)){
            $data = json_encode($data);
        }

        if($update){
            return phive('SQL')->save('misc_cache', ['id_str' => $key, 'cache_value' => $data]);
        }

        return phive('SQL')->insertArray('misc_cache', ['id_str' => $key, 'cache_value' => $data]);
    }

    /**
     * Store api calls to external services that require audit trails
     *
     * @param string $tag
     * @param array $request
     * @param array $response
     * @param float $time
     * @param int $code
     * @param int $request_id
     * @param int $response_id
     * @param int $uid
     */
    public function externalAuditTbl($tag, $request, $response, $time, $code, $request_id = 0, $response_id = 0, $uid = 0): void
    {
        $insert = [
            'user_id' => uid($uid),
            'tag' => $tag,
            'request' => json_encode($request),
            'response' => json_encode($response),
            'response_time' => $time,
            'status_code' => (int)$code,
            'request_id' => empty($request_id) ? phive()->uuid() : $request_id,
            'response_id' => $response_id
        ];
        phive('Logger')->getLogger('external_audit_log')->info($insert['user_id'], $insert);

        phive('SQL')->insertArray('external_audit_log', $insert);
        // I set the return type to void
        // because during the investigation I couldn't find any calls where we use the result of this function
    }

    /**
     * Wrapper around empty(), will return the default value in case $val is empty.
     *
     * @param mixed $val The value to check.
     * @param mixed $def The default value to return in case $val is empty.
     *
     * @return mixed The value if not empty, otherwise the default value.
     */
    function emptyDef($val, $def){
        return empty($val) ? $def : $val;
    }

    /**
     * Returns the Internet Explorer version (in case IE is used).
     *
     * @return int The version number.
     */
    function ieversion() {
        $match = preg_match('/MSIE (\d+)\.(\d+)/', $_SERVER['HTTP_USER_AGENT'], $reg);
        return (int)$reg[1];
    }

    /**
     * Checks if Firefox is used.
     *
     * @return bool True if Firefox is used, false otherwise.
     */
    function isFirefox(){
        return strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== false;
    }

    function isChrome(): bool
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false;
    }

    /**
     * Get's the currently used device number.
     *
     * @return int 0 if desktop, 1 if mobile.
     */
    function getCurrentDeviceNum(){
        return $this->isMobile() ? 1 : 0;
    }

    /**
     * Get the current device type.
     *
     * @return string Returns 'mobile'|'desktop'.
     */
    function getCurrentDeviceType(){
        return $this->isMobile() ? 'mobile' : 'desktop';
    }

    /**
     * Returns readable words corresponding to the device type numbers.
     *
     * @param bool $flip If true flip the result, otherwise don't.
     *
     * @return array The device map.
     */
    function getDeviceMap($flip = false){
        $map = array(0 => "flash", 1 => "html5", 3 => "windows", 2 => "android", 9 => 'live');
        return $flip ? array_flip($map) : $map;
    }

    /**
     * Returns device type, by using the HTTP User Agent header.
     * MobileApp user agents are configured inside the application
     *
     * @return string The device type.
     */
    public function deviceType(): string
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MobileApp/iOS') !== false) {
            return 'app_iphone';
        }

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MobileApp/Android') !== false) {
            return 'app_android';
        }

        $devices = array("iPad", "iPhone", "iPod", "Android", "BlackBerry", "Symbian", "Nokia", "Mobile", "Macintosh");
        foreach ($devices as $e) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], $e) !== false)
                return strtolower($e);
        }

        return 'pc';
    }

    public function isIosDevice(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        if (
            strpos($userAgent, 'iPhone')
            || strpos($userAgent, 'iPad')
            || strpos($userAgent, 'iPod')
            || strpos($userAgent, 'Macintosh')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Is the client an iPad?
     *
     * @return bool True if iPad, false otherwise.
     */
    function isIpad(): bool
    {
        return $this->deviceType() === 'ipad';
    }

    /**
     * Is the client an iPhone?
     *
     * @return bool True if iPhone, false otherwise.
     */
    function isIphone(): bool
    {
        return $this->deviceType() === 'iphone';
    }

    /**
     * Is the client mobile or not?
     *
     * @return bool True if mobile, false otherwise.
     */
    function isMobile()
    {
        if ($this->isMobileApp()) {
            return true;
        }

        $device = $this->deviceType();
        return in_array($device, ['pc', 'macintosh']) ? false : $device;
    }

    /**
    * Is the client mobile APP?
    * @return bool
    */
    function isMobileApp(): bool
    {
        return in_array($this->deviceType(), ['app_iphone', 'app_android']);
    }

    /**
     * Returns a date formatted in one way formatted in another way.
     *
     * @param string $date The input date.
     * @param string $format The format, has to be compatible with PHP's strtotime().
     *
     * @return string The new date.
     */
    function fDate($date, $format = 'Y-m-d'){
        return date($format, strtotime($date));
    }

    /**
     * Used to convert from Y-m-d H:i:s to localized date.
     *
     * %x Preferred date representation based on locale, without the time. Example: 02/05/09 for February 5, 2009
     * %X -> Preferred time representation based on locale, without the date. Example: 03:59:16 or 15:59:16
     *
     * The server needs to be setup like this for this to work:
     * ```
     * locale-gen sv_SE.utf8
     * locale-gen de_DE.utf8
     * locale-gen fi_FI.utf8
     * locale-gen nb_NO.utf8
     * update-locale
     * ```
     *
     * @link http://php.net/manual/en/function.setlocale.php setlocale() at php.net.
     * @link http://php.net/manual/en/function.strftime.php strftime() at php.net.
     * @param string $date A date that needs to be compatible with strtotime().
     * @param string $format The strftime() format, default is %x %X
     *
     * @return string The localized date.
     */
    function lcDate($date, $format = '%x %T'){
        if(is_numeric($date)){
            $time = $date;
        }else{

            if(strpos($date, '0000') !== false)
                return '';
            $time = empty($date) ? time() : strtotime($date);
        }
        return strftime($format, $time);
    }

    /**
     * Translates one timestamp from one timezone to another.
     *
     * @param string $tz The timezone.
     * @param string $time The Y-m-d H:i:s stamp.
     * @param string $format The format of the resultant string.
     *
     * @return string The new stamp.
     */
    function modTz($tz, $time = '', $format = 'Y-m-d H:i:s'){
        $time   = empty($time) ? phive()->hisNow() : $time;
        $tobj   = new DateTime($time);
        $tobj->setTimeZone(new DateTimeZone($tz));
        return $tobj->format($format);
    }

    /**
     * Modified a date.
     *
     * Takes one date and a modifier that needs to be compatible with strtotime() and returns the modified date.
     *
     * @link http://php.net/manual/en/function.strtotime.php strtotime() on php.net.
     * @param string $date The date to modify.
     * @param string $mod The modifier.
     * @param string $format Format for the returned date.
     *
     * @return string The resultant date.
     */
    function modDate($date, $mod, $format = 'Y-m-d'){
        $date = empty($date) ? date('Y-m-d') : $date;
        return date($format, strtotime($mod, strtotime($date)));
    }

    /**
     * Simple frontend for a regex that checks a string for a Y-m-d date format.
     *
     * @param string $date The string to match.
     *
     * @return bool true if it is for instance 2001-01-01, false otherwise.
     */
    function isDate($date){
        return preg_match('|^\d\d\d\d-\d\d-\d\d$|', trim($date));
    }

    /**
     * Gets yesterday's date.
     *
     * @param string $format The date format of the result date.
     *
     * @return string The result date.
     */
    function yesterday($format = 'Y-m-d'){
        return $this->modDate('', '-1 day', $format);
    }

    /**
     * Gets last month's date.
     *
     * @param string $format The date format of the result date, default Y-m.
     * @param string $date Optional date, current date will be used if empty.
     *
     * @return string The result date.
     */
    function lastMonth($format = 'Y-m', $date = ''){
        return $this->modDate($date, '-1 month', $format);
    }

    /**
     * Checks if the passed date is a Monday.
     *
     * @param string $date The date.
     *
     * @return bool True it is a Monday, false otherwise.
     */
    function isMonday($date = ''){
        $date 	= empty($date) ? date('Y-m-d') : $date;
        $stamp 	= strtotime($date);
        return date('N', $stamp) == 1;
    }

    /**
     * Gets the date's previous week's start and end date on the Y-m-d format.
     *
     * @param string $date The date.
     *
     * @return array An array with the start date in the first position and the end date in the second position.
     */
    function getPreviousWeekStartEnd($date){
        return $this->getWeekStartEnd($date, -1);
    }

    /**
     * Gets the date's previous week's start and end date on the Y-m-d format.
     *
     * This method is complex because of the difficulties in returning
     *
     * @param string $date The date.
     * @param int $modifier An optional modifier that will move X weeks bacward or forward in time.
     *
     * @return array An array with the start date in the first position and the end date in the second position.
     */
    function getWeekStartEnd($date = null, $modifier = 0){
        $date = empty($date) ? date('Y-m-d') : $date;
        $stamp = strtotime($date);

        // Get the ISO-8601 year and week number
        $year = date('o', $stamp);
        $week = (int)date('W', $stamp);

        // Apply the modifier
        $week += $modifier;

        // Adjust the year and week number if needed
        if ($week <= 0) {
            $year--;
            // Get the last week number of the previous year
            $week = (int)date('W', strtotime($year . '-12-31')) + $week;
        } elseif ($week > 52) {
            // Check if the year has 53 weeks
            $lastWeek = (int)date('W', strtotime($year . '-12-31'));
            if ($week > $lastWeek) {
                $year++;
                $week -= $lastWeek;
            }
        }

        // Formatting the week number
        $week_str = str_pad($week, 2, '0', STR_PAD_LEFT);

        // Calculate start and end dates of the week
        $start_date = date('Y-m-d', strtotime($year . 'W' . $week_str . '1'));
        $end_date = date('Y-m-d', strtotime($year . 'W' . $week_str . '7'));

        return array($start_date, $end_date);
    }

    /**
     * Get date for the last N months from today.
     *
     * @param int $numberOfMonths number of months to go back
     * @return array
     **/
    function getLastNMonthsFromNow($numberOfMonths = 3) {
        $now = new DateTime('now', new DateTimeZone('Europe/Malta'));

        // Clone $now to avoid modifying the original object
        $previousMonthDate = clone $now;

        // Subtract $months from the current date
        $previousMonthDate->modify('-' . $numberOfMonths . ' months');
        $previousMonthDateFormatted = $previousMonthDate->format('Y-m-d H:i:s');

        // Get the current date formatted
        $nowFormatted = $now->format('Y-m-d H:i:s');

        return array($nowFormatted, $previousMonthDateFormatted);
    }

    /**
     * Returns today's date in the wanted format.
     *
     * A wrapper around date() to avoid having to write date('Y-m-d') all the time.
     *
     * @param string $format The date format.
     *
     * @return string Today's date.
     */
    function today($format = 'Y-m-d'){
        return date($format);
    }

    /**
     * Return start stamp and the now stamp.
     *
     * @param string $mod strtotime() compatible modifier to use in order to get the start stamp.
     *
     * @return array The array with the start time in the first position and now in the second.
     */
    function timeSpan($mod){
        return array($this->hisMod($mod), $this->hisNow());
    }

    /**
     * Return today's start and end stamps
     *
     * Wrapper to avoid having to write date('Y-m-d').' 00:00:00' in a lot of places.
     *
     * @param string $mod strtotime() compatible modifier to use in order to get the start stamp.
     *
     * @return array The array with the start time in the first position and end stamp in the second.
     */
    function todaySpan(){
        $today = $this->today();
        return array("$today 00:00:00", "$today 23:59:59");
    }

    /**
     * Returns the now stamp in wanted format.
     *
     * Typically used to avoid having to write date('Y-m-d H:i:s') everywhere.
     *
     * @param string $stamp The stamp to use, defaults to now.
     * @param string $format The timestamp format.
     *
     * @return string The resultant stamp.
     */
    function hisNow($stamp = '', $format = 'Y-m-d H:i:s'){
        if(!empty($stamp) && is_string($stamp)){
            $stamp = strtotime($stamp);
        }
        return date($format, empty($stamp) ? time() : $stamp);
    }

    /**
     * Gets the date part from a stamp.
     *
     * @param string $stamp The stamp.
     *
     * @return string The date.
     */
    function toDate($stamp){
        list($date, $his) = explode(' ', $stamp);
        return $date;
    }

    /**
     * Naive method to create XML key / value pairs from a 1D assoc array.
     *
     * @param array $arr The array.
     *
     * @return string The XML.
     */
    function xmlFromArr($arr){
        $xml = '';
        foreach($arr as $key => $val)
            $xml .= "<$key>$val</$key>";
        return $xml;
    }

    /**
     * Modifying stamps.
     *
     * Takes a strtotime() compatible modifier and applies is to a date.
     *
     * @param string $mod The modifier, example: '+1 day'
     * @param string $date Optional stamp, if empty we use now.
     * @param string $format The wanted format for the return date.
     *
     * @return string The resultant date.
     */
    function hisMod($mod, $date = null, $format = 'Y-m-d H:i:s'){
        $date = empty($date) ? date($format) : $date;
        return $this->modDate($date, $mod, $format);
    }

    /**
     * Calculates the time difference between two stamps.
     *
     * Takes two stamps and substracts or adds the second stamp from the first. **Both** dates need to be either string or int.
     *
     * @param string|int $date1 Base stamp.
     * @param string|int $date2 Operator stamp.
     * @param string $op The operator, either - or +
     * @param string $format How do we want to report the result? In years (y), days (d), hours (h), minutes (m) or seconds (s)?
     * @param int $precision The precision number to pass to round.
     * @param bool $round Should round the difference
     *
     * @return int The difference according to the $format.
     */
    function timeDiff($date1, $date2, $op = '-', $format = 's', $precision = 0, $round = true){
        $map = array('s' => 1, 'm' => 60, 'h' => 3600, 'd' => 3600 * 24, 'y' => 3600 * 24 * 365);
        if(is_numeric($date1))
            list($time1, $time2) = array($date1, $date2);
        else
            list($time1, $time2) = array(strtotime($date1), strtotime($date2));
        $diff = $op == '-' ? $time1 - $time2 : $time1 + $time2;
        $ret  = $diff / $map[$format];
        if (!$round) {
            return $ret;
        }
        return round($ret, $precision);
    }

    /**
     * Wrapper around timeDiff with the operator set to '-'.
     *
     * @param string|int $date1 Base stamp.
     * @param string|int $date2 Operator stamp.
     * @param string $format How do we want to report the result? In years (y), days (d), hours (h), minutes (m) or seconds (s)?
     * @param int $precision The precision number to pass to round.
     * @param bool $round Should round the difference
     *
     * @return int The difference according to the $format.
     * @uses Phive::timeDiff()
     * @see  Phive::timeDiff()
     */
    function subtractTimes($date1, $date2, $format = 's', $precision = 0, $round = true){
        return $this->timeDiff($date1, $date2, '-', $format, $precision, $round);
    }

    /**
     * Wrapper around timeDiff with the operator set to '+'.
     *
     * @uses Phive::timeDiff()
     * @see Phive::timeDiff()
     * @param string|int $date1 Base stamp.
     * @param string|int $date2 Operator stamp.
     * @param string $format How do we want to report the result? In years (y), days (d), hours (h), minutes (m) or seconds (s)?
     * @param int $precision The precision number to pass to round.
     *
     * @return int The difference according to the $format.
     */
    function addTimes($date1, $date2, $format = 's', $precision = 0){
        return $this->timeDiff($date1, $date2, '+', $format, $precision);
    }

    /**
     * Returns detailed info on how much time has / will elapse(d) in a certain amount of sceonds.
     *
     * Note that both $stime and $etime needs to **both** be passed in or not.
     * An example: ['days' => 1, 'hours' => 2, 'mins' => 10, 'seconds' => 35] that can be used in a string like this:
     * ```php
     * echo "You've got {$time['days']} days and {$time['hours']} hours left to use your award."
     * ```
     * @uses Phive::subtractTimes()
     * @see Phive::subtractTimes()
     * @param int $secs The amount of seconds we want to use for display.
     * @param string|int $stime Optional start stamp used as base.
     * @param string|int $etime Optional end stamp used to subtract from the base stamp.
     *
     * @return array The return array.
     */
    function timeIntervalArr($secs, $stime = '', $etime = ''){
        if(!empty($stime))
            $secs = $this->subtractTimes($etime, $stime);
        $days 	= floor($secs / 86400);
        $rem 	= $secs > 86400 ? $secs % 86400 : $secs;
        $hours 	= floor($rem / 3600);
        $rem	= $secs > 3600 ? $secs % 3600 : $secs;
        $mins	= floor($rem / 60);
        $sec	= (int)$secs % 60;
        return array('days' => $days, 'hours' => $hours, 'mins' => $mins, 'seconds' => $sec);
    }

    /**
     * This is just an alias of Phive::ellipsis().
     *
     * @uses Phive::ellipsis()
     * @see Phive::ellipsis()
     * @param string $str The string to chop off.
     * @param int $limit All chars above this threshold will be removed.
     * @param string $extra Optional, defaults to the ellipsis (...).
     *
     * @return string The modified string.
     */
    function chop($str, $limit, $extra = '...'){
        return $this->ellipsis($str, $limit, $extra);
    }

    /**
     * Alias / shortcut for a very common usage of number_format().
     *
     * @link http://php.net/manual/en/function.number-format.php number_format() at php.net.
     * @param int $num The number.
     * @param int $div The number the number is supposed to be divided with, typically 100 to convert cents to whole currency units.
     *
     * @return string The result, example: 10000.00 for ten thousand.
     */
    function twoDec($num, $div = 100){
        return number_format((int)$num / $div, 2, '.', '');
    }

    /**
     * Wrapper around Phive::twoDec with $div hardcoded to 1.
     *
     * @see Phive::twoDec()
     * @uses Phive::twoDec()
     * @param int $num The number.
     *
     * @return string The result.
     */
    function decimal($num){
        return $this->twoDec($num, 1);
    }

    /**
     * Shuffles an array and respects the keys.
     *
     * @param array &$arr The array passed as reference.
     *
     * @return void We work on the array in a destructive fashion.
     */
    function shuffleAssoc(&$arr){
        uksort($arr, function() { return rand() > rand(); });
    }

    /**
     * Is used to chop off extra chars in case a string won't fit in a certain place, ex: too long username.
     *
     * @uses Phive::ellipsis()
     * @see Phive::ellipsis()
     * @param string $str The string to chop off.
     * @param int $len All chars above this threshold will be removed.
     * @param string $extra Optional, defaults to the ellipsis (...).
     *
     * @return string The modified string.
     */
    function ellipsis($str, $len = 15, $extra = '...'){
        if(strlen($str) < $len)
            return $str;
        return mb_substr($str, 0, $len).$extra;
    }

    /**
     * Runs trim() on all elements in an array.
     *
     * @link http://php.net/manual/en/function.trim.php trim() at php.net.
     * @param array $arr The array to work with.
     * @param string $charlist String with chars to trim away.
     *
     * @return array The resultant array.
     */
    function trimArr($arr, $charlist = ''){
        $rarr = array();
        foreach($arr as $el)
            $rarr[] = trim($el, $charlist);
        return $rarr;
    }

    /**
     * Trims a certain substring of characters from the beginning and end of subject.
     *
     * @link http://php.net/manual/en/function.preg_replace.php preg_replace() at php.net.
     * @param $search
     * @param $subject
     *
     * @return string The resultant string.
     */
    function trimStr($search, $subject){
        $subject = preg_replace('|'.$search.'$|', '', $subject);
        return preg_replace('|^'.$search.'|', '', $subject);
    }


    /**
     * Takes an assoc array and implodes it with the help of two separator characters.
     *
     * Example, if $arr is: ['GB' => 'regulate', 'SE' => 'limit', 'PL' => 'block'] the result will be: GB:regulate|SE:limit|PL:block
     *
     * @see Phive::fromDualStr()
     * @param array $arr The array to work with.
     * @param string $main_sep The main separator, in the above case that would be |.
     * @param string $sub_sep The sub separator, in the above case it is :.
     *
     * @return array The resultant string.
     */
    function toDualStr($arr, $main_sep = '|', $sub_sep = ':'){
        $ret = '';
        foreach($arr as $key => $val)
            $ret .= "$key$sub_sep$val$main_sep";
        return trim($ret, $main_sep);
    }

    /**
     * Wrapper around explode() in order to explode on two different separators and return an assoc array.
     *
     * Example, if $str is: 'GB:regulate|SE:limit|PL:block' the result will be:
     * ```php
     * ['GB' => 'regulate', 'SE' => 'limit', 'PL' => 'block']
     * ```
     * @link http://php.net/manual/en/function.explode.php explode() at php.net.
     * @see Phive::toDualStr()
     * @param string $str The string to work with.
     * @param string $main_sep The main separator, in the above case that would be |.
     * @param string $sub_sep The sub separator, in the above case it is :.
     *
     * @return array The resultant array.
     */
    function fromDualStr($str, $main_sep = '|', $sub_sep = ':'){
        if(empty($sub_sep))
            return explode($main_sep, $str);
        $ret = array();
        foreach(explode($main_sep, $str) as $tmp){
            list($key, $value) = explode($sub_sep, $tmp);
            $ret[$key] = $value;
        }
        return $ret;
    }

    /**
     * Wrapper around explode to make it more resilient or "smart".
     *
     * This method is used on user input that we don't trust, perhaps a config value in the Config table,
     * the config data should perhaps look like this: foo,bar,baz but somehow it looks like this: foo, bar, baz,
     * this method will correctly return ['foo', 'bar', 'baz'] anyway.
     *
     * @link http://wiki.videoslots.com/index.php?title=DB_table_config The config table on the wiki.
     * @see Config
     * @param string $str The string to explode.
     * @param string $char The chars to explode on.
     *
     * @return array The resultant array.
     */
    function explode($str, $char = ''){
        if(is_array($str))
            return $str;
        if(!empty($char))
            return $this->remEmpty(explode($char, $str));
        $str = str_replace(array(' ', '|', ':'), array('', ',', ','), $str);
        return explode(',', $str);
    }

    /**
     * Gets the base site URL of current project.
     *
     * @param string $domain Optional top domain like www.videoslots.com, if empty a Phive.config.php value is used.
     * @param bool $force_ssl If set to true we force https even though the site is only http. Useful for complying with
     * PSP notification URL requirements etc.
     * @param string Optional extra sub dir and / or GET args.
     *
     * @return string The URL.
     */
    function getSiteUrl($domain = '', $force_ssl = false, $extra = ''): string
    {
        $domain = empty($domain) ? $this->getSetting('full_domain') : $domain;
        $https = $force_ssl ? 's' : $this->getSetting('http_type');
        if (!empty($extra)) {
            $extra = '/' . $extra;
        }
        return "http{$https}://{$domain}{$extra}";
    }

    /**
     * Returns the site title
     *
     * @return mixed|null
     */
    public function getSiteTitle()
    {
        return $this->getSetting('site_title');
    }

    /**
     * Returns true if the current project's domain has 'test' in it, example: test2.videoslots.com.
     *
     * This method is used to control behaviour we only want to happen on various stage / test sites.
     *
     * @return bool True if test is present in the domain name, false otherwise.
     */
    function isTest(){
        return strpos($this->getSetting('full_domain'), 'test') !== false;
    }

    /**
     * Checks if current domain has .loc at the end.
     *
     * This method is used to control behaviour we only want to happen (or not happen) when testing on a local machine.
     * For this to work all local domains needs to end with loc, example: www.videoslots.loc.
     *
     * @return bool True if local, false otherwise.
     */
    function isLocal(){
        return strpos($this->getSetting('domain'), '.loc') !== false || $this->getSetting('test_site') === true;
    }


    /**
     * Wrapper around Phive::post()
     *
     * Same like Phive::post() but has GET hardcoded as the HTTP action.
     *
     * @uses Phive::post()
     * @see Phive::post()
     * @param string $url The URL to GET.
     * @param int $timeout Optional timeout in seconds.
     * @param string|array $extra_headers Extra HTTP headers we want to add to default headers.
     * @param string $debug_key If present will be the tag to use in order to dump into trans_log.
     *
     * @return string The returned HTTP body.
     */
    function get($url, $timeout = '', $extra_headers = '', $debug_key = ''){
        return $this->post($url, '', '', $extra_headers, $debug_key, 'GET', $timeout);
    }

    /**
     * Wrapper around curl_exec.
     *
     * This method performs an HTTP request with the help of the CURL functions, it is the default and
     * recommended way of making HTTP requests in Phive.
     *
     * @uses Phive::dumpTbl()
     * @see Phive::dumpTbl()
     * @link http://php.net/manual/en/ref.curl.php The CURL section on php.net.
     * @link http://wiki.videoslots.com/index.php?title=DB_table_trans_log The trans_log table wiki entry.
     * @param string $url The URL endpoint.
     * @param string|array $content The content to POST in the HTTP body, if array it will be json encoded.
     * @param string $type The content type header, defaults to application/json.
     * @param string|array $extra_headers Extra HTTP headers we want to add to default headers.
     * @param string $debug_key If present will be the tag to use in order to dump into trans_log.
     * @param string $method The HTTP action / method.
     * @param mixed $timeout Optional timeout in seconds. If in form of string X-X the second parameter is the curl total timeout
     *                       and the first one will still be the connection timeout. https://curl.haxx.se/libcurl/c/CURLOPT_TIMEOUT.html
     * @param array $extra_options Potentially some extra options that might be needed in special cases.
     * @param string $charset The charset to use.
     * @param bool $return_complete_response Return an array with three values: the body, the status code and the header.
     *
     * @return string The returned HTTP body.
     */
    function post($url, $content = '', $type = 'application/json', $extra_headers = '', $debug_key = '', $method = 'POST', $timeout = '',  $extra_options = [], $charset = 'UTF-8', $return_complete_response = false){
        if(is_string($extra_headers) && !empty($extra_headers))
            $extra_headers = explode("\r\n", $extra_headers);

        $content = empty($content) ? '' : $content;

        // It is allowed to set content type even if there is no body (ie GET), recipient shall not
        // see this as an error.
        $headers = [ !empty($charset) ? "Content-type: $type; charset=$charset" : "Content-type: $type" ];

        $headers = empty($extra_headers) ? $headers : array_merge($headers, $extra_headers);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);

        if(!empty($content)){
            if(is_array($content))
                $content = json_encode($content);
            $headers[] = "Content-length: " . strlen($content);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        }

        switch($method){
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                break;
            case 'GET':
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
                break;
            default: // PUT, PATCH, DELETE and others
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }

        foreach($extra_options as $option_key => $option_value) {
            curl_setopt($curl, $option_key, $option_value);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        // Uncomment this line if you really need to do serious debugging.
        //curl_setopt($curl, CURLOPT_VERBOSE, true);

        curl_setopt($curl, CURLOPT_HEADER, true);

        if(strpos($timeout, '-') !== false){
            $timeout_array = explode('-', $timeout);
            $conn_timeout = (int)$timeout_array[0];
            curl_setopt($curl, CURLOPT_TIMEOUT, (int)$timeout_array[1]);
        } else {
            $conn_timeout = empty($timeout) ? 60 : (int)$timeout;
        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $conn_timeout);

        if(!empty($debug_key))
            $this->dumpTbl($debug_key.'-out', array('url' => $url, 'options' => ['headers' => $headers, 'content' => $content]));

        $res = curl_exec($curl);

        if(!empty($debug_key))
            $this->dumpTbl($debug_key.'-res', $res);
        if($res === false){
            $err = curl_error($curl);
            curl_close($curl);
            error_log("Curl fatal error: to $url: $err");
            $this->dumpTbl("Curl fatal error: $err", [
                'message' => $err,
                'url' => $url,
                'options' => ['headers' => $headers, 'content' => $content],
                'debug' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            ]);
            return false;
        }

        // We might have an arbitrary amount of elements in $re_arr, if we for instance have HTTP/1.1 100 Continue
        // it will end up in the first position. Atm we're only interested in the last two so popping them off.
        $res_arr           = explode("\r\n\r\n", $res);
        $ret_body          = array_pop($res_arr);
        $ret_headers       = array_pop($res_arr);
        $this->res_headers = explode("\r\n", $ret_headers);
        $res_status_code   = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if(!empty($debug_key))
            $this->dumpTbl($debug_key.'-header-and-body', [$this->res_headers, $ret_body]);

        if ($return_complete_response) {
            return [$ret_body, $res_status_code, $this->getHeadersFromCurlResponse($this->res_headers)];
        }

        return $ret_body;
    }

    /**
     * This method transform the curl header response in an associative array
     * @param array $header_exploded
     * @return array
     */
    private function getHeadersFromCurlResponse(array $header_exploded)
    {
        $headers = array();
        foreach ($header_exploded as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * This will handle posts to BO api
     * @param $url
     * @param $data
     * @return mixed
     */
    function postToBoApi($url, $data) {
        return phive()->post(
            phive()->getSetting('admin2_api')['base_url'] . $url,
            http_build_query($data),
            'application/x-www-form-urlencoded',
            phive()->getSetting('admin2_api')['auth_headers'],
            'admin2-api',
            'POST',
            60
        );
    }

    /**
     * Method to handle postback to Raventrack
     *
     * @param string $action
     * @param User $user
     * @param double $amount
     * @return mixed $body
     */
    function postBackToRaventrack($action, $user, $amount = null) {

        //we only have prod keys, hence this check
        //to ensure we only postback on prod
        if($_ENV['APP_ENVIRONMENT'] !== 'prod') {
            return false;
        }

        $postback_url = phive('Redirect')->getSetting('raventrack')['POSTBACK']['DOMAIN_URL'] . 'webhook/integration/player';
        $postback_access_token = phive('Redirect')->getSetting('raventrack')['POSTBACK']['ACCESS_TOKEN'];
        $postback_enabled = phive('Redirect')->getSetting('raventrack')['POSTBACK']['ENABLED'];
        $data = [];

         //If brand postback is not setup with Raventrack
         if( !$postback_enabled) {
            return false;
        }

        //assign vendor id based on brand
        switch(phive('BrandedConfig')->getBrand()) {
            case phive('BrandedConfig')::BRAND_VIDEOSLOTS:
                $data['vendor_id'] = 1;
                break;

            case phive('BrandedConfig')::BRAND_MRVEGAS:
                $data['vendor_id'] = 2;
                break;
            case phive('BrandedConfig')::BRAND_KUNGASLOTTET:
                $data['vendor_id'] = 3;
                break;

            default:
                return false;//if brand is not set
        }

        $bonus_code = $user->getData('bonus_code');
        $tracking_tag = !empty($bonus_code) ?  explode("|", $bonus_code)[0] :  false;

        //if there is no bonus code associated with user
        if(!$tracking_tag) {
            return false;
        }

        //if postback is related to deposits
        if(isset($amount) ) {
            //if deposit is not the first deposit
            if (!phive('Cashier')->hasOnlyOneApprovedDeposit($user->getId())) {
                return false;
            }

            $data['deposit_amount'] = $amount;
        }

        $data['action_date'] = date('Y-m-d'); // set the action date to today
        $data['tracking_tag'] = $tracking_tag;
        $data['action'] = $action;

        //post data to Raventrack
        $body = phive()->post($postback_url, $data, 'application/json', "Authorization: Bearer $postback_access_token");
        $data['response'] = $body;
        phive()->dumpTbl('raventrack_postback', $data);
        return $body;
    }

    /**
     * Get the span / level a certain value is in / on.
     *
     * Example call:
     * ```php
     * phive()->getLvl(15, [5 => 'small', 10 => 'medium', 15 => 'large', 20 => 'massive'], 'small')
     * ```
     *
     * Result: 'massive'
     *
     *
     * @param int|float $amount
     * @param array $levels The array of levels => values.
     * @param mixed $ret The default return value if a level couldn't be determined.
     *
     * @return mixed The return value that signifies a certain level.
     */
    function getLvl($amount, $levels, $ret){
        foreach($levels as $thold => $lvl){
            if($amount < $thold){
                $ret = $lvl;
                break;
            }
        }
        return $ret;
    }

    /**
     * Passes in an array of keys with correpsonding weights which will be used to determine
     * which key we will randomly return. The goal is to emulate the load balancing algo that
     * Nginx for instance is using.
     *
     * @param array $arr The array of keys and weights.
     *
     * @return string The chosen key.
     */
    public function loadBalance($arr){
        $total      = array_sum($arr);
        $picked_num = rand(1, $total);
        $sum        = 0;
        foreach($arr as $key => $weight){
            $sum += $weight;
            if($picked_num <= $sum){
                return $key;
            }
        }
        return $key;
    }

   /**
     * Get the span / level a certain value is in / on. Same as getLvl, but getting the 'lower' level not the 'upper'.
     *
     * Example call:
     * ```php
     * phive()->tholdLvl(19, [5 => 'small', 10 => 'medium', 15 => 'large', 20 => 'massive'], 'small')
     * ```
     *
     * Result: 'large'
     *
     *
     * @param int|float $amount
     * @param array $levels The array of levels => values.
     * @param mixed $ret The default return value if a level couldn't be determined.
     *
     * @return mixed The return value that signifies a certain level.
     */
    function tholdLvl($amount, $levels, $ret) {
        foreach($levels as $thold => $lvl){
            if($amount >= $thold) {
                $ret = $lvl;
            } else {
                break;
            }
        }
        return $ret;
    }

    /**
     * Returns a string version of true / false, used a lot in JS code.
     *
     * @param bool $bool True or false.
     *
     * @return string The string representation.
     */
    function getJsBool($bool){
        return $bool ? 'true' : 'false';
    }

    /**
     * Getting the session handler class.
     *
     * Phive overrides PHP's default session handling logic, we use RAM (Redis) for storage. This method returns an instance of the
     * SessionManager.
     *
     * @see SessionManager
     * @return SessionManager The session manager object.
     */
    function getSessManager(){
        if(!empty($this->sess))
            return $this->sess;
        require_once __DIR__ . '/sessions.php';
        $this->sess = new SessionManager();
        return $this->sess;
    }

    /**
     * Blocks robots in special requests.
     *
     * We don't want robots to double index certain sub domains that we proxy ourselves.
     * Method will echo a noindex meta in case we have a request from our own proxy.
     *
     * @return void
     */
    function generalIndexerBlock(){
        if(!empty($_SERVER['HTTP_PANDA_ORIGINAL_IP']))
            echo '<meta name="robots" content="noindex" />';
    }

    /**
     * Wrapper around a global variable, used to prevent creation of sessions.
     *
     * @return bool True if we don't want to create a session.
     */
    function noSession(){
        return $GLOBALS['no-session'] === true;
    }

    /**
     * Get session data with session id or user object.
     *
     * This will only get the session data, currently there is NO way of manipulating session data and saving it as that could cause extremely hard to
     * catch bugs. Use unique Redis data relevant to your own context instead if you feel like you'd like to change session data.
     *
     * NOTE: Currently not used, but can be helpful for debugging purposes according to Henrik /Paolo
     *
     * @param DBUser|string $identifier User object or session id string.
     *
     * @return array $session The session data.
     */
    function getSessionData($identifier){
        $sess = $this->getSessManager();
        if(is_object($identifier)){
            $sess->startByUser($identifier);
        } else {
            session_id($identifier);
            phive()->secureSessionStart();
        }
        $session = $_SESSION;
        session_write_close();
        $_SESSION = [];
        return $session;
    }

    /**
     * Main method to start / create sessions.
     *
     * If we don't have a session id already we will set a session cookie with a one hour timeout.
     *
     * @uses mCluster()
     * @see mCluster()
     * @see Redis
     * @link http://php.net/manual/en/function.session_id.php session_id() at php.net.
     * @link http://php.net/manual/en/function.session_start.php session_start() at php.net.
     * @link http://php.net/manual/en/function.session_set_cookie_params.php session_set_cookie_params() at php.net.
     *
     * @param bool $skipCheck skip no-session check, default false.
     *
     * @return void
     */
    function sessionStart(bool $skipCheck = false){
        if(!$skipCheck && $this->noSession())
            return;

        $sid = session_id();
        // https://www.php.net/manual/en/function.session-status.php#123404
        $is_session_started = session_status() === PHP_SESSION_ACTIVE;

        // We haven't started the session yet, ie first time call during the request cycle.
        if(!$is_session_started) {
            // if we set all params for session cookies here, we can remove them from php.ini
            $params = [
                'lifetime' => lic('cookieLifetime'),
                'path' => '/',
                'domain' => getCookieDomain(),
                'secure' => phive()->getSetting('cookie_secure'),
                'httponly' => !phive()->getSetting('disable_cookie_httpOnly'), // we want it always enabled by default (settings default to null/false)
                'samesite' => phive()->getSetting('cookie_samesite')
            ];
            session_set_cookie_params($params);
            $this->getSessManager();
            $this->secureSessionStart();
            $sid = session_id();
        }

        //Do not change this conditional
        if(($GLOBALS['no-session-refresh'] !== true) && !empty($_SESSION['user_id'])) {
            setSid($sid, $_SESSION['user_id']);
        }

        $this->checkSession();
    }

    private function checkSession()
    {
        if ($_SESSION['OBSOLETE']) {
            header("location: /?signout=true");
        }
    }

    /**
     * It's called on login to prevent session fixation attacks
     *
     * @see    https://www.php.net/manual/en/function.session-regenerate-id.php#87905
     * @return void
     */
    public function regenerateSession()
    {
        return; // HOTFIX https://videoslots.atlassian.net/browse/CANV-4269
        // Set current session to expire in 1 minute.
        $_SESSION['OBSOLETE'] = true;
        $_SESSION['EXPIRES'] = time() + 60;

        // Create new session without destroying the old one.
        session_regenerate_id(false);

        // Grab current session ID and close both sessions to allow other scripts to use them.
        $newSession = session_id();
        session_write_close();

        // Set session ID to the new one, and start it back up again.
        session_id($newSession);
        setSid($newSession, $_SESSION['user_id']);
        session_start();

        // Don't want this one to expire.
        unset($_SESSION['OBSOLETE']);
        unset($_SESSION['EXPIRES']);
    }

    /**
     * If a cookie's name begins with a case-sensitive match for the string "__Secure-",
     * then the cookie will have been set with a "Secure" attribute.
     *
     * For example, the following "Set-Cookie" header would be rejected by a conformant user agent,
     * as it does not have a "Secure" attribute.
     *    Set-Cookie: __Secure-SID=12345; Domain=site.example
     * Whereas the following "Set-Cookie" header would be accepted:
     *    Set-Cookie: __Secure-SID=12345; Domain=site.example; Secure
     *
     * @see sc-229568 for more information
     * @return void
     */
    public function secureSessionStart()
    {
        // Do not start a PHP session to prevent session ID cookie overwrite
        // on redirect back from PSP to Videoslots/Mr.Vegas within an iframe
        // on successful/cancelled/failed deposit transaction
        // to the urls like '/cashier/deposit/' or '/en/cashier/deposit/'
        // when SameSite cookie attribute is set to Lax (see sc-250343 for the issue details)
        if (phive()->getSetting('cookie_samesite') == 'Lax' && $this->isDepositEndRequest()) {
            return;
        }

        if (phive()->getSetting('cookie_secure') !== false) {
            session_name('__Secure-PHPSESSID');
        }

        session_start();
    }

    /**
     * Write session data to Redis immediately.
     * By default, session data is saved to Redis once request is finished
     *
     * @return void
     */
    public function persistSession(): void
    {
        session_write_close();
        phive()->sessionStart();
    }

    public function isDepositEndRequest()
    {
        $urlWithoutParams = strtok($_SERVER['REQUEST_URI'], '?');
        return ($urlWithoutParams == '/cashier/deposit/' || substr($urlWithoutParams, 3) == '/cashier/deposit/')
            && $_GET['end'] == 'true'
            && empty($_GET['redirected']);
    }

    /**
     * Checks if as string is a proper UUID.
     *
     * Checks if a string is an UUID on this form: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
     *
     * @param string $uuid The potential UUID.
     *
     * @return bool True is it is, false otherwise.
     */
    function isUuid($uuid){
        if(!is_string($uuid))
            return false;
        list($e, $f1, $f2, $f3, $t) = explode('-', $uuid);
        return array(strlen($e), strlen($f1), strlen($f2), strlen($f3), strlen($t)) == array(8, 4, 4, 4, 12);
    }

    /**
     * Returns a UUID.
     *
     * Version 4 UUIDs have the form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx where x is any hexadecimal digit and y is one of 8, 9, A, or B (e.g., f47ac10b-58cc-4372-a567-0e02b2c3d479).
     *
     * This method does however **NOT** follow the above rule, example output: 0f3fb671-2b3d-9728-2ba1-00001357e22a
     *
     * Breaking the "rules" enables us to be even more random than otherwise possible.
     *
     * @link http://php.net/manual/en/function.mt_rand.php mt_rand() at php.net.
     * @link https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_(random) UUID v4 on Wikipedia.
     *
     * @return string The UUID.
     */
  function uuid(){
      return sprintf(
          '%08x-%04x-%04x-%02x%02x-%012x',
          mt_rand(),
          mt_rand(0, 65535),
          bindec(substr_replace(
              sprintf('%016b', mt_rand(0, 65535)), '0100', 11, 4)
          ),
          bindec(substr_replace(sprintf('%08b', mt_rand(0, 255)), '01', 5, 2)),
          mt_rand(0, 255),
          mt_rand()
      );
  }

    /**
     * Folds a string into its canonical form.
     *
     * @link https://software-lab.de/doc/refF.html#fold PicoLisp's fold reference.
     * @param string $str The string to fold.
     * @param int $length Optional length to keep, everything else will be discarded.
     *
     * @return string The resultant string.
     */
    function fold($str, $length = 0){
        $res = $this->rmNonAlphaNums(strtolower($str));
        if(!empty($length))
            return substr($res, 0, $length);
        return $res;
    }

    /**
     * Gets a value from the misc_cache table.
     *
     * @param string $id_str The key / id to get the value for.
     *
     * @return string The value.
     */
    function getMiscCache($id_str){
        return phive("SQL")->getValue("SELECT cache_value FROM misc_cache WHERE id_str = '$id_str'");
    }

    /**
     * Deletes a value from the misc_cache table.
     *
     * @param string $id_str The key / id to delete.
     *
     * @return bool 
     */
    function delMiscCache($id_str){
        return phive('SQL')->delete('misc_cache', ['id_str' => $id_str]);
    }

    /**
     * Return an array to be used on "dumpTbl" with the common global variables to be dumped.
     * Note that "GET" & "POST" are separate on purpose as "REQUEST" would not handle key with same name.
     *
     * TODO we can't just dump everything we need to be selective with data
     *
     * @return array
     */
    public function getCommonLogVariables()
    {
        return [
            'get' => $_GET,
            'post' => $_POST,
            'sess' => $_SESSION,
            'cookie' => $_COOKIE,
            'ip' => remIp()
        ];
    }

    /**
     * Convert all keys to lowercase
     *
     * @param array $item
     * @param bool $shallow
     * @return mixed
     */
    public function convertKeysToLower($item, $shallow = false)
    {
        if (!is_array($item)) {
            return $item;
        }

        foreach ($item as $key => $value) {
            unset($item[$key]);
            $item[strtolower($key)] = is_array($value) && !$shallow ? $this->convertKeysToLower($value) : $value;
        }

        return $item;
    }

    public function isForked(){
        return $GLOBALS['is_forked'] === true;
    }

    public function isArchive(){
        return $GLOBALS['is_archive'] === true;
    }

    public function isQueued(){
        return $GLOBALS['is_queued'] === true;
    }

    public function isCron(){
        return $GLOBALS['is_cron'] === true;
    }

    /**
     * Detect if time is between hours
     *
     * @param DateTime|string $time
     * @param string|int $start_hour
     * @param string|int $end_hour
     * @return bool
     * @throws Exception
     */
    public function isDateTimeBetweenHours($time, $start_hour, $end_hour): bool
    {
        $time = is_string($time) ? new DateTime($time) : $time;
        $time = (new DateTime('NOW'))->setTime(
            $time->format('H'),
            $time->format('i'),
            $time->format('s')
        );

        $end_tomorrow = false;
        $start_hour = (int)$start_hour;
        $end_hour = (int)$end_hour;
        $start = (new DateTime('NOW'))->setTime($start_hour, 0);
        $end = (new DateTime('NOW'))->setTime($end_hour, 0);

        if ($start > $end) {
            $end->modify('+ 1 day');
            $end_tomorrow = true;
        }

        if ($end_tomorrow && $time->format('H') < $end_hour) {
            $time->modify('+ 1 day');
        }

        return $start <= $time && $time <= $end;
    }

    /**
     * Takes XML and attempts to return an array representation of the data.
     *
     * Example 1:
     * ```php
     * $xml = <<<EOS
     * <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
     *    <soap:Body>
     *        <ns2:withdrawAndDeposit xmlns:ns2="http://types.walletserver.casinomodule.com/3_0/">
     *            <description>testmerchant-éßž,-的-</description>
     *            <jackpotContributions>
     *                <contribution>0.014</contribution>
     *                <contribution>0.077</contribution>
     *            </jackpotContributions>
     *        </ns2:withdrawAndDeposit>
     *    </soap:Body>
     * </soap:Envelope>
     * EOS;
     * returns [
     *      "withdrawAndDeposit" => [
     *          "@attributes" => ["ns2" => "http://types.walletserver.casinomodule.com/3_0/"],
     *          "description" => "testmerchant-éßž,-的-",
     *          "jackpotContributions" => ["contribution" => ["0.014", "0.077"]]
     *      ]
     * ]
     *```
     *
     * Example 2:
     * ```php
     * $xml2 = <<<EOS
     * <withdrawAndDeposit>
     *    <description>testmerchant-éßž,-的-</description>
     *    <jackpotContributions>
     *        <contribution>0.014</contribution>
     *        <contribution>0.077</contribution>
     *     </jackpotContributions>
     * </withdrawAndDeposit>
     * EOS;
     * returns [
     *      "withdrawAndDeposit" => [
     *          "description" => "testmerchant-éßž,-的-",
     *          "jackpotContributions" => ["contribution" => ["0.014", "0.077"]]
     *      ]
     * ]
     * ```
     *
     * Note that for example 1 "contribution" element returns "jackpotContributions" => ["contribution" => "0.014"]
     * but 2 "contribution" elements return "jackpotContributions" => ["contribution" => ["0.014", "0.077"]]
     *
     * @param string $xml The XML string to work with.
     * @param bool $remove_namespaces Whether or to run a preg replace that will remove XML namespaces, don't use it
     * if it's' not needed as it might affect your data.
     * @param string $element_to_convert XML element name, pass in this if you want to convert only a specific element in the XML tree.
     *
     * @return array The resultant array.
     */
    public function xmlToArr($xml, $remove_namespaces = false, $element_to_convert = ''): array
    {
        if (empty($xml)) {
            $xml = "";
        }

        if ($remove_namespaces) {
            $xml = preg_replace('/(\w+?)(:)(\w+?)/', '$3', $xml);
        }
        // NOTE that this does not respect CDATA, if you need CDATA you have to use the simple xml object.
        $simple_xml_obj = @simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        // Not valid XML
        if ($simple_xml_obj === false) {
            return [];
        }
        $xml_body = false;
        if (!empty($element_to_convert)) {
            $xml_body = $simple_xml_obj->xpath('//' . $element_to_convert)[0];
        }
        $xml_body = $xml_body ? $xml_body : [$simple_xml_obj->getName() => $simple_xml_obj];
        $json = json_encode($xml_body);
        return json_decode($json, true);
    }

    /**
     * Decode and replace double encoded strings from database to show single quote
     *
     * @param $string
     * @return string
     *
     */
    function html_entity_decode_wq($string): string
    {
        $string = html_entity_decode($string, ENT_QUOTES|ENT_XHTML, 'UTF-8'); //decoding &amp;#39; registrations from database
        return str_replace("&#39;", '\'', $string); //(data already double-encoded in db) after decoding &#39; is left, either needs to be replaced or decoded again.
    }

    function signSerialized(string $data): string
    {
        return hash_hmac('sha256', $data, $this->getSetting('phive_secret'));
    }

    function checkSigned(string $data, string $hash): bool
    {
        return $this->signSerialized($data) === $hash;
    }

    /**
     * Function to handle ping functionality for /ping.php
     *
     * @return void
     */
    public function handlePing(): void
    {
        if (phive('BrandedConfig')->getBrand() === phive('BrandedConfig')::BRAND_MRVEGAS) {
            echo 'Ping mrvegas-web1';
            return;
        }

        $server = gethostname();

        echo $server;

        phive()->pexec('na', 'dumpTbl', ['gameplaytest', $server], 500, true);
    }

    /**
     * Exit with 301 redirect to $header_location
     *
     * @param string $header_location
     * @return void
     */
    private function closedRedirect(string $header_location): void
    {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: {$header_location}");
        header("Connection: close");
        exit;
    }

    /**
     * Function to handle functionality previously declared in /go.php
     *
     * @return void
     */
    public function handleGo(): void
    {
        $brand = phive('BrandedConfig')->getBrand();

        if ($_GET['referral_id'] === 'newmar') {
            die('This affiliate account has been closed.');
        }

        if ($_GET['referral_id'] === 'WEB2' && strpos($_SERVER['HTTP_REFERER'], "ads.{$brand}.com") === false) {

            if (strpos($_GET['dir'], 'sv') !== false) {
                $lang = 'sv/';
            } else if (strpos($_GET['dir'], 'fi') !== false) {
                $lang = 'fi/';
            } else {
                $lang = '';
            }
            $this->closedRedirect("http://ads.{$brand}.com/$lang?referral_id=WEB2");
        }
        // on MrVegas redirect only on Production
        // on VS redirect all the time - to keep the old logic
        $should_redirect = true;
        if ($brand === phive('BrandedConfig')::BRAND_MRVEGAS) {
            $should_redirect = phive('BrandedConfig')->isProduction();
        }

        if ($should_redirect && $_SERVER['SERVER_PORT'] == 80) {
            $this->closedRedirect("https://www.{$brand}.com" . $_SERVER['REQUEST_URI']);
        }

        $redirect_links = $this->getSetting('go_redirect_links');
        $ext_links = $this->getSetting('go_external_links');

        $new_dir = $redirect_links[$_GET['dir']];
        $ext_url = $ext_links[$_GET['dir']];

        if (strpos($_GET['dir'], 'affiliate') !== false) {
            $ext_url = "https://partner.{$brand}.com/";
        }

        if (!empty($new_dir) || !empty($ext_url)) {
            if (!empty($ext_url)) {
                $this->closedRedirect($ext_url);
            } else {
                $this->closedRedirect("http://" . $_SERVER['HTTP_HOST'] . $new_dir);
            }
        }

        // on MrVegas exit ignoring proxy_ips
        // on Videoslots brand exit only if proxy_ip is missing - to keep old logic
        $should_exit = true;
        if ($brand === phive('BrandedConfig')::BRAND_VIDEOSLOTS) {
            $should_exit = !in_array($_SERVER['REMOTE_ADDR'], phive()->getSetting('proxy_ips'), true);
        }

        if ($should_exit && $_SERVER['SERVER_NAME'] !== phive()->getSetting('full_domain')) {
            echo 'exiting';
            exit;
        }
    }

    /**
    * Function to check if page alias added to load content through ajax
    *
    * @return boolean 1 if page is added to load ajax content, 0 if not exist
    */
    public function isAjaxCacheAdded()
    {
        if (in_array(trim(phive('Pager')->getAliasFromCachedPath(phive('Pager')->getPath()),'/'), phive()->getSetting('add_ajax_cache'))) {
            return true;
        } else {
            return false;
        }
    }

	/**
     * Make a request to jackpot microservice and update the data of centralized all brands jackpot data
     *
	 * @return void
	 */
	public function sendJackpotDataToMicroservice(): void
	{
        /** @var Logger $logger */
        $logger = phive('Logger')->getLogger('cron');

        /** @var Redirect $redirect_handler */
        $redirect_handler = phive('Redirect');

        $brand = phive('BrandedConfig')->getBrand();

        // Get the API Settings
        $api_settings = $redirect_handler->getSetting('jackpots');

        if (empty($api_settings['DOMAIN_URL']) || empty($api_settings['ACCESS_TOKEN'])) {
            $logger->getLogger('cron')->error("DOMAIN_URL or ACCESS_TOKEN configs are missing");
            return;
        }

        $last_id    = 0;
        $limit      = 100;
        $failed     = 0;

        // Create a unique run id to identify if we have concurrent CRONS running
        // and set it in redis, so that we know what is the currently active cron
        $pid = (int) getmypid();
        phMset('jackpots_cron_pid', $pid);

        do {
            $data = $this->getJackpotsChunk($last_id, $limit, $brand);
            if (count($data) < 1) return;

            $response = phive()->post(
                rtrim($api_settings['DOMAIN_URL'], '/') . '/api/jackpots/aggregate',
                ['data' => $data],
                'application/json',
                "Authorization: Bearer {$api_settings['ACCESS_TOKEN']}",
                '',
                'POST',
                '',
                [],
                'UTF-8',
                true
            );

            if (is_array($response)) {
                if ((int) $response[1] === 200) {
                    // Everything is ok, resume sending chunks of 100
                    $last_id = max(array_column($data, 'micro_jps_id'));
                    $limit = 100;
                    $failed = 0;
                } elseif (in_array((int) $response[1], [429, 401])) {
                    sleep(2);
                    $logger->notice("Jackpots MS: Got 429 / 401 response from API");
                    $failed++;
               } else {
                    // Encountered error
                    $logger->info("Jackpots MS: {$response[0]}");

                    // Split the current chunk by 3 and try again
                    if ($limit > 1) $limit = floor($limit / 3);
                    else {
                        // We got stuck on 1 particular row, skip it and move forward
                        $logger->error("Jackpots MS: Could not send micro_jp with id {$data[0]['micro_jps_id']}");
                        $limit = 100;
                        $last_id++;
                        $failed++;
                    }
                }
            } else {
                $logger->error("Jackpots MS: Failed to reach to jackpots api");
                $failed++;
                sleep(1);
            }

            // If we're out of data, failed 3 times or another process has started, exit
        } while (count($data) > 0 && $failed < 3 && (int) phMget('jackpots_cron_pid') === $pid);
    }

    /**
     * Get a chunk of jackpots starting from $last_id up to $limit
     *
     * Note: $brand is optional, however, should be supplied to avoid looking up the
     * config every time this function gets called
     *
     * @param int $last_id
     * @param int $limit
     * @param string|null $brand
     * @return array
     */
    private function getJackpotsChunk(int $last_id, int $limit = 100, string $brand = null): array
    {
        if (empty($brand)) $brand = phive('BrandedConfig')->getBrand();

        $rows = phive('SQL')->loadArray("SELECT * FROM micro_jps WHERE id > {$last_id} ORDER BY id ASC LIMIT {$limit}");
        $data = [];

        foreach ($rows as $jackpot) {
            $data[] = [
                'brand_name'    => $brand,
                'micro_jps_id'  => (int) $jackpot['id'],
                'jurisdiction'  => $jackpot['jurisdiction'],
                'amount'        => (int) $jackpot['jp_value'] ?? 0,
                'game_name'     => $jackpot['jp_name'],
                'game_id'       => $jackpot['game_id'],
                'game_img_url'  => phive('MicroGames')->carouselPic($jackpot),
                'currency'      => $jackpot['currency'],
                'network'       => $jackpot['network']
            ];
        }

        return $data;
    }

    /**
     * Push the wheel of jackpot data to the jackpots microservice
     *
     * @return void
     */
    public function sendWheelOfJackpotsToMicroservice()
    {
        /** @var Redirect $redirect_handler */
        $redirect_handler = phive('Redirect');

        /** @var JpWheel $wheel_handler */
        $wheel_handler = phive('DBUserHandler/JpWheel');

        /** @var Logger $logger */
        $logger = phive('Logger');

        // Get the API Settings
        $api_settings = $redirect_handler->getSetting('jackpots');

        if (empty($api_settings['DOMAIN_URL']) || empty($api_settings['ACCESS_TOKEN'])) {
            $logger->getLogger('cron')->error("DOMAIN_URL or ACCESS_TOKEN configs are missing");
            return;
        }

        $data = $wheel_handler->getWheelDataForApi();
        if (empty($data)) {
            $logger->getLogger('cron')->error("Could not load wheel of jackpot data");
            return;
        }

        $response = $this->post(
            rtrim($api_settings['DOMAIN_URL'], '/') . '/api/woj/wheels',
            ['data' => $data],
            'application/json',
            "Authorization: Bearer $api_settings[ACCESS_TOKEN]",
            '',
            'POST',
            '',
            [],
            'UTF-8',
            true
        );

        if (!is_array($response) || $response[1] !== 200) {
            $logger
                ->getLogger('cron')
                ->error("Jackpot microservice returned error {$response[0]}: " . $response[0]);
        }
    }
}

// Construct singleton
$GLOBALS["phive"] = new Phive();

/**
 * The singleton function.
 *
 * This is the singleton frontend.
 *
 * @link https://en.wikipedia.org/wiki/Singleton_pattern Wikipedia article on the singleton pattern.
 * @param string $module The module name, note that this can be aliased so does not have to correspond to the class name.
 *
 * @return object|bool|Phive The global instance.
 */
function phive($module = 'phive'){
    if(empty($module)){
        return false;
    }

    if(strtolower($module) == 'phive'){
        return $GLOBALS["phive"];
    }else{
        // Possible attempt at breaking out of the phive folder so we abort here.
        if(strpos($module, '.') !== false){
            return false;
        }

        if(strpos($module, '/') === false){
            $module = ucfirst($module);
            return $GLOBALS["phive"]->getModule($module);
        }else{
            // We have a custom class
            $obj = $GLOBALS["phive"]->getModule($module);
            if(!empty($obj))
                return $obj;

            require_once __DIR__ . "/../modules/$module.php";
            $array = explode('/', $module);
            $class = array_pop($array);

            if ( phive('BrandedConfig')->newContainer() ) {
                $GLOBALS["phive"]->addModule($class, $module);
                return $GLOBALS["phive"]->getModule($module);
            }

            $obj = new $class();
            $GLOBALS["phive"]->addModule($obj, $module);
            return $obj;
        }
    }
}

/**
 * The memory DB wrapper.
 *
 * Used for setting and getting key -> values to / from RAM. Theoretically the memory DB handler could be anything,
 * perhaps memcached, perhaps Redis. But in practice the system has started to "prefer" Redis so a move from say Redis
 * to memcached would entail a lot of work. This frontend will anyway help a bit in case such a move is deemed necessary.
 *
 * The function will apply various methods on an instance of the memory handler (Redis).
 *
 * @link http://php.net/manual/en/function.func_get_arg.php func_get_args() at php.net.
 * @link http://php.net/manual/en/function.call_user_func_array.php call_user_func_array() at php.net.
 *
 * @return mixed
 */
function phM(){
    $handler 	= phive()->getSetting('mem_handler');
    $args 	= func_get_args();
    $func 	= array_shift($args);
    $key 	= $args[0];
    $value 	= isset($args[1]) ? $args[1] : '';
    $handler 	= phive($handler);
    try{
        return call_user_func_array(array($handler, $func), $args);
    }catch(Exception $e){
        error_log("Redis fatal error: cmd: $func, args and error ".var_export([func_get_args(), $e->getMessage(), $e->getTraceAsString()], true));
    }
}

/**
 * Wrapper around phM() for storing arrays in memory.
 *
 * @uses phM()
 * @see phM()
 * @see Redis::setJson()
 * @param string $key The key to set.
 * @param array $arr The value to set, will be json encoded before insertion.
 * @param int $expire Expire time in seconds.
 * @param int|DBUser $uid The user id or other number to use, will be used as the "router".
 *
 * @return void
 */
function phMsetArr($key, $arr, $expire = 18000, $uid = null){
    if(!empty($uid)){
        $key = mKey($uid, $key);
    }
    phM('setJson', $key, $arr, $expire);
};

/**
 * Wrapper around phM() for storing strings in memory.
 *
 * @uses phM()
 * @see phM()
 * @see Redis::set()
 * @param string $key The key to set.
 * @param mixed $value The value to set (this will be marshalled to a string if it is not a string, eg a number).
 * @param int $expire Expire time in seconds.
 *
 * @return void
 */
function phMset($key, $value, $expire = 18000) { phM('set', $key, $value, $expire); }

/**
 * Wrapper around phM() for storing strings in memory if they don't already exist.
 *
 * @uses phM()
 * @see phM()
 * @see Redis::setnx()
 * @param string $key The key to set.
 * @param mixed $value The value to set (this will be marshalled to a string if it is not a string, eg a number).
 * @param int $expire Expire time in seconds.
 *
 * @return boolean 1 if value was saved, 0 if value already exists
 */
function phMsetNx($key, $value, $expire = 1) { return phM('setNx', $key, $value, $expire); }
/**
 * Wrapper around phM() for getting data from memory.
 *
 * @uses phM()
 * @see phM()
 * @see Redis::get()
 * @param string $key The key data is stored under.
 * @param int $expire Expire time in seconds.
 *
 * @return string The data.
 */
function phMget($key, $expire = 0)             { return phM('get', $key, $expire); }

/**
 * Wrapper around phM() for getting arrays from memory.
 *
 * @uses phM()
 * @see phM()
 * @see Redis::getJson()
 * @param string $key The key data is stored under.
 * @param int $expire Expire time in seconds.
 * @param int|DBUser $uid The user id or other number to use, will be used as the "router".
 *
 * @return array The stored PHP array.
 */
function phMgetArr($key, $expire = 0, $uid = null){
    if(!empty($uid)){
        $key = mKey($uid, $key);
    }
    return phM('getJson', $key, $expire);
};

/**
 * Wrapper around phM() for deleting data from memory.
 *
 * @uses phM()
 * @see phM()
 * @see Redis::del()
 * @param string $key The key data is stored under.
 *
 * @return void
*/
function phMdel($key) { phM('del', $key); }

/**
 * Wrapper around phM() for increasing values in memory.
 *
 * @uses phM()
 * @see phM()
 * @see Redis::incrby()
 * @param string $key The key value is stored under.
 * @param int $value The amount to increase with.
 * @param int $expire Expire time in seconds.
 *
 * @return void
 */
function phMinc($key, $value, $expire = 18000) { phM('incrby', $key, $value, $expire); }

/**
 * Uses the nutcracker format in order to get data from a specific memory node.
 *
 * @uses phMget()
 * @uses mKey()
 * @uses uid()
 * @see phMget()
 * @see Redis::getClient()
 * @param string $key The key to get data from.
 * @param mixed $uid User id we run modulo on in order to get the correct node. This value will be marshalled into an int for use with the modulo operator.
 * @param int $expire Expire in seconds, in case of 0 we don't set the expire.
 *
 * @return string The data.
 */
function phMgetShard($key, $uid = '', $expire = 0){
    $uid = uid($uid);
    return phMget(mKey($uid, $key), $expire);
}

/**
 * Uses the nutcracker format in order to set data in a specific memory node.
 *
 * @uses uid()
 * @see phMset()
 * @uses mKey()
 * @see Redis::getClient()
 * @param string $key The key to set data under.
 * @param string|array $value The data / value to store.
 * @param int $uid The user id to use.
 * @param int $expire Expire in seconds.
 *
 * @return void
 */
function phMsetShard($key, $value, $uid = '', $expire = 18000){
    $uid = uid($uid);
    $key = mKey($uid, $key);
    return is_array($value) ? phMsetArr($key, $value, $expire) : phMset($key, $value, $expire);
}

/**
 * Delets a key / value on a shard.
 *
 * @param string $key The base key.
 * @param mixed $uid User identifying entity.
 *
 * @return null
 */
function phMdelShard($key, $uid = ''){
    $uid = uid($uid);
    phMdel(mKey($uid, $key));
}

/**
 * Uses a user id (or other fixed int) in order to create a nutcracker key.
 *
 * @link https://github.com/twitter/twemproxy twemproxy (AKA "Nutcracker") on github.
 * @see Redis::getClient()
 * @param int|DBUser $uid The user id or other number to use, will be used as the "router".
 * @param string $key The actual key.
 * @param string $prefix The namespace.
 *
 * @return string The key.
 */
function mKey($uid, $key, $prefix = 'user'){
    if(empty($uid))
        return $key;
    $uid = uid($uid);
    if(empty($uid))
        return $key;

    return phive('Redis')->inClusterMode() ? "$prefix:{{$uid}}:$key" : "$prefix:[$uid]:$key";
}

/**
 * Gets the key part from a nutcracker key.
 *
 * Returns bar if $key is foo:[123]:bar.
 *
 * @param string $key The nutcracker key.
 *
 * @return string The key.
 */
function getMkey($key){
    $res = explode(':', $key);
    return array_pop($res);
}

/**
 * Gets the router part from a nutcracker key.
 *
 * Returns 123 if $key is foo:[123]:bar.
 *
 * @param string $key The nutcracker key.
 *
 * @return int The router number.
 */
function getMuid($key) {
    $regex = phive('Redis')->inClusterMode() ? '|\{(\d+)\}|' : '|\[(\d+)\]|';

    if (preg_match($regex, $key, $m)) {
        return (int)$m[1];
    }

    return false;
}

/**
 * Typically used to store MySQL results in memory.
 *
 * Data (the value) will be serialized and the key will be based on an md5() of the SQL query.
 *
 * @see mCluster()
 * @uses mCluster()
 * @param string $query The SQL query.
 * @param array $value The data.
 * @param int $expire Expire in seconds.
 *
 * @return array The value that was passed in.
 */
function phQset($query, $value, $expire = 18000)	{
    mCluster('qcache')->set(md5($query).".qcache", serialize($value), $expire);
    return $value;
}

/**
 * Typically used to get MySQL results from memory.
 *
 * @see mCluster()
 * @uses mCluster()
 * @param mixed $query The SQL query we want to fetch the cached result from / with.
 *
 * @return array The cached data.
 */
function phQget($query)	{ return unserialize(mCluster('qcache')->get(md5($query).".qcache")); }

/**
 * Deletes query cache results from memory.
 *
 * @see mCluster()
 * @uses mCluster()
 * @param mixed $query The SQL query we want to delete cached data from / with.
 *
 * @return void
 */
function phQdel($query){ mCluster('qcache')->del(md5($query).".qcache"); }

/**
 * Store data in memory with a guaranteed unique key.
 *
 * Used mainly with the phive()->pexec( ... ) logic.
 *
 * @see Phive::pexec()
 * @param array $arr The data to store, will be json encoded.
 * @param int $expire Optional expire time in seconds.
 * @param string $uuid Optional UUID, if not supplied a fresh one will be created.
 *
 * @return string The UUID used to store the data.
 */
function uuidSet($arr, $expire = 60, $uuid = ''){
    $uuid = empty($uuid) ? phive()->uuid() : $uuid;
    mCluster('pexec')->setJson($uuid, $arr, $expire);
    return $uuid;
}

/**
 * Get stored data with an UUID.
 *
 * Used mainly with the phive()->pexec( ... ) logic.
 *
 * @see Phive::pexec()
 * @see uuidSet()
 * @see Redis::getJson()
 * @param string $uuid The UUID memory key to fetch data with.
 *
 * @return array The resultant data in the form of a PHP array.
 */
function uuidGet($uuid){
    if(!phive()->isUuid($uuid))
        return false;
    return mCluster('pexec')->getJson($uuid);
}

/**
* Used by loadJs() and loadCss().
*
* @param string $fname The file name.
*
* @return bool Returns true if the file has already been loaded.
 */
function loadFile($fname){
    if(!empty($GLOBALS['loaded'][$fname]))
        return false;
    return $GLOBALS['loaded'][$fname] = true;
}

/**
 * Return the filename with appended (?xxx) the file last modification time.
 *
 * @link http://php.net/manual/en/function.filemtime.php filemtime() on php.net.
 * @param $fname
 * @return string
 */
function getFileWithCacheBuster($fname)
{
    $filemtime = filemtime(dirname(__FILE__) . '/../..'.$fname);
    return "$fname?$filemtime";
}

/**
 * Loads a JS file.
 *
 * Used to load JS files, adds file change stamp to avoid caching of changed files.
 * Echoes the script src tag.
 *
 * @uses loadFile()
 * @see loadFile()
 * @param string $fname The file to load.
 *
 * @return void
 */
function loadJs($fname){
    if(loadFile($fname)){
        echo '<script type="text/javascript" src="'.getFileWithCacheBuster($fname).'"></script>';
    }
}

/**
 * Loads a CSS file.
 *
 * Used to load Css files, adds file change stamp to avoid caching of changed files.
 * Echoes the link href tag.
 *
 * @uses loadFile()
 * @see loadFile()
 * @param string $fname The file to load.
 *
 * @return void
 */
function loadCss($fname){
    if(loadFile($fname)){
        echo '<link rel="stylesheet" type="text/css" href="'.getFileWithCacheBuster($fname).'" />';
    }
}

/**
 * Gets a different location for branded css files
 *
 * @return string
 */
function brandedCss() {
    return phive()->getSetting("branded_css") ?? '';
}

/**
 * Needed in order to be able to create dynamic content within Localized content.
 *
 * Used with call_user_func_array().
 *
 * @link http://php.net/manual/en/function.call_user_func_array.php call_user_func_array() on php.net.
 * @see Localizer
 * @param int|float $fact1 The first factor.
 * @param int|float $fact2 The second factor.
 *
 * @return number The multiplied factors.
 */
function phMulti($fact1, $fact2){
    return $fact1 * $fact2;
}

/**
 * Needed in order to be able to create dynamic content within Localized content.
 *
 * Used with call_user_func_array().
 *
 * @link http://php.net/manual/en/function.call_user_func_array.php call_user_func_array() on php.net.
 * @see Localizer
 * @param int|float $fact1 The first factor.
 * @param int|float $fact2 The second factor.
 *
 * @return int|float Factor one divided with factor two.
 */
function phDiv($fact1, $fact2){
    return $fact1 / $fact2;
}

/**
 * Wrapper around Phive::lcDate()
 *
 * @uses Phive::lcDate()
 * @see Phive::lcDate()
 * @param string $date A date that needs to be compatible with strtotime().
 * @param bool $echo Echo or not, if true we echo.
 * @param bool $timezone Add site timezone after stamp / date or not, if true: add.
 * @param string $format The strftime() format, default is %x %X
 *
 * @return string The localized date.
 */
function lcDate($date, $echo = true, $timezone = true, $format = '%x %X'){
    $res = phive()->lcDate($date, $format);
    if(empty($res)){
        $timezone = false;
        $res = t('na');
    }
    if($timezone)
        $res .= ' '.t('cur.timezone');
    if($echo)
        echo $res;
    else
        return $res;
}

/**
 * Wrapper around a Phive.config.php setting.
 *
 * @return bool True if site is websocket enabled, false otherwise.
 */
function hasWs(){
    return phive()->settingExists('websockets');
}

/**
 * Sends websocket messages / updates.
 *
 * Used to send real time information to clients from the server via websockets.
 *
 * @see Ws::send()
 * @uses Ws::send()
 * @param array $arr The data to send, will be JSON encoded and decoded to JS object in the client.
 * @param string $tag The tag / channel to use.
 * @param int|string $uid The receiving user / player id OR session id, use na to send to all players. Default null to prevent
 * blanket updates from going out to all players.
 *
 * @return void|bool
 */
function toWs($arr, $tag, $uid = null){
    if(!hasWs())
        return false;
    if(empty($uid))
        return false;
    if(!empty($uid) && $uid != 'na'){
        $sid = is_numeric($uid) ? getSid($uid) : $uid;
        phive('Logger')->getLogger('web_sockets')->debug('toWS()', [
            'user_id' => $uid,
            'session_id' => $sid,
            'channel' => $tag,
            'message' => $arr,
        ]);
        if(empty($sid))
            return false;
    }
    phive()->loadApi('ws');
    Ws::send($arr, $sid, $tag);
}

/**
 * Takes a user id and returns that user's current session ID if any exists.
 *
 * @param int|DBUser $uid The user id.
 *
 * @return string The session id.
 */
function getSid($uid)
{
    if (is_object($uid)) {
        $uid = $uid->getId();
    }
    $res = mCluster('uaccess')->get(mKey($uid, 'uaccess'));
    return empty($res) ? $res : explode(':', $res)[0];
}

/**
 * We store the session timeout that can be different per jurisdiction along with the session id on memory/Redis
 * with the format XXXXXX:1000 where 1000 is the timeout in seconds.
 *
 * getSessionTimeout will give you the setting multiplied by 2, so the redis one we set is as / 2
 *
 * @param string $session_id The session id.
 * @param int $user_id The user id
 * @param DBUser|null $user The user object in case we have it available for the lic function
 */
function setSid($session_id, $user_id, $user = null)
{
    $timeout = lic('getSessionTimeout', [], $user);
    $to_redis = $session_id . ':' . ((int)$timeout / 2);

    phM('expire', mKey($user_id, 'session'), $timeout);
    mCluster('uaccess')->set(mKey($user_id, 'uaccess'), $to_redis, $timeout);
}

/**
* Wrapper around $GLOBALS['site_type']
*
* @return string The site type, mobile or normal / not set.
*/
function siteType(){
  return $GLOBALS['site_type'];
}

/**
* Checks if the site type is mobile.
*
* @return bool Returns true if we're on the mobile site.
*/
function isMobileSite(){
    return siteType() === 'mobile';
}

/**
* Making a string work as a command line argument without escaping.
*
* @param string $arg The argument.
*
* @return string The argument with spaces and single quotes removed.
*/
function cleanShellArg($arg){
    return str_replace([' ', "'"], '', $arg);
}

/**
* Controls if system should show Battle of Slots.
*
* @param string $echo_if_has Echo this string if BoS is showing.
*
* @return bool True if BoS should show, false otherwise.
*/
function hasMp($echo_if_has = ''){
    if(phive()->moduleExists('Tournament')){
        if(phive('Config')->getValue('mp', 'show') === 'yes' && !empty(licSetting('show_bos'))){
            if(isCli())
                return true;
            if(!p('tournament.view') && phive('Config')->getValue('mp', 'permission') === 'yes')
                return false;
            echo $echo_if_has;
            return true;
        }
    }
    return false;
}

/**
 * Check if a given ip is in a network
 *
 * @link https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing Wikipedia CIDR article.
 * @link https://gist.github.com/tott/7684443 The gist.
 * @param string $ip    IP to check in IPV4 format eg. 127.0.0.1
 * @param string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
 * @return boolean true if the ip is in this range, false if not.
 */
function ip_in_range( $ip, $range ) {
    if ( strpos( $range, '/' ) == false )
        $range .= '/32';
    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_decimal = ip2long( $range );
    $ip_decimal = ip2long( $ip );
    $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

/**
 * Can be used to check whether or not the site supports mobile BoS / mp / tournaments.
 *
 * @return bool True if support is there, false otherwise.
 */
function hasMobileMp()
{
    if(phive()->moduleExists('Tournament')){
        if(phive('Config')->getValue('mobile_mp', 'show') === 'yes' && !empty(licSetting('show_bos'))){
            if(isCli())
                return true;
            if(!p('tournament.mobile.view') && phive('Config')->getValue('mobile_mp', 'permission') === 'yes')
                return false;
            return true;
        }
    }
    return false;
}

/**
 * Gets the client IP.
 *
 * Note, this method **MUST** be used, $_SERVER['REMOTE_ADDR'] can not be trusted due to various
 * friendly proxies that might or might not be used by the client.
 *
 * @return string The client IP.
 */
function remIp(){
    if(!empty($_SERVER['HTTP_TRUE_CLIENT_IP'])) {
        phive('Logger')->getLogger('rem_ip')->debug("remIp HTTP_TRUE_CLIENT_IP", [
            "trueClientIp" => $_SERVER['HTTP_TRUE_CLIENT_IP'],
            "remoteAddr" => $_SERVER['REMOTE_ADDR']
        ]);
        return $_SERVER['HTTP_TRUE_CLIENT_IP'];
    }

    //Do we have haproxy, if that is the case, check if the proxy ip is in our allowed list, if it is use forwarded for
    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($_SERVER['REMOTE_ADDR'], (array)phive()->getSetting('proxy_ips'))) {
        phive('Logger')->getLogger('rem_ip')->debug("remIp HTTP_X_FORWARDED_FOR", [
            "httpXForwardedFor" => $_SERVER['HTTP_X_FORWARDED_FOR'],
            "remoteAddr" => $_SERVER['REMOTE_ADDR'],
            "confProxyIps" => phive()->getSetting('proxy_ips')
        ]);
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    //We have a completely un-proxied request
    if(empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        phive('Logger')->getLogger('rem_ip')->debug("remIp when HTTP_CF_CONNECTING_IP missing", [
            "remoteAddr" => $_SERVER['REMOTE_ADDR']
        ]);
        // log the entire server object and URL when both REMOTE_ADDR and HTTP_CF_CONNECTING_IP are null.
        if (empty($_SERVER['REMOTE_ADDR'])) {
            phive('Logger')->getLogger('rem_ip')->debug("remIp  HTTP_CF_CONNECTING_IP missing extra ", [
                "server" => $_SERVER,
                "requestUrl" => 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}"
            ]);
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    $cf_ip_ranges = array(
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '104.16.0.0/13',
        '108.162.192.0/18',
        '131.0.72.0/22',
        '141.101.64.0/18',
        '162.158.0.0/15',
        '172.64.0.0/13',
        '173.245.48.0/20',
        '188.114.96.0/20',
        '190.93.240.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '104.24.0.0/14'
    );

    //Preventing spoofing of the CF header
    foreach($cf_ip_ranges as $range) {
        if (ip_in_range($_SERVER['REMOTE_ADDR'], $range)) {
            phive('Logger')->getLogger('rem_ip')->debug("remIp REMOTE_ADDR::HTTP_CF_CONNECTING_IP", [
                "httpCfConnectingIp" => $_SERVER['HTTP_CF_CONNECTING_IP'],
                "remoteAddr" => $_SERVER['REMOTE_ADDR'],
                "range" => $range
            ]);
            return $_SERVER['HTTP_CF_PSEUDO_IPV4'] ?? $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
    }

    phive('Logger')->getLogger('rem_ip')->debug("remIp REMOTE_ADDR", ["remoteAddr" => $_SERVER['REMOTE_ADDR']]);
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Really returns empty in all cases.
 *
 * Due to some newfangled behaviour in some newer version of PHP we can now end up with some kind of
 * NaN type (division by zero perhaps?), this is the way of keeping empty() up to speed with that development.
 *
 * @param mixed $var The variable to check if it is empty.
 *
 * @return bool True if empty, false otherwise.
 */
function empty2($var){
    if(is_nan($var))
        return true;
    return empty($var);
}

/**
* Checks if we're running script on command line or not.
*
* Sometimes we want to make sure a script is running in CLI mode, this is the way to do that.
*
* @return bool True if we're in CLI mode, false otherwise.
*/
function isCli() {
    if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks registration mode of a website. Is PayNPlay or not
 * @param null $user
 * @param null $country
 * @return bool
 */
function isPNP($user = null, $country = null): bool {
    return registrationMode($user, $country) === 'paynplay';
}

/**
 * Checks registration mode of a website. Is OneStep or not
 * @return bool
 */
function isOneStep(): bool {
    return registrationMode() === 'onestep';
}

/**
 * Checks registration mode of a website. Is BankId or not
 * @return bool
 */
function isBankIdMode(): bool {
    return registrationMode() === 'bankid';
}

/**
 * Checks registration mode and returns it's value as string
 * @param null $user
 * @param null $country
 * @return string
 */
function registrationMode($user = null, $country = null):string {
    $registration_mode = lic('getLicSetting', ['registration_mode'], $user ?? cu(), null, $country);

    if (!is_null($registration_mode)) {
        return $registration_mode;
    }

    return 'default';
}

/**
 * setCookieSecure: Wrapper for PHP default setcookie, with default values for security (Secure+httpOnly)
 * @param $key
 * @param $value
 * @param $expire default 30days
 * @param $path default '/'
 * @param $domain default phive()->getSetting('full_domain')
 * @param bool $secure default null (it use the param from the config, but if we pass true/false it can override it)
 * @param bool $httpOnly default null (it use the param from the config, but if we pass true/false it can override it)
 */
function setCookieSecure($key, $value, $expire = false, $path = null, $domain = null, $secure = null, $httponly = null)
{
    // it use the param from the config, but if we pass true/false it can override it, if still empty from the config it will default to false
    if ($secure === null || !is_bool($secure)) {
        $secure = phive()->getSetting('cookie_secure');
        if (empty($secure)) {
            $secure = false;
        }
    }
    if ($httponly === null || !is_bool($httponly)) {
        $httponly = !phive()->getSetting('disable_cookie_httpOnly'); // we want it always enabled by default (settings default to null/false)
    }

    if (!$expire) {
        $expire = time() + 2592000; // 30days // 60*60*24*30
    }
    if (!$path) {
        $path = '/';
    }
    if (!$domain) {
        $domain = getCookieDomain();
    }
    $params = [
        'expires' => $expire,
        'path' => $path,
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => phive()->getSetting('cookie_samesite')
    ];
    setcookie($key, $value, $params);
}

/**
 * Gets the cookie domain for the site.
 *
 * @param bool|string $otherCookieDomain If string and not empty this domain will simply override.
 *
 * @return string The cookie domain as set in the Phive config.
 */
function getCookieDomain($otherCookieDomain=false) {
    // set a different cookie domain
    if($otherCookieDomain) {
        return $otherCookieDomain;
    }
    // get cookie domain from config
    if(phive()->getSetting('cookie_domain')) {
        return phive()->getSetting('cookie_domain');
    }
    // fallback using the current domain (H. code review)
    return '.'.strtolower(phive()->getSetting('domain'));
}

/**
 * Not used, removed on the cleanup branch.
 *
 * @param string $type
 *
 * @return null
 */
function getCsrfField($type = 'form') {
    switch ($type) {
        case 'tokenOnly':
            return SecurityApi::GetCsrf();
            break;
        case 'meta':
            return SecurityApi::GenerateCsrfMeta();
            break;
        case 'form':
        default:
            return SecurityApi::GenerateCsrfField();
            break;

    }
}

/**
 * License helper function for user instances.
 *
 * @see Licensed::doLicense() for more thourough documentation / information.
 *
 * @param string $func The function to call.
 * @param array $params The params to call the function with.
 * @param DBUser $u The user object.
 * @param object $default_obj A fallback instance in case the routing doesn't return an ISO2 instance.
 * @param string $country ISO code of a country in instances where we execute lic in bulk and don't want to do cu on each occurrence but we have the country available via query fex.
 * @return mixed Whatever $func is returning.
 */
function lic($func, $params = [], $u = null, $default_obj = null, $country = null)
{
    if (!phive()->moduleExists('Licensed')) {
        return false;
    }

    $country = $country ?? phive('Licensed')->getLicCountryProvince(cu($u));
    if (phive('Licensed')->isActive($country)) {
        return phive('Licensed')->doLicense($country, $func, $params, $default_obj);
    }
    return phive('Licensed')->doLicense(phive('Licensed')->getBaseJurisdiction(), $func, $params);
}

/**
 * Executes lic or a closure we are looking here into avoid conditionals over lic functions. Note that the empty check
 * might cause issues in the future and maybe returning something more adequate from doLicense is required
 *
 * Example:
 * $user = cu('devtestse');
 * $res = licOrFunc('hasDepositPopup', function () use ($user) {
 *     return $user->hasSetting('show_deposit_popup');
 * });
 *
 * @param $func
 * @param $closure
 * @param array $params
 * @param null $u
 * @return bool|mixed
 */
function licOrFunc($func, $closure, $params = [], $u = null)
{
    $lic_res = lic($func, $params, $u);
    return empty($lic_res) ? $closure(): $lic_res;
}

/**
 * Gets the jurisdiction for a country.
 * In the future it might be conceivable that some countries go under the same jurisdictional logic.
 * If we don't have any License file for that jurisdiction, return MT as default jurisdiction.
 *
 * @param DBUser $u The user object.
 *
 * @return string The name of the jurisdiction for this country
 */
function licJur($u = null)
{
    if (!phive()->moduleExists('Licensed')) {
        return false;
    }
    $u = cu($u);
    $country = phive('Licensed')->getLicCountryProvince($u);
    return licJurFromCountry($country);
}

/**
 * Gets the license jurisdiction from the country, if the country in question is
 * not specifically regulated the default jurisdiction will be returned, eg MT.
 *
 * @param string $country The ISO2 country code.
 *
 * @return string The ISO2 code of the jurisdiction or the country.
 */
function licJurFromCountry($country)
{
    return phive('Licensed')->isActive($country) ? $country : phive('Licensed')->getBaseJurisdiction();
}

/**
 * Gets the license jurisdiction for the user, returns the user's country if unlicensed.
 *
 * @param DBUser|null $u The user object.
 *
 * @return string The ISO2 code of the jurisdiction or the country.
 */
function licJurOrCountry($u = null)
{
    if (!phive()->moduleExists('Licensed')) {
        $country = cuCountry();
    } else {
        $country = phive('Licensed')->getLicCountry($u);
    }

    return $country;
}

/**
 * Wrapper for a common line to shorten it a bit.
 *
 * @param string $setting The setting to check by way of Licensed::getLicSetting()
 * @param DBUser $u The user object.
 *
 * @return mixed The config setting.
 */
function licSetting($setting, $u = null){
    return lic('getLicSetting', [$setting], $u);
}


/**
 * License helper function for instances where there are several countries to check on a cronjob for example
 *
 * @param $func
 * @param array $params
 * @return array|bool
 */
function lics($func, $params = [])
{
    if (!phive()->moduleExists('Licensed')) {
        return false;
    }
    $ret = [];
    foreach (phive('Licensed')->getSetting('licensed_countries') as $country) {
        if (phive('Licensed')->isActive($country)) {
            $ret[] = phive('Licensed')->doLicense($country, $func, $params);
        }
    }
    return $ret;
}

/**
 * Remove accent and special characters.
 *
 * @param $string The string to remove from.
 * @return string The result string.
 */
function removeSpecialCharacter($string)
{
    //remove the accents
    $clean_string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

    //remove special characters
    $clean_string = trim(preg_replace("/[^a-zA-Z]/", "", strtolower($clean_string)));

    return $clean_string;
}

/**
 * Debug function do dump and die, specially useful on cli commands
 *
 * @param mixed ...$args
 */
function dd2(... $args)
{
    foreach ($args as $x) {
        var_dump($x);
    }
    die();
}

/**
* Can be used in an HTML context to print_r with proper indentation etc.
*
* @param mixed ...$args Args to print.
*
* @return null
*/
function prePr(... $args){
    echo '<pre>';
    foreach ($args as $x) {
        print_r($x);
    }
    echo '</pre>';
}

/**
 * Global function to have IDE autocompletion available
 *
 * @param $tag
 * @param mixed ...$args
 */
function dumpTbl($tag, ... $args)
{
    phive()->dumpTbl($tag, $args);
}

/**
* A wrapper around number_format to convert whole cents into other formats.
*
* @param int $amount The cents amount.
* @param bool $return Whether to return or not.
* @param int $divide_by What to divide by (typically 100 but depending on the situation).
* @param string $dec_point The decimal point, typically .
* @param string $thousands_sep The thousands separator, typically ,
*
* @return string|null The formatted number, ex: 1055678 cents becomes 10,556.78 whole currency units, or null in case we weant echoing.
*/
function nf2($amount, $return = false, $divide_by = 1, $dec_point = '.', $thousands_sep = ','){
    if(!is_numeric($amount))
        return $amount;
    $amount = number_format($amount / $divide_by, 2, $dec_point, $thousands_sep);
    if($return)
        return $amount;
    else
        echo $amount;
}

/**
* Wraps nf() by way of the common division by 100 scenario.
*
* @param int $cents The cents amount.
* @param bool $return Whether to return or not.
* @param string $dec_point The decimal point, typically .
* @param string $thousands_sep The thousands separator, typically ,
*
* @return string|null The formatted number, ex: 1055678 cents becomes 10,556.78 whole currency units, or null in case we weant echoing.
*/
function nfCents($cents, $return = false, $dec_point = '.', $thousands_sep = ','){
    if(empty($cents))
        $cents = 0;
    return nf2($cents, $return, 100, $dec_point, $thousands_sep);
}

/**
* Wraps nfCents() by way of the common division by 100 scenario that needs to be returned.
*
* @param int $cents The cents amount.
* @param string $dec_point The decimal point, typically .
* @param string $thousands_sep The thousands separator, typically ,
*
* @return string The formatted number, ex: 1055678 cents becomes 10,556.78 whole currency units.
*/
function rnfCents($cents, $dec_point = '.', $thousands_sep = ','){
    return nfCents($cents, true, $dec_point, $thousands_sep);
}

/**
 * when the "rc-testing" setting is enabled this will show the RC popup if the logic of the page require that.
 * This work only for the "videoslots" popup
 * Ex.
 * - SE player will see this on any page
 * - ROW with RC will see this on game page only
 * - ROW without RC will never see the popup.
 */
function showRCPopupFromTestParam()
{
    if (phive('MicroGames')->getSetting('rc-testing')) {
        ?>
        <!-- This will be used to trigger the rc popup in one second -->
        <div style="display: none" id="rc-testing-button"></div>
        <?php
    }
}

/**
 * Global function to check if an IP is whitelisted
 *
 * @param string|null $ip
 * @return bool
 */
function isWhitelistedIp(string $ip = null): bool {
        $memKey = "global-whitelisted-ips";
        $ips = phMgetArr($memKey);
        $ip = $ip ?: remIp();

        if (empty($ips)) {
            $db_whitelisted_ips = phive('IpGuard')->getWhitelistedIps();
            $reg_whitelisted_ips = phive('DBUserHandler')->getSetting('whitelisted_reg_ips');
            $ips = array_merge($db_whitelisted_ips, $reg_whitelisted_ips);

            phMsetArr($memKey, $ips, 3600);
        }

        return in_array($ip, $ips);
}

/**
 * Keep in the session the number of attempts made to ajax requests on the registration form to avoid enumerating attack
 * check if the number of attempts between the first request and $timeBeforeClear is < $numberOfAttempts
 *
 * Timeout default to 1200 and attempts default to 20.
 *
 * No active if config not present.
 *
 * @param string $key check name in place (Ex. check_username_local)
 * @param string $attribute
 * @param int|null $attempts Override number of attempts on the config
 * @param bool $per_ip should prepend request IP to $key
 * @return bool
 */
function limitAttempts($key, $attribute = '', $attempts = null, bool $per_ip = true) {

    $config =  phive()->getSetting('rate_limiting');
    if(empty($config) || isWhitelistedIp()) {
        return false;
    }
    if (!empty($config[$key])) {
        $config = $config[$key];
    }

    $ttl = !empty($config['timeout']) ? $config['timeout'] : 1200;
    if (empty($attempts)) {
        $n_attempts = !empty($config['attempts']) ? $config['attempts'] : 20;
    } else {
        $n_attempts = $attempts;
    }

    /** @var Redis $redis */
    $redis = mCluster('uaccess');
	$key = getLimitAttemptKey($key, $per_ip);

    $attempts_made = $redis->get($key);
    if (empty($attempts_made)) {
        $attempts_made = 1;
        $redis->set($key, 1, $ttl);
    } else {
        $redis->incr($key);
    }
    phive()->dumpTbl("enumeration", ['made' => $attempts_made, 'key' => $key, 'current_ttl' => $redis->ttl($key), 'attr' => $attribute, [$n_attempts, $ttl]]);
    return $attempts_made >= $n_attempts;
}

	/**
	 * Get the number of attempts made to requests on the form to avoid enumerating attack
	 *
	 * @param string $key check name in place (Ex. check_username_local)
	 * @return int
	 */
	function getLimitAttemptCount(string $key): int
	{
		/** @var Redis $redis */
		$redis = mCluster('uaccess');
	    $key = getLimitAttemptKey($key);

		return $redis->get($key) ?? 0;
	}

	/**
	 * Unset the number of attempts made to request on the form to avoid enumerating attack
	 *
	 * @param string $key check name in place (Ex. check_username_local)
	 * @return bool
	 */
	function unsetLimitAttemptCount(string $key): bool
	{
		/** @var Redis $redis */
		$redis = mCluster('uaccess');
		$key = getLimitAttemptKey($key);

        return $redis->del($key);
	}

	/**
	 * Get the key for storing attempts made to request on the form
	 *
	 * @param string $key check name in place (Ex. check_username_local)
	 * @param bool $per_ip should prepend request IP to $key
	 * @return string
	 */
	function getLimitAttemptKey(string $key, bool $per_ip = true): string
	{
		return $per_ip ? remIp() . $key : $key;
	}

/**
 * Will print an hidden input field containing the fingerprint of the device stored in the COOKIE.
 *
 * If the COOKIE doesn't exist it will load Adyen JS lib to create the FP using "dfDo(INPUT_ID)"
 * to populate the value of hidden input and create the cookie
 * It will attempt to generate the cookie for max 5sec before failing (0.5s x 10 attempt)
 *
 * The FP will be added to all AJAX calls as an extra header "X-DEVICE-FP" (like X-CSRF-TOKEN).
 * this should always work except in the following 2 scenarios:
 * - FP not being generated by the Adyen lib (empty value)
 * - AJAX requests done before the FP is generated on the first page load, it usually takes < 1sec.
 *
 * @param bool $force If false we don't do fingerprint unless the current page is a certain page that must have fingerprint.
 *
 */
function generateFingerprint($force = false)
{
    if (empty($_COOKIE['device-fingerprint'])) {
        ?>
        <?php
        $print_fingerprint = false;
        if (empty($force)) {
            $pages_requiring_fingerprint = phive('DBUserHandler')->getSetting('pages_requiring_fingerprint', ['login', 'register', 'registration']);
            foreach ($pages_requiring_fingerprint as $page) {
                $print_fingerprint = strpos(phive('Pager')->cur_path, $page) !== false;
                if (!empty($print_fingerprint)) {
                    break;
                }
            }
        }
        if (empty($print_fingerprint) && empty($force)) {
            return;
        }
        ?>
        <script type="text/javascript" src="https://live.adyen.com/hpp/js/df.js?v=<?= time() ?>"></script>
        <script>
            function waitFpCreationAndSetCookie() {
                var cookie;
                var maxTries = 10;
                var waitForFp = setInterval(function () {
                    if (maxTries < 1 || !empty(cookie = getFingerprint())) {
                        clearInterval(waitForFp);
                        if (!empty(cookie)) {
                            sCookie('device-fingerprint', cookie);
                        }
                    } else {
                        maxTries--;
                    }
                }, 500);
            }

            try {
                // Standard way to create FP, work fine on page load only
                dfDo("device-fingerprint");
                waitFpCreationAndSetCookie();
            } catch (err) {
                setTimeout(function() {
                    // Internal FP lib function to generate FP after page load is complete. (Ex. login modal)
                    dfSet(document.getElementById('device-fingerprint'), 0);
                    waitFpCreationAndSetCookie();
                }, 2000);
            }
        </script>
        <?php
    } else {
        ?>
        <input type="hidden" id="device-fingerprint" value="<?= htmlspecialchars($_COOKIE['device-fingerprint']) ?>"/>
        <?php
    }
}

/**
 * Check if external tracking is active for 3rd parties
 *
 * @return bool
 */
function isExternalTrackingEnabled()
{
    return !phive()->isLocal() && phive()->getSetting('google_tag_manager') === true;
}

/**
 * Check if ga4 is active for 3rd parties
 *
 * @return bool
 */
function isGoogleAnalytic4Enabled()
{
    return !phive()->isLocal() && phive()->getSetting('enable_google_analytics_4') === true;
}

/**
 * Removes the trailing zeros from the passed value and
 * performs arithmetic Operation(unary +) to implicitly type conversion.
 * Ex:
 *     "87.00" => 87
 *     "126.02" => 126.02
 *     "0.0000" => 0
 *     "37.5"  => 37.5
 *
 * @param string $remove_trailing_zeros
 * @return int|float
 */
function removeTrailingZeros($value)
{
    return +rtrim(rtrim($value, '0'), '.');
}

/**
 * Function removes the trailing zeros from passed rtp value and
 * checks if return value is float or not,
 * if float then uses the nf2 function to return the formatted number.
 *
 * @param string $rtp
 * @return int|float
 */
function formatRTP($rtp)
{
    $rtp = removeTrailingZeros($rtp);
    return is_float($rtp) ? nf2($rtp, true) : $rtp;
}

/**
 * @return void
 */
if(!function_exists('csrf_input')) {
    function csrf_input(): void
    {
        try {
            $token = (new \FormerLibrary\CSRF\CsrfToken())->generate();
            echo '<input type="hidden" id="csrf_token" name="csrf_token" value="' . $token . '">';
        } catch (\Exception $exception) {
            // Will result in no input field. This should not happen though.
            phive()->dumpTbl('Csrf_token', 'Token could not be create '. $exception->getMessage());
        }
    }
}

function addCacheHeaders($cache_type)
{
        if (($cache_type === "nocache")
            || (!in_array($_GET['func'], phive()->getSetting("cache_excluded_functions"))
            && (!in_array($_GET['start_format'], phive()->getSetting("cache_excluded_formats"))
            && !in_array($_GET['show'], phive()->getSetting("cache_excluded_show"))))
        ) {
        header(phive()->getSetting("cf_cache_" . $cache_type . "_1"));
        header(phive()->getSetting("cf_cache_" . $cache_type . "_2"));
    }
}

/**
 * Verify if the brand is still using the old design.
 *
 * @return bool Returns true if the new old design is still used, false otherwise.
 */
function useOldDesign()
{
    return phive()->getSetting('old_design');
}

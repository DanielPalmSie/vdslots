<?php
require_once __DIR__ . '/PhConfigurable.php';
require_once __DIR__ . '/PhMessage.php';

// TODO henrik remove
define("REQUIRED", 1);
// TODO henrik remove
define("OPTIONAL", 2);

/**
 * The basic abstract class that expands on PhConfigurable and which all modules extend from.
 * It contains common logic to all modules.
 */
abstract class PhModule extends PhConfigurable{
    // TODO henrik remove
    protected $using = [];

    // TODO henrik remove
    private $using_links = [];

    /**
     * Returns an array of aliases for the module in question, this is how we can
     * phive('Cashier') to load CasinoCashier for instance, because Cashier is an alias
     * of CasinoCashier.
     *
     * @return array The array of aliases.
     */
    function phAliases(){
        return [];
    }

    /**
     * An initialization method that is being run in addition to the constructor.
     * Was traditionally used to load a GUI for installing Phive in case the modules.php
     *
     * @return null
     */
    function phInstall(){}

    /**
     * Just a wrapper around get_class().
     *
     * @return string The module / class name.
     */
    function getModuleName(){ return get_class($this); }

    // TODO henrik remove
    function pEdit($p = ''){
        if(!$this->hasPermission(empty($p) ? $this->edit_p : $p) && !isCli()){
            echo 'No permission';
            exit;
        }
    }

    // TODO henrik remove this, refactor calling code
    function setDb($db = null, $is_shard = false){
        if(empty($db)){
            $this->db = phive('SQL');
            $is_shard = false;
        }else{
            $this->db = $db;
            $this->is_shard = $is_shard;
        }
        return $this;
    }

    // TODO henrik remove
    function setupUsingLinks(){
        if(empty($GLOBALS['available_modules'])){
            $this->using_links = array();
            foreach ($this->using as $module=>$type){
	        $realmodule = phive()->moduleExists($module);
	        if ($realmodule !== false)
	            $this->using_links[$module] = $realmodule;
            }
        }else{
            $this->using_links = $GLOBALS['sub_classed'];
        }
    }

    /**
     * Common XHR logic, if we're in an XHR context we just echo and die, otherwise we return.
     *
     * @param array $arr The data to JSON encode.
     * @param bool $ajax True if we are to echo and die, false otherwise.
     *
     * @return string JSON string.
     */
    function retJsonOrDie($arr, $ajax = false){
        if($ajax)
            die(json_encode($arr));
        else
            return json_encode($arr);
    }


    // TODO henrik remove
    function retArgOrDie($var, $ajax = false){
        if($ajax)
            die($var);
        else
            return $var;
    }

    /**
     * Common XHR logic, if we're in an XHR context we just echo and die, otherwise we return.
     *
     * @uses t() In order to translate the passed in alias.
     * @see t()
     *
     * @param bool $ajax True if we are to echo and die, false otherwise.
     * @param string $txt The localized string alias to translate.
     *
     * @return string JSON string.
     */
    function retOrDie($ajax, $txt){
        if($ajax)
            die(t($txt));
        else
            return t($txt);
    }

    // TODO henrik remove
    function isTest(){
        return $this->getSetting('test') === true;
    }

    /**
     * Wrapper round dumpTbl().
     *
     * @param string $tag The log tag.
     * @param mixed $var The data to log.
     * @param ?int $uid User id.
     * @see  Phive::dumpTbl()
     *
     * @uses Phive::dumpTbl()
     */
    function dumpLog($tag, $var = '', $uid = 0){
        if($this->getSetting('dump_debug') === true){
            phive()->dumpTbl($tag, $var, $uid);
        }
    }

    /**
     * Returns an empty string which should prevent the caller from logging in case logging is turned off in the config.
     *
     * @param string $key Log / debug key.
     *
     * @return string The passed in log / debug key in case debugging is turned on, empty string otherwise.
     */
    public function extCallDebug($key){
        return $this->getSetting('extcall_debug') === true ? $key : '';
    }

    // TODO henrik remove
    function getUsing(){ return $this->using; }

    // TODO henrik remove
    function getById($id){
        return phive('SQL')->loadAssoc("SELECT * FROM {$this->tbl} WHERE id = ".intval($id));
    }

    /**
     * Returns a success structure.
     *
     * @param mixed $result The result data.
     *
     * @return array The success structure.
     */
    public function success($result)
    {
        return ['success' => true, 'result' => $result];
    }

    /**
     * Wraps success()
     *
     * @uses PhModule::success()
     * @see PhModule::success()
     *
     * @param mixed $result The result data.
     *
     * @return string The JSON encoded success data.
     */
    public function jsonSuccess($result)
    {
        return json_encode($this->success($result));
    }

    /**
     * Returns a fail structure.
     *
     * @param mixed $result The result data.
     *
     * @return array The fail structure.
     */
    public function fail($result)
    {
        return ['success' => false, 'result' => $result];
    }

    /**
     * Wraps fail()
     *
     * @uses PhModule::fail()
     * @see PhModule::fail()
     *
     * @param mixed $result The result data.
     *
     * @return string The JSON encoded fail data.
     */
    public function jsonFail($result)
    {
        return json_encode($this->fail($result));
    }
}

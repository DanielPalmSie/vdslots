<?php
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/Box.php';
define("ALL_CONTAINERS", '__ALL_CONTAINERS__');
class BoxHandler extends PhModule {
    /**
     * @var int
     */
    public const MENU_BOX_MOBILE = 937;

  public function __construct() {
    $this->canCheckSyntax = true;
    //$this->canCheckSyntax = $this->checkFileSyntax(__FILE__);
  }

  public function getCompatibleBoxesFor($container, $checkValidity = false){
    return $this->getAllAvailableBoxes();
  }

  public function getAllAvailableBoxes(){
    $main_page_id = phive('Pager')->getPageByAlias('.', 0)['page_id'];
    $current_page_id = phive('Pager')->page_id;
    $excepted_boxes = ['AccountBox.php'];
    $path = $this->getDomainSetting('BOX_PATH');
    $dh = opendir($path);
    $classes = array();
    while(false !== ($file = readdir($dh))){
      if(is_file($path.'/'.$file)){
        if (in_array($file, $excepted_boxes) && $main_page_id == $current_page_id) continue;

        $classes[] = basename($file, '.php');
      }
    }
    closedir($dh);
    sort($classes);
    return array_unique($classes);
  }

  function arrToBoxes($arr, $checkValidity){
    $boxes = array();
    foreach($arr as $row){
      $box = $this->getNewBox($row['box_class'], $row['box_id'], $checkValidity);
      $box->container = $row['container'];
      $boxes[] = $box;
    }
    return $boxes;
  }

  function getBoxesInPageAsArr($page_id_esc){
    return phive('SQL')->loadArray("SELECT * FROM boxes WHERE page_id = $page_id_esc ORDER BY priority ASC");
  }

  function getDanglingBoxes(){
    return phive("SQL")->loadArray("SELECT * FROM boxes WHERE page_id NOT IN(SELECT page_id FROM pages)");
  }

  function purgeDanglingBoxes(){
    $boxes = $this->getDanglingBoxes();
    foreach($boxes as $b){
      print_r($b);
      $this->purgeBox($b['box_id']);
    }
    phive('SQL')->query("DELETE FROM boxes_attributes WHERE box_id NOT IN (SELECT box_id FROM boxes)");
    return count($boxes);
  }

  public function getBoxesInPage($page_id, $checkValidity = false, $respect_pos = true){
    $box_db = $this->getSetting("DB_PAGEBOXES");
      $page_id_esc = (int)$page_id;

    if($respect_pos){
      phive("SQL")->query("SELECT DISTINCT(container) FROM $box_db WHERE page_id = $page_id_esc");
      $containers = phive("SQL")->fetchArray();
      $ans = array();
      foreach($containers as $container){
	$container = $container['container'];
	phive("SQL")->query("SELECT * FROM $box_db WHERE container = '$container' AND page_id = $page_id_esc ORDER BY  priority ASC");
	$ans[$container] = $this->arrToBoxes(phive("SQL")->fetchArray(), $checkValidity);
      }
      return $ans;
    }else{
      $barr = $this->getBoxesInPageAsArr($page_id_esc);
      return $this->arrToBoxes($barr, $checkValidity);
    }
  }

  public function getAllUsedContainers(){
    $box_db = $this->getSetting("DB_PAGEBOXES");
    phive("SQL")->query("SELECT DISTINCT(container) FROM $box_db");
    $ans = array();
    $res = phive("SQL")->fetchArray();
    foreach($res as $row)
      array_push($ans, $row['container']);
    return $ans;
  }

  function _move($box_id, $cmp){
      $box_id_esc = (int)$box_id;
    $dbt = $this->getSetting("DB_PAGEBOXES");
    if($cmp == '>')
      $order = 'ASC';
    else if($cmp == '<')
      $order = 'DESC';

    phive("SQL")->query("SELECT * FROM $dbt WHERE box_id = $box_id_esc");
    $target = phive("SQL")->fetch();

    if($target !== FALSE){
      phive("SQL")->query("
        SELECT * FROM $dbt
        WHERE container = '{$target['container']}'
        AND page_id = {$target['page_id']}
        AND priority $cmp {$target['priority']}
        ORDER BY priority $order LIMIT 1");
      $switcher = phive("SQL")->fetch();
    }

    if($target !== false && $switcher !== false){
      $do_ok = phive("SQL")->query("UPDATE $dbt SET priority = {$target['priority']} WHERE box_id = {$switcher['box_id']}");
      $do_ok = phive("SQL")->query("UPDATE $dbt SET priority = {$switcher['priority']} WHERE box_id = {$target['box_id']}");
    }
    return $do_ok;
  }

  public function moveDown($box_id){
    if(!$this->canDo($box_id))
      return;
    $this->_move($box_id, '>');
  }

  public function moveUp($box_id){
    if(!$this->canDo($box_id))
      return;
    $this->_move($box_id, '<');
  }

  function canDo($box_id){
    $check_perm = $this->getAttr($box_id, 'check_perm');
    if($check_perm == 1 && !p("box.".$box_id))
      return false;
    return true;
  }

  public function deleteBox($box_id){
    return $this->purgeBox($box_id);
  }

    function purgeBox($box_id){
        $box_id = (int)$box_id;
        if(!$this->canDo($box_id))
            return;
        $do_ok = phive('SQL')->query("DELETE FROM boxes_attributes WHERE box_id = $box_id");
        $do_ok = phive('SQL')->query("DELETE FROM boxes WHERE box_id = $box_id");
        return $do_ok;
    }

  //TODO does this exist in the admin interface somewhere, if not can it be removed or is it good to have? If it is used it needs to be able to handle Distributed data.
  public function transfer($box_id, $newpage){
    $box_id_esc = phive("SQL")->escape($box_id);
    $newpage_esc = phive("SQL")->escape($newpage);
    phive("SQL")->query("SELECT COALESCE(MAX(priority), 0) FROM {$this->getSetting('DB_PAGEBOXES')} WHERE page_id = $newpage_esc");
    $newprio = phive("SQL")->result() + 1;
    return phive("SQL")->updateArray($this->getSetting('DB_PAGEBOXES'), array("page_id" => $newpage, "priority" => $newprio), "box_id = $box_id_esc");
  }

  function getBoxesByClass($class){
    return phive('SQL')->loadArray("SELECT * FROM `boxes` WHERE `box_class` = '$class'");
  }

  function getAttrsByVal($name, $val){
    return phive('SQL')->loadArray("SELECT DISTINCT * FROM `boxes_attributes` WHERE `attribute_name` = '$name' AND `attribute_value` = '$val'");
  }

  function getAttrsByName($name){
    return phive('SQL')->loadArray("SELECT DISTINCT * FROM `boxes_attributes` WHERE `attribute_name` = '$name'");
  }

  public function getBoxPage($box_id){
      $box_id_esc = (int)$box_id;
    phive("SQL")->query("SELECT page_id FROM {$this->getSetting('DB_PAGEBOXES')} WHERE box_id = $box_id_esc");
    return phive("SQL")->result();
  }

  public function addBox($box_class, $container, $page_id = 0){
    $page_id_esc = phive("SQL")->escape($page_id);
    $container_esc = phive("SQL")->escape($container); //Could possibly cause problems with insertArray...

    $dbt = $this->getSetting("DB_PAGEBOXES");
    phive("SQL")->query("SELECT priority+1 FROM $dbt WHERE container = $container_esc AND page_id = $page_id_esc ORDER BY priority DESC LIMIT 1");
    $prio = max(phive("SQL")->result(), 0);

    $do_ok = phive("SQL")->insertArray($dbt, array("box_class" => $box_class, "container"=> $container, 'page_id' => $page_id, 'priority' => $prio));

    // Create an image alias when we add a FullImageBox, DynamicImageBox to a page
    if($do_ok && ($box_class == 'FullImageBox' || $box_class == 'DynamicImageBox')) {
        $box_id = $do_ok;  // If $do_ok evaluates to true, it means it is the box_id.

        if($box_class == 'FullImageBox') {
            $alias = 'fullimagebox.'.$box_id;
            $this->createImageDataAndAlias($alias);

        } elseif ($box_class == 'DynamicImageBox') {
            $this->createImageAliasesForDynamicImageBox($box_id);
        }

    }

    return $do_ok ? phive("SQL")->insertBigId() : false;
  }

    function createImageAliasesForDynamicImageBox($box_id)
    {
        // We need to create 2 images, for logged in and for logged out users
        $this->createImageDataAndAlias('fullimage.loggedin.' . $box_id);
        $this->createImageDataAndAlias('fullimage.loggedout.' . $box_id);
    }

    function createImageDataAndAlias($alias)
    {
        $table_image_data = phive("ImageHandler")->getSetting("TABLE_IMAGE_DATA");
        $table_image_alias = phive("ImageHandler")->getSetting("TABLE_IMAGE_ALIASES");
        // If the alias in "image_alias" already exist we don't do anything, otherwise we end up creating unused rows into "image_data"
        if (!empty(phive('SQL')->loadAssoc("SELECT * FROM $table_image_alias WHERE alias = '$alias'"))) {
            return;
        }

        // The legacy DB structure forces us to add a record in image_data first, even though we don't have any images uploaded yet.
        $image_id = phive("SQL")->nextAutoId('image_data');
        $image_data = array('image_id' => $image_id, "filename" => '', "width" => 0, "height" => 0, "lang" => 'any', "original" => 1);
        phive('SQL')->insertArray($table_image_data, $image_data);
        // then we add the alias assigning the newly created image to it.
        phive("SQL")->insertArray($table_image_alias, array("alias" => $alias, "image_id" => $image_id));
    }

  function getRawBoxById($id){
    $id = intval($id);
    return phive('SQL')->loadAssoc("SELECT * FROM {$this->getSetting('DB_PAGEBOXES')} WHERE box_id = $id");
  }

  function getAttributeByMonth($box_id, $month, $attr){
    $sql = "SELECT *
	    FROM boxes_attributes
	    WHERE box_id = $box_id
	    AND attribute_name LIKE '$month%'
	    AND attribute_name LIKE '%$attr%'";

    //echo $sql;

    return phive('SQL')->fetchResult($sql);
  }

  function getAttrValByMonth($box_id, $month, $attr){
    $attr = $this->getAttributeByMonth($box_id, $month, $attr);
    return $attr['attribute_value'];
  }

  function getAttrVal($box_id, $attr){
    return phive('SQL')->queryAnd(
      "SELECT attribute_value
       FROM boxes_attributes
       WHERE box_id = $box_id
	   AND attribute_name = '$attr'")->result();
  }

  public function getBoxById($box_id){
      $box_id_esc = (int)$box_id;
    if(phive("SQL")->query("SELECT box_class FROM {$this->getSetting('DB_PAGEBOXES')} WHERE box_id = $box_id_esc")==false)
      return false;
    $bclass = phive("SQL")->result();
    if(empty($bclass))
      return false;
    return $this->getNewBox($bclass, $box_id);
  }

  private function getNewBox($type, $id, $checkValidity = false)
  {
    $file = $this->getDomainSetting("BOX_PATH").'/'.$type. '.php';
    if($checkValidity)
    {
      if(!file_exists($file))
	return new BOX_ERROR($type, $id, "File doesn't exist ($file)");
        //if(!$this->checkFileSyntax($file))
	//return new BOX_ERROR($type, $id, "Parse errors in box file ($file)");
    }
    require_once($file);
    return eval("return new $type($id);");
  }

  function includeSiteBox($box){
    require_once __DIR__."/../../../{$this->getSetting('box_folder')}/boxes/$box.php";
  }

  function includeMainBox(){
    $this->includeSiteBox($this->getSetting('base_box'));
  }

    /*
  private function checkFileSyntax($file)
  {
    if(!$this->canCheckSyntax)
      return true; //Assume all is fine rather than bad
    exec("php -l $file",$error,$code);
    return ($code==0);
  }
    */

  function getAllAttributes($month){
    $sql = "SELECT * FROM boxes_attributes WHERE attribute_name LIKE '{$month}%'
                AND box_id IN(SELECT box_id FROM boxes WHERE box_class IN('RakeChaseBox', 'PointsRaceBox', 'RakeRaceBox', 'ComboRaceBox', 'AffeRaceBox'))";
    return phive('SQL')->loadArray($sql);
  }

  function getAttr($box_id, $attr_name){
    return phive('SQL')->getValue(
      "SELECT attribute_value FROM boxes_attributes WHERE box_id = $box_id AND attribute_name = '$attr_name'");
  }

  function saveAttr($box_id, $attr_name, $value){
    return phive('SQL')->save('boxes_attributes', array('box_id' => $box_id, 'attribute_name' => $attr_name, 'attribute_value' => $value));
  }

  function deleteAttr($box_id, $attr_name){
    return $this->getBoxById($box_id)->deleteAttribute($attr_name);
  }

  function getAttributes($box_id, $keyed = false){
    $attrs = phive('SQL')->loadArray("SELECT * FROM `boxes_attributes` WHERE `box_id` = $box_id");
    if($keyed){
      $rarr = array();
      foreach($attrs as $attr)
	$rarr[ $attr['attribute_name'] ] = $attr['attribute_value'];
      return $rarr;
    }
    return $attrs;
  }

  function getStartEnd($box_id, $stamp, $def_start = '', $def_end = ''){
    $start_date = $this->getAttr($box_id, 'start_date');
    $end_date 	= $this->getAttr($box_id, 'end_date');

    $def_start 	= empty($def_start) ? date("Y-m-d", $stamp) : $def_start;
    $def_end 	= empty($def_end) 	? date("Y-m-t", $stamp) : $def_end;

    $start_date = empty($start_date) 	? $def_start : $start_date;
    $end_date 	= empty($end_date) 		? $def_end : $end_date;

    return array($start_date, $end_date);
  }

  function updateAttr($attr){
    return phive('SQL')->updateArray(
      'boxes_attributes',
      $attr,
      array('box_id' => $attr['box_id'], 'attribute_name' => $attr['attribute_name']));
  }

  function populateAllAttributes($cur_month, $prior_month){
    foreach($this->getAllAttributes($prior_month) as $attr){
      $cur_key = preg_replace("|^$prior_month|", $cur_month, $attr['attribute_name']);
      $result = phive('SQL')->queryAnd("SELECT * FROM boxes_attributes WHERE box_id = {$attr['box_id']} AND attribute_name = '$cur_key'")->fetch();
      if(empty($result)){
	$attr['attribute_name'] = $cur_key;
	phive('SQL')->insertArray('boxes_attributes', $attr);
      }
    }
  }

    function getRawBox($class, $init = false){
        $cur_file = __DIR__.'/../../../'.$this->getSetting('box_folder').'/boxes/'.$class.'.php';
        if(!file_exists($cur_file))
            return false;
        require_once $cur_file;
        $box = new $class();
        if($init)
            $box->init();
        return $box;
    }

    function getRawBaseBox($class, $init = false){
        $cur_file = __DIR__."/boxes/{$this->getSetting('box_folder')}/$class.php";
        if(!file_exists($cur_file))
            return false;
        require_once $cur_file;
        $box = new $class();
        if($init)
            $box->init();
        return $box;
    }

    function rawBoxHtml($class, $func){
        $func = empty($func) ? 'printHTML' : $func;
        $box = $this->getRawBox($class);
        if(!method_exists($box, $func))
            return false;
        $box->onAjax();
        $box->$func();
    }

  //$class, $func, $param1, $param2 ...
    function getRawBoxHtml(){
        $args   = func_get_args();
        $class  = array_shift($args);
        $func   = array_shift($args);
        $params = $args;
        $box    = $this->getRawBox($class);
        if(!method_exists($box, $func))
            return '';
        $box->init();
        ob_start();
        call_user_func_array(array($box, $func), $params);
        return ob_get_clean();
    }

    function boxHtml($boxid, $func = '', bool $return = false) {
        if(!empty($boxid))
            $box_target = $this->getRawBoxById($boxid);

        $cur_box_id 	= (int)$boxid;

        if($cur_box_id != 0){
            $cur_box_class 	= $box_target['box_class'];
            $cur_file = __DIR__.'/../../../'.$this->getSetting('box_folder').'/boxes/'.$cur_box_class.'.php';
            if(is_file($cur_file)){
	        require_once $cur_file;
	        $cur_box = new $cur_box_class($cur_box_id);
	        $cur_box->init();
            }
        }

        $func = empty($func) ? 'printHTML' : $func;

        if ($cur_box_id != 0 && method_exists($cur_box, $func)) {
            $result = $cur_box->$func();
            if ($return) {
                return $result;
            }
        }
    }

    function getFooterData(): array
    {
        $menuer = phive('Menuer');
        $footer_menu = $menuer->forRender('footer');
        $result = [];

        foreach ($footer_menu as $item) {
            $parsed = [
                "content" => [
                    "value" => $item['alias'],
                    "type" => "alias"
                ],
                "graphic" => [
                    "source" => $item['icon'],
                    "type" => "icon"
                ],
                "operation" => [
                    "source" =>str_replace(["href=\"", "\""], "", $item['params']),
                    "type" => "navigation"
                ],
                "page_id" => $item['page_id']
            ];

            $result[] = $parsed;
        }

        return ["data" => $result];
    }
    function ajaxGetBoxHtml($func){
        if(is_numeric($_REQUEST['boxid']))
            $this->boxHtml($_REQUEST['boxid'], $func);
        else{
            $this->rawBoxHtml($_REQUEST['boxid'], $func);
        }
    }

    /**
     * Initialize and return instance of a DiamondBet box class
     *
     * @param string $class
     *
     * @return mixed|null
     *
     * @api
     */
    public function initBoxClass(string $class)
    {
        $path = __DIR__ . "/boxes/diamondbet/{$class}.php";

        if (! class_exists($class) && file_exists($path)) {
            require_once $path;
        }

        return class_exists($class)
            ? new $class
            : null;
    }

    /**
     * Initialize Diamond's box base
     *
     * @param string $alias
     *
     * @return mixed|void
     *
     * @api
     */
    public function getDiamondBoxBase(string $alias)
    {
        $folder = phive('BoxHandler')->getSetting('box_folder');
        $path = __DIR__ . '/boxes/' . $folder . '/' . $alias . '.php';

        if (file_exists($path)) {
            require_once $path;

            return new $alias();
        }
    }
}

/**
 * Check if a module exists and return it's file name
 *
 * @param string $module The module folder to use, case sensitive.
 * @param string $file The file to get inside the module/html/ folder, also case sensitive.
 * @param string $iso Optional iso2 code, if not null will be used as a sub directory.
 *
 * @return string If file exists, return the file name. If not, return empty string
 */
function moduleFile($module, $file, $iso)
{
    $country = $iso;
    $subRegion = '';
    if (strpos($iso, '-') !== false) {
        $iso = explode('-', $iso);
        $subRegion = phive()->rmNonAlphaNums($iso[1]) . '/';
        $country = $iso[0];
    }
    $country = empty($country) ? '' : phive()->rmNonAlphaNums(strtoupper($country)).'/';

    $file   = phive()->rmNonAlphaNumsNotSpaces($file, '|_|\/');
    $module = phive()->rmNonAlphaNumsNotSpaces($module, '|_|\/');

    $file = __DIR__ . "/../$module/{$country}html/{$subRegion}{$file}.php";

    if (!empty($subRegion) && !file_exists($file)) {
        $file = str_replace($subRegion, "", $file);
    }

    if (!empty($country) && !file_exists($file)) {
        $file = str_replace($country, "", $file);
    }

    return file_exists($file) ? $file : '';
}

/**
 * Global function to get HTML.
 *
 * This function handles the routing in order to render HTML when a full blown box
 * is overkill.
 *
 * @param string $module The module folder to use, case sensitive.
 * @param string $file The file to get inside the module/html/ folder, also case sensitive.
 * @param bool $return True if we want to return the HTML, false if we want to echo it.
 * @param string $iso Optional iso2 code, if not null will be used as a sub directory.
 * @param array $data Optional array of data to pass to the file. see example on deposit_amount_input
 *
 * @return mixed A string if we want to return, null otherwise.
 */

function moduleHtml($module, $file, $return = false, $iso = null, $data = [])
{
    $box_folder = phive('BoxHandler')->getSetting('box_folder');

    // Ar we looking at an attempt to load a file in a non authorized location?
    if(strpos($module, '.') !== false || strpos($file, '.') !== false){
        phive()->dumpTbl('moduleHtml_hacking_attempt', $_SERVER);
        return false;
    }

    $file = moduleFile($module, $file, $iso);
    if (empty($file)) {
        return false;
    }

    include_once __DIR__ . "/../../../$box_folder/html/display.php";

    // Extract the data array to variables
    if (!empty($data)) {
        extract($data, EXTR_SKIP);
    }

    if($return){
        return phive()->ob($file);
    }

    include $file;
}

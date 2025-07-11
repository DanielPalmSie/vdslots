<?php

require_once __DIR__ . '/../../modules/HierarchySQL/HierarchySQL.php';

class Pager extends HierarchySQL{
    public $page_id = 0;
    public $path;
    public $raw_dir;
    public $meta_title;
    public $meta_keywords;
    public $meta_description;
    public $arguments;
    public $page_routes = null;
    public $country = null;

    /**
     * @var string|null Language from current URL string (e.g. for /sv/jackpots/ $url_lang is 'sv')
     */
    private ?string $url_lang = null;

    public function __construct(){
        $this->meta_title = null;
        $this->meta_keywords = null;
        $this->meta_description = null;
        $this->page_exists = true;
        $this->setKeys("page_id", "parent_id", "alias");
        $this->cur_path = $_GET['dir'] ?? '';
    }

    /**
     * Cache page_routes as country-<page_id>, country-<cached_path> for fast access to items
     */
    public function initPageRoutes()
    {
        if ($this->page_routes) {
            return;
        }
        $this->country = getCountry();
        $this->page_routes = phive('SQL')->loadArray("
            SELECT r.*, p.cached_path FROM page_routes r
            LEFT JOIN pages p on r.page_id = p.page_id
        ");
        $this->page_routes = array_reduce($this->page_routes, function ($carry, $item) {
            $carry[$item['country'] . '-' . $item['cached_path']] = $item;
            $carry[$item['country'] . '-' . $item['page_id']] = $item;
            return $carry;
        }, []);
    }

    /**
     * Get url based on page_routes configuration
     * $id can be pages.page_id or pages.cached_path
     *
     * @param null|string|int $id
     * @return mixed|string|null
     */
    public function getPageRouteUrlByCountry($id = null)
    {
        $this->initPageRoutes();
        if (empty($id)) {
            return null;
        }
        $url = $this->page_routes[$this->country . '-' . $id];
        if (empty($url)) {
            return null;
        }
        $url = $url['route'];
        if (empty($url)) {
            return null;
        }
        if ($url[0] !== '/') {
            $url = '/' . $url;
        }
        return $url;
    }

    /**
     * Get url based on page_routes configuration or default to provided parameter
     * @param $url
     * @return mixed|string
     */
    public function resolvePageRoutes($url)
    {
        $new_url = $this->getPageRouteUrlByCountry($url);
        if (empty($new_url)) {
            return $url;
        }
        return $new_url;
    }

    /*
       Example Usage:
       $browse_path = $this->getBreadcrumbs();
       foreach($browse_path as $k => $p) {
       echo '<a href="'.$p['cached_path'].'">'.$p['title'].'</a>';
       if ($k < count($browse_path)-1)
       echo " &gt; ";
       }
     */
    function getBreadcrumbs($absolute = false){

        if (isset($this->crumbs))
            return $this->crumbs;

        $path 	= array();
        $i 		= 0;
        $j 		= 0;

        if(!$absolute){
            $start            = $this->getPageByAlias('.', 0);
            $start['title'] 	= t('home');
            $start['page_id']	= 0;
            $path[0]          = $start;
            $i                = 1;
        }

        $cur_path = '/';

        foreach($this->raw_dir as $alias){
            $cur_path	.= "$alias/";
            if(!is_numeric($alias)){
                if(!$absolute && $alias != '.' && $alias != '.index'){

                    $pid 	= $i == 0 ? 0 : $path[$i - 1]['page_id'];
                    $temp = array($alias, $pid);
                    $page	= $this->getPageByAlias($alias, $pid);

                    if(!empty($page)){

                        $page['title'] = t( $alias );
                        $path[$i] = $page;
                    }else{
                        $path[$i]['cached_path'] = $cur_path;
                        if(count($this->raw_dir) - 1 == $j)
                            $path[$i]['title'] = phive('Localizer')->getPotentialString( $this->meta_title );
                        else if(!empty($alias))
                            $path[$i]['title'] = ucfirst($alias);
                    }

                    if(empty($path[$i]['title']))
                        $path[$i]['title'] = ucfirst($this->getAliasFromCachedPath($cur_path));

                    $i++;
                }
            }
            $j++;
        }

        $this->crumbs = $path;

        return $this->crumbs;
    }

    public function getList(){
        $prio = $this->getKey('priority');
        $p_str = (($prio)?" ORDER BY `$prio`":"");
        $table = $this->getSetting('table_entries');
        $str = "SELECT $table.*, ps.value AS title, ps2.value AS domain FROM `$table`
                LEFT JOIN page_settings AS ps ON ps.page_id = pages.page_id AND ps.name = 'title'
                LEFT JOIN page_settings AS ps2 ON ps2.page_id = pages.page_id AND ps2.name = 'domain'
                " . $p_str;
        return phive('SQL')->loadArray($str);
    }

    public function updateEntry(array $entry){
        if (!$entry['filename'])
            $entry['filename'] = $this->getSetting("default_filename");

        $ret = parent::updateEntry($entry);
        if (!$ret)
            return false;

        if ($entry['page_id']){
            $this->cachePath($entry['page_id']);
            return true;
        }else{
            $id = phive('SQL')->insertBigId();
            $this->cachePath($id);
            return $id;
        }
    }

    // Delete entry should also delete settings
    public function deleteEntry($entry_id){
        $ret = parent::deleteEntry($entry_id);
        if ($ret){
            $table = $this->getSetting('table_page_settings');
            phive('SQL')->query("DELETE FROM $table WHERE `" . $this->getKey('id') . "`='$entry_id'");
        }
        return $ret;
    }

    // Alias to above
    public function updatePage(array $entry) { return $this->updateEntry($entry); }
    public function getPage($id) { return $this->getEntry($id); }

    public function getPageByAlias($alias, $parent=0){
        $table = $this->getSetting('table_entries');
        phive('SQL')->query(
            "SELECT * FROM `$table` WHERE `alias`=" .
            phive('SQL')->escape($alias) . " AND `parent_id`=" .
            phive('SQL')->escape($parent));
        return phive('SQL')->fetch();
    }

    public function executeByID($id){
        $this->page_id = $id;
        if ($this->page_id){
            // Get page info
            $page = $this->getEntry($this->page_id);

            try {
                // Let's include from the directory where phive/ is.
                include __DIR__ . '/../../../' . $page['filename'];
                return true;
            }catch (Throwable $e) {
                phive('Logger')->error(
                    "Pager execution error",
                    [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                return false;
            }
        }
        return false;
    }

    public function execute($dir){
        $page_id = $this->getPageFromDir($dir, false);
        return $this->executeByID($page_id);
    }

    function redirectToLandingPage($dir) {

        $pager = phive('Pager');

        if( !$pager->getSetting('show_landing_page')
            || isset($_SESSION['home_page'])
            || isset($_GET['show_login'])
            || !$this->isHomePage($dir) )
            return null;

        $page_alias = $pager->getSetting('landing_page_alias');

        if(empty($page_alias))
            return null;

        $alias = phive()->isMobile() ? ["mobile", $page_alias] : [$page_alias];

        $landingPageId = $this->getPageFromDir($alias, false);
        $_SESSION['home_page'] = true;
        return $landingPageId;
    }

    // Execute the Pager engine.
    // $dir is the search path as an array.
    public function getPageFromDir($dir, $checkRedirection = true){

        if($checkRedirection) {
            $page_id = $this->redirectToLandingPage($dir);
            if (!empty($page_id)) {
                return $page_id;
            }
        }

        if(count($dir) == 1 && empty($dir[0]))
            $dir = array();

        // checking if a language is specified in the url "/it, /sv" and set the language accordingly, otherwise default.
        if($this->getSetting('loc_type') == 'dir'){
            $cur_lang = $dir[0];
            if(phive('Localizer')->languageExists($cur_lang)){
                $this->cur_lang = array_shift($dir);
                if (!phive('Localizer')->isLanguageSupported($this->cur_lang)) {
                    $this->page_exists = false;
                    $this->cur_lang = phive('Localizer')->getDefaultLanguage();
                }
            }else{
                $this->cur_lang = phive('Localizer')->getDefaultLanguage();
            }
        }

        $this->path = "";
        // Now let's see how much exists (if any)
        $parent = 0; // parent should start at 0.
        $i = 0;
        $do_page_routes = false;
        $sql = phive('SQL');
        $is_mobile_dir = $dir[0] === 'mobile';

        // building up the full path from the existing pages, and setting everything else as arguments
        // Ex. /casino/games/sakura-fortune-quickspin/
        // casino and games will match on entryExists so the path will be /casino/games
        // while the game name "sakura-fortune-quickspin" will be passed as an argument
        foreach ($dir as $alias){
            if (!$do_page_routes && ($id = $this->entryExists($alias, $parent))){
                $this->path .= '/' . $alias;
                $parent = $id;
            } elseif ($do_page_routes || ($is_mobile_dir && $i === 1) || (!$is_mobile_dir && $i === 0)) {
                // when not mobile fallback only when $dir[0] was not found by entryExists
                // when is mobile and $i==0 will be 'mobile' which entryExists can find so we only fallback from the next step
                $do_page_routes = true;
                $path = $this->path . '/' . $alias;
                $res = $sql->loadAssoc("SELECT * FROM page_routes WHERE route = {$sql->escape(substr($path, 1))} ");
                if (!empty($res)) {
                    $parent = $res['page_id'];
                    $this->path .= '/' . $alias;
                }
            } else {
                // Slice the array and break
                $this->arguments = array_slice($dir, $i);
                break;
            }
            ++$i;
        }

        // Check if arguments contain and adding this to $_GET variable
        $i=0;
        if (!empty($this->arguments)){
            foreach ($this->arguments as $arg){
                $_GET['arg' . $i] = $arg;
                ++$i;
            }
        }

        // We check for a route since no page could be straight up matched.
        if (!empty($dir) && !empty($parent) && $do_page_routes){
            return $parent;
        }

        $this->raw_dir = $dir;
        $this->page_id = $parent;

        if(!empty($this->page_id)){
            $country    = getCountry();
            $page_route = phive('SQL')->loadAssoc($q = "SELECT * FROM page_routes WHERE page_id = {$this->page_id} AND (country = '{$country}' or country = '') ORDER BY country ASC LIMIT 1");
            if (!empty($page_route)) {
                $prefix = ($this->cur_lang === phive('Localizer')->getDefaultLanguage()) ? '/' : '';
                // fix for casino/games/game-name where game-name was being left out
                if (count($dir) !== count(explode('/', $page_route['route']))) {
                    $original = phive('SQL')->loadAssoc("SELECT * FROM pages WHERE page_id = {$this->page_id}");
                    $original_page = $original ? $original['cached_path'] : '';

                    $current_route = '/' . implode('/', $dir);
                    $page_route['route'] = str_replace($original_page, $page_route['route'], $current_route);
                }
                if ($page_route['route'][0] !== '/') {
                    $prefix = '/';
                }
                return phive('Redirect')->to($prefix . $page_route['route'], $this->cur_lang);
            }
        }

        if (!$this->page_id){
            // if nothing is matched i return the generic page_id for the home page.
            // only if an invalid path was provided i will set page_exist to false that will redirect the user to the 404
            if(!empty($this->raw_dir))
                $this->page_exists = false;

            $this->page_id = $this->entryExists('.', 0);
        }

        return $this->page_id;
    }

    // Updates a settings, works like updateEntry
    public function updateSetting(array $setting){
        $table = $this->getSetting('table_page_settings');
        if (isset($setting['setting_id']))
            $where = "`setting_id`='$setting[setting_id]'";
        else
            $where = null;

        return phive('SQL')->insertArray($table, $setting, $where);
        return false;
    }

    // Delete setting
    public function deleteSetting($setting_id){
        $str = "DELETE FROM `" . $this->getSetting('table_page_settings') . "` WHERE `setting_id`='$setting_id'";
        return phive('SQL')->query($str);
        return false;
    }

    // Get arguments
    public function getArguments(){
        return $this->arguments;
    }

    // Get specific argument
    public function arg($i){
        return (isset($this->arguments[$i])) ? $this->arguments[$i] : null;
    }

    // Get page setting variable
    public function get($setting, $page_id=null){
        if ($page_id === null)
            $page_id = $this->page_id;

        if ($page_id == 0)
            return null;

        $sql = phive('SQL');
        $table = $this->getSetting('table_page_settings');
        $sql->query("SELECT `value` FROM `$table` WHERE `page_id`={$sql->escape($page_id)} AND `name`={$sql->escape($setting)}");
        return phive('SQL')->result();
    }

    // Retrieve a list of settings (as arrays straight from SQL)
    public function getSettings($page_id){
        $table = $this->getSetting('table_page_settings');
        phive('SQL')->query(
            "SELECT * FROM `$table` WHERE `page_id`='$page_id'");
        return phive('SQL')->fetchArray();
    }

    function fetchSetting($kvalue, $page_id = null){
        $page_id = empty($page_id) ? $this->page_id : $page_id;
        return phive('SQL')->getValue("SELECT value FROM page_settings WHERE name = '$kvalue' AND page_id = $page_id");
    }

    function fetchLandingPage(){
        $lp = $this->fetchSetting("landing_bkg");
        if(!empty($lp)){
            $lang_lp 	= phive("Localizer")->getLanguage()."_".$lp;
            if(phive("Filer")->hasFile($lang_lp))
                return $lang_lp;
        }
        return $lp;
    }

    // Get raw path (including arguments) without trailing
    public function getRawPathNoTrailing(){
        return '/' . (is_array($this->raw_dir) ? implode('/', $this->raw_dir) : '');
    }

    // Get path
    public function getPath($id = null){
        $path = $this->getPathNoTrailing($id);
        return Pager::addSlash($path);
    }

    static public function addSlash($path){
        return ($path === '/') ? $path : $path . '/';
    }

    public function getPathNoTrailing($id = null){
        if ($id === null) {
            $page_route = $this->getPageRouteUrlByCountry($this->path);
            if (!empty($page_route)) {
                return $page_route;
            }
            return $this->path;
        }

        return$this->getCachedPath($id);
    }

    function getCurSection(){
        if(empty($this->cur_section))
            $this->cur_section = $this->getAtLvl(0);
        return $this->cur_section;
    }

    function getSectionForCss(){
        $map = $this->getSetting('section_map');
        return $map[$this->getCurSection()];
    }

    function isRealSection(){
        return in_array($this->getCurSection(), $this->getSetting('real_sections'));
    }

    // This function gets the path of one specific level
    //  level 0 means beneath the root. something.com/zero/one/two/
    public function getPathLevel($level, $id=null){
        $path = $this->getPathNoTrailing($id);
        $ar = explode('/', $path);
        return ($level >= count($ar) - 1) ? null : $ar[$level + 1];
    }

    function getPageAtLevel($level = 0){
        $alias = $this->getPathLevel($level);
        return $this->getPageByAlias($alias);
    }

    function getAtLvl($lvl){
        return $this->raw_dir[ $lvl  ];
    }

    function getLastLvl(){
        return $this->raw_dir[ count($this->raw_dir) - 1  ];
    }

    private function buildPath($id){
        phive('SQL')->query(
            "SELECT alias, parent_id FROM `" .
            $this->getSetting('table_entries') .
            "` WHERE `page_id`='$id' LIMIT 1");
        $all = phive('SQL')->fetchArray();

        return ($all[0]['parent_id']?($this->buildPath($all[0]['parent_id'])):'') . '/' . $all[0]['alias'];
    }

    public function getId(){
        return $this->page_id;
    }

    public function getCurrentPageAlias($id = null) {
        $path = $this->getPath($id = null);
        return $this->getAliasFromCachedPath($path);
    }

    function getAliasFromCachedPath($path){
        return array_pop(phive()->remEmpty(explode('/', $path)));
    }

    public function getBrowsePath(){
        if (isset($this->browse_path))
            return $this->browse_path;

        $m = phive('Menuer');
        $l = phive('Localizer');

        $i = $m->getCurrentMenuObject();
        while($i) {
            $ml[] = $m->getMenu($i);
            $i = $m->getParentId($i);
        }
        $ml = array_reverse($ml);

        foreach($ml as $k => $i) {
            $browse_path[$k]['name'] = $l->getPotentialString($i['name']);
            $browse_path[$k]['url'] = $m->getLinkParams($i);
        }
        $this->browse_path = $browse_path;
        return $this->browse_path;
    }

    function getGets($additional=null){
        $ret = "";
        $get = array_merge($_GET, (array)$additional);
        foreach ($get as $key=>$value){
            if ($key=='dir')
                continue;
            if (substr($key, 0, 3)==='arg' && is_numeric(substr($key, 3, 1)))
                continue;

            if ($value!==null){
                if ($ret==="")
                    $ret .= '?';
                else
                    $ret .= '&amp;';
            }

            if ($value)
                $ret .= $key . '=' . $value;
            else if ($value!==null)
                $ret .= $key;
        }
        return $ret;
    }

    static function urlize($name){
        $a = array(' '=>'-');
        return strtolower(strtr($name, $a));
    }

    // 0 = all
    public function cacheAll(){
        $sql = phive('SQL');
        $table = $this->getSetting('table_entries');
        $sql->query("SELECT `page_id` FROM `$table`");
        $res = $sql->fetchArray('NUM');
        foreach ($res as $item)
            $this->cachePath($item[0]);
    }

    public function cachePath($page_id){
        $path = $this->buildPath($page_id);
        $table = $this->getSetting('table_entries');
        phive('SQL')->query("UPDATE `$table` SET `cached_path`=" . phive('SQL')->escape($path) .
                            " WHERE `page_id`=" . phive('SQL')->escape($page_id));
    }

    public function getCachedPath($page_id){
        $page_route = $this->getPageRouteUrlByCountry($page_id);
        if (!empty($page_route)) {
            return $page_route;
        }
        phive('SQL')->query("SELECT `cached_path` FROM `" . $this->getSetting('table_entries') . "`
      WHERE `page_id`=" . phive('SQL')->escape($page_id));
        return phive('SQL')->result();
    }

    function saveSiteMap($config, $base_url, $langs, $bmap, $partner_types = array(1, 3), $news_handler = 'NewsHandler'){
        $bh 	= phive('BoxHandler');
        $pages 	= $this->getHierarchy();
        foreach($langs as $lang){

            $lang = $lang['language'];

            $filtered = array();

            foreach($pages as $p){
                if(strpos($p['cached_path'], 'admin') !== false)
                    continue;

                if(strpos($p['filename'], 'generic.php') === false)
                    continue;

                $boxes = $bh->getBoxesInPageAsArr($p['page_id']);

                foreach($boxes as $b){
                    if(in_array($b['box_class'], array_keys($bmap))){
                        list($tbl, $field) 	= explode(',', $bmap[ $b['box_class'] ]);
                        $urls 				= phive('SQL')->load1DArr("SELECT * FROM $tbl", $field);
                        foreach($urls as $url){
                            $filtered[] = $p['cached_path']."/$url";
                        }
                    }
                }

                $filtered[] = $p['cached_path'];
            }

            foreach(phive('Raker')->getPartners() as $p){
                if($p['active'] == 1 && $p['visible'] == 1 && in_array($p['type_id'], $partner_types)){
                    if(empty($config['offer_base']))
                        $base = $p['type_id'] == 1 ? 'offers' : 'casino-bonus';
                    else
                        $base = $config['offer_base'];

                    $filtered[] = "/$base/{$p['url_name']}";
                }
            }

            $articles = phive($news_handler)->getByMainCountry(null,null,$lang);

            foreach($articles as $a){
                $url_key 	= phive($news_handler)->getSetting('article_url');
                $base 		= $config['article_base']."/{$a->getAttr('category_alias')}/{$a->getAttr('article_id')}";
                if($a->getAttr($url_key) != '')
                    $base 	.= '/'.$a->getAttr($url_key);
                $filtered[] = $base;
            }

            array_shift($filtered);

            $base_url = sprintf($base_url, $lang);

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            ob_start();
    ?>
            <urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/09/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url>
                    <loc><?php echo $base_url.'/' ?></loc>
                    <changefreq>daily</changefreq>
                    <priority>1.0</priority>
                </url>
                <?php foreach($filtered as $url): ?>
                    <url>
                        <loc><?php echo $base_url.$url.'/' ?></loc>
                        <changefreq>weekly</changefreq>
                        <priority>0.5</priority>
                    </url>
                <?php endforeach ?>
            </urlset>
    <?php
            $xml .= ob_get_contents();
            ob_end_clean();

            $file = __DIR__ . "/../../../sitemap_$lang.xml";

            file_put_contents($file, $xml);

            unlink($file.'.gz');

            shell_exec("gzip $file");

            chmod($file.'.gz', 0777);
        }
    }

    function is404($boxes = null){

        if($this->page_exists === false)
            return true;

        $boxes = empty($boxes) ? $this->all_boxes : $boxes;

        foreach ($boxes as $b) {
            if (!$b->is404($this->arguments))
                return false;
        }

        return true;
    }

    function initPage(){

        $smith = phive('Permission');
        $boxHandler = phive("BoxHandler");
        $page_id = $this->getId();

        $redirect = $this->get('redirect', $page_id);

        if(!empty($redirect)){
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: ".$redirect);
            header("Connection: close");
            exit;
        }

        if (!privileged() && phive('Menuer')->countryIsBlocked(phive('Menuer')->getCurMenu($page_id)) === true) {
            $this->page_exists = false;
            return $this->is404();
        }

        $simulate_loggedout = isset($_GET['sim_loggedout']) && $smith->hasPermission('admin');

        $this->edit_content = isset($_GET['editcontent']);
        if ($this->edit_content){
            $_GET['editimages'] = true;
            $_GET['editstrings'] = true;
        }

        $this->edit_boxes = isset($_GET['editboxes']) && $smith->hasPermission('editboxes.light');
        if(($EDITSTRINGS = isset($_GET['editstrings'])) && $smith->hasPermission('translate.%')){
            phive('Localizer')->setTranslatorMode(true);
        }

        if (($this->edit_images = isset($_GET['editimages'])) && $smith->hasPermission('editimages')){
            phive('ImageHandler')->setEditMode(true);
        }

        if (isset($_POST['addbox']) && $this->edit_boxes){
            $boxHandler->addBox($_POST['boxtype'], $_POST['container'], $page_id);
        }

        $this->boxes = $boxHandler->getBoxesInPage($page_id);

        if (!isset($boxes["left"]))  $boxes["left"] = array();
        if (!isset($boxes["right"])) $boxes["right"] = array();
        if (!isset($boxes["full"])) $boxes["full"] = array();

        $this->all_boxes = array_merge((array)$this->boxes["left"], (array)$this->boxes["right"], (array)$this->boxes['full']);

        if($simulate_loggedout){
            phive('UserHandler')->simulateLoggedOut(true);
        }

        $first_box = true;
        foreach($this->all_boxes as $b){
            $b->baseInit();
            $b->init();
            if($first_box){
                $b->is_first = true;
                $first_box = false;
            }
        }
    }

    public function setTitle($meta_title){
        $this->meta_title = $meta_title;
    }

    public function setMetaKeywords($meta_keywords){
        $this->meta_keywords = $meta_keywords;
    }

    public function setMetaDescription($meta_description){
        $this->meta_description = $meta_description;
    }

    function setBotBlock(){
        $this->bot_block = true;
    }

    function setBrowsePath($browse_path) {
        $this->browse_path = $browse_path;
    }

    function getBodyStyle(){
        return $this->body_style;
    }

    function setBodyStyle($body_style){
        $this->body_style = $body_style;
    }

    public function getTitle(){
        if ($this->meta_title)
            return $this->meta_title;
        else{
            $this->meta_title = $this->get('title');
            return $this->meta_title;
        }
    }

    public function getMetaKeywords(){
        if ($this->meta_keywords)
            return $this->meta_keywords;
        else{
            $this->meta_keywords = $this->get('keywords');
            return $this->meta_keywords;
        }
    }

    public function getMetaDescription(){
        if ($this->meta_description)
            return $this->meta_description;
        else{
            $this->meta_description = $this->get('description');
            return $this->meta_description;
        }
    }

    function samePageSansParams(){
        $to_url = "http".phive()->getSetting('http_type')."://".$_SERVER['HTTP_HOST'].$_SERVER['REDIRECT_URL'];
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: $to_url");
        header("Connection: close");
        exit;
    }

    /**
     * Gets the list of the hreflang html tags for Google SEO
     *
     * @return array|void hreflang list
     */
    public function getHreflangLinks($return = false)
    {
        $page_routes = $this->getPageRoutes();
        $default_path = substr($this->getPath($this->page_id), 1);
        $tags = $this->getSetting('seo_alternate_hreflang');

        if ($default_path === "./") {
            $default_path = "";
        }
        $seo_path_url = '';

        foreach ($tags as $index => [$lang, $country, $link]) {
            $path = $page_routes[$country] ?? $default_path;

            // remove '/' when last char in link is '/'
            if (substr($link, -1) === '/') {
                $link = substr($link, 0, -1);
            }

            // ensure '/' at the start of the $path
            if ($path[0] !== '/') {
                $path = '/' . $path;
            }

            $tags[$index] = "<link rel='alternate' hreflang='{$lang}-{$country}' href='{$link}{$path}'/>";

            if ($lang === 'x') {
                $mobile_prefix = '/mobile';
                $cookie_policy_path = '/cookie-policy/';

                if ($path === "{$mobile_prefix}{$cookie_policy_path}") {
                    $seo_path_url = "<link rel='canonical' href='{$link}{$cookie_policy_path}'/>";
                } elseif ($path === $cookie_policy_path) {
                    $seo_path_url = "<link rel='alternate' media='only screen and (max-width: 640px)' href='{$link}{$mobile_prefix}{$cookie_policy_path}'/>";
                }
            }
        }

        if(!empty($seo_path_url)) {
            $tags[] = $seo_path_url;
        }

        if ($return) {
            return $tags;
        }

        echo implode("", $tags);
    }

    /**
     * Gets page routes if there is specific url in page_routes for the countries
     * with the given page_id parameter or the page_id of current page.
     * @param string|null $page_id
     * @return array path list in the format [ DE => 'onlinespiele/blackjack' ... ]
     */
    public function getPageRoutes($page_id = null)
    {
        $page_id = $page_id ?? $this->page_id;
        $countries = phive("Localizer")->getCountryByLanguage();
        $countries = phive('SQL')->makeIn($countries);
        $sql = "SELECT * FROM page_routes WHERE page_id ='{$page_id}' AND (country in ({$countries}) OR country = '')";
        $page_routes = phive('SQL')->loadArray($sql);
        $result = array();

        foreach ($page_routes as $route){
            $result[$route["country"]] = $route["route"];
        }

        return $result;
    }

    /**
     * @param string $lang
     *
     * @return void
     */
    public function setLanguage(string $lang): void
    {
        phive('Localizer')->setLanguage($lang);
    }

    /**
     * @param string $lang
     * @return void
     */
    public function setUrlLanguage(string $lang): void
    {
        $this->url_lang = $lang;
    }

    /**
     * @return string|null
     */
    public function getUrlLanguage(): ?string
    {
        return $this->url_lang;
    }

    /**
     * @return bool
     */
    function isDisplayModeIos(): bool
    {
        return isset($_GET['display_mode']) && $_GET['display_mode'] === 'ios';
    }

    function isLanding(): bool {
        return $this->get('landing_page') ?: false;
    }

    /**
     * @param $dir
     * @return bool
     */
    public function isHomePage($dir): bool
    {
        return (empty($dir) || count($dir) == 1 && ($dir[0] === 'mobile' || $dir[0] === ""));
    }

    /**
     * @param $paramsToRemove
     * @Param $url
     * @return string
     */
    function removeQueryParams(array $paramsToRemove, string $url = null): string {
        $url = $url ?? $_SERVER['REQUEST_URI'];

        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $query = $parsedUrl['query'] ?? '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        // Parse query string and remove specified parameters
        parse_str($query, $queryParams);
        foreach ($paramsToRemove as $param) {
            unset($queryParams[$param]);
        }

        // Rebuild URL
        $newQuery = http_build_query($queryParams);
        return $path . ($newQuery ? '?' . $newQuery : '') . $fragment;
    }
}

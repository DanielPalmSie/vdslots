<?php
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/DeepL/DeepL.php';

class Localizer extends PhModule{
    private $country = array();

    // Current language
    public $lang = null;

    // Local cache array to check against for languageExists function
    private $lang_exists = array();

    // Local cache array to check against for getCountryFromLang function
    private $country_by_lang = array();

    // Translator mode
    public $translator_mode = false;

    // Cached strings (map with alias pointing to value in set language)
    public $cached = array();

    private $capture = null;

    public $locale = null;

    public $countries_by_language = array();

    private string $excludes = "";

    private array $excludedLanguages = array();

    public function __construct(){
        $this->default_country = $this->getDomainSetting("default_country");
    }

    public function removeStrings(string $column, array $excludedStrings){
        if (!empty($excludedStrings)) {
            $excludedPatterns = implode("|", $excludedStrings);
            $query = " AND {$column} NOT REGEXP '^({$excludedPatterns})'";
        } else {
            $query = "";
        }

        $this->excludes = $query;
        return $this;
    }

    /**
     * Should be run as a monthly cron in order to do content maintenance.
     *
     * @return void
     */
    public function monthlyCron(){
        // Copy English trophy names to all non-translated languages
        $langs   = array_column($this->getAllLanguages(), 'language');
        $aliases = phive('SQL')->load1DArr("SELECT alias FROM localized_strings WHERE alias LIKE 'trophyname.%'", 'alias');
        foreach($aliases as $alias){
            $eng_content = $this->getRawString($alias, 'en');
            if(empty($eng_content)){
                continue;
            }
            foreach($langs as $lang){
                $content = $this->getRawString($alias, $lang);
                if(empty($content)){
                    $this->addString($alias, $eng_content, $lang, false);
                }
            }
        }
    }

    function setFromReq(){
        if(!empty($_REQUEST['lang'])){
            $this->setLanguage($_REQUEST['lang'], true);
        }
    }

    public function getIntlDtFormat($date_type = '', $time_type = ''){
        return $this->getIntlDtFormatter($date_type = '', $time_type = '')->getPattern();
    }

    public function getIntlDtFormatter($date_type = '', $time_type = ''){
        if(!empty($this->intl_date_formatter))
            return $this->intl_date_formatter;
        return $this->createIntlDtFormatter($date_type, $time_type);
    }

    public function createIntlDtFormatter($date_type = '', $time_type = ''){
        $date_type = empty($date_type) ? IntlDateFormatter::SHORT : $date_type;
        $time_type = empty($time_type) ? IntlDateFormatter::NONE : $time_type;
        list($locale, $encoding) = explode('.', $GLOBALS['current_locale']);
        $this->intl_date_formatter = new IntlDateFormatter($locale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
        return $this->intl_date_formatter;
    }

    public function getStampFromIntl($string_stamp, $as = 'int'){
        $formatter = $this->createIntlDtFormatter();
        $stamp = $formatter->parse($string_stamp);
        if($as == 'int')
            return $stamp;
        $new_string_stamp = phive()->hisNow($stamp);
        if($as == 'string')
            return $new_string_stamp;
        if($as == 'object')
            return new DateTime($new_string_stamp);
    }

  // Set current language
  public function setLanguage($lang, $sub = false){
      $lang = $this->checkLanguageOrGetDefault($lang);
      $this->lang = $lang;
      if ($sub) {
          $this->setNonSubLang($lang);
      }
      $country = $this->getCountryFromLang($lang);
      $this->country = $country;
      phive('Pager')->cur_lang = $lang;
      $this->locale = $GLOBALS['current_locale'] = setlocale(LC_TIME, $this->getCountryValue('setlocale'));
      phive('Licensed')->resetCaches();
  }

  // Get current language
  public function getLanguage(){
    return $this->lang;
  }

  public function doExtraLocalization() {
      return in_array($this->lang, $this->getSetting('extra_localization', ['ja']));
  }

  function getCurNonSubLang(){
    $p = phive('Pager');
    return empty($p->cur_lang) ? $this->getDefaultLanguage() : $p->cur_lang;
  }

  function setLangs(){
    $this->langs = phive("SQL")->loadArray("SELECT * FROM languages", 'ASSOC', 'language');
  }

  function isX($col){
    if(empty($this->langs))
      $this->langs = phive("SQL")->loadArray("SELECT * FROM languages", 'ASSOC', 'language');
    return $this->langs[$this->lang][$col] == 0 ? false : true;
  }

  function isLight(){ return $this->isX('light'); }
  function isSelectable(){ return $this->isX('selectable'); }

  function getSubIndependentLang(){
    return ($this->getSetting('use_sub')) ? $this->getLanguage() : $this->getCurNonSubLang();
  }

  function saveMultiple($arr){
    $sql = phive('SQL');
    foreach($arr as $r){
      $sql->save('localized_strings', $r);
    }
  }

  function getNonSubLang($lang = ''){
    $lang = empty($lang) ? phive('Pager')->cur_lang : $lang;
    return (empty($lang) || ($lang == $this->getDefaultLanguage())) ? '' : "/{$lang}";
  }

  function setNonSubLang($lang){
    $p = phive('Pager');
    $p->cur_lang = $lang;
   }

  function isExtUrl($url){
    return strpos($url, 'http') === 0 ? true : false;
  }

  function fullLangLink($page = '', $lang = ''){
    if($this->getSetting('use_sub') === true)
        return $page;

      if(empty($lang))
          $lang = $this->getCurNonSubLang();

      // Do we have an absolute URL or not?
      if($this->isExtUrl($page)){
          if ($lang == $this->getDefaultLanguage()) {
              return $page;
          }
          $arr = explode('/', $page);
          if(strlen($arr[3]) == 2){
              $langs = array_column($this->getAllLanguages(), 'language');
              // The language part is already in the URL so we return it right away.
              if(in_array($lang, $langs))
                  return $page;
          }
          array_splice($arr, 3, 0, $lang);
          return implode('/', $arr);
      }

      // We have a local URL
      $p = phive('Pager');
      $lang = ($lang == $this->getDefaultLanguage()) ? '' : "/{$lang}";
      $page = empty($page) ? $p->getRawPathNoTrailing() : $page;
      if(strpos($page, '/') !== 0)
          $page = "/$page";
      return $lang.$page;
  }

  function langLink($lang = '', $page = ''){
    if($this->isExtUrl($page) || $this->getSetting('use_sub') === true)
      return $page;
    $p = phive('Pager');
    $lang = empty($lang) ? $this->getNonSubLang() : $lang;
    if (empty($lang)) {
        $lang = null; // Remove "needle is empty" warning.
    }
    if(strpos($page, $lang) !== false)
      return $page;
    $page = empty($page) ? $p->getRawPathNoTrailing() : $page;
    return $lang.$page;
  }

  // Set/Get translator mode
  public function setTranslatorMode($mode) { $this->translator_mode = $mode; }
  public function getTranslatorMode() { return $this->translator_mode; }

  // Add a string
  // lang = null means current language.
  // $quote: whether to quote the string when escaping or not
  public function addString($alias, $value, $lang = null, $quote = true, $tag = null){

    if ($lang === null)
      $lang = $this->getLanguage();

    if (!$lang){
      trigger_error("Trying to call Localizer::addString() without language specified.", E_USER_ERROR);
      return 0;
    }

    if(!$this->languageExists($lang))
      return;

    if(empty($value))
      $value_str = "NULL";
    else
      $value_str = phive("SQL")->escape($value, $quote);

    $save_arr = array('alias' => $alias, 'language' => $lang);

    if(!empty($value)){
      $this->memSet($alias, $lang, $value);
      $save_arr['value'] = $value_str;
    }

      $save_arr_check = phive('SQL')->sanitizeArray($save_arr, "Localizer_addString");
      if ($save_arr_check["alias"] === $save_arr["alias"]) {

          // We have some random numeric aliases appearing in localized_strings table.
          // Here we are logging a backtrace of numeric alias insertions to find out what code causes them.
          // Should be removed after issue with random numeric aliases fixed.
          if (is_numeric(trim($save_arr['alias']))) {
              phive('Logger')->warning(
                  'Inserting numeric alias',
                  ['alias' => $save_arr['alias'], 'trace' => debug_backtrace(0)]
              );
          }

          $ret = phive('SQL')->save('localized_strings', $save_arr);
      }

      if (!empty($tag) && !str_ends_with($alias, '.prev')) {
          $save_arr_conn_check = phive('SQL')->sanitizeArray(['tag' => $tag, 'target_alias' => $alias], "Localizer_addString");
          if ($save_arr_conn_check["target_alias"] === $alias) {
              phive('SQL')->save('localized_strings_connections', ['tag' => $tag, 'target_alias' => $alias]);
          }
      }

    return $ret;
  }

  // GetPotentialStringArray
  // Returns a translated array of string
  public function getPotentialStringArray($array, $lang=null, $nochange=false)
  {
    foreach($array as $key => $string) {
      if (substr($string, 0, 1) === '#') {
        $array[$key] = $this->getString(substr($string, 1, strlen($string)-1), $lang, $nochange);
      }
    }
    return $array;
  }

  // Edit string (will add if it doesn't exist)
    public function editString($alias, $value, $lang = null)
    {

        if (!pIfExists("string.$alias"))
            return false;

        if ($lang === null)
            $lang = $this->getLanguage();

        if (!$lang) {
            trigger_error("Trying to call Localizer::editString() without language specified.", E_USER_ERROR);
            return null;
        }

        $prevQuery = "SELECT * FROM localized_strings WHERE alias ='$alias' AND language='$lang' LIMIT 1";
        $prev = phive('SQL')->loadAssoc($prevQuery);
        if (count($prev)) {
            $this->setPreviousAliasValue($alias, $prev['language'], (string)$prev['value']);
        }

        $ret = phive('SQL')->save('localized_strings', array('alias' => $alias, 'language' => $lang, 'value' => $value));

        $this->memSet($alias, $lang, $value);

        if ($ret && $lang == $this->getLanguage()) {
            $this->cached[$lang . $alias] = $value;
        }

        if($ret){
            return ['current'=>$value, 'previous'=>$prev['value']];
        }

        return $ret;
    }

  // Delete string
  public function deleteString($alias, $lang=null){
    if(!pIfExists("string.$alias"))
      return false;

    return phive('SQL')->query(
      "DELETE FROM `" . $this->getSetting('table_localized_strings') .
      "` WHERE `alias`=" . phive('SQL')->escape($alias) .
      ($lang?(" AND `language`=" . phive('SQL')->escape($lang)):""));
  }

  // Returns all languages a certain string exists in
  public function getStringLanguages($alias)
  {
    $sql = phive('SQL');
    $table = $this->getSetting('table_localized_strings');
    $sql->query(
      "SELECT `language` FROM `$table` WHERE `alias`=" . $sql->escape($alias));
    $array = $sql->fetchArray('NUM');
    $ret = array();
    if (!empty($array))
    foreach ($array as $lang)
    {
      $ret[] = $lang[0];
    }
    return $ret;
  }

  function getAllTranslations($alias){
    $ret = phive('SQL')->load1DArr("SELECT * FROM localized_strings WHERE alias = '$alias'", 'value', 'language');
    $default_lang 	= $this->getDefaultLanguage();
    if(empty($ret)){
      foreach($this->getAllLanguages() as $lr)
        $ret[$lr['language']] = "($alias)";
      return $ret;
    }

    foreach($ret as $lang => $str){
      if(empty($str))
        $ret[$lang] = $ret[$default_lang];
      if(empty($ret[$lang]))
        $ret[$lang] = "($alias)";
    }
    return $ret;
  }

  function getRawString($alias, $lang){
    $alias = phive('SQL')->escape($alias);
    $lang = phive('SQL')->escape($lang);
    return phive("SQL")->getValue("SELECT value FROM localized_strings WHERE alias = $alias AND language = $lang");
  }

  /**
   * Get alias and value from localized_strings table
   *
   * @param string $lang
   * @param array $prefixes
   * @return array
   **/
  function getRawStringFromPrefixes($lang, $prefixes = array()){
        // Escape the prefixes
        $aliases = array_map(function($prefix) {
            return phive('SQL')->escape("mp.$prefix.prize");
        }, $prefixes);

        // Implode the aliases and construct a WHERE IN clause to filter by alias and language
        $aliases = implode(',', $aliases);
        $whereClause = " WHERE alias  IN ($aliases) AND language = '$lang'";

        //return multiple select values
        return phive("SQL")->loadArray("SELECT alias, value FROM localized_strings $whereClause");
  }

    function memGet($alias, $lang){
        // We first check local cache and return if found.
        $ret = $this->cached[$lang.$alias] ?? null;
        if($ret !== null){
            return $ret;
        }

        $key         = "localizer$alias$lang";
        $mem_host    = $this->getSetting('mem_host');
        $mem_cluster = $this->getSetting('mem_cluster');

        if(!empty($mem_cluster)) {
            $ret = mCluster($mem_cluster)->get($key);
        } else if(!empty($mem_host)) {
            $ret = phive('Redis')->exec('get', [$key], $mem_host, '', $this->getSetting('mem_port'));
        } else {
            $ret = phMget($key);
        }

        // We set the local cache.
        $this->cached[$lang.$alias] = $ret;

        return $ret;
    }

    function memSet($alias, $lang, $value){
        $key = "localizer$alias$lang";
        /*
           Commented out the below delete row, what is the point of it? examaple:
           [localizergameswithfreespins.cgamesen] => Games with Free Spins
           [localizerrowsen] => Rows
           We're effectively deleting stuff before we update, doesn't make sense?
         */
        //phM('delAll', "$key*");
        //phive('Redis')->exec('publish', array($channel, $msg), $ss['ws_node_host'], '', $ss['ws_node_port'])

        $mem_host = $this->getSetting('mem_host');
        $mem_cluster = $this->getSetting('mem_cluster');
        if(!empty($mem_cluster)){
            return mCluster($mem_cluster)->set($key, $value, 18000);
        }else if(!empty($mem_host)){
            return phive('Redis')->exec('set', [$key, $value, 18000], $mem_host, '', $this->getSetting('mem_port'));
        }else
            return phMset($key, $value);
    }

    /**
     * Flags empty alias for provided language
     *
     * @param $alias
     * @param $language
     * @return array|mixed|string|void
     */
    public function flagMissingAlias($alias, $language)
    {
        return $this->memSet($alias . '-empty-' . $language, $language, 1);
    }

    /**
     * Detect if alias+language were flagged as missing
     *
     * @param $alias
     * @param $language
     * @return bool
     */
    public function isAliasFlaggedMissing($alias, $language): bool
    {
        return !empty($this->memGet($alias . '-empty-' . $language, $language));
    }

  // Get a string
  // The $nochange option means the string is not meant to be altered under any
  //  circumstances. Ordinarily strings are changed to javascript links in
  //  translator mode.
  // ignorefallback: if true, then fall back language won't be used
    public function getString($alias, $lang = null, $nochange = null, $escape = false, $nofallback = false, $do_insert = true, $tag = null)
    {
        //Non existing alias due to programming error probably
        if (empty($alias))
            return '';

        //No weird characters in the alias please
        if (preg_match('|[^a-zA-Z0-9\.,_\-: ]|i', $alias))
            return '';

        if (strlen($alias) > 100) {
            return '';
        }

        //We have a broken alias due to programming or other error
        if (substr($alias, 0, 1) == '.' || substr($alias, -1) == '.' || strpos($alias, '..') !== false)
            return "$alias";

        $lang = $this->setLang($lang);

        //Someone is trying to hack by way of the language part
        if ($lang != 'all' && (strlen($lang) > 4 || preg_match('/[^\da-z]/i', $lang)))
            return '';

        if ($this->capture)
            $this->ca[] = $alias;

        if (!$lang)
            return null;

        // Are we in translator mode?
        $tm = $this->getTranslatorMode();
        $ret = null;
        if ($lang === 'all') {
            $sqlstr = "SELECT `language`, `value` FROM `" . $this->getSetting('table_localized_strings') . "` " .
                "WHERE `alias`=" . phive('SQL')->escape($alias);
            $ret = phive('SQL')->loadKeyValues($sqlstr, 'language', 'value');
        } else {
            $lang_overwrite = $this->getDomainLanguageOverwrite();
            // don't overwrite the provided $lang when in translator mode
            $try_overwrite = !$tm
                && !empty($lang_overwrite)
                && strtolower($lang_overwrite) !== strtolower($lang)
                // don't try to overwrite when there's no translation for alias + overwrite:
                && !$this->isAliasFlaggedMissing($alias, $lang_overwrite);

            if ($try_overwrite) {
                $ret = $this->memGet($alias, $lang_overwrite);
                if (!empty($ret)) {
                    return $ret;
                }

                $ret = $this->getRawString($alias, $lang_overwrite);
                // flag alias + overwrite to skip future db queries
                if (empty($ret)) {
                    $this->flagMissingAlias($alias, $lang_overwrite);
                } else {
                    $this->memSet($alias, $lang_overwrite, $ret);
                }
            }

            if (empty($ret)) {
                if (!$tm) {
                    $ret = $this->memGet($alias, $lang);
                    if (!empty($ret)) {
                        return $ret;
                    }
                }

                $ret = $this->getRawString($alias, $lang);
                if (empty($ret)) {
                    $ret = $this->getRawString($alias, $this->getDefaultLanguageFromConfig());
                }
                if (!$tm) {
                    $this->memSet($alias, $lang, $ret);
                }

                if (phive("SQL")->numRows()) {
                    $ret = phive("SQL")->result();
                    if ($ret == false) {
                        $ret = $nochange !== 'verystrict' ? "($alias)" : "";
                    } else if ($tm && $ret == "")
                        $ret = '(empty)';
                } else {
                    // Create it with "null" as value
                    // You can run a clean all "null" later
                    if ($do_insert)
                        $this->addString($alias, null, $lang, true, $tag);

                    if (!$nochange)
                        $ret = "($alias)";
                    else
                        $ret = "";
                }
            }
        }

        if ($escape)
            $ret = htmlspecialchars($ret);

        // This might look odd, but we don't want to add any code with blank space
        //  in them, as they might be converted to &nbsp; by certain parts of Phive.
        // Here we only use break and tabs, which still makes it valid HTML.
        if ($tm && !$nochange) {
            $path = phive()->getPath() . '/modules/Localizer/html/editstrings.php?arg0=' . $lang . '&arg1=' . $alias;
            // $url_alias = str_replace('.', ':', $alias);
            $ret = "<span onclick=\"window.open('$path','mywindow')\" style=\"cursor:pointer\">$ret</span>";
        }

        // Off for now as I believe we don't use it /Henrik
        // if ($lang != 'all' && $do_insert)
        //    $this->setTimestamp($alias, $lang);

        return $ret;
    }

    public function setPreviousAliasValue(string $alias, string $lang, string $value): void
    {
        $previousAlias = $alias . '.prev';
        $this->memSet($previousAlias, $lang, $value);
    }

    public function getPreviousAliasValue(string $alias, string $lang): ?string
    {
        $previousAlias = $alias . '.prev';
        return $this->memGet($previousAlias, $lang);
    }

    public function getPreviousAliasValuesForAllLangs(string $alias): array
    {
        $langs = array_map(fn ($value) => $value['language'], $this->getAllLanguages());
        $langs = array_combine($langs, $langs);

        return array_map(
            fn ($lang) => $this->getPreviousAliasValue($alias, $lang),
            $langs
        );
    }

  // GetPotentialString
  //  So this takes a string that could potentially be an alias
  //  If it doesn't start with #, it will return the same value,
  //   if it starts with #, the rest is taken as the alias and the
  //   string is fetched.
  public function getPotentialString($string, $lang=null, $nochange=false){
    if (substr($string, 0, 1) === '#')
      $str = $this->getString(substr($string, 1, strlen($string)-1), $lang, $nochange);
    else
      $str = $string;
    return $this->handleReplacements($str);
  }

    public function getPotentialStringOrAlias($string, $lang = null, $nochange = false){
        $str = $this->getPotentialString($string, $lang, $nochange);
        if(preg_match('|^\((.*)\)$|', $str, $m) === 1){
            return $m[1];
        }
        return $str;
    }

  public function getPotentialStringOrEmpty($string, $lang=null, $nochange=false){
    $res = $this->getPotentialString($string, $lang, $nochange);
    if(preg_match('|^\(.*\)$|', $res) === 1)
      return '';
    return $res;
  }

  public function setLang($lang = null){
    if ($default = ($lang === 'default'))
      $lang = $this->getLanguage();
    else if ($lang === null){
      // See if a temporarily language has been set
      if (isset($_SESSION['language']) &&
          $_SESSION['language'] &&
          $_SESSION['language'] !== 'denied')
        $lang = $_SESSION['language'];
      else
        $lang = $this->getLanguage();
    }
    $lang = $this->checkLanguageOrGetDefault($lang);
    return $lang;
  }

  function isEditing(){
    return isset($_GET['editcontent']);
  }

  public function getEditLink($alias, $lang=null)
  {
    $lang = $this->setLang($lang);
    $path = phive()->getPath() . '/modules/Localizer/html/editstrings.php?arg0=' . $lang . '&arg1=' . $alias;
    $ret = "<span onclick=\"window.open('$path','mywindow')\" style=\"cursor:pointer\">Edit $alias</span>";
    return $ret;
  }

  public function getLanguages($exclude_current = false, $exclude = array()){
    if($exclude_current)
      $exclude[] = $this->getLanguage();

    if(!empty($exclude)){
      $in = phive("SQL")->makeIn($exclude);
      $where = "WHERE language NOT IN($in)";
    }

    return phive('SQL')->load1DArr("SELECT * FROM languages $where", 'language');

  }

  public function languageExists($lang)
  {
    if ($lang == 'all') {
       return true;
    }

    // {2,4} - allow 2 to 4 characters
    if (!preg_match("/^[a-z]{2,4}$/i", $lang)) {
      return false;
    }

    if (isset($this->lang_exists[$lang])) {
      return $this->lang_exists[$lang];
    }

    $cache_key = ".lang.exists.";
    $cache_lang_exists = $this->memGet($cache_key, $lang);

    if (isset($cache_lang_exists)) {
        return (boolean) $cache_lang_exists;
    }

    $table = $this->getSetting('table_languages');
    $lang_exist = (boolean) phive('SQL')->getValue("SELECT count(*) FROM `$table` WHERE `language`=" . phive('SQL')->escape($lang));
    $this->memSet($cache_key, $lang, $lang_exist);
    $this->lang_exists[$lang] = $lang_exist;

    return $lang_exist;
  }


  public function addLanguage($lang)
  {
    $table = $this->getSetting('table_languages');
    return phive('SQL')->query(
      "INSERT INTO `$table` SET `language`=" . phive('SQL')->escape($lang));
  }

  public function deleteLanguage($lang)
  {
    $table = $this->getSetting('table_languages');
    return phive('SQL')->query(
      "DELETE FROM `$table` WHERE `language`=" . phive('SQL')->escape($lang));
  }

  public function getAllLanguages($where = '', $select = 'language', bool $useCache = false)
  {
    $table = $this->getSetting('table_languages');
    if (count($this->excludedLanguages)) {
        $where = trim($where);
        $condition = " language NOT REGEXP '^(" . implode("|", $this->excludedLanguages) . ")'";
        if (empty($where)) {
            $where .= " WHERE {$condition}";
        } else {
            $where .= " AND {$condition}";
        }
    }

    $query = "SELECT $select FROM `$table` $where";
    $memKey = "localizer.$query";

    if ($useCache) {
        $languages = phMgetArr($memKey);
        if ($languages) {
            return $languages;
        }
    }

    $languages = phive('SQL')->loadArray($query);

    if ($useCache) {
        phMsetArr($memKey, $languages, 3600);
    }

    return $languages;
  }

  /**
   * Gets the country code by with language
   * if $language parameter provided, it will return country code for the language
   * if $language is null then it will return all the languages with country codes in format [ de => DE, da => DK ... ]
   * @param string|null $language language, Format: de
   * @return string|array
   */
  public function getCountryByLanguage($language = null)
  {
      if(!empty($this->countries_by_language)) {
          return !empty($language) ? $this->countries_by_language[$language] : $this->countries_by_language;
      }

      $sql = "SELECT c.langtag FROM languages as l
              INNER JOIN countries as c ON l.language = c.language
              WHERE l.selectable = 1";

      $langtags = phive('SQL')->loadArray($sql);

      foreach ($langtags as $langtag){
         $exploded = explode("-", $langtag['langtag']);
         $lang = $exploded[0];
         $country  = $exploded[1];
         $this->countries_by_language[$lang] = $country;
      }

      return !empty($language) ? $this->countries_by_language[$language] : $this->countries_by_language;
  }

    public function getLangSelect($where = '', $excluded_languages = []): array
    {
        $langs = $this->getAllLanguages($where);
        $curLangs = array();
        $this->excludedLanguages = array_unique(array_merge($this->excludedLanguages, $excluded_languages));
        foreach ($langs as $l) {
            $language = $l['language'];
            if (in_array($language, $this->excludedLanguages)) {
                continue;
            }
            $curLangs[$l['language']] = t('lang.' . $language);
        }

        return $curLangs;
    }

    public function filterLanguageOptions(DBUser $user = null, array $screenedLanguages = []): Localizer
    {
        if ($user && $user->getCountry() != 'DE') {
            $screenedLanguages[] = 'de';
        }
        $this->excludedLanguages = $screenedLanguages;

        return $this;
    }

  /**
   * For the countries table
   */
  public function getCountriesTableStructure()
  {
    $table_str = $this->getSetting('table_countries');

    $sql = phive('SQL');
    $sql->query("SHOW FULL COLUMNS FROM " . $table_str);
    $structure = $sql->fetchArray();

    return $structure;
  }

  public function getCountry(){
    return $this->country['country'];
  }

  function getSubDomain(){
    return $this->country['subdomain'];
  }

    public function getCountryData($country = null){
        if ($country === null)
            return $this->country;
        $table = $this->getSetting('table_countries');
        return phive('SQL')->loadAssoc('', 'countries', array('country' => $country));
    }

    function getLocale($lang, $column = 'setlocale'){
        $loc = $this->getCountryValue($column, $lang);
        if(empty($loc))
            return 'en_GB';
        return str_replace('.utf8', '', $loc);
    }

  public function getAllBankCountries($key = false){
    return phive('SQL')->readOnly()->loadArray("SELECT * FROM `bank_countries`", 'ASSOC', $key);
  }

  function getBankCountryByIso($iso2){
    return phive('SQL')->loadAssoc("SELECT * FROM `bank_countries` WHERE iso = '$iso2'");
  }

    /** Gets a 3 letter country code from a 2 letter country code
     *
     * @param $iso2 ISO 3166-1 alpha-2  (2 Letter country code)
     *
     * @return string ISO 3166-1 alpha-3  (3 Letter country code)
     */
  function getCountryIso3FromIso2($iso2){
    return phive('SQL')->getValue("", 'iso3', 'bank_countries', ['iso' => $iso2]);
  }

  function getBankCountryByCallCode($code){
    return phive('SQL')->loadAssoc("SELECT * FROM `bank_countries` WHERE calling_code = '$code'");
  }

  function getCountries($exclude_current = false, $exclude = array()){
    if($exclude_current)
      $exclude[] = $this->getLanguage();

    if(!empty($exclude)){
      $in = phive("SQL")->makeIn($exclude);
      $where = "WHERE language NOT IN($in)";
    }

    return phive('SQL')->loadArray("SELECT * FROM countries $where");
  }

  public function getAllCountries(){
    $table = $this->getSetting('table_countries');
    return phive('SQL')->loadArray("SELECT * FROM `$table`");
  }

    public function getStringCount($lang, array $multiple = [])
    {
        $table = $this->getSetting('table_localized_strings');
        if (count($multiple) > 0) {
            $queryBuilder = "SELECT ";
            foreach ($multiple as $languages => $lang) {
                $as = $lang . "_string_count";
                $queryBuilder .= "(SELECT COUNT(*) FROM $table WHERE value IS NOT NULL AND language=" . phive('SQL')->escape($lang) . ") as $as, ";
            }
            $queryBuilder = rtrim($queryBuilder, ", ");
            return phive('SQL')->loadArray($queryBuilder);
        } else {
            phive('SQL')->query(
                "SELECT COUNT(*) FROM `$table` WHERE `value` IS NOT NULL AND `language`=" .
                phive('SQL')->escape($lang));
            return phive('SQL')->result();
        }
    }

    public function getTotalStringCount()
    {
        $table = $this->getSetting('table_localized_strings');
        phive('SQL')->query(
            "SELECT COUNT(DISTINCT `alias`) FROM `$table`");
        return phive('SQL')->result();
    }

  private function buildQueryUntranslatedByPage($page_id, $lang, $sql_extra="")
  {
    $la = phive('SQL')->escape($lang);
    $table = $this->getSetting('table_localized_strings');
    $db_cache = $this->getSetting('table_cache');

    return "FROM `$table` as str
      INNER JOIN `$db_cache` as cache ON str.`alias`=cache.`alias` AND cache.`page_id`=$page_id
      WHERE
      ((`value` IS NULL AND `language`=$la) OR (str.`alias` NOT IN
      (SELECT `alias` FROM `$table` WHERE `language`=$la))) $this->excludes $sql_extra";
  }

  public function getUntranslatedByPage($page_id, $lang, $sql_extra="")
  {
    $sql = phive('SQL');
    $req_str = $this->getSetting('set_timestamp')?", str.`requested`":"";
    $sql->query("SELECT DISTINCT str.`alias` $req_str " .
      $this->buildQueryUntranslatedByPage($page_id, $lang, $sql_extra));

    return $sql->fetchArray();
  }

  public function numUntranslatedByPage($page_id, $lang, $sql_extra="")
  {
    $sql = phive('SQL');
    $sql->query("SELECT COUNT(DISTINCT str.`alias`) " .
      $this->buildQueryUntranslatedByPage($page_id, $lang, $sql_extra));

    return $sql->result();
  }

    public function getUntranslatedStrings($lang, $sql_extra = "", $count = false)
    {
        $table = $this->getSetting('table_localized_strings');
        $default_lang = $this->getDefaultLanguage();

        if ($lang == $default_lang) {
            $query = $count ? "SELECT COUNT(alias) as count" : "SELECT LOWER(alias) as alias, value, requested";
            $query = $query . " FROM $table WHERE language = '$default_lang' AND (value IS NULL OR value = '') " . $this->excludes . " ORDER BY alias" . $sql_extra;
        } else {
            // For non-default languages, it checks for strings that either:
            // Don't exist in the target language, or Exist in the target language but are empty.
            $query = $count ? "SELECT COUNT(DISTINCT ls1.alias) as count" : "SELECT LOWER(ls1.alias) as alias, ls1.value as def_value, ls1.requested";
            $query .= " FROM $table as ls1";
            $query .= " WHERE ls1.language = '$default_lang'";
            $query .= " AND (NOT EXISTS (SELECT 1 FROM $table as ls2 WHERE ls2.alias = ls1.alias AND ls2.language = '$lang' AND ls2.value != '')";
            $query .= " OR (SELECT ls2.value FROM $table as ls2 WHERE ls2.alias = ls1.alias AND ls2.language = '$lang') = '')";
            $query .= $this->excludes;

            if (!$count) {
                $query .= " ORDER BY ls1.alias" . $sql_extra;
            }
        }

        if ($count) {
            phive('SQL')->query($query);
            return phive('SQL')->result();
        } else {
            return phive('SQL')->loadArray($query, $count ? 'NUM' : 'ASSOC', $count ? false : 'alias');
        }
    }

    public function getUntranslatedStringCount($lang, $sql_extra = "", array $multiple = [])
    {
        return $this->getUntranslatedStrings($lang, $sql_extra, true);
    }

    function getAllStrings($lang, $sql_extra = "", $includenulls = false, $filter = '', $filterbyval = '', $by_key = null)
    {
        $table = $this->getSetting('table_localized_strings');
        $where = $this->getAllStringsWhere($includenulls, $filter, $filterbyval);
        $query = "SELECT LOWER(`alias`) AS alias, `value`, `requested` FROM `$table` WHERE $where `language`=" . phive('SQL')->escape($lang);
        $query .= "{$this->excludes} ORDER BY `alias` ";
        $str = $query . "  $sql_extra";

        return phive('SQL')->loadArray($str, 'ASSOC', $by_key);
    }

    function getAllStringsCountWithConditions($lang, $sql_extra = "", $includenulls = false, $filter = '', $filterbyval = '', $by_key = null)
    {
        $table = $this->getSetting('table_localized_strings');
        $where = $this->getAllStringsWhere($includenulls, $filter, $filterbyval);
        $query = "SELECT COUNT(*) FROM `$table` WHERE $where `language`=" . phive('SQL')->escape($lang);
        $query .= $this->excludes . " $sql_extra";

        return phive('SQL')->getValue($query);
    }

    private function getAllStringsWhere($includenulls = false, $filter = '', $filterbyval = '')
    {
        $where = !$includenulls ? "`value` IS NOT NULL AND " : "";
        $where .= empty($filter) ? "" : " alias LIKE '%$filter%' AND ";
        $where .= empty($filterbyval) ? "" : " value LIKE '%$filterbyval%' AND ";
        $where .= " alias NOT LIKE '%.prev' AND ";

        return $where;
    }

  // Don't get the null strings
  public function getAllStringsByPage($page_id, $lang, $sql_extra="", $includenulls=false)
  {
    $sql = phive('SQL');
    $page_id_esc = $sql->escape($page_id);

    $table = $this->getSetting('table_localized_strings');
    $db_cache = $this->getSetting('table_cache');
    $where = (!$includenulls?"`value` IS NOT NULL AND ":"");
    $sql->query(
      "SELECT `$table`.`alias`, `$table`.`value`, `$table`.`requested` FROM `$table`
      INNER JOIN `$db_cache` ON `$table`.`alias`=`$db_cache`.`alias` AND `$db_cache`.`page_id`=$page_id_esc
      WHERE $where `$table`.`language`=" .
      $sql->escape($lang) .
      "$this->excludes ORDER BY `$table`.`alias` $sql_extra");
    return $sql->fetchArray();
  }

  public function setCountryBySubdomain($sub)
  {
    $table = $this->getSetting('table_countries');
    phive('SQL')->query(
      "SELECT * FROM `$table` WHERE `subdomain`=" .
      phive('SQL')->escape($sub));
    $this->country = phive('SQL')->fetch();
    if (isset($this->country['language']))
    {
      $this->setLanguage($this->country['language']);
      return true;
    }
      return false;
  }

  public function setCountry($country)
  {
    $table = $this->getSetting('table_countries');
    phive('SQL')->query(
      "SELECT * FROM `$table` WHERE `country`=" .
      phive('SQL')->escape($country));
    $this->country = phive('SQL')->fetch();
    if (isset($this->country['language']))
      $this->setLanguage($this->country['language']);
  }

  public function setDefaultCountry()
  {
    $this->setCountry($this->default_country);
  }

  function getCountryFromLang($lang): array
  {
      if (isset($this->country_by_lang[$lang])) {
          return $this->country_by_lang[$lang];
      }

      $cache_key = ".cache.country.";
      $cache_lang_country = $this->memGet($cache_key, $lang);

      if (isset($cache_lang_country)) {
          return json_decode($cache_lang_country, true);
      }

      $country = (array) phive('SQL')->loadAssoc("SELECT * FROM countries WHERE `language`=" . phive('SQL')->escape($lang));
      $this->memSet($cache_key, $lang, json_encode($country));
      $this->country_by_lang[$lang] = $country;
      return $country;
  }

  function getLangFromCountry($country){
    $lang = phive('SQL')->getValue("SELECT language FROM countries WHERE subdomain = '$country'");
    if(empty($lang))
      return $this->getDefaultLanguage();
    return $lang;
  }

    /**
     * This method is called on each page load and redirects to the correct domain url based on the country given by IP and jurisdiction
     *
     * @param string $to_url
     * @param int $check_lvl
     * @param string $sess_key
     * @param string $ip
     */
    function redirectToUsersNonSub($to_url = '/', $check_lvl = 0, $sess_key = 'start_lang', $ip = ''){
        //$ip = '178.16.216.152'; // Swedish IP
        //$ip = '62.149.208.192'; // Italian IP
        //$ip = '216.73.162.239'; // Ontario IP
        $ip = !empty($ip) ? $ip : remIp();
        $redir_code = "301 Moved Permanently";

        if($this->getSetting('test') === true)
            return;

        $geo_ip          = phive('IpBlock')->getGeoIpRecord($ip);
        $countries       = phive('Config')->valAsArray('countries', 'ip-block');
        $country         = phive('IpBlock')->getCountry($ip);
        $province        = !empty($geo_ip->mostSpecificSubdivision->isoCode) ?
            $geo_ip->mostSpecificSubdivision->isoCode :
            phive('IpBlock')->getProvinceFromIp(null, $ip, 'province_redirect_to_own_top_domain');
        $country_domain = $this->getSetting('country_domain')[$country];
        $province_domain = !empty($province) ? $this->getSetting('province_domain')[$country][$province] : null;
        $current_domain = $_SERVER['SERVER_NAME'];
        $is_forbidden_country = $this->getSetting('forbidden_provinces')[$country][$province] || in_array($country, $countries);
        $included_countries = $this->getSetting('included_countries');
        $is_included_country = empty($included_countries) || in_array($country, $included_countries);
        $is_whitelisted_ip = phive('IpGuard')->isWhitelistedIp($ip);

        if ((!$is_included_country || $is_forbidden_country) && !$is_whitelisted_ip) {
            if ($_SERVER['REQUEST_URI'] === '/forbidden-country/') {
                return;
            }
            phive('Redirect')->to("/forbidden-country/");
        }

        $target_domain = $province_domain ?? $country_domain;

        $iso_overwrite = phive('Localizer')->getDomainSetting('domain_iso_overwrite');
        $ip_country_province = phive('IpBlock')->getJurisdictionFromIp($ip);
        if (empty($target_domain) && !empty($iso_overwrite) && $iso_overwrite !== $ip_country_province) {
            $target_domain = $this->getSetting('primary_domain');
        }

        $should_redirect_to_another_domain = !phive()->isLocal()
            && !$is_whitelisted_ip
            && !empty($target_domain)
            && $target_domain != $current_domain;

        if ($should_redirect_to_another_domain) {
            $url = "https://{$target_domain}";
            if (!empty($_SESSION['affiliate'])) {
                $arr = parse_url($url);
                $query = (!empty($arr['query']) ? "{$arr['query']}&" : '') . "referral_id={$_SESSION['affiliate']}";
                $url = "{$arr['scheme']}://{$arr['host']}{$arr['path']}?{$query}";
            }
            return phive('Redirect')->toExt($url);
        }

        $url_language = phive('Pager')->getUrlLanguage();
        $user = cu();
        $lic_country = !empty($user) ? phive('Licensed')->getLicCountry($user) : $country;

        $query_string = http_build_query(
            array_filter($_GET, fn($key) => $key !== 'dir', ARRAY_FILTER_USE_KEY)
        );

        if ($url_language && !$this->isLanguageSupported($url_language, $lic_country)) {
            $to_url = preg_replace("/^$url_language/", '', $to_url ?? '');

            if ($query_string) {
                $to_url .= '?' . $query_string;
            }

            phive('Redirect')->to($to_url, '', false, $redir_code);
        }

        if (lic('getLicSetting', ['forced_sub_lang'], null, null, $lic_country)) {
            unset($_SESSION[$sess_key]);
            if (empty($user) || $lic_country != $country) {
                $redir_code = "307 Temporary Redirect";
                $country = $lic_country; // users navigating from a different country
            }
        } else {
            if (!empty($_SESSION[$sess_key]))
                return;
        }

        $_SESSION[$sess_key] = true;

        $top_page  = phive('Pager')->getAtLvl($check_lvl);
        if(!empty($top_page))
            return;

        if (phive('Redirect')->isBot(null, true)) {
            return;
        }

        $lang = $this->getLangFromCountry($country);
        if (!$this->isLanguageSupported($lang)) {
            $lang = $this->getDefaultLanguage();
        }

        if($lang == $this->getDefaultLanguage())
            return;
        $sub_lang  = $this->getCurNonSubLang();
        if($lang == $sub_lang)
            return;

        if(phive()->isMobile()) {
            if($lang !== $sub_lang && $sub_lang  . '/mobile' === $to_url) {
                $to_url = '';
            }
        } else {
            if($lang !== $sub_lang && $sub_lang === $to_url) {
                $to_url = '';
            }
        }

        if ($query_string) {
            $to_url .= '?' . $query_string;
        }

        phive('Redirect')->to($to_url, $lang, false, $redir_code);
    }

    function setCurNonSubCountry(){
        $dir = explode('/', $_GET['dir']);
        $lang = array_shift($dir);

        $is_correct_lang = $this->isLanguage($lang);
        if ($is_correct_lang) {
            phive('Pager')->setUrlLanguage($lang);
        }

        if (!$this->isLanguageSupported($lang)) {
            $lang = $this->getDefaultLanguage();
        }
        $this->setNonSubLang($lang);
        $this->setLanguage($lang);
    }

    /**
     * Gets the default language for the site.
     *
     * @return mixed|string
     */
    public function getDefaultLanguage(): ?string
    {
        return $this->getDomainSetting('default_lang', 'en');
    }

    /**
     * Gets the default language for the site from default config.
     *
     * @return mixed|string
     */
    public function getDefaultLanguageFromConfig(): ?string
    {
        $setting = $this->getSetting('default_lang', 'en');
        $environment = 'default';

        if (!is_array($setting)) {
            return $setting;
        }

        if (!empty($setting[$environment])) {
            return $setting[$environment];
        }
        return 'en';
    }

    /**
     * Gets the language overwrite for configured domain.
     * When user is provided get language based on provided user country.
     *
     * @return mixed|string
     */
    public function getDomainLanguageOverwrite($user = null): ?string
    {
        if (empty($user)) {
            return $this->getDomainSetting('domain_language_overwrite', null);
        }

        $user = cu($user);
        if (empty($user)) {
            return null;
        }
        $language = lic('getDomainLanguageOverwrite', [], $user);
        // doing additional check to normalize the return types to string or null
        if (empty($language)) {
            return null;
        }

        return $language;
    }

    /**
     * @param string|null $language
     * @return bool
     */
    public function isLanguage(?string $language = null): bool
    {
        if (!$language) {
            return false;
        }

        $languages = array_map(
            function ($value) {
                return $value['language'];
            },
            $this->getAllLanguages('', 'language', true)
        );

        return in_array($language, $languages, true);
    }

    /**
     * @param string|null $lang
     * @return bool
     */
    public function isLanguageSupported(?string $lang = null, ?string $lic_country = null): bool
    {
        if (!$this->languageExists($lang)) {
            return false;
        }

        $forbidden_url_languages = lic('getLicSetting', ['forbidden_url_languages'], null, null, $lic_country);
        if (in_array($lang, $forbidden_url_languages)) {
            return false;
        }

        if ($lang && !isLogged() && !$this->isLanguageSupportedForLoggedOutUser($lang)) {
            return false;
        }

        if ($lang && isLogged() && !$this->isLanguageSupportedForLoggedInUser($lang)) {
            return false;
        }

        $langs = $this->getDomainSetting('allowed_lang');
        $langs = array_filter(is_array($langs) ? $langs : [(string)$langs]);
        if (empty($langs)) {
            $langs = ['all'];
        }
        return in_array('all', $langs) || in_array($lang, $langs);
    }

    /**
     * @param string $language
     * @return bool
     */
    private function isLanguageSupportedForLoggedOutUser(string $language): bool
    {
        $escapedLanguage = phive('SQL')->escape($language);
        $languages = $this->getAllLanguages("WHERE language = $escapedLanguage", 'logged_out', true);

        return !empty($languages) && (int)$languages[0]['logged_out'] === 1;
    }

    /**
     * @param string $language
     * @return bool
     */
    private function isLanguageSupportedForLoggedInUser(string $language): bool
    {
        $escapedLanguage = phive('SQL')->escape($language);
        $languages = $this->getAllLanguages("WHERE language = $escapedLanguage", 'logged_in', true);

        return !empty($languages) && (int)$languages[0]['logged_in'] === 1;
    }

  public function getCurrentCountry()
  {
    return $this->country;
  }

    public function getCountryValue($value, $lang = null){
        if ($lang === null)
            return $this->country[$value];
        else{
            $table = $this->getSetting('table_countries');
            $str = "SELECT `$value` FROM `$table` WHERE `language`=" .phive('SQL')->escape($lang);
            return phive('SQL')->getValue($str);
        }
    }

  // Delete all "null"-valued strings in all languages
  public function deleteNulls()
  {
    $sql = phive('SQL');
    $table = $this->getSetting('table_localized_strings');
    $sql->query(
      "DELETE FROM `$table` WHERE `value` IS NULL");
  }

  public function getCurLang(){
    $host = explode('.', $_SERVER['HTTP_HOST']);
    $this->setCountryBySubdomain($host[0]);
    return $this->getLanguage();
  }

  public function getCurCountry(){
    $host = explode('.', $_SERVER['HTTP_HOST']);
    return $host[0];
  }

  function ajaxSetLang(){
    if(!empty($_REQUEST['lang']))
      $this->setLanguage($_REQUEST['lang']);
    else
      $this->setLanguage($this->getCurNonSubLang());
  }

  public function setTimestamp($alias, $lang){
    if (!$this->getSetting('set_timestamp'))
      return;

    $sql = phive('SQL');
    $tbl = $this->getSetting('table_localized_strings');
    $arr = array("`alias`={$sql->escape($alias)}",
             "`language`={$sql->escape($lang)}");

    $where = "WHERE " . implode(" AND ", $arr);
    $sql->query("SELECT COUNT(*) FROM `$tbl` $where LIMIT 1");
    if($sql->result() > 0)
      $sql->query("UPDATE `$tbl` SET `requested`=NOW() $where");
    else
      $sql->query("INSERT INTO `$tbl` SET `requested`=NOW(), " .
        implode(", ", $arr));
  }

  function editing(){
    return isset($_GET['editcontent']);
  }

  function getCompNotificationStr($str, $lang = null, $as_str = true){
    if(strpos($str, 't:') === false)
      return false;

    if(strpos($str, 't:') === 0)
      list($t, $str) = explode(':', $str);
    else{
      //tt: case
      $arr = phive()->fromDualStr($str);
      $str = $this->getString($arr['tt'], $lang);
      return $this->replaceAssoc($arr, $str);
    }
    return $as_str ? $this->getString($str, $lang) : $str;
  }

  function replaceAssoc($arr, $str, $lang = 'en'){
    $keys = array_map(function($key){ return '{{'.$key.'}}'; }, array_keys($arr));
    $vals = array_values($arr);
    foreach($vals as &$val){
      if($tmp = $this->getCompNotificationStr($val, $lang, true))
        $val = $tmp;
    }
    return str_replace($keys, $vals, $str);
  }

  /*
     replacements on the form "bla bla {{1}} bla bla {{2}}" where $replacements is a numeric array
     AND:
     {{ciso}} -> current currency, ex: EUR
     {{csym}} -> current currency symbol, ex: $
     {{clang}} -> current language, ex: en
     {{cdn}} -> random media machine, ex: https://media1.videoslots.com
     {{modm:10}} -> 100 if currenct currency is SEK
     {{modd:100}} -> 10 if currenct currency is SEK
     {{phive|UserHandler|getUser|12}} -> get user with user id 12
     {{phDiv|100|10}} -> 10
     {{freespins:10}} -> 10 as default value, or another value based on a bonus code
     {{welcomebonus:200}} -> 200 as default value, or another value based on a bonus code
   */
  function doReplace($str, $replacements = array()){
    $needles = array();

    if(!is_array($replacements))
      $replacements = array($replacements);

    foreach($replacements as $key => $value)
      $needles[] = is_numeric($key) ? "{{".($key+1)."}}" : "{{".$key."}}";
    return $this->handleReplacements(str_replace($needles, $replacements, $str));
  }

    function handleReplacements($str, $cents = false, $alias = '', $lang = ''){

        if(strpos($str, '{') === false)
            return $str;

        if(empty($lang))
            $lang = cLang();

        $page = phive("Pager")->getRawPathNoTrailing();

        $str = str_replace(array('{{page}}'), array($page), $str);

        if(phive("Currencer")->getSetting('multi_currency') == true){
            $str = str_replace(
                ['{{ciso}}', '{{csym}}', '{{clang}}', '{{cdn}}', '{{baseurl}}'],
                [ciso(), cs(), $lang, getMediaServiceUrl(), phive('Casino')->getBasePath($lang, null, true)],
                $str
            );
            $str = preg_replace_callback('|\{\{([^\{]+)\}\}|', function($m) use ($cents){
                $arr = phive()->trimArr(explode(":", $m[1]));

                if($arr[0] == 'modm' || $arr[0] == 'welcomebonus'){

                    if($arr[0] == 'welcomebonus'){
                        // {{welcomebonus:11}}
                        $this->getReplacerFromBonusTypes($arr, 'welcomebonus');
                    }

                    $should_format_number = str_contains($arr[1], ',');
                    if ($arr[0] == 'modm' && $should_format_number) {
                        $arr[1] = str_replace(',', '', $arr[1]);
                    }

                    if($cents)
                        $arr[1] /= 100;
                    $amount = mc($arr[1], ciso(), 'multi', false);
                    if($amount > 999 && $should_format_number)
                        return number_format($amount);
                    else
                        return $amount;
                }else if($arr[0] == 'modd'){
                    if($cents)
                        $arr[1] /= 100;
                    $amount = mc($arr[1], ciso(), 'div', false);
                    if($amount > 999)
                        return number_format($amount);
                    else
                        return $amount;
                }else if($arr[0] == 'freespins'){
                    // {{freespins:11}}
                    $this->getReplacerFromBonusTypes($arr, 'freespins');
                    return $arr[1];
                }else{
                    return $m[0];
                }
            }, $str);

            if(!empty($cache_key))
                phMset($cache_key, $str);
        }

        if(strpos($str, '|') !== false){
            $str = preg_replace_callback('|\{\{([^\{]+)\}\}|', function($m){
                $arr = phive()->trimArr(explode("|", $m[1]));
                if($arr[0] == 'phive'){
                    array_shift($arr);
                    $module = array_shift($arr);
                    $func   = array_shift($arr);
                    if(in_array($func, ['accUrl', 'getUserAccountUrl', 'modDate'])){
                        // If module is empty we go for the Phive.base.php class.
                        $module = empty($module) ? 'phive' : $module;
                        return call_user_func_array(array(phive($module), $func), $arr);
                    }
                } else if($arr[0] == 'env') {
                    return phive('Distributed')->getLocalBrandId();
                } else {
                    $func = array_shift($arr);
                    if(in_array($func, ['phDiv', 'phMulti', 'date', 'nfCents', 'nf2', 'rnfCents'])){
                        return call_user_func_array($func, $arr);
                    }
                }
            }, $str);
        }

        return $str;
    }

    /**
     * Get the replacer from table bonus_types, if the user has a bonus code in the session.
     *
     * @param array $arr
     * @param string $type
     */
    function getReplacerFromBonusTypes(&$arr, $type)
    {
        // check if we have a bonus code
        $bonus_code = phive('Bonuses')->getBonusCode();
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        if(!empty($bonus_code)) {
            $sql = "SELECT bonus_name FROM bonus_types WHERE bonus_code = '{$bonus_code}' AND brand_id = {$brandId} AND bonus_name LIKE '%{$type}%'";
            $result = phive('SQL')->getValue($sql);
            // TODO: HENRIK sharding, if needed
            //$result = phive('SQL')->sh('', '', 'bonus_types')->getValue($sql);
            if(!empty($result)) {
                // get the modm value from the result, and use that as $arr[1]
                $start_position = strpos($result, "{$type}:") + strlen($type) + 1;
                $end_position = strpos($result, '}}', $start_position);
                $length = $end_position - $start_position;
                $string = substr($result, $start_position, $length);
                $arr[1] = (int) $string;
            }
        }
    }

    //From: http://bb.brucebli.com/2011/06/06/use-regular-expression-to-validate-html-brokenmismatched-tags/
    function checkHtml($content){
        $content = strtolower($content);
        $content = str_replace('</ ', '</', $content);
        // do not match the self closed ones, such as
        $selfClosed = array('img', 'image', 'br', 'hr', 'link', 'meta', 'base', 'basefont', 'input', 'area', 'param');
        // 1st of all, get all tags (with/without slash) out
        preg_match_all('/<(\/{0,1}[\w]*[^\s^<^>])/sim', $content, $alltags);
        if (!empty($alltags[1])) {
            // means we found matches!
            $tmpTag      = array();
            $matchedTags = $alltags[1];
            // what is impossible here is that there's no way to check nested ones
            foreach ($matchedTags as $tag) {
                // rtrim the / for br/ hr/ etc
                $tag = rtrim($tag, '/');
                // skip the self closed ones
                if (!in_array($tag, $selfClosed)) {
                    // do tag matching
                    if (strpos($tag, '/') === false) {
                        // start counting
                        if (empty($tmpTag[$tag])) {
                            $tmpTag[$tag] = 1;
                        } else {
                            $tmpTag[$tag] += 1;
                        }
                    } else {
                        // there's / at the front, then ...
                        $tag = ltrim($tag, '/');
                        if (empty($tmpTag[$tag])) {
                            $tmpTag[$tag] = -1;
                        } else {
                            $tmpTag[$tag] -= 1;
                        }
                    }
                }
            }
            // there we go, so if it's not 0, it's a mismatch!!!
            // work on the message here...
            $msg = null;
            foreach ($tmpTag as $tag => $count) {
                if ($count > 0) {
                    $msg .= " <{$tag}>x{$count} -";
                }
                if ($count < 0) {
                    $count = - $count;
                    $msg .= " <!--{$tag}-->x{$count} -";
                }
            }
        }
        return $msg;
    }

    /**
     * Used to get the Chat URL.
     *
     * Returns the Chat URL with proper langauge preselected or Zendesk js command in order to open chat
     *
     * @param string $lang Optional ISO2 language code that will be used.
     *
     * @param string $setting Provide setting that specify which chat should be open
     *
     * @param string $kayako_url_setting Provide setting that specify kayako url
     *
     * @return string The URL.
     */
    function getChatUrl($lang = '', $chat_support_widget_setting = 'chat_support_widget', $kayako_url_setting = 'kayako_url'){
        $is_chat_disabled = phive()->getSetting('chat_support_disabled');
        if ($is_chat_disabled) {
            return 'showChatDisabledPopup()';
        }

        $chat_support_widget_type = phive()->getSetting($chat_support_widget_setting);
        switch ($chat_support_widget_type) {
            case 'zendesk':
                return 'openZendesk()';
            case 'freshdesk':
                return 'openFreshdesk()';
            case 'ada-chatbot':
                return 'openAdaChatBot()';
            default:
                return '';
        }
    }

    /**
     * Check if the language exist or return a default one.
     *
     * This variable cleaning is to avoid possible XSS injection reported on the "pentest" and avoid the user setting a non existing language.
     *
     * @param string $lang
     *
     * @return string The language.
     */
    public function checkLanguageOrGetDefault($lang) {
        if($this->languageExists($lang))
            return $lang;
        return $this->getDefaultLanguage();
    }

    /**
     * Check if the content for a specific alias is translated.
     * If no translation exist we will receive back a string containing the alias surrounded by parenthesis.
     *
     * Ex.
     * $alias = 'some.text'; $content = t($alias);
     * in case of missing translation $content will be "(some.text)" (if we are in Admin mode editing content the string will be wrapped in a `<span>`)
     *
     * @param string $content
     * @param string $alias
     * @return boolean
     */
    public function isContentTranslated(string $content, string $alias): bool {
        if($this->getTranslatorMode() && preg_match('/(gameinfo.*)/', $content)){
            return false;
        }else if ($content == '(' . $alias . ')') {
            return false;
        }
        return true;
    }

    /**
     * Get Site Language
     *
     * @param DBUser $u_obj
     *
     * @return string
     */
    public function getSiteLanguage(DBUser $u_obj): string
    {
        $res = phive('IpBlock')->getCountry();
        $lic_country = !empty($u_obj) ? lic('getLicCountry', [$u_obj], $u_obj) : $res;

        if (lic('getLicSetting', ['forced_sub_lang'], null, null, $lic_country)) {
            return $this->getLangFromCountry($lic_country);
        }

        return $u_obj->getLang();
    }
}

function modifyUrlParameter(array $parameters): string
{
    $queryString = $_SERVER['QUERY_STRING'];
    parse_str($queryString, $queryArray);
    $queryArray = array_merge($queryArray, $parameters);
    array_shift($queryArray);
    $newQueryStr = http_build_query($queryArray);

    return '?' . $newQueryStr;
}

function llight(){
  return phive("Localizer")->isLight();
}

function lightRedir($url, $code = 301){
  if(!llight())
    return;
  $code_map = array(301 => "301 Moved Permantently", 302 => "Found");
  phive("Redirect")->to($url, phive("Localizer")->getLanguage(), false, $code_map[$code]);
}

function pt($string, $lang=null, $nochange=true){
  return phive("Localizer")->getPotentialString($string, $lang, $nochange);
}

function t2($alias, $replacements = array(), $lang = null){
    if(is_object($lang)){
        // We have a user Object
        $lang = $lang->getLang();
    }
    $str = phive('Localizer')->getString($alias, $lang);
    return phive('Localizer')->doReplace($str, $replacements);
}

function translateOrKey($alias, $lang = null): string
{
    return  trim(phive('Localizer')->getString($alias, $lang), '()');
}

function t($alias, $lang = null, $do_insert = true){
    if(is_object($lang)){
        // We have a user Object
        $lang = $lang->getLang();
    }

    $str = phive('Localizer')->getString($alias, $lang, null, false, false, $do_insert);
    return phive("Localizer")->handleReplacements($str, false, $alias, $lang);
}

function ttag($alias, $tag, $lang = null, $do_insert = true){
    $str = phive('Localizer')->getString($alias, $lang, null, false, false, $do_insert, $tag);
    return phive("Localizer")->handleReplacements($str, false, $alias, $lang);
}

/*
   replacements on the form "bla bla {{key1}} bla bla {{key2}}" where $arr is an assoc array with key1 => value, key2 => value
*/
function tAssoc($alias, $arr, $lang = null, $doReplacements = false){
    $str = phive('Localizer')->getString($alias, $lang);
    if($doReplacements)
        $str = phive("Localizer")->handleReplacements($str, false);
    return phive('Localizer')->replaceAssoc($arr, $str, $lang);
}

function traw($alias, $lang){
  return phive('Localizer')->getRawString($alias, $lang);
}

function rep($str, $user = null, $cents = false){
  if(is_object($user))
    setCur($user);
  return phive("Localizer")->handleReplacements($str, $cents);
}
function llink($page = '', $lang = ''){                      return phive("Localizer")->fullLangLink($page, $lang);}
function cLang(){                                            return phive("Localizer")->getLanguage();}
function tstrict($alias, $lang = null){                      return phive('Localizer')->getString($alias, $lang, true);}
function et($alias, $lang = null, $do_insert = true){  echo t($alias, $lang, $do_insert); }
function ept($string, $lang = null, $nochange = true){       echo pt($string, $lang, $nochange); }
function etDiv($alias, $class='', $lang = null){             echo '<div class="'.$class.'">'.t($alias, $lang).'</div>'; }
function et2($alias, $replacements = array(), $lang = null){ echo t2($alias, $replacements, $lang); }
function etAssoc($alias, $arr, $lang = null){                echo tAssoc($alias, $arr, $lang); }
function tAll($alias, $replacements = array()){
  $ret = array();
  foreach(phive('Localizer')->getAllTranslations($alias) as $lang => $str)
    $ret[$lang] = phive('Localizer')->doReplace($str, $replacements);
  return $ret;
}

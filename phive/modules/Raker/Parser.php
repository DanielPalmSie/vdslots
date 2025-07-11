<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class Parser{
  public function init(){
    include_once getConfigFile('Parser.config.php');
  }

  public function canParseNet(){return false;}
  public function canParseXML(){return false;}

  static function start(){
    return new Parser();
  }

  public function parseNet(){
    throw( new Exception("raker.parser_not_implemented"));
  }
  public function parseXML(){
    throw( new Exception("raker.parser_not_implemented"));
  }

  final static private function getXMLRow($row) {
    $ans = array();
    //$v = preg_match_all("/(|<Cell[^/>]/>)/", $row, $ans)
    $ans = explode("\n", strip_tags(trim($row)));
    foreach($ans as $k  => $v)
    {
      $ans[$k] = trim($v);
    }
    return $ans;
  }
  final static protected function getXMLStruct($xml){
    $a = preg_split("/<\/?Row[^>]*>/", $xml);
    $pre = $a[0];
    if(false == strstr($pre, "<"."?xml"))
      throw(new Exception("Malformed xml file (no start tag)"));

    unset($a[0]);
    array_pop($a);
    foreach($a as $k =>$v)
    {
      if(trim($v) == "")
        unset($a[$k]);
    }
    $a = array_values($a);

    $fields = Parser::getXMLRow($a[0]);
    $fieldMap = array_flip($fields);
    unset($a[0]);
    $a = array_values($a);
    $ans = array();
    foreach($a as $r)
    {
      $row = array(); // hashmap, field name => field value
      $d = Parser::getXMLRow($r);
      foreach($fieldMap as $fieldName => $fieldID)
      {
        $row[$fieldName] = $d[$fieldID];
      }
      array_push($ans, $row);
    }
    return $ans;
  }

  final static protected function extractNum($field){
    $s = preg_replace("/&[^;]*;/", "", $field); //remove any entities that could possibly match a number (like &#160;)
    $s = str_replace(array(",", " "), "", $s);//comma and spaces are no good either
    preg_match('/([0-9\.]+)/', $s, $a);
    return max(0, $a[1]);
  }

  /**
   * Convenience function to save a few lines.
   *
   * @param $rarr The content to continue working with.
   * @return self to be able to chain
   */
  public function rArr($cont){
     $this->content = $cont;
     return $this;
  }

  public function setContent($cont){
    return $this->rArr($cont);
  }

  /**
   * This one is used with messy HTML to get at table rows.
   * Example expression: '/<tr class="(t1|t2)">/', see the tmg parser for more info
   *
   * @param $reg Regular expression
   * @param $content String with HTML
   * @return self
   */
  public function getRows($reg = '|<tr[^>]*>|i', $content = ''){
    $result = preg_split($reg, (empty($content) ? $this->content : $content));
    return $this->rArr($result);
  }

  /**
   * Used to get at the columns (tds) that make up a player in a HTML table.
   *
   * @param $reg The regex to use, see the tmg parser for an example.
   * @return self
   */
  public function getColumns($reg = "|<td[^>]*>([^>]*)</td>|si"){
    $rarr = array();
    foreach($this->content as $player){
      preg_match_all($reg, $player, $matches);
      $rarr[] = $matches[1];
    }
    return $this->rArr($rarr);
  }

  function getPlainTable(){
    return $this->getTable()->getRows()->getColumns();
  }

  public function getXmlColumns($reg = "|<([^>]*)>([^>]*)</([^>]*)>|si"){
    $rarr = array();
    foreach($this->content as $player){
      preg_match_all($reg, $player, $matches);
      $rarr[] = array_combine($matches[1], $matches[2]);
    }
    return $this->rArr($rarr);
  }

  public function shift($num = 1){
    for($i = 0; $i < $num; $i++)
      array_shift($this->content);
    return $this;
  }

  public function sum($bykey){
    $rarr = array();
    foreach($this->content as $player){
      $cur_val = $player[ $bykey ];
      if(empty($rarr[ $cur_val ]))
        $rarr[ $cur_val ] = $player;
      else{
        foreach($player as $key => $value){
          if(is_numeric(trim($value)))
            $rarr[ $cur_val ][ $key ] += $value;
        }
      }
    }
    return $this->rArr($rarr);
  }

  /**
   * Mostly used to replace , with . in all players, see the tmg parser for more info
   *
   * @param $from The char to convert
   * @param $to Tje char to convert to
   * @param $avoidKeys We might want to avoid some fields where the from char is not to be converted
   * @return self for easy chaining
   */
  public function convertChar($from, $to, $avoidKeys = array()){
    foreach($this->content as &$player){
      foreach($player as $key => &$value){
        if(!in_array($key, $avoidKeys))
          $value = str_replace($from, $to, $value);
      }
    }
    return $this;
  }


  /**
   * Filters out HTML with the help of a regular expression.
   * See the tmg parser for more info
   *
   * @param $reg Regular expression, can be string or an array with strings
   * @return self
   */
  public function removeHtml($reg){
    $rarr = array();
    foreach($this->content as $player)
      $rarr[] = trim(preg_replace($reg, '', $player));
    return $this->rArr($rarr);
  }

  public function replaceCompletely($replace){
    $rarr = array();
    foreach($this->content as $arr){
      $tmp = array();
      foreach($arr as $key => $value)
        $tmp[ trim(str_replace($replace, '', $key)) ] = trim(str_replace($replace, '', $value));
      $rarr[] = $tmp;
    }
    return $this->rArr($rarr);
  }

  /**
   * Convenience function to get the return rake stats.
   *
   * @param $mf The keys to use and map from, the player might have more fields so we get only the field here.
   * @param $mt The keys to map to, default is handle, rake and bonus
   * @return array with the final rake stats
   */
  public function getStats($mf, $mt = array('handle', 'rake', 'bonus')){
    $rarr = array();
    foreach($this->content as $player){
      $tmp = array();
      for($i = 0; $i < count($mt); $i++)
        $tmp[ $mt[$i] ] = trim($player[ $mf[$i] ]);
      $rarr[] = $tmp;
    }
    return $rarr;
  }

  /**
   * Simple shortcut function for a common and messy regex.
   * It will fetch the value of an input field when supplied the value of said field.
   *
   * @param $name The name of the input field.
   * @param $content The HTML to work with.
   * @return String, the value to fetch.
   */
  public function getInputValue($name, $content){
    preg_match("|<input( [^>]*name=['\"]{$name}['\"][^>]*)>|", $content, $match);
    return $this->getMatch("|value=['\"](.*?)['\"]|", $match[1]);
  }

  /**
   * Convenience function to set the dates to use, we will use the prior month if it's the first
   * day of the current month, see the tmg parser for usage.
   *
   * @return self for easy chaining
   */
  public function setStartEndDate($day_now = true){
    $cur_day = date('d');
    if($cur_day == '01'){
      $this->start_date = date('Y-m', strtotime('-1 month')).'-01';
      $day_now = false;
    }else
      $this->start_date = date('Y-m').'-01';

    $cur_stamp 			= strtotime($this->start_date);
    $this->end_date 	= $day_now ? date('Y-m-d') : date('Y-m-t', $cur_stamp);
    return $this;
  }

  public function setDates($year, $month, $day_now = true){
    if(empty($year) || empty($month) || $month == date('m'))
      return $this->setStartEndDate($day_now);
    else{
      $this->start_date 	= "$year-$month-01";
      $cur_stamp 			= strtotime($this->start_date);
      $this->end_date 	= date('Y-m-t', $cur_stamp);
      return $this;
    }
  }

  public function formatDates($format){
    $this->start_date 	= date($format ,strtotime($this->start_date));
    $this->end_date 	= date($format ,strtotime($this->end_date));
    return $this;
  }

  public function fixEndDate($modifier = "-1 day", $format = '%Y-%m-%d'){
    if(date('d') == '01')
      $this->end_date = strftime($format, strtotime($modifier));
    return $this;
  }

  function getDatePart($num){
    $arr = explode('-', $this->end_date);
    return $arr[$num];
  }

  function getCurDay(){
    return $this->getDatePart(2);
  }

  public function adjustEndDate($modifier = "-1 day", $format = '%Y-%m-%d'){
    $this->end_date = strftime($format, strtotime($modifier));
    return $this;
  }

  function sort($flags, $key){
      foreach($this->content as $sub)
        $s_col[] = $sub[$key];
      $s_type   	= is_numeric($s_col[0]) ? SORT_NUMERIC : SORT_STRING;
      $flag     	= $flags == 'desc' ? SORT_DESC : SORT_ASC;
      if($s_type == SORT_STRING) $s_col = array_map('strtolower', $s_col);
      array_multisort($s_col, $s_type, $flag, $this->content);
      return $this;
  }

  /**
   * Used with services that uses the current month in some way.
   *
   * @param $override Array to override the default month settings with.
   * @param $date Date to use instead of $this->date
   * @param $format Format to use if anything else than the abbreviated month name is needed.
   * @return self
   */
  public function setCurMonth($override = array(), $date = '', $format = '%b'){
    $date = empty($date) ? $this->start_date : $date;
    if(empty($override))
      $this->month = strftime($format, strtotime($date));
    else{
      $month_num = (float)strftime('%m', strtotime($date));
      $this->month = $override[ $month_num - 1 ];
    }
    return $this;
  }

  public function nextMonth($format = '%m'){
    return $this->getFuture("+1 month", $format);
  }

  public function getFuture($modifier = "+1 month", $format = '%m'){
    return strftime($format, strtotime($modifier));
  }

  /**
   * Convenience function to set the year to work with, if setStartDate has been called
   * it should be able to manage to return the correct year to work with, ie first of Jan
   * should result in the prior year.
   *
   * @param $date The date to work with, if empty we use $this->start_date
   * @param $format Can override the default
   * @return self
   */
  public function setCurYear($date = '', $format = '%y'){
    $date = empty($date) ? $this->start_date : $date;
    $this->year = strftime($format, strtotime($date));
    return $this;
  }

  /**
   * Convenience function to get rid of the preg_match two-liner.
   *
   * @param $reg The regex to use
   * @param $content The string to use the regex on
   * @return string The match
   */
  public function getMatch($reg, $content, $key = 1){
    preg_match($reg, $content, $match);
    return $match[$key];
  }

  public function getTag($tag, $content, $end_tag = ''){
    $end_tag = empty($end_tag) ? $tag : $end_tag;
    return $this->getMatch("|<$tag>(.+?)</$end_tag>|si", $content);
  }

  public function getOptionValue($content, $label, $modifier = ''){
    return $this->getAttrValue($content, 'option', 'value', $label, $modifier);
  }

  public function getAttrValue($content, $tag, $attr_name, $tag_content = '[^<]*', $modifier = ''){
    return $this->getMatch("|<$tag [^>]*$attr_name=['\"](.*?)['\"][^>]*>$tag_content</$tag>|$modifier", $content);
  }

  public function getAttrValues($content, $tag, $quote = '"'){
    $content = empty($content) ? $this->content : $content;
    preg_match_all("|<$tag\s([^>]+)>|si", $content, $matches);
    $rarr = array();
    foreach($matches[1] as $m){
      $tmp = explode($quote, $m);
      $arr = array();
      for($i = 0; $i < count($tmp) - 1; $i += 2)
        $arr[ trim(substr($tmp[$i], 0, -1)) ] = $tmp[$i + 1];
      $rarr[] = $arr;
    }
    return $this->rArr($rarr);
  }

  function removeAttrs($content, $tag = '\S+'){
    $content = empty($content) ? $this->content : $content;
    return $this->rArr(preg_replace("|<($tag)\s([^>]+)>|sim", '<${1}>', $content));
  }

  public function getAttrValuesAssoc($tag = 'value', $content = ''){
    $content = empty($content) ? $this->content : $content;
    preg_match_all("|<(\w+)[^>]+$tag=\"(.*?)\"[^>]*>|si", $content, $matches);
    $rarr = array();
    $i = 0;
    for($i = 0; $i < count($matches[1]); $i++)
      $rarr[ $matches[1][$i] ] = $matches[2][$i];
    return $this->rArr($rarr);
  }

  public function getTable($content = '', $reg = "|<table[^>]*>(.+)</table>|sim", $tbl = ''){
    if(empty($reg))
      $reg = "|<{$tbl}[^>]*>(.+)</$tbl>|sim";
    preg_match($reg, (empty($content) ? $this->content : $content), $match);
    return $this->rArr($match[1]);
  }

  public function getTables($content = '', $reg = "|<table[^>]*>(.*?)</table>|si", $tbl = ''){
    if(empty($reg))
      $reg = "|<{$tbl}[^>]*>(.+)</$tbl>|sim";
    preg_match_all($reg, (empty($content) ? $this->content : $content), $matches);
    return $this->rArr($matches);
  }

  function pos($pos){
    return $this->rArr($this->content[$pos]);
  }

  public function getContent(){
    return $this->content;
  }

  public function pop(){
    array_pop($this->content);
    return $this;
  }

  public function trim(){
    foreach($this->content as &$player){
      foreach($player as &$field)
        $field = trim($field);
    }
    return $this;
  }

  /**
   * Used to add one column to another, used in the Sun Poker parser.
   *
   * @param $add The column name to add.
   * @param $to The column name to add to.
   * @return self
   */
  public function addTo($add, $to){
    foreach($this->content as &$player){
      $player[ $to ] += trim($player[ $add ]);
    }
    return $this;
  }

  public function rmDollarTrim($value){
    $value = empty($value) ? $this->content : $value;
    return trim(str_replace(array('$', '€', '£', ' '), '', $value));
  }

  function cleanAmount($amount){
    $amount = $this->rmDollarTrim($amount);
    if(preg_match('|^\d+,\d\d$|', $amount))
      return str_replace(',', '.', $amount);
    else if(strpos($amount, ',') !== false)
      return str_replace(',', '', $amount);
    return round($amount, 2);
  }

  public function isNumeric($value){
    return is_numeric( $this->cleanAmount($value) );
  }

  function divideBy($divide_by = 100, $col = 'rake'){
    foreach($this->content as &$sub)
      $sub['rake'] /= $divide_by;
    return $this;
  }

  public function makeAbs($work_with){
    foreach($this->content as &$sub)
      $sub[$work_with] = abs($sub[$work_with]);
    return $this;
  }

  public function makeClean($work_with = ''){
    foreach($this->content as &$sub){
      if(empty($work_with)){
        foreach($sub as $key => &$col)
          $col = $this->cleanAmount($col);
      }else
        $sub[$work_with] = $this->cleanAmount($sub[$work_with]);
    }
    return $this;
  }

  public function applyRegexTo($work_with, $reg = "|[\d\.]+|", $key = 0){
    foreach($this->content as &$sub)
      $sub[$work_with] = $this->getMatch($reg, $sub[$work_with], $key);
    return $this;
  }

  public function hasPlayer($username, $players = null, $key = 'handle'){
    $players = empty($players) ? $this->content : $players;
    foreach($players as $player){
      if($player[$key] == $username)
        return true;
    }
    return false;
  }

  public function isValid($content = ''){
    $content = empty($content) ? $this->content : $content;
    $status = false;
    foreach($content as $player){
      foreach($player as $value){
        if($this->isNumeric($value))
          $status = true;
      }
    }
    return $status;
  }

  public function merge($content){
    return $this->rArr(array_merge($this->content, $content));
  }

  public function clean(){
    $rarr = array();
    foreach($this->content as $player){
      $ok = false;
      foreach($player as $field){
        if($this->isNumeric($field))
          $ok = true;
      }
      if($ok)
        $rarr[] = $player;

    }
    return $this->rArr($rarr);
  }

  public function keepWhere($keep_key, $keep_value){
    $rarr = array();
    foreach($this->content as $player){
      if(is_array($keep_value)){
        if(in_array($player[ $keep_key ], $keep_value))
          $rarr[] = $player;
      }else if($player[ $keep_key ] == $keep_value)
        $rarr[] = $player;
    }
    return $this->rArr($rarr);
  }

  function filterWhere($filter_key, $filter_value){
    $rarr = array();
    foreach($this->content as $player){
      if($player[$filter_key] != $filter_value)
        $rarr[] = $player;
    }
    return $this->rArr($rarr);
  }

  function removeEmpty($content = ''){
    $content = empty($content) ? $this->content : $content;
    $rarr = array();
    foreach($content as $player){
      if(!empty($player))
        $rarr[] = $player;
    }
    return $this->rArr($rarr);
  }

  function csvRemoveQuote($limiter, $line, $q){
    $qstrs = array($q.$limiter.$q, $q.$limiter, $limiter.$q);
    foreach($qstrs as $qstr){
      if(strpos($line, $qstr) !== false)
        $line = str_replace($qstr, $limiter, $line);
    }
    return trim($line, $q);
  }

  function csvRemoveQuotes($limiter, $line){
    $str = $this->csvRemoveQuote($limiter, $line, "'");
    return $this->csvRemoveQuote($limiter, $str, '"');
  }

  function csvToArr($limiter = ';', $content = null){
    $content 	= empty($content) ? $this->content : $content;
    $content 	= is_array($content) ? $content : explode("\n", $content);

    $header		= array_shift($content);
    $header 	= $this->csvRemoveQuotes( $limiter, $header );
    $keys 		= explode($limiter, $header);
    foreach($keys as &$key)
      $key = trim($key);

    $rarr 		= array();
    foreach($content as $row){
      $rarr[] = array_combine($keys, explode($limiter, $this->csvRemoveQuotes($limiter, $row)));
    }

    return $rarr;
  }

  function csvToArrFluent($limiter = ';', $content = null){
    return $this->rArr($this->csvToArr($limiter, $content));
  }

  function simpleCsv($mapwith, $limiter = ';', $keep = array(), $content = null){
    $rarr = $this->csvToArr($limiter, $content);
    $csv = new stdClass();
    $csv->data = $rarr;
    return $this->getWithCsv($csv, $mapwith, $keep);
  }

  function parseCsv($content = ''){
    $csv = $this->csvToArr($limiter = ';', $content);
    return $this->rArr($csv);
  }

  /**
   * Generic from CSV to handle/rake/bonus array. The keep array
   * is used in case of multiple affiliate statistics in the same file,
   * see chipleader for an example, absolute and ultimatebet inherits from it.
   *
   * @param $content The CSV data as a string
   * @param $mapwith The fields to map to handle/rake/bonus, see chipleader and betsafe for examples.
   * @param $keep If applicable keys/values that will be checked in the data and if ok added to the result
   * @return unknown_type
   */
  public function getCsv($content, $mapwith, $keep = array()){
    $content = empty($this->content) ? $content : $this->content;
    $csv = new ParseCsv\Csv($content);
    return $this->getWithCsv($csv, $mapwith, $keep);
  }

  public function getWithCsv($csv, $mapwith, $keep = array()){
    $ret = array();
    foreach($csv->data as $player){
      if(trim($player[ $mapwith['handle'] ]) != ''){

        if(!empty($keep)){
          $go = false;
          foreach($keep as $key => $value){
            if($player[$key] == $value){
              $go = true;
              break;
            }
          }
        }else
          $go = true;

        if($go){
          $rake 		= $player[ $mapwith['rake'] ];
          $bonus 		= abs( (float)$player[ $mapwith['bonus'] ] );
          $handle 	= $player[ $mapwith['handle'] ];
          $extra		= $player[ $mapwith['extra'] ];

          $rake 		= empty($rake) ? '0.0' : $rake;

          $ret[] = array('rake' => $rake, 'bonus' => $bonus, 'handle' => trim($handle), 'extra' => $extra);
        }
      }
    }

    $this->content = $ret;

    return $ret;
  }

  public function shaveRows($content, $num, $as_rows = false){
    $result = array_slice(explode("\n", (empty($content) ? $this->content : $content)), $num);
    $result = $as_rows ? $result : implode("\n", $result);
    return $this->rArr($result);
  }

  public function splitSub($delim = ','){
    foreach($this->content as &$sub){ $sub = explode($delim, $sub); }
    return $this;
  }

  function simpleRawPost($url, $data, $optional_headers = null){
    $params = array(
      'http' 		=> array(
      'method' 	=> 'POST',
      'content' 	=> $data)
    );

    if ($optional_headers !== null)
      $params['http']['header'] = $optional_headers;

    $ctx = stream_context_create($params);

    $fp = @fopen($url, 'rb', false, $ctx);

    if (!$fp)
      throw new Exception("Problem with $url, $php_errormsg");

    $response = @stream_get_contents($fp);

    if ($response === false)
      throw new Exception("Problem reading data from $url, $php_errormsg");

    return $response;
  }

    function rawPost($url, $data, $header = array('Content-Type: text/plain')){
        return phive()->post($url, $data, 'text/plain', $header, '', 'POST', 120);
    }

  function domLoad($str){
    $this->content = $str;
    return $this;
  }

  function setDom($str){
    if(is_string($str))
      $this->domLoad($str);
    else
      $this->content = $str;
    return $this;
  }

  function domTag($tag, $strict = false){
    $reg = $strict ? "|<$tag>(.+?)</$tag>|sim" : "|<{$tag}[^>]*>(.+?)</$tag>|sim";
    $content = is_array($this->content) ? $this->content[0] : $this->content;
    preg_match_all($reg, $content, $match);
    return $this->rArr($match[1]);
  }

  function domTags(){
    $args = func_get_args();
    $rarr = array();
    foreach($args as $tag){
      $reg = "|<{$tag}[^>]*>(.+?)</$tag>|sim";
      $content = is_array($this->content) ? $this->content[0] : $this->content;
      preg_match_all($reg, $content, $match);
      $rarr[$tag] = $match[1][0];
    }

    return $this->rArr($rarr);
  }

  function dom2assoc(){
    $rarr = array();
    $reg = "|</[^>]+>|sim";
    foreach($this->content as $key => $cont){
      $tmp = array();

      foreach(preg_split($reg, $cont) as $field){
        preg_match('|^<([^>]+)>(.+)|sim', trim($field), $match);
        if(!empty($match[2])){
          if(empty($tmp[ $match[1] ]))
            $tmp[ $match[1] ] = strip_tags(trim($match[2]));
          else if(is_array($tmp[ $match[1] ]))
            $tmp[ $match[1] ][] = strip_tags(trim($match[2]));
          else
            $tmp[ $match[1] ] = array($tmp[ $match[1] ], strip_tags(trim($match[2])));
        }
      }

      $rarr[$key] = $tmp;
    }

    return $this->rArr($rarr);
  }

  function domItem($num = 0){
    return $this->content[$num];
  }

  function domVal($tag){
    $this->domTag($tag);
    if(is_array($this->content)){
      if(count($this->content) == 1)
        return $this->domItem(0);
    }
    return $this->content;
  }

  function domKeyVal($arr){
    list($kkey, $kval) = $arr;
    $rarr = array();

    foreach($this->content as $tcont){
      $reg = "|<([^>]+)>(.+?)</[^>]+>|sim";
      $match = array();
      preg_match_all($reg, $tcont, $match);
      array_shift($match);

      $knum = $vnum = 0;
      for($i = 0; $i < count($match[0]); $i++){
        if($match[0][$i] == $kkey)
          $knum = $i;
        else if($match[0][$i] == $kval)
          $vnum = $i;
      }

      //echo ':'.$match[1][$knum].':';

      $rarr[ $match[1][$knum] ] = $match[1][$vnum];
    }

    return $rarr;
  }
}

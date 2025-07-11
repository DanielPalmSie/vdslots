<?php
//Phive module class
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/Article.php';
class ArticleHandler extends PhModule {
  
  public function getArticle($article_id){
    if($article_id !== null){
      $art =  new Article($this,$article_id,null);
      if ($art->exists())
	return $art;
    }
    return false;
  }
  
  public function createArticle($user_id){
    $new = new Article($this,null,$user_id);
    if ($new->exists())
      return $new;
    else
      return false;
  }
  
  /**
   * Returns an array of Articles
   *
   * @param string $start 
   * @param string $limit 
   * @return void
   * @author Viljam
   */
  
  public function getArticles($start = null,$limit = null,$country = null, $category = "ALL",$status = "APPROVED"){
    $where = " WHERE 1 ";
    if ($status == 'APPROVED'){
      $where .= " AND status = 'approved' ";
    }
    else if($status == 'PENDING'){
      $where .= " AND status = 'pending' ";
    }
    if ($category !== "ALL"){
      $where .= " AND category_alias = ".phive("SQL")->escape($category);
    }
    if($country !== null){
      $where .= " AND country = ".phive("SQL")->escape($country);
    }
    if ($limit !== null){
      $q_limit = " LIMIT ".(float)$limit;
    }
    if ($limit !== null and $start !== null){
      $q_limit = " LIMIT ".(float)$start.",".(float)$limit;
    }
    phive("SQL")->query("SELECT * FROM ".$this->getSetting("DB_ARTICLES")." $where ORDER BY time_created DESC ".$q_limit);
    $articles = phive("SQL")->fetchArray();
    $arr = array();
    foreach ($articles as $a) {
      $arr[] = $this->getArticle($a);
    }
    return $arr;
  }
  
  public function search($search,$country = null, $start = null,$limit = null){
    if ($limit !== null){
      $q_limit = " LIMIT ".(float)$limit;
    }
    if ($limit !== null and $start !== null){
      $q_limit = " LIMIT ".(float)$start.",".(float)$limit;
    }
    if($country !== null){
      $where_country = " AND country = ".phive("SQL")->escape($country);
    }
    $search = strtr($search,array(" ","%"));
    $search = phive("SQL")->escape("%".$search."%");
    phive("SQL")->query("SELECT * FROM ".$this->getSetting("DB_ARTICLES")." WHERE (abstract LIKE ".$search." OR content LIKE ".$search." OR headline LIKE ".$search." OR subheading LIKE ".$search.") $where_country ORDER BY time_created DESC $q_limit");
    $res = phive("SQL")->fetchArray();
    $articles = array();
    foreach ($res as $r) {
      $articles[] = $this->getArticle($r);
    }
    return $articles;
  }
  
  public function searchUser($user,$search,$country = null, $start = null,$limit = null){
    if ($limit !== null){
      $q_limit = " LIMIT ".(float)$limit;
    }
    if ($limit !== null and $start !== null){
      $q_limit = " LIMIT ".(float)$start.",".(float)$limit;
    }
    if($country !== null){
      $where_country = " AND country = ".phive("SQL")->escape($country);
    }
    $search = strtr($search,array(" ","%"));
    $search = phive("SQL")->escape("%".$search."%");
    phive("SQL")->query("SELECT * FROM ".$this->getSetting("DB_ARTICLES")." WHERE user_id = ".$user->getId()." AND (abstract LIKE ".$search." OR content LIKE ".$search." OR headline LIKE ".$search." OR subheading LIKE ".$search.") ORDER BY time_created DESC $q_limit");
    $res = phive("SQL")->fetchArray();
    $articles = array();
    foreach ($res as $r) {
      $articles[] = $this->getArticle($r);
    }
    return $articles;
  }
  
  public function getCountWhere($where){
    phive("SQL")->query("SELECT COUNT(*) FROM ".$this->getSetting("DB_ARTICLES")." WHERE ".$where);
    return phive("SQL")->result();
  }
  
  public function getSearchUserCount($user,$search,$country = null){
    if($country !== null){
      $where_country = " AND country = ".phive("SQL")->escape($country);
    }
    $search = strtr($search,array(" ","%"));
    $search = phive("SQL")->escape("%".$search."%");
    return $this->getCountWhere(
      "user_id = ".$user->getId()." AND (abstract LIKE ".$search." OR content LIKE ".$search." OR headline LIKE ".$search." OR subheading LIKE ".$search.")"
    );
    //phive("SQL")->query("SELECT COUNT(*) FROM ".$this->getSetting("DB_ARTICLES")." WHERE user_id = ".$user->getId()." AND (abstract LIKE ".$search." OR content LIKE ".$search." OR headline LIKE ".$search." OR subheading LIKE ".$search.")");
    //return phive("SQL")->result();
  }
  public function getSearchCount($search,$country = null){
    if($country !== null){
      $where_country = " AND country = ".phive("SQL")->escape($country);
    }
    $search = strtr($search,array(" ","%"));
    $search = phive("SQL")->escape("%".$search."%");
    return $this->getCountWhere(
      "abstract LIKE ".$search." OR content LIKE ".$search." OR headline LIKE ".$search." OR subheading LIKE ".$search." ORDER BY time_created DESC"
    );
    //phive("SQL")->query("SELECT count(*) FROM ".$this->getSetting("DB_ARTICLES")." WHERE abstract LIKE ".$search." OR content LIKE ".$search." OR headline LIKE ".$search." OR subheading LIKE ".$search." ORDER BY time_created DESC");
    //return phive("SQL")->result();
  }
  public function searchViewPage($search,$results_per_page,$page = 1){
    $start = max(0,$page-1)*$results_per_page;
    return $this->search($search,$start,$results_per_page);
  }
  
  public function getCatWhere($category){
    
    if(is_array($category)){
      if(count($category) == 1)
	$category = $category[0];
      else{
	$col_name = is_numeric($category[0]) ? 'category_id' : 'category_alias';
	$category = phive("SQL")->makeIn($category);
      }
    }
    
    if(strpos($category, ',') !== false)
      return " AND $col_name IN($category)";
    else if(is_numeric($category))
    return " AND category_id = $category";
    else
      return " AND category_alias = ".phive("SQL")->escape($category);
  }
  
  public function getLatest($start = null,$limit = null,$country = null, $category = "ALL",$status = "APPROVED", $extra = ""){
    $where = " WHERE 1 ";
    if($category !== "ALL")
      $where .= $this->getCatWhere($category);
    
    if ($status == 'APPROVED')
      $where .= " AND status = 'approved' ";
    else if($status == 'PENDING')
    $where .= " AND status = 'pending' ";
    
    if($country !== null)
      $where .= " AND country = ".phive("SQL")->escape($country);
    
    if($limit !== null)
      $q_limit = " LIMIT " . (float)$limit;
    
    if($start !== null && $limit !== null)
      $q_limit = " LIMIT " . (float)$start . "," . (float)$limit;
    
    $where .= $extra;	
    
    //echo "SELECT * FROM ".$this->getSetting("DB_ARTICLES")." $where ORDER BY time_created DESC $q_limit";
    //exit;
    
    phive("SQL")->query("SELECT DISTINCT * FROM ".$this->getSetting("DB_ARTICLES")." $where ORDER BY time_created DESC $q_limit");
    $res = phive("SQL")->fetchArray();
    $articles = array();
    foreach ($res as $r)
      $articles[] = $this->getArticle($r);
    
    return $articles;
  }
  
  function getAnyByCountry($start = null,$limit = null,$country = null, $category = "ALL",$status = "APPROVED", $extra = ""){
    $extra = " AND ( country = '$country' OR countries LIKE '%$country%' ) ";
    return $this->getLatest($start, $limit, null, $category, $status, $extra);
  }
  
  function getByMainCountry($start = null,$limit = null,$country = null, $category = "ALL",$status = "APPROVED", $extra = ""){
    $extra = " AND country = '$country' ";
    return $this->getLatest($start, $limit, null, $category, $status, $extra);
  }
  
  public function getMostRead($start, $limit, $country = null, $since = null,$category = "ALL",$status = "APPROVED", $col = 'visits'){
    
    if(!empty($limit))
      $q_limit = " LIMIT ".(float)$start.",".(float)$limit;
    
    $where = " WHERE 1 ";
    
    if ($status == 'APPROVED')
      $where .= " AND status = 'approved' ";
    else if($status == 'PENDING')
    $where .= " AND status = 'pending' ";
    
    if ($since !== null)
      $where .= " AND time_created > ".phive("SQL")->escape($since);
    
    if($category !== "ALL")
      $where .= $this->getCatWhere($category);
    
    if($country !== null)
      $where .= " AND country = ".phive("SQL")->escape($country);
    
    $query = "SELECT * FROM ".$this->getSetting("DB_ARTICLES")." $where ORDER BY $col DESC ".$q_limit;
    
    phive("SQL")->query($query);
    $res = phive("SQL")->fetchArray();
    $articles = array();
    
    foreach ($res as $r)
      $articles[] = $this->getArticle($r);
    
    return $articles;
  }
  
  public function getMostVisitedUsers($start = null, $limit = null,$country = null,$since = null){
    if ($limit === null){
      $q_limit = $this->getSetting("DEFAULT_VIEWS_PER_PAGE");
      echo "LIMIT: $limit";
    }
    
    if ($start !== null)
      $q_limit = " LIMIT ".(float)$start.",".(float)$limit;
    else 
      $q_limit = " LIMIT ".(float)$limit;

    $where = " WHERE 1 ";
    if ($since !== null)
      $where .= " AND time_created > ".phive("SQL")->escape($since);	
    
    if($country !== null)
      $where .= " AND country = ".phive("SQL")->escape($country);
    
    $query = "SELECT user_id FROM ".$this->getSetting("DB_ARTICLES")."
		$where
		GROUP BY user_id
		ORDER BY SUM(visits) DESC
		$q_limit";
    
    phive("SQL")->query($query);
    $res = phive("SQL")->fetchArray();
    $users = array();
    foreach ($res as $r)
      $users[] = cu($r['user_id']);
    
    return $users;
  }
  
  public function getArticlesFromUser($user,$start = null,$limit = null,$from = null,$to = null){
    return $this->getArticlesFromUserByCountry(null, $user, $start, $limit, $from, $to);
  }
  
  public function getArticlesFromUserByCountry($country, $user,$start = null,$limit = null,$from = null,$to = null){
    if (!is_object($user))
    {
      trigger_error("Invalid user.", E_USER_ERROR);
      return;
    }
    
    if ($limit !== null){
      $q_limit = " LIMIT ".(float)$limit;
    }
    if ($limit !== null and $start !== null){
      $q_limit = " LIMIT ".(float)$start.",".(float)$limit;
    }
    if($from !== null && $to !== null){
      $where_time = " AND time_created >= ".phive("SQL")->escape($from)." AND time_created <= ".phive("SQL")->escape($to);
    }
    if ($country !== null)
    {
      $where_country = " AND `country` = " . phive("SQL")->escape($country);
    }
    phive("SQL")->query("SELECT * FROM ".$this->getSetting("DB_ARTICLES")." WHERE user_id = ".phive("SQL")->escape($user->getId())." $where_time $where_country ORDER BY time_created DESC ".$q_limit);
    $articles = phive("SQL")->fetchArray();
    $arr = array();
    foreach ($articles as $a) {
      $arr[] = $this->getArticle($a);
    }
    return $arr;
  }
  public function countArticlesFromUser($user){
    phive("SQL")->query("SELECT COUNT(*) FROM ".$this->getSetting("DB_ARTICLES")." WHERE user_id = ".phive("SQL")->escape($user->getId()));
    return phive("SQL")->result();
  }
  public function countArticles($country = null){
    if($country)
      $where_country = " WHERE country = ".phive("SQL")->escape($country);
    phive("SQL")->query("SELECT COUNT(*) FROM ".$this->getSetting("DB_ARTICLES").$where_country);
    return phive("SQL")->result();
  }
  
  public function getCategoriesOld(){
    return phive("SQL")->loadArray("SELECT * FROM ".$this->getSetting("DB_CATEGORIES")." ORDER BY category_alias ASC");
  }
  
  public function getCategories(){
    return phive("CategoryHandler")->getHierarchy();
  }
  public function getCategoryName($category_alias){
    phive("SQL")->query("SELECT name FROM ".$this->getSetting("DB_CATEGORIES")." WHERE category_alias = ".phive("SQL")->escape($category_alias));
    return phive("SQL")->result();
  }
  public function getOldestFromUser($user)
  {
    return $this->getOldestFromUserByCountry(null, $user);
  }
  public function getOldestFromUserByCountry($country, $user){
    if ($country !== null)
      $where_country = " AND `country` = " . phive('SQL')->escape($country);
    $query = "SELECT time_created FROM ".$this->getSetting("DB_ARTICLES")." WHERE user_id = ".phive("SQL")->escape($user->getId()).$where_country." ORDER BY time_created ASC LIMIT 1";
    phive("SQL")->query($query);
    $date =  phive("SQL")->result();
    return $date;
  }
  
  function metaIndex(){
    $p = phive('Pager');
    $cur_path = $p->getPathNoTrailing();
    if($cur_path == '/news' || $cur_path == '/articles'){
      $args 		= $p->getArguments();
      $article 	= $this->getArticle( $args[0] );
      if(!empty($article)){
	$alang 		= $article->getAttr('country');
	$cur_lang 	= phive('Localizer')->getLanguage();
	if($cur_lang != $alang)
	  echo '<meta name="robots" content="noindex">';
      }
    }
  }
  
  public function getContext($news,$search,$search_res_length){
    $strlen = mb_strlen($search);
    $length = $search_res_length - $strlen;
    $halv = $length/2;
    
    $text = null;
    if($search && FALSE !== ($pos = mb_strpos($news->getAbstract(),$search)))
      $text = $news->getAbstract();
    else if($search && FALSE !== ($pos = mb_strpos($news->getContent(),$search)))
    $text = $news->getContent();
    
    if ($text){			
     $beg = $end = "";
     if($pos-$halv < 0){
	$start = 0;
      }
     else{
	$start = $pos-$halv;
	$beg = "...";
      }
     if($start + $search_res_length < mb_strlen($text)){
	$end = "...";
      }
     return $beg.mb_substr($text, $start,$search_res_length).$end;
     }
    else if(strlen($news->getAbstract()) > 0){
      return mb_substr($news->getAbstract(),0,$search_res_length);
    }
    else{
      return mb_substr($news->getContent(),0,$search_res_length);
    }
  }

  public function getArticleByAlias($alias) {
    $article = phive("SQL")->loadAssoc(
      "SELECT article_id FROM ".$this->getSetting("DB_ARTICLES")." WHERE article_alias=".phive("SQL")->escape($alias)." LIMIT 1"
    );
    return $this->getArticle($article['article_id']);
  }
  
  public function updateCategory($id, $alias, $name, $image_path) {
    $id = intval($id);
    $q ="UPDATE ".$this->getSetting("DB_CATEGORIES").
	" SET ".
	"`category_alias` = " . phive('SQL')->escape($alias) . ", " .
	"`name` = " . phive('SQL')->escape($name) . ", " .
	"`image_path` = " . phive('SQL')->escape($image_path) .
	" WHERE `category_id` = $id LIMIT 1";
    phive("SQL")->query($q);
    return (phive('SQL')->affectedRows() > 0);
  }
  
  public function createCategory($alias, $name, $image_path = null){
    $array = array("category_alias"=>$alias, "name"=>$name, "image_path"=>$image_path);
    phive("SQL")->insertArray($this->getSetting("DB_CATEGORIES"), $array); //This will escape the variables..
    return phive("SQL")->insertBigId();
  }
  
  public function deleteCategory($id){
    $id = intval($id);
    $q = "DELETE FROM ".$this->getSetting("DB_CATEGORIES")." WHERE category_id=$id LIMIT 1";
    phive('SQL')->query($q);
    return (phive("SQL")->affectedRows() > 0);
  }
  
  public function getCategory($category_id_or_alias) {
    $where = "WHERE ";
    if (is_numeric($category_id_or_alias)) {
      $where .= "`category_id`=" . phive("SQL")->escape($category_id_or_alias);
    } else {
      $where .= "`category_alias`=" . phive("SQL")->escape($category_id_or_alias);
    }
    return phive("SQL")->loadAssoc("SELECT * FROM ".$this->getSetting("DB_CATEGORIES"). " " . $where);
  }
  
}

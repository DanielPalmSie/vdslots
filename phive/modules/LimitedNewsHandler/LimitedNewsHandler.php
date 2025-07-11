<?php
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/LimitedArticle.php';
require_once __DIR__ . '/../NewsHandler/NewsHandler.php';
class LimitedNewsHandler extends NewsHandler {
  function phAliases()	{ return array('NewsHandler'); }

  public function getArticle($article_id){
    if($article_id !== null){
      $art =  new LimitedArticle($this,$article_id,null);
      if ($art->exists())
        return $art;
    }
    return false;
  }

  public function createArticle($user_id){
    $new = new LimitedArticle($this,null,$user_id);
    if ($new->exists())
      return $new;
    else
      return false;
  }

    public function getArchivedMonths($where){
        phive("SQL")->query("SELECT DISTINCT DATE_FORMAT(time_created, '%Y-%m-01') FROM ".$this->getSetting("DB_ARTICLES")." WHERE ".$where." AND time_created >= DATE_SUB(now(),INTERVAL 1 year)  ORDER BY time_created DESC LIMIT 8");
        return phive("SQL")->fetchArray('NUM');
    }

    public function getCategoriesfromArticles($lang, $id_arr)
    {
        $ids = phive('SQL')->makeIn($id_arr);
        if (empty($ids)) {
            return [];
        }
        return phive("SQL")->loadArray(
            "SELECT DISTINCT c.* FROM " .
            phive("CategoryHandler")->getSetting('table_entries') .
            " c INNER JOIN " .
            $this->getSetting("DB_ARTICLES") .
            " ln ON c.id = ln.category_id
                WHERE ln.country = " . $lang . "
                 AND ln.category_id IN($ids)"
        );
    }

  function sortByTimeStatus($news){
    $spliced = array();
    foreach($news as $n)
      $spliced[ $n->getTimeStatus() ][] = $n;

    return array_merge((array)$spliced['current'], (array)$spliced['upcoming'], (array)$spliced['old']);
  }

  function sortByStatus($news) {
    $spliced = array();
    foreach($news as $n) {
      $status = $n->getStatus();
      $status = ($status[2]) ? $status[2] : "nostatus";
      $spliced[ $status ][] = $n;
    }
    return array_merge((array)$spliced['active'], (array)$spliced['upcoming'], (array)$spliced['finished'], (array)$spliced['nostatus']);
  }
  /*
  function sortByDateAdded($news){
    function cbda($a, $b) {
        return strcmp($b->time_created, $a->time_created);
    }
    usort($this, $news, 'cbda');
    return $news;
  }*/

  /*
   * country is really the language
   */
  public function getLatestTopList($country = null, $category = "ALL", $status = "APPROVED", $extra = "", $limit = ''){
    $where = " WHERE 1 ";

    if ($status == 'APPROVED')
      $where .= " AND status = 'approved' ";
    else if($status == 'PENDING')
      $where .= " AND status = 'pending' ";

    if(!empty($category) && $category != 'ALL')
      $where .= " AND category_alias = '$category' ";

    if($country !== null)
      $where .= " AND country = ".phive("SQL")->escape($country);

    $where .= " AND time_created > DATE_SUB(NOW(), INTERVAL 7 day) ";

    $where .= $extra;

    $tbl = $this->getSetting("DB_ARTICLES");

    $sql = "SELECT ln.article_id, ln.category_alias, ln.country, ln.headline, ln.category_id, ln.abstract, ln.content, ln.time_created ,ln.status, ln.url_name, ln.image_path, ln.header_image FROM $tbl ln $where ORDER BY article_id DESC ".phive('SQL')->getLimit($limit);
      
    $res = phQget($sql);
    if(empty($res)) {
       $res = phive("SQL")->loadArray($sql);
       phQset($sql, $res, 120);
    }

    $articles = array();
    foreach ($res as $r)
      $articles[] = $this->getArticle($r);

    return $articles;
  }

  function getCatDropDownMulti($categories){ ?>
    Category: <select id="category" name="category[]" size="20" multiple>
      <?php foreach($this->getCategories() as $c): ?>
        <option value="<?php echo $c['id']; ?>" <?php if(in_array($c['id'], $categories)) echo 'selected="selected"'; ?>>
          <?php foreach(array_fill(0, $c['depth'], '&nbsp;-&nbsp;') as $space) echo $space; echo $c['name']; ?>
        </option>
      <?php endforeach ?>
    </select>
  <?php }

  function getCatDropDown($selected){ ?>
    Category: <select name="category">
      <option value="ALL" <?php if($selected == "ALL") echo 'selected="selected"'; ?>>All</option>
      <?php foreach($this->getCategories() as $c): ?>
        <option value="<?php echo $c['id']; ?>" <?php if($selected == $c['id']) echo 'selected="selected"'; ?>>
          <?php foreach(array_fill(0, $c['depth'], '&nbsp;-&nbsp;') as $space) echo $space; echo $c['name']; ?>
        </option>
      <?php endforeach ?>
    </select>
  <?php }
}

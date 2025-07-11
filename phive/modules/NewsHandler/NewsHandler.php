<?php
//Phive module class
require_once __DIR__ . '/../../api/PhModule.php';

require_once __DIR__ . '/../ArticleHandler/ArticleHandler.php';

class NewsHandler extends ArticleHandler {
  
  public function countArticles($country = null,$category = null){
    if($country)
      $where_country = " WHERE country = ".phive("SQL")->escape($country);
    if($category)
      $where_category = " AND category_alias = ".phive("SQL")->escape($category);
    phive("SQL")->query("SELECT COUNT(*) FROM ".$this->getSetting("DB_ARTICLES").$where_country.$where_category);
    return phive("SQL")->result();
  }
  
  function getPartnersFromCat($cat_id){
    return phive('SQL')->loadArray(
      "SELECT * FROM raker_partners 
			WHERE partner_id IN( SELECT partner_id FROM partner_category WHERE category_id = $cat_id )
			ORDER BY weight DESC"
    );
  }

}

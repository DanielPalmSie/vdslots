<?php
require_once __DIR__ . '/../../../phive.php';

foreach(phive('SQL')->loadArray("SELECT * FROM news_freq") as $f){
	
	$a_prob 		= $f['freq'] / 2;
	$a_num 			= max(1, floor($a_prob));
	
	if($a_prob > 1){
		$a_prob_e 	= 100 * ($a_prob - floor($a_prob));
		if($a_prob_e >= rand(1, 100))
			$a_num += floor($a_prob);
	}
	
	if($a_prob * 100 >= rand(1, 100)){
		$articles = phive("SQL")->loadArray("SELECT * FROM limited_news WHERE published = 0 AND country = '{$f['lang']}' ORDER BY RAND() LIMIT 0,$a_num");
		
		foreach($articles as $a){
			$site = phive('Site')->getSite($a['site_id']);
			unset($a['site_id']);
			unset($a['time_created']);
			$aid = $a['article_id'];
			unset($a['article_id']);
			unset($a['published']);
			$a['status'] = 'approved';
			$d = array();
			$d['table'] = 'limited_news';
			$d['data'] = $a;
		    phive("SQL")->updateArray('limited_news', array('published' => 1), array('article_id' => $aid));
		}
	}
}

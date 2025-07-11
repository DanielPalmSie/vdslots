<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class SitemapBoxBase extends DiamondBox{

  function init(){
    $this->hide = array('editnews', 'deletenews', 'update-account', 'account-history', 'my-bonuses', 'game-history', 'admin_log', 'account', 'cashier');

   $hide_pages =  lic('getLicSetting', ['exclude_page_from_sitemap']);
   $this->hide = array_merge($this->hide, $hide_pages);
    $pa 			= phive('Pager');
    $pages 			= $pa->getHierarchy();
    $this->loc 		= phive('Localizer');

    $this->filtered = array();
    foreach($pages as $p){
      if(strpos($p['cached_path'], 'admin') !== false || $this->hide($p))
	continue;

      if(strpos($p['filename'], 'generic.php') === false)
	continue;

      if (isset($p['domain']) && strpos($p['domain'], phive()->getSetting('domain')) === false)
    continue;

        $title = phive('Localizer')->getPotentialString($p['title']);


        if($p['cached_path'] == '/casino'){
            $text = phive('Localizer')->getPotentialString($p['title']);
            $gameCounter =   phive('MicroGames')->countWhere();
            $title = preg_replace_callback('/\b\d{1,3}(?:[,\.\s]?\d{3})*\b/', function($match) use ($gameCounter) {
                return $gameCounter;
           }, $text);
        }

      $this->filtered[] = array(
	'path' 		=> $p['cached_path'],
	'padding' 	=> $p['depth'],
	'name' 		=> empty($title) ? ucfirst( str_replace(array('_', '-'), ' ', $p['alias'])) : $title,
	'news' 		=> array()
      );
    }

    $this->lang = $this->loc->getCurNonSubLang();

    $articles = phive('LimitedNewsHandler')->getByMainCountry(null,null,$this->lang);

    foreach($articles as $a){
      $cat_alias = $a->getAttr('category_alias');
      $base = "/$cat_alias/{$a->getAttr('article_id')}";
      if($a->getAttr('url_name') != '')
	$base .= '/'.$a->getAttr('url_name');
      $this->addNews($cat_alias, array('path' => $base, 'name' => $a->getAttr('headline')));
    }
  }

  function addNews($alias, $news){
    foreach($this->filtered as &$f){
      if(strpos($alias, trim($f['path'], '/')) === 0){
	$f['news'][] = $news;
      }
    }
  }

  function hide($p){
    foreach($this->hide as $h){
      if(strpos($p['cached_path'], $h) !== false)
	return true;
    }
    return false;
  }

  function printHTML(){
?>
  <div class="frame-block">
    <div class="frame-holder sitemap">
      <h1><?php et('sitemap.headline') ?></h1>
      <ul style="list-style:none;">
	<?php foreach($this->filtered as $f): ?>
	  <li style="padding-left: <?php echo 10 * $f['padding']; ?>px;">
	    <a href="<?php echo $this->loc->langLink('', $f['path']) ?>"> <?php echo $f['name'] ?> </a>
	    <?php if(!empty($f['news'])): ?>
	      <ul>
		<?php foreach($f['news'] as $n): ?>
		  <li>
		    <a href="<?php echo $this->loc->langLink('', $n['path'] ) ?>"> <?php echo $n['name'] ?> </a>
		  </li>
		<?php endforeach ?>
	      </ul>
	    <?php endif ?>
	  </li>
	<?php endforeach ?>
      </ul>
    </div>
  </div>
<?php }

}







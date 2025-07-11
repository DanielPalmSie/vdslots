<?php
require_once __DIR__.'/NewsBase.php';
class NewsFullBoxBase extends NewsBase{

  public function init($article = null){
    $this->nh 	= phive("LimitedNewsHandler");
    $this->p 	= phive('Permission');
    $this->uh 	= phive('UserHandler');
    //edit settings
    
    $this->handlePost(array('number_top', 'number_category', 'top_length'));
    
    if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()){
      $this->setAttribute("number_top", 		$_POST['number_top']);
      $this->setAttribute("number_category", 	$_POST['number_category']);
      $this->setAttribute("top_length", 		$_POST['top_length']);
    }
    
    $this->news = false;
    
    if($article == null){
      if(isset($_GET['arg0']))
	$this->news = $this->nh->getArticle($_GET['arg0']);
      else
	return;
    }else
    $this->news = $article;
    
    if(empty($this->news))
      return;
    
    
    //User is logged in
    $user = $this->uh->getUser();
    if ($user){ 
      if ($_GET['arg1'] == "delete"){
	$news = $this->nh->getArticle($_GET['arg0']);
	if($news != false){
	  if($this->p->hasPermission("News.delete")
	    || (cu()->getId() == $news->getUser()->getId()
		&& $this->p->hasPermission("News.delete_own"))){
	    $news->remove();
	  }
	}

	header("Location: /");
	exit;
      }
      
      $this->can_edit 	= $this->p->hasPermission("News.edit");
      $this->can_delete 	= $this->p->hasPermission("News.delete");
      $this->can_publish 	= $this->p->hasPermission("News.publish");
    }
    $this->parent 		= "news";
    $this->parent_id 	= $this->news->getId();
    
    $this->top_length 	= ($this->attributeIsSet("top_length"))?$this->getAttribute("top_length"):20;
    
    $this->limit 		= min($this->limit,sizeof($this->news))-1;
    $this->num_top 		= min($this->num_top,sizeof($this->top));
      $this->show_status 	= 1;

      $this->setTitle();

    phive('Pager')->setMetaDescription($this->news->getMetaDescription());
    phive('Pager')->setMetaKeywords($this->news->getMetaKeywords());
    if(phive('Localizer')->getCurNonSubLang() != $this->news->country)
      phive('Pager')->setBotBlock();
    return $this->news;
  }

    public function setTitle(){
        if(empty($this->news)){
            return null;
        }
        phive('Pager')->setTitle($this->news->getHeadline()." - ".$this->getArticleDate($this->news, true));
    }

  public function printHTML(){ 
    if(empty($this->news))
      $this->render = false;
    else
      $this->printArticle();
  }
  
  public function printArticle(){
?>
<p>
    <h1 class="big_headline"> <?php echo rep($this->news->getHeadline()) ?> </h1>
  <?php $this->drawArticleInfo($this->news) ?>
</p>
<p class="author">
  <?php if ($this->can_edit): ?>
    <a href="<?php echo llink("/news/editnews/".$this->news->getId()); ?>/"><?php echo t("newsfull.edit"); ?></a>
  <?php endif ?>
  <?php if ($this->can_delete): ?>
    <a href="/news/deletenews/<?php echo $this->news->getId(); ?>/delete" onclick="return confirm_delete()">
      <?php echo t("newsfull.delete"); ?>
    </a>
  <?php endif ?>
</p>
<p>
  <?php echo $this->news->getParsedContent() ?>
</p>
<?php
}

public function printInstanceJS(){
  parent::printInstanceJS();
?>
function confirm_delete(){
return confirm('<?php echo t("Newsbox.confirm_delete"); ?>');
}
<?php
}
}

<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class NewsTopBase extends DiamondBox{

	public function init(){
		
		if((isset($_GET['arg0']) && is_numeric($_GET['arg0'])) || !empty($_GET['page'])){
			$this->render = false;
			return;
		}
		
		if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()){
			$this->setAttribute("news_id", $_POST['news_id']);
			$this->setAttribute("category", $_POST['category']);
			$this->setAttribute("show_status", $_POST['show_status']);
		}
		
		$this->nh 				= phive("LimitedNewsHandler");
		$this->news_id 			= ($this->attributeIsSet("news_id") 		&& $this->getAttribute("news_id") !== "") ? $this->getAttribute("news_id"):null;
		$this->category 		= ($this->attributeIsSet("category")) 		? $this->getAttribute("category"):"ALL";
		$this->show_status 		= ($this->attributeIsSet("show_status"))	? $this->getAttribute("show_status"):0;
		
		$this->cath				= phive("CategoryHandler");
		$ids = $this->category == "ALL" ? $this->category : $this->cath->getTreeIds($this->category);
		
		if ($this->news_id !== null){
			$this->news = $this->nh->getArticle($this->getNewsId());
		} else {
			$this->news = $this->nh->getLatest(0,1,phive('Localizer')->getLanguage(),$ids);
			$this->news = $this->news[0];
		}
	}
	
	function getNewsId(){
		if(is_numeric($this->news_id))
			return $this->news_id;
			
		$cur_lang = phive('Localizer')->getLanguage();
		$res = array();
		foreach(array_chunk(explode(':', $this->news_id), 2) as $lang){
			if($lang[0] == $cur_lang)
				return $lang[1];
			else
				$res[ $lang[0] ] = $lang[1];
		}
		return $res['en'];
	}
	
	public function getHeadline(){
		return empty($this->news) ? null : h($this->news->getHeadline());
	}
	
	public function printHTML(){
	if($this->news !== FALSE && $this->news !== NULL):
	?>
	<div class="box newstopbox">
		<div class="main">
			<table class="news_table">
				<tr>
					<?php if($this->news->getImagePath() != ""): ?>
						<td class="newstop_image">
							<a href="<?php echo $this->getArticleUrl($this->news); ?>">
								<?php img($this->news->getImagePath(),300,176); ?>	
							</a>
						</td>
					<?php endif; ?>
					<td class="newstop_content">
			 			<p class="subheader"><?php echo $this->news->getSubheading(); ?></p>
						<p class="author"><?php echo date("Y-m-d",strtotime($this->news->getTimeCreated())); ?></p>
						<p class="abstract"><?php echo $this->news->getAbstract(); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<div class="bottom">
			<div class="row">
				<p class="item"><a href="<?php echo $this->getArticleUrl($this->news); ?>"><?php echo t("newstop.read_more"); ?></a></p>
				<?php showTac($this->news) ?>
				<?php $this->printStatus($this->news) ?>
				<!--  
				<p class="item">•</p>
				<p class="item"><?php echo t("newstop.category"); ?>: <a href="/poker/<?php echo $this->news->getCategoryAlias(); ?>/">
				<?=t('misc.category.'.$this->news->getCategoryAlias())?></a></p>
				-->
			</div>
		</div>
	</div>
	<?php
	endif;
	}
	public function printCustomSettings(){?>
		<form method="post" action="?editboxes#box_<?= $this->getId()?>">
	        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
			<input type="hidden" name="box_id" value="<?=$this->getId()?>"/>
			<p>
				Specify a static news to see, if empty NewsTop will display the latest news. 
				If not it has to be an id to link to or on the following form: en:1:sv:2
			</p>
			<p>
				<label for="news_id">News id: </label>
				<input type="text" name="news_id" value="<?=$this->news_id?>" id="news_id"/>
			</p>
			<?php $this->printSelStartEndDate(false) ?>
			<p>
			Select a category to view, All will show the latest news from all categories
			</p>
			<p>
				<?php $cats = phive("LimitedNewsHandler")->getCategories();?>
				Category: 
				<select name="category">
					<option value="ALL" <?php if($this->category == "ALL") echo 'selected="selected"'; ?>>All</option>
					<?php foreach($cats as $c): ?>
						<option value="<?php echo $c['id']; ?>" <?php if($this->category == $c['id']) echo 'selected="selected"'; ?>>
							<?php foreach(array_fill(0, $c['depth'], '&nbsp;-&nbsp;') as $space) echo $space; echo $c['name']; ?>
						</option>
					<?php endforeach ?>
				</select>
			</p>
			<input type="submit" name="save_settings" value="Save and close" id="save_settings"/>
		</form>
	<?php
	}
}
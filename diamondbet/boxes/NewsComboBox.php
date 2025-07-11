<?php
require_once __DIR__.'/DiamondBox.php';
require_once __DIR__.'/NewsListBox.php';
require_once __DIR__.'/NewsFullBox.php';

class NewsComboBox extends DiamondBox{

  function init(){
      //We need also redirect light version lagnguages to the news page
      //lightRedir('/');

    $this->bfull = new NewsFullBox();
    $this->blist = new NewsListBox();

    $this->comboInitCommon();
      
    if (llight() && empty($this->bfull->news) && empty($this->blist->news))
        lightRedir('/');

    $this->bfull->setHeaderVals(586, 230, 586, 230);

    //$this->cats = phive('CategoryHandler')->getRootCategories();
    //$this->cats = phive('CategoryHandler')->getByIds($this->blist->category);
      $this->cats = phive('LimitedNewsHandler')->getCategoriesfromArticles(phive("SQL")->escape(cLang()),$this->blist->category);

      $this->setTitle();
  }

  function is404($args){
    return $this->bfull->is404($args);
  }

    function setTitle(){
        empty($_GET['arg0']) ? $this->blist->setTitle() : $this->bfull->setTitle();        
    }
    
  function printHTML(){ ?>
     <div class="news-container">
      <div class="news-top">
        <div class="news-top-bkg">
          <div class="news-content clearfix">
            <h1 class="big_headline"><?php et('cat.'.$this->blist->category.'.headline') ?></h1>
            <br/>
            <?php $this->bfull->printBanner(); ?>
            <?php $this->bfull->printHTML(); ?>
          </div>
          <div class="news-archive">
            <?php $this->newsArchive($this->blist->archived_months, $this->alink) ?>
            <br/>
            <br/>
            <h3 class="big_headline"><?php et('news.categories.headline') ?></h3>
            <?php foreach($this->cats as $c): ?>
              <div class="archive-month">
                <a class="a-big" href="<?php echo $this->loc->langLink('', "/{$c['alias']}" ) ?>">
                  <?php et("cat.{$c['alias']}") ?>
                </a>
              </div>
            <?php endforeach ?>
          </div>
        </div>
      </div>
      <div class="news-middle">
        <div class="news-content">
        <?php $this->blist->printHTML(); ?>
        </div>
      </div>
      <div class="news-bottom">

      </div>
    </div>

  <?php }

  function printExtra(){
    $this->bfull->printExtra();
    $this->blist->printExtra();
    ?>
      <p>
        <label for="alink">Archive link (ex: news/archive or archive ): </label>
        <input type="text" name="alink" value="<?= $this->alink ?>" />
      </p>
    <?php
  }

}

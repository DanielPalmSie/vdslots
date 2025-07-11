<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/NewsTopListBase.php';
class NewsListBox extends NewsTopListBase {

  function printRow(&$n){
    $stamp = strtotime($n->getTimeCreated());
    $cur_link = llink($this->getArticleUrl($n));
?>
  <div class="list-news-item">
      <div class="list-news-top">
          <div class="img-left">
              <a href="<?php echo $cur_link ?>">
                  <?php img($n->getImagePath(), 150, 135) ?>
              </a>
          </div>

          <div class="list-news-content">
              <h3 class="big_headline">
                  <?php echo rep($n->getHeadline()) ?>
              </h3>
              <p>
                  <?php echo rep($n->getAbstract()) ?>
              </p>
          </div>
      </div>
    <div class="list-news-bottom">
      <div class="list-news-readmore">
        <?php btnSmall(t('read.more'), $cur_link) ?>
      </div>
      <?php $this->drawArticleInfo($n, $stamp, 'list-article-info') ?>
    </div>
  </div>
  <?php }
  
  function printHTML(){
    if(!empty($this->news)):
    ?>
    <div class="news-top-list">
        <?php $i = 0; foreach($this->news as $n):
          if($n->getId() == $this->full_news->getId())
            continue;
        ?>
          <?php $this->printRow($n) ?>
        <?php $i++; endforeach; ?>
    </div>
    <?php
      if($this->show_pages == 'yes')
        $this->p->render();
    ?>
    <?php
    endif;
  }
}

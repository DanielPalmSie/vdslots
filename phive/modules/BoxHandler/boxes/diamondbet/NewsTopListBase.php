<?php
require_once __DIR__.'/NewsBase.php';

class NewsTopListBase extends NewsBase {
  public function printInstanceJS(){
    parent::printInstanceJS();
  }

  public function init($start = null){

    $this->handlePost(array('number_of_news', 'show_status', 'status_only', 'show_pages'));

      if (isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId() && $_POST['category']) {
          $this->setAttribute("category", implode(',', $_POST['category']));
      }

    $this->show_news_url 	= "/";

    /*
    if(isset($_GET['arg0']) && is_numeric($_GET['arg0']))
      $this->start = 0;
    else
    */

    if($start === null)
      $this->start 		= 0;
    else
      $this->start 		= $start;

    $this->show_pages 		= $this->attributeIsSet("show_pages")		? $this->getAttribute("show_pages"):'yes';
    $this->limit 		= $this->attributeIsSet("number_of_news")	? $this->getAttribute("number_of_news"):4;
    $this->category         = $this->getNewsCategory();
    $this->show_status		= $this->attributeIsSet("show_status")		? $this->getAttribute("show_status"):1;
    $this->nh 			= phive("LimitedNewsHandler");
    $this->cath			= phive("CategoryHandler");
    $this->p			= phive("Paginator");
    $this->cur_lang 		= phive('Localizer')->getSubIndependentLang();
    //$date_now 			= date('Y-m-d');
      $extra                    = null;
      $this->sdate 		= phive('SQL')->sanitizeDate($_GET['start_date']);
      $this->edate 		= phive('SQL')->sanitizeDate($_GET['end_date']);
    $this->archived_months = $this->nh->getArchivedMonths("country = " .phive("SQL")->escape(cLang()));
      
      if(!empty($this->sdate) && !empty($this->edate)){
          $extra = "time_created >= '{$this->sdate}' AND time_created <= '{$this->edate}'";
      }

    switch($this->status_only){
      case 'upcoming':
        $extra = "start_date > '$date_now'";
        break;
      case 'current':
        $extra = "start_date < '$date_now' AND end_date > '$date_now'";
        break;
      case 'old':
        $extra = "end_date < '$date_now'";
        break;
    }

    $extra 		= empty($extra) ? '' : ' AND '.$extra;
    $ids 		= $this->category == "ALL" ? $this->category : implode(',', $this->category);
    $where 		= $this->category == "ALL" ? "1" : "category_id IN($ids)";
    //echo $where; exit;
    $this->news = $this->nh->getLatestTopList($this->cur_lang, '', 'APPROVED', $extra." AND ".$where);

    if($this->p->getOffset($this->limit)){
      $this->start 	= $this->p->getOffset($this->limit) + $this->start;
      $total_count 	= count($this->news);
    }else
      $total_count 	= count($this->news) - $this->start;

    $this->p->setPages($total_count, '', $this->limit);

    $this->news = $this->nh->sortByTimeStatus( $this->news );

    $this->news = array_slice($this->news, $this->start, $this->limit);

    $this->can_edit = p("news.edit");


      /*
    $this->top 				= $this->nh->getMostRead(
      0, $this->num_top, phive('Localizer')->getLanguage(), date("Y-m-d",strtotime("-1 week")), $this->category);


    $this->older = $this->nh->getLatest($this->limit, $this->num_old, $cur_lang, $this->category);
    */
  }

    
    
    public function setTitle(){
        phive('Pager')->setTitle( empty($_GET['page']) ?  t('news.and.updates') : t2('news.and.updates.sub.page', [$_GET['page']]));        
    }
    
  public function printHTML(){
    if(!empty($this->news)):
    ?>
    <div class="news-top-list">
      <h3 class="big_headline"> <?php echo t("news.box{$this->getId()}.headline") ?> </h3>
      <table class="zebra-tbl">
        <?php $i = 0; foreach($this->news as $n):
          $stamp = strtotime($n->getTimeCreated());
        ?>
        <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
          <td class="news-date"><?php echo ucfirst(strftime("%b", $stamp)).' '.strftime("%e", $stamp) ?></td>
          <td>
            <a class="a-big" href="<?php echo llink($this->getArticleUrl($n)) ?>">
              <?php echo $n->getHeadline() ?>
            </a>
          </td>
        </tr>
        <?php $i++; endforeach; ?>
      </table>
    </div>
    <br>
    <?php
      if($this->show_pages == 'yes')
        $this->p->render();
    ?>
    <?php
    endif;
  }

  public function printExtra(){?>
    <p>
      <label for="number_of_news">Number of news to list: </label>
      <input type="text" name="number_of_news" value="<?=$this->limit?>" id="name"/>
    </p>
    <p>
      <label for="number_of_news">Show the status of articles (0 or 1): </label>
      <input type="text" name="show_status" value="<?=$this->show_status?>" id="show_status"/>
    </p>
    <p>
      <label for="number_of_news">Show only articles with status "upcoming", "old" or "current" or leave empty to show all: </label>
      <input type="text" name="status_only" value="<?=$this->status_only?>" id="status_only"/>
    </p>
    <p>Select a category to view, All will show the latest news from all categories</p>
    <p>
      <?php phive("LimitedNewsHandler")->getCatDropDownMulti($this->category) ?>
    </p>
    <p>
      <label for="show_pages">Show pages: (yes/no)</label>
      <input type="text" name="show_pages" value="<?=$this->show_pages?>" id="show_pages"/>
    </p>
  <?php
  }
}

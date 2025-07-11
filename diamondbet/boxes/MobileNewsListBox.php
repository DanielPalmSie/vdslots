<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/NewsTopListBase.php';
class MobileNewsListBox extends NewsTopListBase {


  public function init($start = null, $search_query = "") {

    $this->handlePost(array('number_of_news', 'show_status', 'status_only', 'show_pages'));

    if (isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()){
      $this->setAttribute("category", implode(',', $_POST['category']));
    }

    $this->show_news_url 	= "/";

    /*
    if(isset($_GET['arg0']) && is_numeric($_GET['arg0']))
      $this->start = 0;
    else
    */

    if ($start === null)
      $this->start 	  	   = 0;
    else
      $this->start 	  	   = $start;

    $this->show_pages      = $this->attributeIsSet("show_pages")	   ? $this->getAttribute("show_pages"):'yes';
    $this->limit 		       = $this->attributeIsSet("number_of_news") ? $this->getAttribute("number_of_news") : 6;

    $number_of_news        = trim(phive("Config")->getValue("news-mobile", "number-of-news"));

    $this->limit 		       = $number_of_news ? $number_of_news : $this->limit;
    $this->category 	     =  $this->getNewsCategory();
    $this->show_status     = $this->attributeIsSet("show_status")	? $this->getAttribute("show_status") : 1;
    $this->nh 	    		   = phive("LimitedNewsHandler");
    $this->cath			       = phive("CategoryHandler");
    $this->p			         = phive("Paginator");
    $this->sql             = phive('SQL');
    $this->cur_lang        = phive('Localizer')->getSubIndependentLang();
    //$date_now 			= date('Y-m-d');
    $extra 					       = null;
    $this->sdate 		       = $_GET['start_date'];
    $this->edate           = $_GET['end_date'];
    $this->archived_months = $this->nh->getArchivedMonths("country = " .phive("SQL")->escape(cLang()));

    if (!empty($this->sdate) && !empty($this->edate)) {
      $extra = "time_created >= '{$this->sdate}' AND time_created <= '{$this->edate}'";
    }

    switch ($this->status_only) {
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

    $extra = empty($extra) ? '' : ' AND '.$extra;

    $column_to_search = trim(phive("Config")->getValue("news-mobile", "column-to-search"));
    $column_to_search = empty($column_to_search) ? "headline" : $column_to_search;

    $serach_terms = preg_split('/\s+/', $search_query , -1, PREG_SPLIT_NO_EMPTY);

    foreach ($serach_terms as $query) {
      $extra .= " AND {$column_to_search} LIKE '%{$this->sql->escape($query, false)}%'";
    }

    $ids   = $this->category == "ALL" ? $this->category : implode(',', $this->category);
    $where = $this->category == "ALL" ? "1" : "category_id IN($ids)";
    //echo $where; exit;
    $this->news = $this->nh->getLatestTopList($this->cur_lang, '', 'APPROVED', $extra." AND ".$where);
    $this->total_news = count($this->news);

    if ($this->p->getOffset($this->limit)) {
      $this->start = $this->p->getOffset($this->limit) + $this->start;
      $total_count = count($this->news);
    } else {
      $total_count = count($this->news) - $this->start;
    }

    $this->p->setPages($total_count, '', $this->limit);
    $this->news = $this->nh->sortByTimeStatus( $this->news );
    $this->news = array_slice($this->news, $this->start, $this->limit);
    $this->can_edit = p("news.edit");

    /*
    $this->top = $this->nh->getMostRead(
      0, $this->num_top, phive('Localizer')->getLanguage(), date("Y-m-d",strtotime("-1 week")), $this->category);


    $this->older = $this->nh->getLatest($this->limit, $this->num_old, $cur_lang, $this->category);
    */
  }

  function printHTML() {
    if (!empty($this->news)):
    ?>
      <div id="news-top-list" class="news-top-list-mobile">
        <?php
        foreach ($this->news as $n) {
          if (isset($_GET['arg0'])) {
            if (!empty($this->full_news) && $n->getId() == $this->full_news->getId()) {
              continue;
            }
          }
          $this->printRow($n);
        }
        ?>
      </div>
    <?php
    endif;
  }

  function printRow(&$n) {
    $stamp = strtotime($n->getTimeCreated());
    $cur_link = llink('/mobile' . $this->getArticleUrl($n))
?>
    <div class="list-news-item list-news-item-mobile">
      <h2 class="big_headline big_headline_mobile">
          <?php echo rep($n->getHeadline()) ?>
      </h2>

      <div class="img-left img-left-mobile">
        <a href="<?php echo $cur_link ?>">
          <?php img($n->getImagePath(), 150, 135) ?>
        </a>
      </div>

      <div class="list-news-content list-news-content-mobile">
        <p class="list-news-abstract">
          <?php echo rep($n->getAbstract()) ?>
        </p>
      </div>
      <div class="list-news-bottom-mobile">
        <?php $this->drawArticleInfo($n, $stamp, 'list-article-info-mobile') ?>
      </div>

    </div>
<?php
  }

  function drawArticleInfo($news, $stamp, $cls = "article_info") {
      $cur_link = llink('/mobile' . $this->getArticleUrl($news))
    ?>
    <div class="<?php echo $cls ?>">
      <button class="btn btn-m btn-default-xl btn-mobile-read-more" onclick="goTo('<?php echo $cur_link; ?>')">
        <span><?php echo t('read.more') ?></span>
      </button>
      <span class="header-big-mobile">
      <?php
        echo ucfirst(t(date("M", $stamp))) .' '. strftime("%d", $stamp) .' '. strftime("%G", $stamp);
        $status = $news->getStatus();
        if($status): ?>
          <span class="bigNewsStatus" style="color:<?php echo $status[1]; ?>; display:none;"><?php echo $status[0]; ?></span>
  <?php endif ?>
      </span>
    </div>
  <?php
  }
}

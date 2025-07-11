<?php
require_once __DIR__.'/DiamondBox.php';
require_once __DIR__.'/NewsListBox.php';
require_once __DIR__.'/NewsFullBox.php';
require_once __DIR__.'/NewsComboBox.php';
require_once __DIR__.'/MobileNewsFullBox.php';
require_once __DIR__.'/MobileNewsListBox.php';

class MobileNewsComboBox extends NewsComboBox {

  function init() {
    //We need also redirect light version languages to the news page
    //lightRedir('/');

    $this->bfull = new MobileNewsFullBox();
    $this->blist = new MobileNewsListBox();

    $min_amount_search_characters = trim(phive("Config")->getValue("news-mobile", "min-amount-search-characters", 3));
    $this->min_amount_search_characters = empty($min_amount_search_characters) ? 3 : $min_amount_search_characters;

    $this->comboInitCommon();

    if (llight() && empty($this->bfull->news) && empty($this->blist->news))
      lightRedir('/');

    $this->bfull->setHeaderVals(586, 230, 286, 230);

    //$this->cats = phive('CategoryHandler')->getRootCategories();
    //$this->cats = phive('CategoryHandler')->getByIds($this->blist->category);
    $this->cats = phive('LimitedNewsHandler')->getCategoriesfromArticles(phive("SQL")->escape(cLang()),$this->blist->category);

  }

  function printHTML() { ?>
     <div class="news-container-mobile">
       <div class="news-top-mobile">
         <div class="news-top-bkg">
          <div class="news-content news-content-mobile clearfix">
            <h3 class="big_headline big_headline_mobile"><?php echo t('cat.'.$this->blist->category.'.headline') ?></h3>
            <br/>
            <?php if (isset($_GET['arg0'])): ?>
            <?php $this->bfull->printBanner(); ?>
            <?php $this->bfull->printHTML(); ?>
            <?php endif ?>
          </div>
          <!--
          <div class="news-archive">
            <?php $this->newsArchive($this->blist->archived_months, $this->alink) ?>
            <br/>
            <br/>
            <h3 class="big_headline"><?php echo t('news.categories.headline') ?></h3>
            <?php foreach($this->cats as $c): ?>
              <div class="archive-month">
                <a class="a-big" href="<?php echo $this->loc->langLink('', "/{$c['alias']}" ) ?>">
                  <?php et("cat.{$c['alias']}") ?>
                </a>
              </div>
            <?php endforeach ?>
          </div>
          -->
        </div>
      </div>

      <div class="news-middle-mobile">
        <div class="input-container-news-mobile">
          <!-- <i class="icon-news-mobile icon-vs-search"></i> -->
          <!-- <input type="search" class="search-news-mobile"  value="" placeholder="Search News" /> -->
          <svg xmlns="http://www.w3.org/2000/svg" style="display:none">
            <symbol xmlns="http://www.w3.org/2000/svg" id="sbx-icon-search" viewBox="0 0 40 41">
              <path d="M25.54 28.188c-2.686 2.115-6.075 3.376-9.758 3.376C7.066 31.564 0 24.498 0 15.782 0 7.066 7.066 0 15.782 0c8.716 0 15.782 7.066 15.782 15.782 0 4.22-1.656 8.052-4.353 10.884l1.752 1.75 1.06-1.06L40 37.332l-3.72 3.72-9.977-9.976 1.062-1.062-1.826-1.826zm-9.758.746c7.264 0 13.152-5.888 13.152-13.152 0-7.263-5.888-13.152-13.152-13.152C8.52 2.63 2.63 8.52 2.63 15.782c0 7.264 5.89 13.152 13.152 13.152z" fill-rule="evenodd" />
            </symbol>
            <symbol xmlns="http://www.w3.org/2000/svg" id="sbx-icon-clear" viewBox="0 0 20 20">
              <path d="M8.96 10L.52 1.562 0 1.042 1.04 0l.522.52L10 8.96 18.438.52l.52-.52L20 1.04l-.52.522L11.04 10l8.44 8.438.52.52L18.96 20l-.522-.52L10 11.04l-8.438 8.44-.52.52L0 18.96l.52-.522L8.96 10z" fill-rule="evenodd" />
            </symbol>
          </svg>

          <form novalidate="novalidate" onsubmit="return false;" class="searchbox sbx-custom">
            <div role="search" class="sbx-medium__wrapper">
              <input id="search-news-mobile" type="search" name="search" placeholder='<?php et("search.news"); ?>' autocomplete="off" required="required" class="sbx-custom__input">
              <button type="submit" title="Submit your search query." class="sbx-custom__submit">
          <!-- <i class="icon-news-mobile icon-vs-search"></i> -->
                <svg role="img" aria-label="Search">
                  <use xlink:href="#sbx-icon-search"></use>
                </svg>
              </button>
              <button id="reset-search-news-mobile" type="reset" title="Clear the search query." class="sbx-custom__reset">
                <svg role="img" aria-label="Reset">
                  <use xlink:href="#sbx-icon-clear"></use>
                </svg>
              </button>
            </div>
          </form>

        </div>
        <div id="news-content-mobile" class="news-content-mobile">
          <?php if(!isset($_GET['arg0'])): ?>
          <?php array_unshift($this->blist->news, $this->bfull->news); ?>
          <?php endif ?>
          <?php $this->blist->printHTML(); ?>
        </div>
        <button id="btn-mobile-view-more" class="btn btn-xl btn-mobile-view-more" data-news-start-offset="<?php echo $this->blist->limit; ?>">
          <span><?php et('view.more') ?></span>
        </button>
      </div>
      <div class="news-bottom-mobile">
      </div>
    </div>

  <?php

    $this->printMobileNewsJs();
  }

  function printExtra() {
    $this->bfull->printExtra();
    $this->blist->printExtra();
    ?>
      <p>
        <label for="alink">Archive link (ex: news/archive or archive ): </label>
        <input type="text" name="alink" value="<?= $this->alink ?>" />
      </p>
    <?php
  }

  function getNews() {

    $startoffset = $_REQUEST['startoffset'];
    $searchquery = $_REQUEST['searchquery'];

    $this->blist = new MobileNewsListBox();
    $this->blist->init($startoffset, $searchquery);

    $jsondata = [];
    ob_start();
    $this->blist->printHTML();
    $jsondata['html']        = ob_get_clean();
    $jsondata['amount_news'] = count($this->blist->news);
    $jsondata['more_news']   = $this->blist->start + count($this->blist->news) < $this->blist->total_news;

    echo json_encode($jsondata);
  }

  function printMobileNewsJs() {
    ?>
    <script>
      var sMobileNewsComboBox = 'MobileNewsComboBox';

      function getNewsWithOffset() {
        var search_str = $("#search-news-mobile").val();
        if (search_str.length < <?php echo $this->min_amount_search_characters ?>) {
          search_str = "";
        }

        $("#btn-mobile-view-more").text("<?php echo t('mobile.news.loading') ?>");

        var news_start_offset = $("#btn-mobile-view-more").data('news-start-offset');
        var params = {startoffset: news_start_offset, searchquery: search_str, func: 'getNews'};

        ajaxGetBoxHtml(params, cur_lang, sMobileNewsComboBox, function(ret) {
          var json_object = JSON.parse(ret);

          $("#news-content-mobile").append(json_object.html);
          $("#btn-mobile-view-more").text("<?php et('view.more') ?>");

          // Hide the "View More" button if html is empty or the amount of news is less
          // than limit, since that means there would be no more to load.
          if (json_object.html.length == 0 || !json_object.more_news) {
            $("#btn-mobile-view-more").hide();
          } else {
            $("#btn-mobile-view-more").show();
          }

          $("#btn-mobile-view-more").data('news-start-offset', news_start_offset+json_object.amount_news);
        });
      }

      function searchNews(search_query = null) {

        $("#btn-mobile-view-more").show();
        $("#news-content-mobile").html("");

        $("#btn-mobile-view-more").data('news-start-offset', 0);

        var search_str = $("#search-news-mobile").val();

        if (search_query != null) { // Parameter overrides what's in the input field.
          $("#search-news-mobile").val(search_query);
          search_str = search_query;
        }

        if (search_str.length > 0 && search_str.length < <?php echo $this->min_amount_search_characters ?>) {
          $("#btn-mobile-view-more").attr('disabled', true);
          var characters_left = (<?php echo $this->min_amount_search_characters ?> - search_str.length);
          if (characters_left > 1) {
            $("#btn-mobile-view-more").text(characters_left+" <?php echo t('mobile.news.search.min.chars') ?>");
          } else {
            $("#btn-mobile-view-more").text(characters_left+" <?php echo t('mobile.news.search.min.char') ?>");
          }
          return;
        }

        $("#btn-mobile-view-more").attr('disabled', false);

        getNewsWithOffset();
      }

      $(document).ready(function() {
        $('.footer-holder').find('div').first().css('width', ""); // Override the hardcoded width 380px to strech the footer to fill the width of screen.
        $('.footer-holder').find('div').first().find('div').css('margin-left', "10px").css('margin-right', "10px"); // Setting margin left and right on divs with content inside the footer-holder.

        $("#btn-mobile-view-more").click(function() {
          getNewsWithOffset();
        });

        $("#search-news-mobile").keyup(function() {
          searchNews();
        });

        $("#reset-search-news-mobile").on('click', function() {
          searchNews("");
        });
      });
    </script>
    <?php
  }

}

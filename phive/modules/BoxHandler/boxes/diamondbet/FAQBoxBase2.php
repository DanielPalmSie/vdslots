<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class FAQBoxBase2 extends DiamondBox{
  function init(){
    $this->handlePost(array('num_q', 'num_cat'));
    $this->numq_arr = explode(',', $this->num_q);
    if(!empty($_POST['search-field'])){
      $lang = phive("Localizer")->getLanguage();
      $tmp1 = $this->searchStr($_POST['search-field'], 'answer', $lang);
      $tmp2 = $this->searchStr($_POST['search-field'], 'question', $lang);
      $this->search_res = array_unique(array_merge($tmp1, $tmp2), SORT_REGULAR);

    }
  }

  function searchStr($search, $type, $lang){
    $other_type 		= $type == 'answer' ? 'question' : 'answer';
    $str 				= "SELECT * FROM localized_strings WHERE alias LIKE 'faqbox.$type%' AND language = '$lang' AND value LIKE '%$search%'";
    $rarr				= array();
    foreach (phive("SQL")->loadArray($str) as $s) {
      $other_alias 	= str_replace(array($type, '.html'), array($other_type, ''), $s['alias']);
      $other_str 		= phive("SQL")->loadAssoc('', 'localized_strings', " alias LIKE '$other_alias%' AND language = '$lang' ");
      $rarr[] 		= array($type => $s, $other_type => $other_str);
    }
    return $rarr;
  }

  function printHTML(){?>
  <link href="/phive/js/ui/css/custom-theme/jquery-ui-1.8.11.custom.css" rel="stylesheet" type="text/css"/>
  <link href="/diamondbet/css/<?php echo brandedCss() ?>ui.css"  rel="stylesheet" type="text/css"/>
  <script type="text/javascript">
   jQuery(document).ready(function(){
     $(".faq-cat-accordion").accordion({ collapsible: true, active: false, heightStyle: 'content' });
     $("#search-field").focus(function(){ $(this).val(''); });
   });
  </script>
  <div class="frame-block generalSubBlock">
    <div class="frame-holder">
      <div>
	<table class="v-align-top">
	  <tr>
	    <td>
	      <div class="faq-left">
		<ul>
		  <?php $this->printHelpMenu() ?>
		</ul>
	      </div>
	    </td>
	    <td>
	      <div class="faq-right">
		<h1><?php et("faq") ?></h1>

		<?php $this->faqSearch() ?>

		<?php if(!empty($this->search_res) && !empty($_POST['search-field'])): ?>

		  <h2><?php et("search.result") ?></h2>
		  <div class="faq-cat-accordion">
		    <?php foreach($this->search_res as $arr): ?>
		      <h6><a href="#"><?php echo rep($arr['question']['value']) ?></a></h6>
		      <div><?php echo rep($arr['answer']['value']) ?></div>
		    <?php endforeach ?>
		  </div>

		<?php elseif(!empty($_POST['search-field'])): ?>

		  <h2><?php et("search.result.no.result") ?></h2>

		<?php else: ?>

		  <?php for($i = 0; $i < $this->num_cat; $i++): ?>
		    <h2><?php et("faqbox.".$this->getId().".cat.".$i) ?></h2>
		    <div class="faq-cat-accordion">
		      <?php for($j = 0; $j < $this->numq_arr[$i]; $j++): ?>
			<h6><a class="faq__anchor-question" href="#"><?php echo t("faqbox.question.".$this->getId().".$i.$j") ?></a></h6>
			<div><?php et("faqbox.answer.".$this->getId().".$i.$j.html") ?></div>
		      <?php endfor ?>
		    </div>
		  <?php endfor ?>

		<?php endif ?>
	      </div>
	    </td>
	  </tr>
	</table>
      </div>
    </div>
  </div>
<?php }

function printExtra(){ ?>
  <p>
    <label for="alink">Number of categories: </label>
    <input type="text" name="num_cat" value="<?= $this->num_cat ?>" />
  </p>
  <p>
    <label for="alink">Number of questions in categories ex; 6,5,8 for 6 in the first category, 5 in the second etc: </label>
    <input type="text" name="num_q" value="<?= $this->num_q ?>" />
  </p>
<?php }
}

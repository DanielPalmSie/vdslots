<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class FAQBoxBase extends DiamondBox{
	function init(){
		$this->handlePost(array('num_questions'), array('num_questions' => 5));
	}

	function printHTML(){?>
		<link href="/phive/js/ui/css/custom-theme/jquery-ui-1.8.11.custom.css" rel="stylesheet" type="text/css"/>
		<link href="/diamondbet/css/<?php echo brandedCss() ?>ui.css"  rel="stylesheet" type="text/css"/>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				$("#accordion").accordion();
			});
		</script>
		<div class="general-content">
			<h1 class="orange">
				<?php et("faq") ?>
			</h1>
			<div class="orange_hr"></div>
			<div>
				<?php et("faq.html") ?>
			</div>
			<br>
			<div id="accordion">
				<?php for($i = 1; $i <= $this->num_questions; $i++): ?>
					<h3><a href="#"><?php echo "$i. ".t("faqbox.".$this->getId().".question.".$i) ?></a></h3>
					<div><?php et("faqbox.".$this->getId().".answer.".$i.".html") ?></div>
				<?php endfor ?>
			</div>
		</div>
	<?php }

	function printExtra(){ ?>
		<p>
			<label for="alink">Number of questions: </label>
			<input type="text" name="num_questions" value="<?= $this->num_questions ?>" />
		</p>
	<?php }
}

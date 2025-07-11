<?php
$menuer 		= phive('Menuer');
$footer_menu 	= $menuer->forRender('footer');
$first_item 	= array_shift($footer_menu);
$loc 			= phive("Localizer");
?>
<div class="<?php echo $only_logo == 1 ? "transfooter" : "footer" ?>">
  <div class="footer-holder">
    <div class="menu">
      <?php if($only_logo != 1): ?>
	<ul>
        <li class="first"><a <?php echo $first_item['params']?>><?php echo $first_item['txt']?></a></li>
	  <?php foreach($footer_menu as $item): ?>
	    <li><a <?php echo $item['params']?>><?php echo $item['txt']?></a></li>
	  <?php endforeach ?>
	</ul>
      <?php endif ?>
      <br/>
      <?php et2('footer.section.html') ?>
    </div>
  </div>
</div>
<?php
include __DIR__ . '/chat-support.php';

<?php 
?>
<br clear="all"/>
<div style="<?= phive()->isMobileApp() ? 'display:none' : ''; ?>">
  <div class="footer-holder">
    <br clear="all"/>
    <?php et2('mobile.footer.section.html', array(date('Y'))) ?>
  </div>
</div>
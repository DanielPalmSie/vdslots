<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';


// TODO this is deprecated as of Feb 2018, should be removed. /Henrik
class SimpleMessageBoxBase extends DiamondBox{
  
  function init(){
    header('X-XSS-Protection: 0');
    if(!empty($_GET['showstr'])){
      $this->show_msg = t($_GET['showstr']);
    }else if(!empty($_POST['showmsg'])){
      $tmp = json_decode(urldecode($_POST['showmsg']), true);
      $this->show_msg = $tmp['msg'];
    }else if(!empty($_POST['plainmsg'])){
      $this->show_msg = urldecode($_POST['plainmsg']);
    }else{
      $this->action = json_decode(urldecode($_POST['msg']), true);
      $tmp = array('failBonusConfirm', 'failBonusWrongGame');
      $this->php_funcs = array_combine($tmp, $tmp);
    }
  }
  
  function printHtml(){
    $php_func = $this->php_funcs[$this->action['phpfunc']];
    if(empty($php_func) && empty($this->show_msg))
      return;
?>
<script>
 $(document).ready(function(){
   $("#back-btn-cont").click(function(){ goTo('<?php echo $_SERVER['HTTP_REFERER'] ?>') });
   <?php if(!empty($_POST['msg'])): ?>
     var actions = JSON.parse('<?php echo str_replace("'", "\'", html_entity_decode(urldecode($_POST['msg']), ENT_QUOTES)) ?>')['jsactions'];
     $.each(actions, function(i, o){
       var el = $(o.sel);
       if(o.args)
         jQuery.fn[o.action].apply(el, o.args);
       else 
         el[o.action]();
     });
   <?php endif ?>

   <?php if(!empty($_POST['plainmsg'])): ?>
     var btns = $(".simple-msg-content-area").find('button'); 
     var num_texts = $(".simple-msg-content-area").find('input[type=text]').length; 
     var num_pwds = $(".simple-msg-content-area").find('input[type=password]').length; 
     if(btns.length > 1 || num_texts > 0 || num_pwds > 0){
       btns.each(function(){
         var priorClick = $(this).attr("onclick");
         if(empty(priorClick))
           $(this).click(function(){ goTo('<?php echo $_SERVER['HTTP_REFERER'] ?>') });
       });
       $("#back-btn-cont").hide();
     }
   <?php endif ?>

 });
</script>
<div class="frame-block">
  <div class="frame-holder simple-msg-content-area">
    <?php echo empty($this->show_msg) ? $php_func() : $this->show_msg ?>
    <br/>
    <br/>
    <center>
      <div id="back-btn-cont">
	<?php btnDefaultXl(t('back'), '', '', 200) ?>
      </div>
    </center>
    <br clear="all"/>
  </div>
</div>
<?php }
}

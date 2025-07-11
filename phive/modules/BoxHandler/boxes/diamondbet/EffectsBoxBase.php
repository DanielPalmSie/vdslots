<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class EffectsBoxBase extends DiamondBox{

  function init(){
    $this->handlePost(array('table_hide_num', 'table_hide_class', 'table_hide_id'));
  }

  function printHTML(){ ?>
  <script>
    jQuery(document).ready(function(){
      <?php if(!empty($this->table_hide_num)): ?>
        function hideTableRows(tbl, btn){
          tbl.find('tr').each(function(i){
            if(i > <?php echo $this->table_hide_num ?>){
              $(this).hide();
            }
        });

        if(typeof(btn) == 'undefined'){
          btn = $($("#hide-rows-cont").html());
          tbl.after( btn );

          btn.on('click', function() {
            if ($(this).hasClass('toggled')) {
              hideTableRows(tbl, btn);
              $(this).html('<?php et('view.all') ?>');
              $(this).removeClass('toggled');
            } else {
              $(this).prev().find('tr').show();
              $(this).html('<?php et('hide.all') ?>');
              $(this).addClass('toggled');
            }
          })
        }
      }

      <?php if(!empty($this->table_hide_class)): ?>
        $(".<?php echo $this->table_hide_class ?>").each(function(i){
          hideTableRows($(this));
        });
      <?php endif?>

      <?php if(!empty($this->table_hide_id)): ?>
        hideTableRows($("#<?php echo $this->table_hide_id ?>"));
      <?php endif?>

     <?php endif?>
   });
  </script>
  <?php if(!empty($this->table_hide_num)): ?>
    <div id="hide-rows-cont" style="display: none;">
      <div class="hide-rows-viewall"><?php et('view.all') ?></div>
    </div>
  <?php endif ?>
<?php
	}


function printExtra(){ ?>
  <p>
    <strong>WARNING, place only ONE EffectsBox on any given page, putting more than one can break JS execution and therefore the whole site!</strong>
  </p>
  <p>
    Show number of table rows (empty or 0 to disable):
    <input type="text" name="table_hide_num" value="<?php echo $this->table_hide_num ?>" />
    <br/>
    <br/>
    Hide table rows class (hide table rows only with this class):
    <input type="text" name="table_hide_class" value="<?php echo $this->table_hide_class ?>" />
    <br/>
    <br/>
    Hide table rows id (hide table rows only with this id):
    <input type="text" name="table_hide_id" value="<?php echo $this->table_hide_id ?>" />
  </p>
<?php }
}

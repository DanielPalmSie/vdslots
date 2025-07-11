<?php
$rg     = rgLimits();
$mbox   = new MboxCommon();
$u_obj  = $mbox->getUserOrDie();
$limits = $rg->getOldLimits($u_obj);
?>
<script>
 function keepRgLimits(){
     licJson('keepRgLimits', {return_format: 'html'}, function(ret){
         mboxClose();
     });
 }

 function revertToOldLimits(){
     licJson('revertToOldLimits', {return_format: 'html'}, function(ret){
         mboxClose();
     }); 
 }
</script>
<p style="text-align: center;">
    <?php et('revert.to.old.limits.q') ?>
</p>
<table class="revert-limits-table">
    <thead>
        <tr>
            <th><?php et('type') ?></th>
            <th><?php et('time.span') ?></th>
            <th><?php et('current.limit') ?></th>
            <th><?php et('old.limit') ?></th>
        </tr>
    </thead>
<?php foreach($limits as $rgl): ?>
    <tr>
        <td><?php et($rgl['type']) ?></td>
        <td><?php et($rgl['time_span']) ?></td>
        <td><?php echo $rg->prettyLimit($rgl['type'], $rgl['cur_lim']) ?></td>
        <td><?php echo $rg->prettyLimit($rgl['type'], $rgl['old_lim']) ?></td>
    </tr>
<?php endforeach ?>
</table>
<br clear="all"/>
<div class="revert-limits-buttons">
    <div class="right"><?php btnActionXl(t('no'), '', 'keepRgLimits()', 100) ?></div>
    <div class="left"><?php btnCancelXl(t('yes'), '', 'revertToOldLimits()', 100) ?></div>
    <br clear="all">
</div>

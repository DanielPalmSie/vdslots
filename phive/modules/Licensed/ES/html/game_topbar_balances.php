<?php if(empty($_GET['eid'])): ?>
<div class="top-bar-session-info-container">
    <div class="top-bar-menuitem no-border top-bar-session-info">
        <?php et('my.all.time.wagered') ?>: <?php echo cs() . ' ' ?> <strong id="session_wagered"> </strong>
    </div>
    <div class="top-bar-menuitem no-border top-bar-session-info">
        <?php et('won') ?>: <?php echo cs() . ' ' ?> <strong id="session_won"> </strong>
    </div>
    <div class="top-bar-menuitem no-border top-bar-session-info">
        <?php et('session.balance') ?>: <?php echo cs() . ' ' ?> <strong id="session_balance"> </strong>
    </div>
</div>
<?endif;
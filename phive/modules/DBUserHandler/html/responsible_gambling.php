<?php

die('Page not found. Code 291291-2121');

function rgLimitForm($str, $money, $setting, $limit){ ?>
  <div><?php echo t("change.your.$str.limit").' '.($money ? ciso() : '') ?></div>
  <?php dbInput($setting, $limit === false ? '' : $limit / ($money ? 100 : 1), 'text', 'input-normal') ?>
  <br clear="all" />
  <br clear="all" />
  <button id="cancelbtn_<?php echo $setting ?>" class="btn btn-l btn-cancel-l w-100"><?php et('cancel') ?></button>
  <span class="account-spacer">&nbsp;</span>
  <button id="savebtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-100"><?php et('save') ?></button>
<?php }

function rgDuration($setting, $select = ''){
  $select = empty($select) ? ($setting == 'betmax-lim' ? 'none' : 'day') : $select;
  $extra_str = $setting == 'betmax-lim' ? '.cooloff' : '';
?>
  <form>
    <div id="rg-duration-<?php echo $setting ?>">
      <?php if($setting == 'betmax-lim'): ?>
        <div class="left">
          <input class="left" type="radio" name="rg_duration" value="none" <?php if($select == 'none') echo 'checked="checked"' ?> />
            <div class="left" style="margin-top: 2px;">
              <?php et("rg.none$extra_str") ?>
            </div>
        </div>
      <?php endif ?>
      <div class="left">
        <input class="left" type="radio" name="rg_duration" value="day" <?php if($select == 'day') echo 'checked="checked"' ?> />
        <div class="left" style="margin-top: 2px;">
          <?php et("rg.day$extra_str") ?>
        </div>
      </div>
      <div class="left">
        <input class="left" type="radio" name="rg_duration" value="week" <?php if($select == 'week') echo 'checked="checked"' ?> />
        <div class="left" style="margin-top: 2px;">
          <?php et("rg.week$extra_str") ?>
        </div>
      </div>
      <div class="left">
          <input class="left" type="radio" name="rg_duration" value="month" <?php if($select == 'month') echo 'checked="checked"' ?> />
          <div class="left" style="margin-top: 2px;">
            <?php et("rg.month$extra_str") ?>
          </div>
      </div>
    </div>
  </form>
<?php  }

function accLimRight(&$u, $cur_limit, $remaining, $str, $setting, $money, $reset_date, $normal = true, $forced_limit = 0){
  $activated_on = $u->getSetting("{$setting}_stamp");
  $cur_dur = $u->getSetting("{$setting}_duration");
?>
<?php if ($normal) : ?>
<div class="account-sub-box acc-lim-right responsible-gambling-box">
  <div class="account-headline"><?php et("$str.limit.this.week") ?></div>
  <span class="account-small-headline"> <?php et('limit.this.week') ?> </span>
  <span id="current_<?php echo $setting ?>"> <?php echo $money ? efEuro($cur_limit, true) : $cur_limit ?> </span>
  <?php if(($money || empty($remaining) == false) && $setting != 'betmax-lim'): ?>
    <br/>
    <span class="account-small-headline"> <?php et('remaining.amount') ?> </span><span> <?php efEuro($remaining > 0 ? $remaining : 0) ?> </span>
    <?php if($cur_limit === false): ?>      
      <button class="btn btn-l btn-cancel-l w-125 right neg-margin-top-25"><?php et('week.limit.removed') ?></button>
    <?php elseif($remaining > 0): ?>
      <button id="lowerbtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-125 right neg-margin-top-25"><?php et('lower.week.limit') ?></button>
    <?php else: ?>
      <button class="btn btn-l btn-cancel-l w-125 right    neg-margin-top-12 "><?php et('week.limit.reached') ?></button>
    <?php endif ?>
  <?php else: ?>
    <button id="lowerbtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-125  neg-margin-top-12 right"><?php et('lower.week.limit') ?></button>
  <?php endif ?>
  <br/>
  <?php if($setting != 'betmax-lim'): ?>
    <span class="account-small-headline"> <?php et('reset.date') ?> </span><span> <?php echo phive()->lcDate($reset_date, '%x %R') ?> </span>
    <br/>
  <?php endif ?>
  <?php if(p('account.view')): ?>
    <span class="account-small-headline"> <?php et('activated.on') ?> </span><span> <?php echo phive()->lcDate($activated_on, '%x %R')  ?> </span>
    <br/>
    <span class="account-small-headline"> <?php et('cur.duration') ?> </span><span> <?php et($cur_dur)  ?> </span>
  <?php endif ?>
</div>
<?php else: ?>
<div class="acc-lim-right"><!-- responsible-gambling-box"> -->
  <h3><?php et("$str.limit.this.week") ?></h3>
  <span> <?php et('limit.this.week') ?> </span>
  <span id="current_<?php echo $setting ?>"> <?php echo $money ? efEuro($cur_limit, true) : $cur_limit ?> </span>
  <?php if(($money || empty($remaining) == false) && $setting != 'betmax-lim'): ?>
    <br/>
    <span> <?php et('remaining.amount') ?> </span><span> <?php efEuro($remaining > 0 ? $remaining : 0) ?> </span>
    <?php if($cur_limit === false): ?>      
      <button class="btn btn-l btn-cancel-l w-125 right neg-margin-top-25"><?php et('week.limit.removed') ?></button>
    <?php elseif($remaining > 0): ?>
      <button id="lowerbtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-125 right neg-margin-top-25"><?php et('lower.week.limit') ?></button>
    <?php else: ?>
      <button class="btn btn-l btn-cancel-l w-125 right neg-margin-top-25"><?php et('week.limit.reached') ?></button>
    <?php endif ?>
  <?php else: ?>
    <button id="lowerbtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-125 neg-margin-top-25 right"><?php et('lower.week.limit') ?></button>
  <?php endif ?>
  <br/>
  <?php if($setting != 'betmax-lim'): ?>
    <span> <?php et('reset.date') ?> </span><span> <?php echo phive()->lcDate($reset_date, '%x %R') ?> </span>
    <br/>
  <?php endif ?>
  <?php if(p('account.view')): ?>
    <span> <?php et('activated.on') ?> </span><span> <?php echo phive()->lcDate($activated_on, '%x %R')  ?> </span>
    <br/>
    <span> <?php et('cur.duration') ?> </span><span> <?php et($cur_dur)  ?> </span>
  <?php endif ?>
</div>

<?php endif ?>
<?php }

function accLimLeft(&$u, $str, $setting, $money, $limit, $active_date, $normal = true, $forced_limit = 0){
  $active_on_str = strtotime($active_date) <= time() ? 'activated.on' : 'active.on';
  $new_dur = $u->getSetting("{$setting}_newduration");
?>
<?php if ($normal) : ?>
<div class="account-sub-box acc-lim-left responsible-gambling-box">
  <div class="account-headline"><?php et("my.weekly.$str.limit") ?></div>
  <span class="account-small-headline"> <?php et('my.limit') ?> </span>
  <span id="display_<?php echo $setting ?>"> <?php echo $limit === false ? t('none') : ($money ? efEuro($limit, true) : $limit) ?> </span>
  <br/>
  <span class="account-small-headline"> <?php et($active_on_str) ?> </span><span> <?php echo phive()->lcDate($active_date, '%x %R') ?> </span>
  <br/>
  <?php if(!empty($new_dur) && p('account.view')): ?>
    <span class="account-small-headline"> <?php et('duration') ?> </span><span> <?php et($new_dur) ?> </span>
  <?php endif ?>
  <?php //if(empty($forced_limit)) : todo I don't hide it for now?>
  <button id="changebtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-125 neg-margin-top-40 right clearfix"><?php et('change.my.limit') ?></button>
  <br clear="all"/>
  <button id="removebtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-125 neg-margin-top-15 right clearfix"><?php et('remove.my.limit') ?></button>
  <?php //endif;?>
</div>
<?php else: ?>
<div class="acc-lim-left responsible-gambling-box">
  <h3><?php et("my.weekly.$str.limit") ?></h3>
  <span> <?php et('my.limit') ?> </span>
  <span id="display_<?php echo $setting ?>"> <?php echo $limit === false ? t('none') : ($money ? efEuro($limit, true) : $limit) ?> </span>
  <br/>
  <span> <?php et($active_on_str) ?> </span><span> <?php echo phive()->lcDate($active_date, '%x %R') ?> </span>
  <br/>
  <?php if(!empty($new_dur) && p('account.view')): ?>
    <span> <?php et('duration') ?> </span><span> <?php et($new_dur) ?> </span>
  <?php endif ?>
  <?php //if(empty($forced_limit)) : ?>
  <button id="changebtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-125 neg-margin-top-40 right clearfix"><?php et('change.my.limit') ?></button>
  <br clear="all"/>
  <button id="removebtn_<?php echo $setting ?>" class="btn btn-l btn-default-l w-125 neg-margin-top-15 right clearfix"><?php et('remove.my.limit') ?></button>
  <?php //endif;?>
</div>
<?php endif ?>
<?php }

function accLim($u, $remaining, $str, $setting, $money, $site_type, $normal = true){
  $limit       = $u->getSetting($setting);
  $cur_limit   = $u->getSetting("cur-{$setting}");
  $active_date = $u->getSetting("cur-$setting-update-date");
  $reset_date  = $u->getSetting("{$setting}_unlock");
  $forced_limit  = $u->getSetting("force-{$setting}");
?>
<?php if($site_type == 'normal'): ?>
<table class="account-table">
  <tr>
    <td class="account-table-td">
      <?php accLimLeft($u, $str, $setting, $money, $limit, $active_date, $normal, $forced_limit) ?>
    </td>
    <td class="account-table-td">
      <?php accLimRight($u, $cur_limit, $remaining, $str, $setting, $money, $reset_date, $normal, $forced_limit) ?>
    </td>
  </tr>
</table>
<?php else: ?>
<?php accLimLeft($u, $str, $setting, $money, $limit, $active_date, $normal, $forced_limit) ?>
<?php accLimRight($u, $cur_limit, $remaining, $str, $setting, $money, $reset_date, $normal, $forced_limit) ?>
<?php endif ?>
<?php
}

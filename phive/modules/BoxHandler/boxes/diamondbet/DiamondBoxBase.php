<?php

use Videoslots\User\TrophyBonus\TrophyBonusService;

require_once __DIR__.'/../../../../../diamondbet/html/display.php';
require_once __DIR__.'/../../BoxExtra.php';

class DiamondBoxBase extends BoxExtra{

    /**
     * Function to create tournament js variables
     * @return void
     */
    public function tournamentCommon()
    {
        ?>
        <?php
            if(!empty($this->tournament) || !empty($this->t)):
                if ($this->tournament) {
                    $currentTournament = $this->tournament;
                } elseif ($this->t) {
                    $currentTournament = $this->t;
                }
            ?>
            var curPlayTid = '<?php echo $currentTournament['id'] ?>';
            var mpEid = '<?php echo $this->t_eid ?>';
            var mpUserId = '<?php echo cuPlAttr('id')  ?>';
            var mpUrls = {
                my_info: '<?php echo phive('UserHandler')->wsUrl(phive('Tournament')->getMpInfoKey($currentTournament['id']), false) ?>',
                <?php if(phive('Tournament')->getSetting('fi-debug') === 'true'): ?>
                    extend: '<?php echo phive('UserHandler')->wsUrl('mpextendtest') ?>',
                <?php endif ?>
                limit: '<?php echo phive('UserHandler')->wsUrl('mplimit'.$currentTournament['id']) ?>',
                calculated: '<?php echo phive('UserHandler')->wsUrl('mpcalculated'.$currentTournament['id']) ?>',
                main: '<?php echo phive('UserHandler')->wsUrl('mp'.$currentTournament['id'], false) ?>'
            };
        <?php endif ?>

        var wsMpInterval = <?php echo intval(1000 / phive('Config')->getValue('websockets', 'mp-leaderboard-updates')) ?>;
        <?php
    }

  function comboInitCommon(){
    $this->bfull->setId( $this->getId() );
    $this->blist->setId( $this->getId() );

    if(!isset($_GET['arg0'])){
      $this->blist->init(0);
      $this->blist->full_news = $this->bfull->init( array_shift( $this->blist->news ) );
    }else{
      $this->blist->init(0);
      $this->blist->full_news = $this->bfull->init();
    }

    $this->handlePost(array('alink'));

    $this->loc = phive('Localizer');
  }

    function pOrSelfStop($key = 'user_id'){
        // We have an Ajax call
        if(!empty($_REQUEST[$key]) && empty($this->cur_user))
            $this->cur_user = cu((int)$_REQUEST[$key]);
        // If the person trying to view this is not the owner of the data or an admin we exit
        if(!pOrSelf('account.view', $this->cur_user))
            die('not allowed');
    }

  function printRaceAmount($sum, $race){
    if($race['race_type'] == 'spins')
      echo empty($sum) ? 0 : $sum;
    else
      efEuro(mc($sum));
  }

  function fmtRacePrize($p, $ajax = false){
    return is_numeric($p) ? ($ajax ? ciso().' '.(mc($p) / 100) : efEuro(mc($p), true)) : $p;
  }

  function towerTxt($txt, $class = 'tower-txt'){
    if(!phive('Localizer')->editing())
      $txt = implode(' ', str_split($txt));
    ?>
    <div class="<?php echo $class.'-outer' ?>">
      <div class="<?php echo $class.'-middle' ?>">
        <div class="<?php echo $class ?>">
          <?php echo $txt ?>
        </div>
      </div>
    </div>
    <?php
  }

  function setShow(){
    $uh = phive('UserHandler');
    if($this->showfor == 'in' && !isLogged())
      $this->show = false;
    else if($this->showfor == 'out' && isLogged())
      $this->show = false;
    else
      $this->show = true;
  }

  function printSettingsHTML(){

    ?>
    <div style="border: 1px solid black; border-top: none; padding: 2px; margin-bottom: 5px; background: white; color:black;">
      <?php $this->printCustomSettings(); ?>
    </div>
    <?php
  }

  function prErr($err, $start = 'register.err.'){
    if(!empty($err)){
      if(strpos($err, ' ') === false)
        echo t($start.$err);
      else
        echo $err;
    }
  }

  public function init(){
    if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId())
      $this->setAttribute("show_headline", $_POST['show_headline'] ?? '');
    $this->show_headline  = ($this->attributeIsSet("show_headline"))?$this->getAttribute("show_headline"):1;
  }

  function setTrTypes(){
    $this->show_types = array(4,5,6,9,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,29,31,32,34,35,38,44,45,46,47,48,52,53,54,61,62,63,64,65,66,67,69,72,77,80,84,85,86,94,95,96,101,103);
  }

  public function getHeadline($str = null){
    if(empty($str))
      return null;

    if($this->show_headline)
      return t($str);

    return null;
  }

  public function printBanner(){
    echo '';
  }

  public function hasBanner(){
    return false;
  }

  function printFields($arr){
    foreach($arr as $f){
      ?>
      <p>
        <?php echo ucfirst($f) ?>:
        <input type="text" name="<?php echo $f ?>" value="<?php echo $this->$f ?>"/>
      </p>
      <?php
    }
  }

  function printExtra(){}

  function printCustomSettings(){
    if($this->getAttribute('check_perm') == 1 && !p("box.".$this->getId()))
      return;
    ?>
    <form method="post" action="?editboxes#box_<?= $this->getId()?>">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <input type="hidden" name="box_id" value="<?=$this->getId()?>"/>
      <?php $this->printShowHeadline() ?>
      <?php $this->printExtra() ?>
      <input type="submit" name="save_settings" value="Save and close" id="save_settings"/>
    </form>
    <?php
  }

  function getTimeStatus($news){
    if($this->show_status == 0 || $news->getStartDate() == '0000-00-00' || $news->getEndDate() == '0000-00-00')
      return false;

    return $news->getTimeStatus();
  }

  public function printStatus($news, $print_bullet = true){
    $txt = empty($news->time_status) ? $news->getTimeStatus() : $news->time_status;
    if($txt){ ?>
      <?php if($print_bullet): ?> <p class="item">&#x25cf;</p> <?php endif; ?>
      <p class="item item_<?php echo $txt ?>"><?php echo t("newstop.$txt") ?></p>
    <?php }
  }

  function printShowHeadline(){?>
    <p>
      <label for="sub_box">Is sub box: </label>
      <select name="sub_box" id="sub_box">
        <option value="0" <?php if(empty($this->sub_box)) echo 'selected="selected"'; ?>>No</option>
        <option value="1" <?php if($this->sub_box) echo 'selected="selected"'; ?>>Yes</option>
      </select>
    </p>
    <p>
      <label for="check_perm">Check permission: </label>
      <select name="check_perm" id="check_perm">
        <option value="0" <?php if(empty($this->check_perm)) echo 'selected="selected"'; ?>>No</option>
        <option value="1" <?php if($this->check_perm) echo 'selected="selected"'; ?>>Yes</option>
      </select>
    </p>
  <?php }

  function jsRedirect($url){
    jsRedirect($url);
  }

  function baseInit(){
    $this->handlePost(array('sub_box', 'check_perm'));
  }

  function attrOrDefault($key, $default){
    return $this->attributeIsSet($key) ? $this->getAttribute($key) : $default;
  }

  function faqSearch($action = ''){ ?>
    <script type="text/javascript">
      jQuery(document).ready(function(){
        $("#search-field").focus(function(){ $(this).val(''); });
      });
    </script>
    <h3 class="header-3"><?php et("faq.subheading") ?></h3>
    <form id="faq-search-form" action="<?php echo $action ?>" method="post">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <table border="0" cellspacing="5" cellpadding="5">
        <tr>
          <td><?php dbInput('search-field', t('search'), 'text', 'faq-search') ?></td>
          <td><?php btnDefaultXl(t("go"), '', '$("#faq-search-form").submit();', 80) ?></td>
        </tr>
      </table>
    </form>
  <?php }

  function printHelpMenu(){ ?>
    <?php foreach(phive('Menuer')->forRender('customer-service') as $item): ?>
      <li>
        <a <?php echo $item['params']?>>
          <?php echo $item['txt']?>
          <img src="<?php fupUri("faqmenu-".$item['alias'].".png") ?>" />
        </a>
      </li>
    <?php endforeach ?>
  <?php }

  function fancyPlaycss(){ ?>
    <style type="text/css">
      #fancybox-outer { background:none repeat scroll 0 0 #000; }
      #fancybox-content { border:0 solid #000; }
    </style>
  <?php }

  function includes($carousel_skin = "videoslots"){
    loadJs("/phive/js/selectbox/js/jquery.selectbox-0.1.3.min.js");
    loadJs("/phive/js/jcarousel/lib/jquery.jcarousel.min.js");
    loadJs("/phive/modules/Micro/play_mode.js");
  }

  public function printSelStartEndDate($show_only = true){
  ?>
  <p>Show status (upcoming, current, old):</p>
  <p>
    <select name="show_status">
      <option value="1" <?php if($this->show_status == "1") echo 'selected="selected"'; ?>>Yes</option>
      <option value="0" <?php if($this->show_status == "0") echo 'selected="selected"'; ?>>No</option>
    </select>
  </p>
  <?php if($show_only): ?>
  <p>Show only:</p>
  <p>
    <select name="status_only">
      <option value="ALL" <?php if($this->status_only == "ALL") echo 'selected="selected"'; ?>>ALL</option>
      <option value="upcoming" <?php if($this->status_only == "upcoming") echo 'selected="selected"'; ?>>Upcoming</option>
      <option value="current" <?php if($this->status_only == "current") echo 'selected="selected"'; ?>>Current</option>
      <option value="old" <?php if($this->status_only == "old") echo 'selected="selected"'; ?>>Old</option>
    </select>
  </p>
  <?php endif; ?>
  <?php
  }

  function prTrDescr($t, bool $return = false){
    if($t["description"][0] == "#")
      $ttype = phive('Localizer')->getPotentialString($t["description"], null, true);
    $ttype = empty($ttype) ? t('transtype.'.$t['transactiontype']) : $ttype;

    if($return) {
        return $ttype;
    }

      echo $ttype;
  }

    /**
     * Create the "view more" button with the onclick viewMore JS function associated to it.
     *
     * @param $append_to - jQuery CSS selector for the element where we want to append the data.
     * @param $box_class - Phive class name
     * @param $box_function - above class function
     * @param int $initial_offset - how many items are loaded initially to keep the right offset
     */
    public function printViewMoreButton($append_to, $box_class, $box_function, $initial_offset = 3)
    {
        ?>
        <div id="view-more" class="view-more-mobile" onclick="viewMore('<?=$append_to?>', '<?=$box_class?>', '<?=$box_function?>')" data-offset="<?=$initial_offset?>">
            <?php et('view.more'); ?>
        </div>
        <?php
    }

    /**
     * Handle the limits on specific pages.
     *
     * @param $page
     * @return int
     */
    protected function getLimit($page)
    {
        switch ($page) {
            case 'accHistory':
                $limit = phive()->isMobile() ? 3 : 5;
                break;
            case 'rewardHistory':
                $limit = phive()->isMobile() ? 3 : 10;
                break;
            default:
                $limit = 0;
                break;
        }

        return $limit;
    }

  /**
   * Print the last X transactions HTML
   *
   * @param int[] $sizes - table col sizes
   * @param string $class - table container class
   */
  public function drawRecentAccHistory($sizes = array(25, 250, 200, 135, 25), $class = 'full-account-table')
  {
      // For the mobile website, we need to show 3 items, and a 'View more' button that will show the next 3 items
      $limit  = $this->getLimit('accHistory');
      $offset = 0;

    $transactions   = phive('Cashier')->getUserTransactions($this->cur_user, array_merge($this->show_types, array(3,8)), $limit, [], "timestamp", $offset);
    if(empty($transactions))
      return;
  ?>
  <div class="simple-box <?php echo $class ?>">
    <h3><?php echo t('recent.account.history.h3') ?></h3>
    <table class="zebra-tbl" id="recent_account_history">
      <?php foreach($sizes as $sz): ?>
        <col width="<?php echo $sz ?>"/>
      <?php endforeach ?>
        <?php if(phive()->isMobile()): ?>
        <tr>
            <th></th>
            <th><?=t('date')?> / <?=t('time')?></th>
            <th><?=t('amount')?></th>
            <th></th>
            <th></th>
        </tr>
        <?php endif; ?>
        <?php $this->printTransactionTableRows($transactions);?>
    </table>
    <?php
        if(phive()->isMobile()) {
            $this->printViewMoreButton('#recent_account_history tr:last', 'MobileTrophyListBox', 'getMoreTransactions', $limit);
        }
    ?>
    <?php if(count($transactions) > 5): // we are always returning the last 5 transactions, is this still link needed? /Paolo ?>
      <div style="position:relative; left:550px;">
        <a href="<?php echo phive('UserHandler')->getUserAccountUrl('account-history') ?>">
          <?php et('see.more') ?>
        </a>
      </div>
    <?php endif ?>
  </div>
    <?php
  }


    function printTransactionTableRows($transactions, $offset = 0)
    {
        for($i = 0; $i < count($transactions); $i++):
            $j = $i + $offset;
            $date = phive()->lcDate($transactions[$i]['timestamp']).' '.t('cur.timezone');
        ?>
        <tr class="<?php if(!phive()->isMobile()) { echo $j % 2 == 0 ? 'even' : 'odd'; } ?>">
          <td></td>
          <td><?php echo $date; ?></td>
          <td><?php echo cs().' '.rnfCents($transactions[$i]['amount'], ".", "") ?></td>
          <td><?php $this->prTrDescr($transactions[$i]) ?></td>
          <td></td>
        </tr>
         <?php
         endfor;
    }


    public function getMoreTransactions()
    {
        $offset = $_GET['offset'];
        $limit  = $this->getLimit('accHistory');

        $this->setTrTypes();
        $user = $this->cur_user !== cu() ? cu() : $this->cur_user;
        $transactions   = phive('Cashier')->getUserTransactions($user, array_merge($this->show_types, array(3,8)), $limit, [], "timestamp", $offset);

        $this->printTransactionTableRows($transactions, $offset);
    }


    function drawCurrentBalances($sizes = array(25, 450, 135, 25)){
        $balance_bonuses  = phive("Bonuses")->getUserBonuses($this->cur_user->getId(), '', " = 'active'", "IN('casino', 'freespin')");
        $balances         = phive('Casino')->balances($this->cur_user);
        $casino_balance   = $balances['bonus_balance'] + $balances['casino_wager'];
        $balance_on_last_login = lic('lastLoginBalance',[], $this->cur_user);
        $withdrawable_balance = lic('getBalanceAvailableForWithdrawal', [$this->cur_user], $this->cur_user);
        $balance_list = [
            [
                    'alias' => 'casino.last.login.balance.h3',
                    'amount' => isset($balance_on_last_login) ? $balance_on_last_login : false
            ],
            [
                    'alias' => 'casino.bonus.balance.h3',
                    'amount' => !empty($balance_bonuses['bonus_balance']) ? $balances['bonus_balance'] : false
            ],
            [
                    'alias' => 'pending.rewards.h3',
                    'amount' => !empty($casino_balance) ? $casino_balance : false,
            ],
            [
                    'alias' => 'casino.withdrawable.h3',
                    'amount' => $withdrawable_balance,
            ]
        ];
        $even = false;
  ?>
  <div class="simple-box padded-with-mleft">
    <h3><?php echo t('current.balances.h3') ?></h3>
    <table class="zebra-tbl margin-ten-bottom">
      <?php foreach($sizes as $sz): ?>
        <col width="<?php echo $sz ?>"/>
      <?php endforeach ?>
      <tr class="odd">
        <td></td>
        <td><?php echo t('casino.balance.h3') ?></td>
        <td style="text-align: right;"><?php echo cs().' '.(rnfCents($balances['cash_balance'], ".", "")) ?></td>
        <td></td>
      </tr>

      <?php foreach($balance_list as $balance): ?>
        <?php if ( $balance['amount'] === false) {continue;} else { $even = !$even;} ?>
        <tr class="<?= $even ? 'even' : 'odd' ?>">
        <td></td>
        <td><?php echo t($balance['alias']) ?></td>
        <td style="text-align: right;"><?php echo cs().' '.(rnfCents($balance['amount'], ".", "")) ?></td>
        <td></td>
      </tr>
      <? endforeach ?>

    </table>

  </div>
  <?php
  }

    function handleCancelPending()
    {
        if (!empty($_SESSION['cancelling_pending'])) {
            return false;
        }
        $_SESSION['cancelling_pending'] = true;
        $pid = (int)$_GET['id'];
        $p = phive('Cashier')->getPending($pid);
        if ($_GET['action'] == 'delete_pending' && $p['user_id'] == $_SESSION['mg_id']) {
            phive('Cashier')->disapprovePending($p, false);
            jsReloadBase();
        }
        if ($_GET['action'] == 'flush_pending' && ($p['user_id'] == $_SESSION['mg_id'] || p('flush.pending'))) {
            phive('Cashier')->flushPending($p);
            jsReloadBase();
        }
        $_SESSION['cancelling_pending'] = false;

    }


  function printTrTable($rows, $headline){
    if(empty($rows)) {
      return;
    }
    if($this->site_type == 'mobile') {
        $cols = isIpad() ? array(145, 105, 105, 105) : array(120, 80, 80, 80);
    }
    else {
        $cols = array(200, 160, 200, 100);
    }
    $rgl = licSetting('limits', $this->cur_user)['deposit'];
    $has_withdrawal_undo = $rgl['undo'] && rgLimits()->hasUndoWithdrawals($this->cur_user);
    if($has_withdrawal_undo) {
        $cbox = phive('BoxHandler')->getRawBox('DesktopDepositBox', true);
        $channel = phive()->isMobile() ? 'mobile' : 'desktop';
        $cbox->init($this->cur_user);
        $cbox->deposit_limit_skip_popup = true;
        loadJs("/phive/js/cashier.js");
        $cbox->setPspJson();
        phive('Casino')->setJsVars($channel == 'mobile' ? 'mobile' : 'normal');
        loadJs("/phive/js/jquery.validate.min.js");
        loadJs('/phive/js/jquery.json.js');
        $cbox->setCashierJs();
        ?>
        <script>var cashierOverrideReturnUrlType = 'account-history';</script>
        <?php
    }
    $is_admin = privileged();
  ?>
  <h3><?php et($headline) ?></h3>
  <table class="account-tbl">
    <tr>
      <td style="vertical-align: top;">
        <table class="zebra-tbl">
          <col width="<?php echo $cols[0] ?>"/>
          <col width="<?php echo $cols[1] ?>"/>
          <col width="<?php echo $cols[2] ?>"/>
          <col width="<?php echo $cols[3] ?>"/>
          <tr class="zebra-header">
            <td><?php et('trans.time') ?></td>
            <td><?php et('trans.status') ?></td>
            <td><?php et('trans.type') ?></td>
            <td><?php echo t('amount') ?></td>
          </tr>
          <?php $i = 0; foreach($rows as $t): ?>
          <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
            <td><?php echo phive()->lcDate($t['timestamp']).' '.t('cur.timezone') ?></td>
            <td>
              <?php $status = empty($t['undone']) ? $t['status'] : 'withdrawal.undone' ?>
              <?php et('transstatus.'.$status); ?>
              <?php if($t['status'] == 'pending' && empty($t['flushed']) && $t['stuck'] != CasinoCashier::STUCK_UNKNOWN): ?>
                <a href="?action=delete_pending&id=<?php echo $t['id']?>"><?php et('cancel') ?></a>
                <a href="?action=flush_pending&id=<?php echo $t['id']?>"><?php et('account.withdrawals.lock') ?></a>
              <?php endif ?>
            </td>
            <td style="cursor:default;" title="<?php echo $t['ext_id']; ?>">
            <?php
                $type = phive('Cashier')->getPaymentMethod($t['payment_method'], true);

                if ($type === 'Trustly') {
                    $formattedBankName = mb_convert_case(str_replace('_', ' ', $t['bank_name']), MB_CASE_TITLE, 'UTF-8');
                    echo $formattedBankName . ' - ' . $t['bank_account_number'];
                } else if(!empty($t['bank_name'])) {
                    echo htmlentities($t['bank_name']);
                } else if(!empty($t['iban']) || $type == 'Zimpler') {
                    echo 'BANK';
                } else if(in_array(strtolower($type), Mts::getInstance()->getCcSuppliers())){
                    echo empty($t['scheme']) ? t('ccard') : $t['scheme'];
                } else {
                  echo translateOrKey($type);
                }
            ?>
            </td>
            <td>
                <?php echo "-" . cs()." ".(rnfCents($t['amount'], ".", "")) ?>
                <?php if (licSetting('undo_withdrawals', $this->cur_user) === true): ?>
                    <?php if(empty($t['undone']) && $t['payment_method'] == 'zimpler' && $has_withdrawal_undo && $t['status'] === 'approved' && phive()->subtractTimes(phive()->hisNow(), $t['approved_at'], 'd') <= 60): ?>
                        <?php if ($is_admin): ?>
                            <a href="javascript:void(0);"><?php et('undo')?></a>
                        <?php else: ?>
                            <a href="javascript:void(0);" onclick="theCashier.postTransaction('deposit', '', '<?php echo $t['amount'] / 100 ?>', '<?php echo $t['payment_method'] ?>', 'undo', '<?php echo $t['id'] ?>')"><?php et('undo')?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
          </tr>
          <?php $i++; endforeach; ?>
        </table>
      </td>
    </tr>
  </table>
  <?php }

  function printBonusJs(){
    if(!empty($_GET['activate'])){
      phive('Bonuses')->activatePendingEntry($_GET['activate'], uid());
      jsTag("updateTopBalances(null, " . phive('Bonuses')->getBalanceByUser(uid()) / 100 . ")");
    }
  ?>
  <script>
   function failBonusConfirm(entry_id){
       var onClick = "goTo('"+jsGetBase()+"?action=deletebonusentry&id="+entry_id+"')";
       $("button[onclick = 'deletebonus']").attr('onclick', onClick);
       mboxMsg($("#fail-confirm").html(), false, undefined, undefined, undefined, undefined, "<?php et('msg.title') ?>");
   }

   function retryBonus(entry_id, module){
     mgAjax({eid: entry_id, action: 'retry-frb'}, function(res){
         mboxMsg(res, true, function(){ $("#retry-btn-"+entry_id).hide(); }, undefined, undefined, undefined, "<?php et('msg.title') ?>");
     });
   }

   /**
    * Method to show the "popup" on click to inform player that cannot forfit active on_going_game_session.
    *
    * @param entry_id
    */
   function cannotForfit(entry_id){
       var onClick = "goTo('"+jsGetBase()+"?action=deletebonusentry&id="+entry_id+"')";
       $("button[onclick = 'deletebonus']").attr('onclick', onClick);
       mboxMsg($("#on-going-game-session").html(), false, undefined, undefined, undefined, undefined, "<?php et('msg.title') ?>");
   }

  </script>
  <?php
  failBonusConfirm();
  cannotForfit();
  }

  function oddOrEven($start_with = 'odd', $return = false){
    if(empty($this->coe))
      $this->coe = $start_with;
    else
      $this->coe = $this->coe == 'odd' ? 'even' : 'odd';
    if ($return)
      return $this->coe;
    else
      echo $this->coe;
  }

  function printBonus(&$b, $cur_active = false, $return = false){
    $end_time = strtotime($b['end_time']);
    $now_time = time();

    if($b['bonus_type'] == 'freespin' && $b['status'] == 'pending' && $b['bonus_tag'] == 'microgaming')
      $b['status'] = 'active';

    if($b['status'] === 'active' && p('bonus.retry') && in_array($b['bonus_tag'], array('netent', 'bsg'))){
      if(phive($b['bonus_tag'])->frbStatus($b) === 'activate')
        $show_retry = true;
    }

    $bar_width = siteType() === 'normal' ? 350 : 150;

    //$progress = $b['cost'] == 0 ? 100 : number_format(min(100, ($b['progress'] / $b['cost']) * 100 ), 2);
    //$progress = number_format(min(100, ($b['progress'] / $b['cost']) * 100 ), 2);

    $progressData = phive("Bonuses")->getBonusProgressData($b, $bar_width, true);

    if(!empty($b['cash_cost']))
      $cash_progress = $b['cash_cost'] == 0 ? 100 : number_format(min(100, ($b['cash_progress'] / $b['cash_cost']) * 100 ), 4);
    $class = 'bonus_entry';
    $has_ongoing_session = phive("Casino")->checkPlayerIsPlayingAGame($this->cur_user->getId());
    $forfeit_bonus_button = phive("Bonuses")->getForfeitBonusFlag($b['bonus_id']);

    $params = array(
      'end_time'     => $end_time,
      'now_time'     => $now_time,
      'b'            => $b,
      'cur_active'   => $cur_active,
      'show_retry'   => $show_retry,
      'bar_width'    => $bar_width,
      'progress'     => $progressData['progress'],
      'progress_width' => $progressData['progress_width'],
      'cash_progress' => $cash_progress ?? false,
      'has_ongoing_session' => $has_ongoing_session,
      'forfeit_bonus_button' => $forfeit_bonus_button,
      'class'        => $class);

    if($return) {
        return $params;
    }

    $this->printBonusHTML($params);
  }

  function printBonusHTML($params) {
    extract($params);
    ?>
  <div class="<?php echo $class ?>">
    <div class="simple-box pad10 margin-ten-top left reward-detailed my-bonuses">
      <h3><?php ept( empty($b['bonus_name']) ? phive('Bonuses')->nameById($b['bonus_id']) : $b['bonus_name'] ) ?></h3>
      <div class="simple-frame left pad10 my-bonuses-bonuspic">
        <img src="<?php phive('Bonuses')->doPic($b) ?>" />
      </div>
      <table class="bonus_entry_table">
        <col width="320"/>
        <col width="320"/>
        <tr class="<?php $this->oddOrEven() ?>">
          <td><?php echo $b['status'] == 'completed' ? t('completed.date') : t('expire.date') ?>:</td>
          <td><?php echo phive()->lcDate($b['end_time'], '%x') ?></td>
        </tr>
        <tr class="<?php $this->oddOrEven() ?>">
          <td><?php echo t('days.left') ?>:</td>
          <td>
            <?php if($b['status'] == 'active'): ?>
              <?php echo abs( round( ($end_time - $now_time) / 86400, 0) ) ?>
            <?php else: ?>
              <?php echo t("bonus.status.{$b['status']}") ?>
            <?php endif ?>
          </td>
        </tr>

        <tr class="<?php $this->oddOrEven() ?>">
          <td><?php echo t('bonus.status') ?>:</td>
          <td>
            <?php echo t("bonus.status.{$b['status']}") ?>
          </td>
        </tr>

        <tr class="<?php $this->oddOrEven() ?>">
          <td><?php echo t('bonus.activation.time') ?>:</td>
          <td>
            <?php echo $b['activated_time'].' '.t('cur.timezone') ?>
          </td>
        </tr>
        <tr class="even">
          <td colspan="2">
            <div class="bonus-progress-holder">
              <div class="award-progressbar-bkg bonus-progressbar-bkg"></div>
              <div class="award-progressbar-bar gradient-trophy-bar bonus-progressbar-bar" style="width: <?php echo $params['progress_width'] ?>px;"></div>
              <div class="bonus-entry-progress-txt"><?php echo "{$params['progress']}";?></div>
              <?php if(!phive()->isMobile()): ?>
                  <div class="bonus-entry-btn-holder">
                    <?php if(phive('Bonuses')->canFail($b) &&
                             (p('account.removebonus') || $b['user_id'] == $this->cur_user->getId()) &&
                             in_array($b['status'], array('pending', 'completed', 'active')) ): ?>
                        <?php

                        if($params['forfeit_bonus_button']){
                          if ($params['has_ongoing_session']) {
                              btnCancelDefaultXs(t('forfeit'), '', "cannotForfit({$b['id']});", 100);
                          } else {
                              btnCancelDefaultXs(t('forfeit'), '', "failBonusConfirm({$b['id']});", 100);
                          }
                        }

                        ?>

                      <?php if($show_retry): ?>
                        <br clear="all" />
                        <br clear="all" />
                        <div id="retry-btn-<?php echo $b['id'] ?>">
                          <?php btnCancelDefaultXs('Retry', '', "retryBonus({$b['id']});", 100) ?>
                        </div>
                      <?php endif ?>
                    <?php endif ?>
                  </div>
        <?php endif ?>
            </div>
            <?php if($b['status'] == 'failed'): ?>
              <br clear="all"/>
            <?php endif ?>
          </td>
        </tr>
      <?php if(phive()->isMobile()): ?>
          <tr class="even">
              <td colspan="2">
                  <div class="bonus-entry-btn-holder">
                      <?php if(phive('Bonuses')->canFail($b) &&
                          (p('account.removebonus') || $b['user_id'] == $this->cur_user->getId()) &&
                          in_array($b['status'], array('pending', 'completed', 'active')) ): ?>
                          <?php
                          if($params['forfeit_bonus_button']){
                              $width = phive()->isMobile() ? 0 : 100;
                              if ($params['has_ongoing_session']) {
                                  btnCancelDefaultXs(t('forfeit'), '', "cannotForfit({$b['id']});", $width, 'bonus-forfeit-btn');
                              } else {
                                  btnCancelDefaultXs(t('forfeit'), '', "failBonusConfirm({$b['id']});", $width, 'bonus-forfeit-btn');
                              }
                          }
                          ?>

                          <?php if($show_retry): ?>
                              <br clear="all" />
                              <br clear="all" />
                              <div id="retry-btn-<?php echo $b['id'] ?>">
                                  <?php btnCancelDefaultXs('Retry', '', "retryBonus({$b['id']});", 100) ?>
                              </div>
                          <?php endif ?>
                      <?php endif ?>
                  </div>
              </td>
          </tr>
        <?php endif ?>
        <?php if(!empty($b['cash_cost'])): ?>
          <tr class="even">
            <td colspan="2">
              <div class="bonus_entry_progress">
                <div class="bonus_entry_bar" style="width:<?php echo $cash_progress ?>%">
                  &nbsp;
                </div>
                <div class="bonus_progress_text">
                  <?php et('cash_progress') ?>: <?php echo $cash_progress ?>%
                </div>
              </div>
            </td>
          </tr>
        <?php endif ?>
          <?php if($b['status'] == 'pending'): ?>
          <tr class="<?php echo !empty($b['cash_cost']) ? 'odd' : 'even' ?>">
            <td colspan="2">
              <a href="<?php echo llink('/welcome-bonus/') ?>"><?php et('terms-and-conditions')  ?></a>
              <br/>
              <br/>
              <?php btnDefaultM(t('bonus.accepttoc.activate'), "?activate=".$b['id'], '', 100) ?>
              <br clear="all"/>
              <br clear="all"/>
            </td>
          </tr>
        <?php endif ?>

      </table>
      <?php if($b['id'] == $_GET['activate'] && $GLOBALS['bonus_activation'] === false): ?>
        <div class="error"><?php et('bonus.activation.failed.html') ?></div>
      <?php endif ?>
    </div>
  </div>
  <?php if(!$cur_active): ?>
    <br clear="all">
  <?php endif ?>
  <?php
  }

    function handleDeleteBonusEntry($extras = '')
    {
        if($_GET['action'] == 'deletebonusentry') {
            $id = intval($_GET['id']);

            $service = new TrophyBonusService();
            $result = $service->forfeitBonus($id);

            if ($result !== TrophyBonusService::ERROR_NOT_AN_OWNER) {
                if ($result === null) {
                    echo "<script>window.location.href = '?deletedone=true$extras';</script>";
                } else {
                    echo "<script>window.location.href = '/account';</script>";
                }
            }
        }
  }

  function printTournamentJs(){
  ?>
      <?php if(phive()->getSetting('fi-debug') === true): ?>
         // This is raw testing that is only needed when implementing a new GP to test if they send certain events at all
         fiCalls = {
             freeSpinStarted: function(){
               console.log('freespins started');
             },
             bonusGameStarted: function(){
               console.log('bonus game started');
             },
             spinStarted: function(){
               console.log('spin started');
             },
             spinEnded: function(){
               console.log('spin ended');
             },
             gameRoundStarted: function(){
               console.log('game round started');
             },
             gameRoundEnded: function(){
               console.log('game round ended');
             },
             balanceChanged: function(){
             },
             bonusGameEnded: function(){
               console.log('bonus game ended');
             },
             freeSpinEnded: function(){
               console.log('freespin ended');
             }
         };
         setupFiEvents();
     <?php elseif(!empty($this->tournament)): ?>
           setupFiEvents();
           mpFiSetup(<?php echo phive('Tournament')->getSetting('fi-debug') ?>);

           var cDown = <?php $this->tl->getCdown() ?>;
           var cDownIntv = setInterval(function(){
             cDown.secs--;
             if(cDown.secs == -1){
               cDown.mins--;
               cDown.secs = 59;
             }

             if(cDown.mins == -1){
               cDown.hours--;
               cDown.mins = 59;
             }

             if(cDown.hours == -1){
               clearInterval(cDownIntv);
               return;
             }

               if(cDown.hours == 0 && cDown.mins == 0 && cDown.secs == 0){
                   canRebuy = false;
                   clearInterval(cDownIntv);
                   mpFinishedAjax(<?php echo $this->t_eid ?>, '<?php phive('Tournament')->finBkg() ?>');
                   return;
               }

             $('#tdown-sec').html(padNum(cDown.secs, 2));
             $('#tdown-min').html(padNum(cDown.mins, 2));
             $('#tdown-hours').html(padNum(cDown.hours, 2));
           }, 1000);

           <?php phive('Tournament')->startDescrJsCall($this->tournament['id']) ?>

           <?php
           if(cuPlSetting('mp-hiw-general-understood') != 'yes' && isLogged())
             echo "mpHiw('prGeneralMpInfo', 'mp-hiw-general');";
           ?>


     <?php endif ?>
  <?php }

  function printRaceJs($print_ws = false){
  ?>
  <script>
   function statusArrow(colour, asString){
     if(typeof asString === 'undefined'){
      return $('<img src="/diamondbet/images/<?= brandedCss() ?>game_page2/'+colour+'_arrow.png"/>');
     } else {
       return '<img src="/diamondbet/images/<?= brandedCss() ?>game_page2/'+colour+'_arrow.png"/>';
     }
   }

   function moveRaceUp(el, res, evType){
     if(typeof evType == 'undefined')
       evType = 'race';
     var spins = el.find('.race-amount');
     var rowAbove = el.prev();
     var spinsAbove = rowAbove.find('.race-amount');
     if(parseInt(spinsAbove.html()) < res.spins && !empty(spinsAbove.length)){
       var moveDown = {spins: spinsAbove.html(), spinsLeft: rowAbove.find('.race-left').html(), fname: rowAbove.find('.race-fname').html(), id: rowAbove.attr('id')};
       spinsAbove.html(res.spins);
       rowAbove.find('.race-fname').html(el.find('.race-fname').html());
       rowAbove.find('.race-left').html(el.find('.race-left').html());
       rowAbove.find('.race-arrow').html(statusArrow('green'));
       rowAbove.attr('id', el.attr('id'));

       spins.html(moveDown.spins);
       el.find('.race-fname').html(moveDown.fname);
       el.find('.race-left').html(moveDown.spinsLeft);
       el.find('.race-arrow').html(statusArrow('red'));
       el.attr('id', moveDown.id);
       if(evType == 'race'){
         updateMyRacePos(rowAbove);
         updateMyRacePos(el);
       }
       moveRaceUp(rowAbove, res, evType);
     }else{
       spins.html(res.spins);
       if(evType == 'race')
         updateMySpins(el);
     }

       //if(evType == 'mp')
       //    updateMpTop(el);
   }

   /*
     function updateMpTop(el){
       var curUid = $('#cur-mp-top').attr('uid');
       var uid = el.attr('id').split('-').pop();
       if(curUid == uid){
         var spins = el.find('.race-left').html();
         var pos = el.find('.race-position').html();
         var score = el.find('.race-amount').html();
         $("#cur-mp-spins-"+uid).html(spins);
         $("#cur-mp-position-"+uid).html(pos);
         $("#cur-mp-score-"+uid).html(score);
       }else{
         var pos = $("#cur-mp-position-"+curUid).html();
         $("#cur-mp-position-"+curUid).html(pos);
       }

     }
   */

   function updateMyRacePos(el){
     if(empty(el.attr('id')))
       return;
     var uid = el.attr('id').split('-').pop();
     var curUid = $('#race-info').attr('uid');
     if(uid != curUid)
       return;
     $("#race-info").show();
     $("#cur-race-balance-"+uid).html(el.find('.race-amount').html());
     $("#cur-race-prize-"+uid).html(el.find('.race-prize').html());
     $("#cur-race-position-"+uid).html(el.find('.race-position').html());
     $("#cur-race-arrow-"+uid).html(el.find('.race-arrow').html());
   }

   function updateMySpins(el, spins){
     if(typeof el === 'object'){
       var uid = el.attr('id').split('-').pop();
       var spins = el.find('.race-amount').html();
     }else{
       var uid = el;
     }
     $("#cur-race-balance-"+uid).html(spins);
   }

   var raceWsLoaded = false;
   var raceWsHandle = null;
   var trophyWsLoaded = false;
   var trophyWsHandle = null;

   function cleanUpRaceTab(){

       try {
        if (raceWsHandle) {
            raceWsHandle.close();
            raceWsLoaded = false;
        } else {
            console.warn('cleanUpRaceTab: raceWsHandle is null or undefined');
        }

        if (wsQIntvs['racetab']) {
            clearInterval(wsQIntvs['racetab']);
        } else {
            console.warn('cleanUpRaceTab: wsQIntvs["racetab"] is undefined');
        }

        wsQd['racetab'] = [];
    } catch (error) {
        console.error('Error in cleanUpRaceTab:', error);
    }
   }

   function raceWs(page){
     if(hasWs() && !raceWsLoaded){
       <?php if((int)cuSetting('realtime_updates') !== 1): ?>
         return;
       <?php endif ?>
       raceWsLoaded = true;

         var wsInterval = <?php echo intval(1000 / phive('Config')->getValue('websockets', 'leaderboard-updates')) ?>;
         wsQf('racetab', wsInterval, function(res){
             if(res.user_id == 'msg'){
                 if(res.spins == 'race.closed'){
                     if(page == 'play')
                         getRace(page);
                     else
                         jsReloadBase();
                 }
             }

             var el = $("#raceuser-"+res.user_id);
             if(el.length > 0){
                 moveRaceUp(el, res);
             }else{
                 var lastSpins = $('#race-tab-table tr').last().find('.race-amount');
                 // Do we have a result from a player outside the leaderboard?
                 if(parseInt(lastSpins.html()) < res.spins){
                     // We get the current prizes and positions, they will be used to correctly display positions and prizes for each position on the leaderboard after it has been changed.
                     var prizes = $('#race-tab-table .race-prize');
                     var positions = $('#race-tab-table .race-position');
                     var inserted = false;
                     // We get all the leaderboard rows
                     var trs = $('#race-tab-table tr');
                     // We ignore the headlines
                     trs.shift();
                     // We loop the leaderboard
                     trs.each(function(){
                         var r = $(this);
                         var amount = parseInt(r.find('.race-amount').html());
                         // Is the amount of spins on the current entry lower than the newcomer?
                         if(!isNaN(amount) && amount < res.spins){
                             // Have we not inserted the newcomer yet?
                             if(!inserted){
                                 inserted = true;
                                 // We clone the current row in the loop
                                 var nr = r.clone();
                                 // We change the id to the user id of the newcomer.
                                 nr.attr('id', 'raceuser-' + res.user_id);
                                 // If the newcomer is the currently logged in player we set the username to red.
                                 var cls = res.user_id == userId ? 'red' : '';
                                 // We set the name of the newcomer
                                 nr.find('.race-fname').html('<span class="'+cls+'">'+res.fname+'</span>');
                                 // We set the amount of spins of the newcomer.
                                 nr.find('.race-amount').html(res.spins);
                                 // We set the arrow to a green arrow as the newcomer has per definition overtaken people.
                                 nr.find('.race-arrow').html(statusArrow('green'));
                                 // We insert the new row before the current row.
                                 r.before(nr);
                                 // We update the race position at the top.
                                 updateMyRacePos(nr);
                             }
                             // We update the race position at the top.
                             updateMyRacePos(r);
                             // Current row has been overtaken so we put a red arrow.
                             r.find('.race-arrow').html(statusArrow('red'));
                         }
                     });

                     var trs = $('#race-tab-table tr');
                     trs.shift();
                     // We loop all rows again, a second pass.
                     trs.each(function(i){
                         var r = $(this);
                         // Is it outside of the leaderboard? If so we remove it.
                         if(typeof positions[i] == 'undefined')
                             r.remove();
                         else{
                             // We set the correct zebra class.
                             r.attr('class', i % 2 == 0 ? 'even' : 'odd');
                             // We set the correct new position for the row.
                             r.find('.race-position').html($(positions[i]).html());
                             // We set the correct new prize for the row.
                             r.find('.race-prize').html($(prizes[i]).html());
                         }
                     });
                     // New person on the leaderboard end logic
                 }else{
                     updateMySpins(res.user_id, res.spins);
                 }

             }
         });


       raceWsHandle = doWs('<?php echo phive('UserHandler')->wsUrl('racetab', false) ?>', function(e) {
           var res = JSON.parse(e.data);
           wsQ('racetab', res);
       });
     }
   }

    var trophyCount = 0;
    var trophyItems = [];
    var trophiesFromWs = false;

    var htmls = [];
    var trophyIntv;

    function drawTrophyFeed() {
        htmls = [];
        //TODO rewrite this with underscore js _.each()
        for (var i = 0; i <= trophyItems.length - 1; i++) {
            var html = trophyFeedItemTpl(trophyItems[i]);
            htmls.push(html);
        }
        $('#trophy-nano > ul').html(htmls.join(' '));
    }

    function updateTrophyItemList() {
        if (trophyItems.length && doUpdTrophies && !isAnimating) {
            if(doSorting) {
                // sort the trophies on progress_for_sorting
                trophyItems = _.sortBy(trophyItems, 'progress_for_sorting');
                trophyItems.reverse();
            }

            drawTrophyFeed();
            doUpdTrophies = false;
        }
    }

    // @todo: Low priority:
    // Right now, when playing a game for the first time, we do not see any
    // trophies of type = win UNTILL we win for the first time. This is of course
    // because those trophies will be added to table trophy_events when the Trophy::onWin() function is triggered.
    // The best way to solve this issue would be to add all trophies to trophy_events during the registration process.
    var lastRes;
    var doUpdTrophies = true;
    var isAnimating = false;
    var doSorting = false;
    function trophyWs(page){
        var userId = parseInt('<?php echo $_SESSION['mg_id'] ?>');
        if(hasWs() && !trophyWsLoaded){
            trophyWsLoaded = true;
            //TODO this needs logic to include the game id / ref in the ws tag, otherwise we run into problems with play in two different tabs
            // BUT some trophies do not belong to a specific game, they need to be updated in all tabs, just like now
            trophyWsHandle = doWs('<?php echo phive('UserHandler')->wsUrl('trophytab') ?>', function(e) {
                var res = JSON.parse(e.data);
                setTimeout(function(){

                    doUpdTrophies = true;
                    doSorting = true;

                    var el = $('#' + res.teid + '-info');
                    if (!trophiesFromWs) {
                        trophiesFromWs = true;
                        trophyIntv = setInterval(function() { updateTrophyItemList(); }, 1000);
                    }
                    if (typeof res != 'undefined' && res.threshold > 0) {

                        inarr = trophyItems.filter(
                            function(obj) {
                                return obj.alias == res.alias;
                            }
                        );

                        lastRes = res;
                        if (inarr.length) {
                             i = 0;
                             // update the progress percent in the global array
                             //TODO rewrite with underscore js?
                             for(i = 0; i < trophyItems.length; i++) {
                                 if(trophyItems[i].alias == res.alias) {
                                     trophyItems[i].progress_percent = res.progress_percent;
                                     trophyItems[i].progress_for_sorting = res.progress_for_sorting;
                                     trophyItems[i].progr = res.progress;
                                     trophyItems[i].progress = res.progress;
                                     trophyItems[i].threshold = res.threshold;
                                     break;
                                 }
                             }
                        } else {
                            trophyItems.push(res);
                        }
                    }

                    // remove trophy from array if the progress is 100%
                    trophyItems = _.reject(trophyItems, function(obj){
                        return obj.progress_percent == 100;
                    });

                    // update progress bar
                    // this is here to update the progress in real time,
                    // and not only after the trophy tab gets drawn again
                    var bar = $(el).find('.trophy-progressbar-bar');
                    bar.css('width', res.progress_percent + '%');
                    var absProgr = $(el).find('.progress-absolute');
                    absProgr.html(res.progress + ' / ' + res.threshold);

                    if (res.progress_percent == 100) {
                        // fade out and move other trophies up
                        isAnimating = true;
                        var ul = $('#trophy-nano > ul');
                        $(el).fadeTo(1500, 0,function(el){
                            $(this).css('height', "90px");
                            $(this).empty();
                            $(this).animate({'height': 0}, 1000, function() {
                                isAnimating = false;
                            });
                        });
                        return;
                    }
                }, 5000);
            });
        }
    }

    function getTrophies(page, game_id){
        doUpdTrophies = true;
        doSorting = false;
        clearInterval(afIntv);
        $("#activity-feed").html('');
        var url = '/phive/modules/DBUserHandler/xhr/trophy_actions_xhr.php?game_id=' + game_id + '&lang=' + cur_lang;
        $.post(url, function(data) {
            $("#activity-feed").html('<div id="trophy-nano" class="nano"><ul class="nano-content pad-stuff-five"></ul></div>');
            trophyItems = JSON.parse(data);
            updateTrophyItemList();
            $("#trophy-nano").height('100%');
            $("#trophy-nano").nanoScroller();
            trophyWs(page);
        });
    }

    function getRace(page){
        clearInterval(afIntv);
        $("#activity-feed").html('');
        ajaxGetBoxHtml({func: 'playPageRace'}, cur_lang, 'CasinoRaceBox2', function(ret){
            $("#activity-feed").html(ret);
            $("#race-nano").height($('#play-box').height()  - 140);
            $("#race-nano").nanoScroller();
            raceWs(page);
        });
    }

    function getWheelJackpotWinners(){
        $("#activity-feed").html('');
        ajaxGetBoxHtml({func: 'printWheelInformationContent'}, cur_lang, 'WheelBox', function(ret){
          $("#activity-feed").html(ret);
            $("#jackpotwinners").height( $("#activity-feed").height() - $("#jackpotvalues").height() - 100 );
            $("#jackpotwinners").nanoScroller();
        });
    }

    <?php if($print_ws): ?>
    $(document).ready(function(){
      raceWs('normal');
    });
    <?php endif ?>


  </script>
  <?php
  }

  function getGameCommunicator()
  {
    ?>
    <script type="text/javascript">
    /**
    *  Communicates with the game via postMessages sended to the iframe
    *  Processes the messages sended by each provider
    */
    var GameCommunicator = (function(){
        var iframeSelector = 'mbox-iframe-play-box',
            messageProcessor = null;

        return {
          init: function (source, origin ,defaultMessageProcessor, messageProcessor, game) {

            var messageEventListener = this.processReceivedMessage.bind(this);
            self.iframeSelector = 'mbox-iframe-play-box';
            self.messageProcessor = (typeof messageProcessor !== undefined) ? $.extend(defaultMessageProcessor, messageProcessor) : defaultMessageProcessor;

            self.messageProcessor.request = this.request;
            if (typeof MessageProcessor !== "undefined") {
                MessageProcessor.request = this.request;
            }

            // For some games the context is different and
            // the MessageProcessor object doesn't have the resumeGame function
            if (MessageProcessor && !MessageProcessor.resumeGame && typeof self.messageProcessor.resumeGame === 'function') {
                MessageProcessor.resumeGame = self.messageProcessor.resumeGame;
            }

            self.messageProcessor.game = game;
            // If this is BoS we have to give the tournaments object a way to communicating with the game
            if (typeof fiCalls !== 'undefined') {
                gameFi = {
                    "toFrame": self.messageProcessor.bosReloadBalance.bind(this)
                };
            }

            window.addEventListener("message", messageEventListener);
            self.messageProcessor.startGameCommunicator(this.request);  // subscribe to window envents
          },

          request: function (req) {
            if (self.messageProcessor.test === true)
              console.log("GameCommunicator sent the following message:", req);
            // we need to get the iframe source always as we don't control redirections inside the iframe
            document.getElementById(self.iframeSelector).contentWindow.postMessage(req, '*')
          },

          processReceivedMessage: function (message) {
            var type = self.messageProcessor.getEventType(message);
            if (self.messageProcessor.test === true)
              console.log("GameCommunicator received the following event:", message);

            self.messageProcessor.process(message);

            // Execute Battle of slots if there's a mapping for it
            if (typeof self.messageProcessor.bosMapping != 'undefined' && self.messageProcessor.bosMapping.hasOwnProperty(type)){
                var func = self.messageProcessor.bosMapping[type];
                if (typeof fiCalls !== 'undefined' && typeof fiCalls[func] === 'function') fiCalls[func].call();
            }
          },

          backToLobbyCallback: function (event) {
            window.location.href = '<?= phive('Casino')->getLobbyUrl(false, $_REQUEST["lang"]) ?>';
          },

          backToCashierCallback: function (event) {
            goTo('/cashier/deposit/');
          },

          backToHistoryCallback: function (event) {
            goTo('<?= phive('UserHandler')->getUserAccountUrl('game-history') ?>');
          },

          redirectCallback: function (url) {
            goTo(url);
          },
          pauseGame: function() {
              return new Promise(function(resolve, reject) {
                  if (self && self.messageProcessor && typeof self.messageProcessor !== 'undefined' && typeof self.messageProcessor.pauseGame === "function") {
                      self.messageProcessor.pauseGame().then(function() {
                          this.waitRoundFinished(resolve);
                      }.bind(this))
                  } else {
                      resolve();
                  }
              }.bind(this));

          },
            // TODO refactor all logic using pause/resume to remove ES6
            resumeGame: function() {
              return new Promise(function(resolve, reject) {
                  if (self && self.messageProcessor && typeof self.messageProcessor !== 'undefined' && typeof self.messageProcessor.resumeGame === "function") {
                      self.messageProcessor.resumeGame().then(resolve);
                  } else {
                      resolve();
                  }
              }.bind(this));
            },
            waitRoundFinished: function (callback) {
                if (self && self.messageProcessor && typeof self.messageProcessor !== 'undefined' && typeof self.messageProcessor.waitRoundFinished === "function") {
                    self.messageProcessor.waitRoundFinished(function () {
                        callback()
                    });
                } else {
                    callback();
                }
            }
        };
      })();
    </script>
    <?php
  }

  function getGameLoader()
  {
    ?>
    <script type="text/javascript">
    /**
    *   GameLoader loads the game in the iframe in differen ways
    */

    var launched = false;
    var GameLoader = (function(){
      var gameLauncher = null;

      return {
        launchTypes: null,

        init(gameLauncher, iframe, gameUrl) {
          self.gameLauncher = gameLauncher;
          this.loadGame(iframe, gameUrl);
        },

              setLaunchTypes: function() {
                  this.launchTypes = {
                      'src': this.setSrcProperty,
                      'srcDoc': this.setSrcDocProperty,
                      'innerHtml': this.setInnerHtml
                  };
              },
              /**
                  Launch game in the iframe in different ways different ways
              */
              launchGame: function (iframe, params) {
                  const launchType = params.launchType ? params.launchType : 'src';
                  this.setLaunchTypes();
                  this.launchTypes[launchType](iframe, params);
              },

              /*  Game Launch Type 1 */
              setSrcProperty: function (iframe, params) {
                  iframe.src = params.url;
                  if (params.callback) params.callback();
              },

        /*  Game Launch Type 2 */
        setSrcDocProperty: function (iframe, params) {
          // 1. Send a post with the parameters to the game entry point
          $.post(params.url, params.postParameters, function(data){
            // 2. Set the iframe srcdoc property to the response body (the generated HTML page)
            console.log("post response:",data);
            iframe.srcdoc =  data;
            if (params.callback) params.callback();
          });
        },

        /* Game Launch Type 3*/
        setInnerHtml: function (iframe, params) {
          if (params.requestType && params.requestType === 'GET') {
            // 1. Send a post or a get to the game entry point
            $.get(params.url, function(data){
              // 2. Set the iframe srcdoc property to the response body (the generated HTML page)
              console.log("post response:",data);
              iframe.innerHtml =  data;
              if (params.callback) params.callback();
            });
          } else {
            // 1. Send a post or a get to the game entry point
            $.post(params.url, params.postParameters, function(data){
              // 2. Set the iframe srcdoc property to the response body (the generated HTML page)
              console.log("post response:",data);
              iframe.innerHtml =  data;
              if (params.callback) params.callback();
            });
          }
        },

              loadGame: function(iframe, url) {
            if(launched) return;
            launched = true;
                  self.gameLauncher.loadGame(iframe, url); // we have access to the iframe
              },
          };
      })();
    </script>
    <?php
  }

 /**
  *  Default implementation of message processor methods
 */
  public function getDefaultMessageProcessor()
  {
    $basePath = __DIR__.'/../../../../../phive/modules/Micro/js/GameCommunicator/';
    return include($basePath . 'DefaultGameCommunicator.php');
  }

  /**
  * Game Providers Javascript specific logic for launching games
  * @override Overrides the methods on the default message processor implementation
  */
  function getMessageProcessor($networkName)
  {
    // Check if network message processor file exist
    $basePath = __DIR__.'/../../../../../phive/modules/Micro/js/GameCommunicator/';

    $fileName = $basePath . $networkName . 'GameCommunicator.php';

    if (file_exists($fileName)) {
      return include($fileName);
    }
    ?>
    <script type="text/javascript"> var MessageProcessor = undefined; </script>
    <?php
  }

}


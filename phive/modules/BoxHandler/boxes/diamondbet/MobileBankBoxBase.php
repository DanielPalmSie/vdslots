<?php
require_once __DIR__.'/WithdrawDepositBoxBase.php';
//TODO is this box even used?
class MobileBankBoxBase extends WithdrawDepositBoxBase{
    function init(){
        exit;
        
    parent::init();
    
    $this->handlePost(array('action'), array('action' => 'deposit'));
    
    $user = cu();

    if($this->action == 'deposit' && phive()->getSetting('lga_reality') !== true) {
        list($res,) = phive("Cashier")->checkOverLimits($user);
        $this->over_limit = $res;
    }

    
  }
  
  
  function printHeader(){ ?>
<h1><?php et("{$this->action}.start.headline.{$this->getId()}") ?></h1>
<p>
  <?php et("{$this->action}.start.{$this->getId()}.html") ?>
</p>
<br>
<?php }

public function printExtra(){ ?>
<?php parent::printExtra() ?>
<p>
  Withdraw or Deposit (deposit/withdraw):
  <?php dbInput('action', $this->action) ?>
</p>
<?php }
}

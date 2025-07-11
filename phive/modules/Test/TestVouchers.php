<?php
class TestVouchers extends TestPhive{

    /*
    function setInfo($type){
        $this->info = array(
            'countries' => "se bg th jp",
        );
        
        $this->handlers = array(
            'countries' 	=> function($obj, $excl, $incl){
	        $res = "";
	        $obj->reset();
	        $res .= "\n\nEmpty exl and incl: " . phive("Vouchers")->redeem($obj->uid, 'test', 'test');
	        $obj->reset($excl, $incl);
	        $res .= "\n\nExl $excl and incl $incl: " . phive("Vouchers")->redeem($obj->uid, 'test', 'test');
	        $obj->reset('', $incl);
	        $res .= "\n\nEmpty exl and incl $incl: " . phive("Vouchers")->redeem($obj->uid, 'test', 'test');
	        $obj->reset($excl, '');
	        $res .= "\n\nExl $excl and empty incl: " . phive("Vouchers")->redeem($obj->uid, 'test', 'test');
	        return $res;
            });
    }
    */
    
    
    function reset($excl_countries = "bg th jp", $incl_countries = ""){
        phive("SQL")->truncate('vouchers', 'bonus_types', 'bonus_entries');
        $str = "INSERT INTO `bonus_types` (
		    `id` ,`expire_time` ,`num_days` ,`cost` ,`reward` ,`bonus_name` ,`deposit_limit` ,
		    `rake_percent` ,`bonus_code` ,`deposit_multiplier` ,`bonus_type` ,`exclusive` ,`bonus_tag` ,
		    `type` ,`game_tags` ,`cash_percentage` ,`max_payout` ,`reload_code` ,`excluded_countries` ,
		    `deposit_amount` ,`deposit_max_bet_percent` ,`bonus_max_bet_percent` ,`max_bet_amount` ,`included_countries`)
		    VALUES (
		    '1', '2100-08-05', '1', '5000', '100', 'Thunderstruck 2', '0', '0', '', '0', 'casino', '0', '', 'casino', 
		    'mgs_thnderstrck2', '0', '10000', '', '$excl_countries', '0', '0', '0', '0', '$incl_countries')";
        
        phive("SQL")->shs('', '', null, 'bonus_types')->query($str);
        phive("SQL")->insertArray('vouchers', array(
            'voucher_name' 	=> 'test', 
            'voucher_code' 	=> 'test', 
            'bonus_id' 		=> $this->bid));
    }
    
    function setup($username, $bid = 1){
        $u = cu($username);
        $this->uid 		= uid($u);
        $this->bid 		= $bid;
    }

    function testRedeem(){
        echo phive('Vouchers')->redeem($this->uid, 'test', 'test');
    }
    
}

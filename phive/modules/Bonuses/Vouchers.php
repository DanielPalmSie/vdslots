<?php
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class Vouchers extends PhModule {
    
    function createSeries($stub, $count, $bonus_id, $affe_id, $pwd = '', $exclusive = 1, $award_id = 0){
        for($i = 1; $i < $count; $i++){
            $insert = array(
	        'voucher_name' 	=> $stub,
	        'bonus_id' 	=> $bonus_id,
	        'award_id' 	=> $award_id, 
	        'affe_id'	=> $affe_id,
	        'exclusive'	=> empty($exclusive) ? 1 : $exclusive,
	        'voucher_code' 	=> empty($pwd) ? phive()->randCode(8) : $pwd
            );
            
            phive('SQL')->insertArray('vouchers', $insert);	
        }
    }

    // TODO henrik remove this
    function archive(){
        $sql 		= phive("SQL");
        $archive 	= $sql->doDb('archive');
        $sql->updateTblSchema('vouchers');
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        $vs 		= phive("SQL")->loadArray("SELECT * FROM vouchers WHERE bonus_id NOT IN(SELECT id FROM bonus_types WHERE brand_id = {$brandId})");
        foreach($vs as $v){
            if($archive->insertArray('vouchers', $v))
	        $sql->query("DELETE FROM vouchers WHERE id = {$v['id']}");
        }
    }
    
    function insertTemplate($tag, $name, $v_extra = ''){
        $template = phive("Config")->getValue($tag, $name);
        
        if(empty($template))
            return false;

        foreach(explode("\n", $template) as $line){
            list($field, $value) = explode("::", $line);
            if(!empty($field))
	        $v[trim($field)] = rep(trim($value));
        }
        
        if(empty($v['bonus_id']) && empty($v['award_id']))
            return false;

        if(!empty($v['bonus_id'])){
            $config = phive("Config")->getConfig($v['bonus_id']);
            $bonus = phive("Bonuses")->insertTemplate($config['config_tag'], $config['config_name']);
            if(empty($bonus))
                return array();
        }
        
        $vname = $v['voucher_name'].$v_extra;
        $vcode = $v['voucher_code'].$v_extra;
        
        $this->createSeries($vname, $v['count'], $bonus['id'], $v['affe_id'], $vcode, $v['exclusive'], $v['award_id']);
        
        return array($vname, $vcode, $bonus['id'], $v['count'], $v['award_id']);
    }
    
    function userRedeemed($uid){
        return phive("SQL")->sh($uid, '', 'vouchers')->loadArray("SELECT * FROM vouchers WHERE user_id = $uid AND redeemed = 1");
    }
    
    function asCsv($name){
        $p = new ParseCsv\Csv();
        $vouchers = $this->getVouchersByName($name);
        return $p->output(false, 'vouchers.csv', $vouchers, array_keys($vouchers[0]));
    }
    
    function getVouchersByName($name){
        return phive('SQL')->loadArray("SELECT * FROM vouchers WHERE voucher_name LIKE '$name%'");
    }
    
    function getVoucherByNameCode($name, $code, $user_id = ''){
        $where_user = empty($user_id) ? '' : "AND user_id = $user_id";
        return phive('SQL')->loadAssoc("SELECT * FROM vouchers WHERE voucher_name = '$name' AND voucher_code = '$code' $where_user");
    }
    
    function getUnredeemedVoucherByNameCode($name, $code){
        return phive('SQL')->loadAssoc("SELECT * FROM vouchers WHERE voucher_name = '$name' AND voucher_code = '$code' AND redeemed = 0");
    }
    
    function voucherExists($name, $code){
        $res = phive('SQL')->loadAssoc("SELECT * FROM vouchers WHERE voucher_name = '$name' AND voucher_code = '$code'");
        return !empty($res);
    }
    
    function redeemVoucher($voucher, $user_id){
        $voucher['redeemed'] = 1;
        $voucher['user_id'] = $user_id;
        unset($voucher['redeem_stamp']);
        //Sharding we save to the shard when redeemed
        phive('SQL')->sh($user_id, '', 'vouchers')->save('vouchers', $voucher);
        return phive('SQL')->updateArray('vouchers', $voucher, array('id' => $voucher['id']));
    }
    
    function updateVoucher($voucher, $user){
        $user = cu($user);
        return phive('SQL')->sh($user, 'id', 'vouchers')->save('vouchers', $voucher);
    }

    function canRedeem($user_id, $name, $code){
        if($this->redeemed($user_id, $name, $code))
            return 'redeemed';
        $voucher = $this->getVoucherByNameCode($name, $code);
        if($voucher['exclusive'] == 4){
            $mail_action = phive('UserHandler')->getActionByTagUid('voucher', $user_id, $code);
            if(empty($mail_action))
                return 'no-email';
        }
        $entries = phive('Bonuses')->hasActiveExclusives($user_id);
        if(!empty($entries))
            return 'bonus-entries';
        return true;
    }
    
    function redeemed($user_id, $name, $code){
        $user_id = intval($user_id);
        $str = "SELECT * FROM vouchers WHERE voucher_name = '$name' AND voucher_code = '$code' AND exclusive IN(1,4) AND user_id = $user_id";
        $voucher = phive('SQL')->loadAssoc($str);
        return empty($voucher) ? false : true;
    }
    
    function getVoucherSeries(){
        return phive('SQL')->loadArray("SELECT * FROM vouchers GROUP BY voucher_name ORDER BY redeem_stamp DESC");
    }
    
    function getRedeemers($vname){
        //Sharding we fetch from all shards, the inner join works
        return phive('SQL')->shs('merge', '', null, 'vouchers')->loadArray("SELECT * FROM  vouchers v WHERE v.redeemed IN(1, 2) AND v.voucher_name = '$vname' ");
    }
    
    function getBonusEntryFromVoucher($v){
        return phive('SQL')->loadAssoc("SELECT * FROM bonus_entries WHERE bonus_id = {$v['bonus_id']} AND user_id = {$v['user_id']}");
    }
    
    function getBonusBalanceFromVoucher($v){
        $entry = $this->getBonusEntryFromVoucher($v);
        return $entry['balance'];
    }
    
    function redeem($user_id, $name, $code){

        $ud = ud($user_id);
        if(empty($ud))
            return false;//early return if user does not exist
        
        $res = true;
        
        if(empty($name) || empty($code))
            return 'voucher.empty.code.or.name';

        $can_redeem = $this->canRedeem($user_id, $name, $code);
        $msg_map    = ['redeemed' => 'voucher.already.redeemed', 'no-email' => 'no.voucher.email', 'bonus-entries' => 'has.active.exclusives'];
        $err_msg    = $msg_map[$can_redeem];
        if(!empty($err_msg))
            return $err_msg;
        //TODO check that this person got the email
        
        //if($this->redeemed($user_id, $name, $code))
        //return 'voucher.already.redeemed';
        
        if(!$this->voucherExists($name, $code))
            return $this->redeemV2($user_id, $name, $code); // Check the new table for voucher

        $voucher = $this->getUnredeemedVoucherByNameCode($name, $code);

        // TODO is this even used these days? Remove if not.
        if(!empty($voucher['affe_id'])){
            $afh = phive('Affiliater');
            
            //$aff = $afh->getAffiliateFromUser($user_id);
            
            if(!empty($aff) && ($ud['affe_id'] != $voucher['affe_id']))
                return 'voucher.already.affiliate';
            
            $prior_redeemed = $this->userRedeemed($user_id);
            $dep_count 		= phive("Cashier")->getDepositCount($user_id);
            if((!empty($prior_redeemed) || !empty($dep_count)) && empty($aff))
                return 'voucher.already.not.affiliate';
        }
        
        if(empty($voucher))
            return 'voucher.all.redeemed';	

        $user 		= cu($user_id);
        $bonus 		= phive('Bonuses')->getBonus($voucher['bonus_id']);	
        $country_res 	= phive('Bonuses')->allowCountry($bonus, $user);
        $bonus_block	= $user->isBonusBlocked();

        if(!empty($bonus)){
            if(is_string($country_res))
                return $country_res;

            if(!empty($bonus_block) && !$user->isTestAccount())
                return 'voucher.bonus.block';
            
            if(!empty($bonus['deposit_amount'])){
                if((int)phive('MailHandler2')->getUserDepositSum($user->data) < (int)mc($bonus['deposit_amount'], $user))
	            return 'voucher.insufficient.deposits';
            }
            
            if($bonus['expire_time'] < date('Y-m-d'))
                return 'voucher.expired';
        }else if(!empty($voucher['award_id'])){
            phive('Trophy')->giveAward($voucher['award_id'], $user->data);
        }
        
        $this->redeemVoucher($voucher, $user_id);

        $entry_id = phive('Bonuses')->addUserBonus($user_id, $voucher['bonus_id'], true);

        // TODO check if this works if there was a network error which prevented the FRB from being registered at 3rd party.
        if(!empty($bonus) && phive('Bonuses')->isAddedError($entry_id)){
            
            if($bonus['bonus_type'] == 'freespin'){
	        $voucher['redeemed'] = 0;
	        $voucher['user_id'] = 0;
	        $this->updateVoucher($voucher, $user);
	        return phive('Bonuses')->addErrorSuffix('voucher.freespinbonus.not.added', $entry_id);
            }else
                $res = 'voucher.bonus.not.added';
        }
        
        //if(!empty($voucher['affe_id']) && empty($aff)){
        //    $afh->createRelation($voucher['affe_id'], $user_id, false);
        //}
        
        if(is_string($res)){
            $voucher['redeemed'] = 2;
            $voucher['user_id'] = $user_id;
            $this->updateVoucher($voucher, $user);
        }
        
        return $res;
    }

    function getVoucherByNameCodeV2($name, $code){
        return phive('SQL')->loadAssoc("SELECT * FROM voucher_codes WHERE voucher_code = '$code'");
    }

    function redeemVoucherV2($voucher, $user_id){
        phive('UserHandler')->logAction($user_id, $voucher['voucher_code'], "voucher-redeemed");
    }

    function canRedeemV2($user_id, $voucher){
        if($voucher['count'] < 0){
            return 'voucher.all.redeemed';
        }

        $redeemed = phive('UserHandler')->getActionByTagUid('voucher-redeemed', $user_id, $voucher['voucher_code']);

        if(in_array($voucher['exclusive'], [1, 4]) &&  $redeemed)
            return 'voucher.already.redeemed';

        if($voucher['exclusive'] == 4){
            $mail_action = phive('UserHandler')->getActionByTagUid('voucher', $user_id, $voucher['voucher_code']);
            if(empty($mail_action))
                return 'no.voucher.email';
        }

        $extra = json_decode($voucher['requirements'], true);
        $today = (new DateTime())->format('Y-m-d H:i:s');

        if (!empty($extra['expire_time']) && $today > $extra['expire_time'])
            return 'voucher.expired';

        if (!empty($extra['deposit_amount']) && !empty($extra['deposit_start']) && !empty($extra['deposit_end'])) {

            $sum = 0;
            if (empty($extra['deposit_method'])) {
                $sum = phive('Cashier')->getDeposits($extra['deposit_start'], $extra['deposit_end'], $user_id, $type = '', $group_by = 'total')['amount_sum'];
            } else {
                foreach (explode(',', $extra['deposit_method']) as $method) {
                    $sum += phive('Cashier')->getDeposits($extra['deposit_start'], $extra['deposit_end'], $user_id, $method, $group_by = 'total')['amount_sum'];
                }
            }

            if ($sum < $extra['deposit_amount'])
                return 'vouchers.not.enough.deposits';
        }


        $sql = phive('SQL');

        if (!empty($extra['wager_amount']) && !empty($extra['wager_start']) && !empty($extra['wager_end'])) {
            $sdate = $extra['wager_start'];
            $edate = $extra['wager_end'];

            $select = "SELECT SUM(amount) AS amount_total FROM bets";

            $where = "WHERE user_id = '$user_id'
                      AND created_at >= '$sdate'
                      AND created_at <= '$edate'";

            if (!empty($extra['games'])) {
                $games = $sql->makeIn($extra['games']);
                $where .= " AND game_ref IN ($games)";
            }

            if (!empty($extra['game_operators'])) {
                $game_operators = $sql->makeIn($extra['game_operators']);
                $select .= " LEFT JOIN micro_games ON game_ref = game_id";
                $where .= " AND network IN ($game_operators)";
            }

            // This will take time
            $res = $sql->sh($user_id, '', 'bets')->loadArray($select.' '.$where)[0];

            if ($res['amount_total'] < $extra['wager_amount'])
                return 'vouchers.not.enough.wagered';

        }

        if (!empty($extra['user_on_forums'])) {
            foreach (explode(',', $extra['user_on_forums']) as $username) {
                if (empty(cuSetting('forum-username-'.$username, $user_id)))
                    return 'vouchers.missing.forum.name';
            }
        }

        return true;
    }

    function incrAndRet($voucher, $msg){
        phive('SQL')->incrValue('voucher_codes', 'count', ['id' => $voucher['id']], 1);
        return $msg;
    }
    
    function redeemV2($user_id, $name, $code){
        $ud = ud($user_id);
        if(empty($ud))
            return false;

        $voucher = $this->getVoucherByNameCodeV2($name, $code);

        if(empty($voucher))
            return 'voucher.does.not.exist';

        if(empty($name) || empty($code))
            return 'voucher.empty.code.or.name';

        if($voucher['count'] <= 0)
            return 'voucher.all.redeemed';

        // decrement here
        phive('SQL')->incrValue('voucher_codes', 'count', ['id' => $voucher['id']], -1);
        $voucher['count']--;

        $can_redeem = $this->canRedeemV2($user_id, $voucher);
        if($can_redeem !== true){
            return $this->incrAndRet($voucher, $can_redeem);
        }

        $user 		= cu($user_id);
        $bonus 		= phive('Bonuses')->getBonus($voucher['bonus_id']);

        $country_res 	= phive('Bonuses')->allowCountry($bonus, $user);
        $bonus_block	= $user->isBonusBlocked();

        if(!empty($bonus)){
            if(is_string($country_res))
                return $this->incrAndRet($voucher, $country_res);

            if(!empty($bonus_block))
                return $this->incrAndRet($voucher, 'voucher.bonus.block');

            if(!empty($bonus['deposit_amount'])){
                if((int)phive('MailHandler2')->getUserDepositSum($user->data) < (int)mc($bonus['deposit_amount'], $user))
                    return $this->incrAndRet($voucher, 'voucher.insufficient.deposits');
            }

            if($bonus['expire_time'] < date('Y-m-d'))
                return $this->incrAndRet($voucher, 'voucher.expired');

        }else if(!empty($voucher['award_id'])){
            phive('Trophy')->giveAward($voucher['award_id'], $user->data);
        }

        // Note, this could take 30 seconds due to an external call to third party.
        $entry_id = phive('Bonuses')->addUserBonus($user_id, $voucher['bonus_id'], true);

        // TODO check if this works if there was a network error which prevented the FRB from being registered at 3rd party.
        if(!empty($bonus) && phive('Bonuses')->isAddedError($entry_id)){

            if($bonus['bonus_type'] == 'freespin'){
                return $this->incrAndRet($voucher, phive('Bonuses')->addErrorSuffix('voucher.freespinbonus.not.added', $entry_id));
            }else{
                phive('UserHandler')->logAction($user_id, $voucher['voucher_code'], "voucher-redeemed-failed");
                return $this->incrAndRet($voucher, 'voucher.bonus.not.added');
            }
        }

        // Here we decrement the count
        $this->redeemVoucherV2($voucher, $user_id);

        return true;
    }

}

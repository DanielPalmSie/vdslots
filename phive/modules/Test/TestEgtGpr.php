<?php

require_once 'TestGpr.php';

class TestEgtGpr extends TestGpr
{

    public function init($args){
        parent::init($args);
        $this->http_data_type = 'xml';
        $this->http_response_data_type = 'xml';
        $this->echo_res_body = false;
    }

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                // NOTE, the below should work for the OS API AUTH call as well.
                return [
                ];
                break;
            default:
                break;
                
        }
    }

    public function getXml($method, $params = []){
        ob_start();
?>
   <?php echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' ?> 
    <<?php echo $method ?>>
    <?php foreach($params as $key => $val): ?>
        <?php echo "<$key>$val</$key>" ?>
    <?php endforeach ?>
    <PortalCode>SomeOperatorId_EUR</PortalCode>
    <SessionId>364e6b3945a0a5614867b6556d791cb0</SessionId>
    </<?php echo $method ?>>
    <?php
        $xml = ob_get_clean();
        return $xml;
    }

    public function balance($args){
        $xml = $this->getXml('GetPlayerBalanceRequest', [
            'PlayerId' => $this->getUsrId($args['uid']),
            'GameId'   => $args['gid'],
        ]);
        list($res_xml, $res_code, $res_headers) = $this->_post($xml, '');
    }

    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['defenceCode'];

        $xml = $this->getXml('AuthRequest', [
            'PlayerId' => $get_args['playerId'],
            'DefenceCode' => $this->sess_key
        ]);
        list($res_xml, $res_code, $res_headers) = $this->_post($xml, '');
    }

    
    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }

        $xml = $this->getXml('WithdrawRequest', [
            'TransferId'    => $this->bet_id,
            'PlayerId'      => $this->getUsrId($args['uid']),
            'Amount'        => $args['bet'],
            'GameNumber'    => $this->round_id,
            'GameId'        => $args['gid'],
            'PlatformType'  => $args['device']
        ]);
        
        return $this->_post($xml, '');        
    }

    public function win($args, $win_id = null, $jp_win = null){
        if(!$win_id){
            $this->win_id = rand(1000000, 10000000);
        }

        $arr = [
            'TransferId'    => $this->win_id,
            'PlayerId'      => $this->getUsrId($args['uid']),
            'Amount'        => $args['win'],
            'GameNumber'    => $this->round_id,
            'GameId'        => $args['gid'],
            'PlatformType'  => $args['device'],
            'Reason'        => 'ROUND_END'
        ];

        if(!empty($jp_win)){
            $arr['Reason'] = 'JACKPOT_END';
            // They actually have hardcoded 999 for the game id instead of the actual game id for JP wins...
            $arr['GameId'] = 999;
        }
        
        $xml = $this->getXml('DepositRequest', $arr);
        
        return $this->_post($xml, '');       
    }

    public function betAndWin($args, $bet_id = null, $jp_win = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }

        $arr = [
            'TransferId'    => $this->bet_id,
            'PlayerId'      => $this->getUsrId($args['uid']),
            'Amount'        => $args['bet'],
            'WinAmount'     => $args['win'],
            'GameNumber'    => $this->round_id,
            'GameId'        => $args['gid'],
            'PlatformType'  => $args['device'],
            'Reason'        => 'ROUND_END'
        ];

        if(!empty($jp_win)){
            $arr['Reason'] = 'JACKPOT_END';
            // They actually have hardcoded 999 for the game id instead of the actual game id for JP wins...
            $arr['GameId'] = 999;
        }
        
        $xml = $this->getXml('WithdrawAndDepositRequest', $arr);
        
        return $this->_post($xml, '');       
    }  

    public function jpWin($args){
        return $this->win($args, null, 10010);
    }

    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->mobileLaunchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";        
        
        $this->authorize($args, $launch_url);
        $this->balance($args);
        exit;
        
        $this->bet($args);
        //$this->win($args, null);

        $this->betAndWin($args);
        $this->betAndWin($args, $this->bet_id);
        

        // Idempotency test
        //$this->bet($args, $this->bet_id);

        exit;
        //$this->jpWin($args);
        //$this->rollback($args, $this->bet_id);
        
        // Idempotency test.
        //$this->win($args, $this->win_id);
        
    }
    
}

<?php

require_once 'TestGpr.php';

class TestPlayngoGpr extends TestGpr
{

    public function init($args){
        parent::init($args);
        $this->echo_res_body = false;
        $this->http_data_type = 'xml';
        $this->access_token = 'stagestagestagestage';
        $this->http_response_data_type = 'xml';
    }

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v1="http://playngo.com/v1">
              <soapenv:Header/>
              <soapenv:Body>
                <statusCode>0</statusCode>
                <v1:AddFreegameOffers>
                    <v1:FreegameExternalId>45678924</v1:FreegameExternalId>
                </v1:AddFreegameOffers>
              </soapenv:Body>
              </soapenv:Envelope>';
                break;
            case 'cancelFrb':
              return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v1="http://playngo.com/v1">
              <soapenv:Header/>
              <soapenv:Body>
                <statusCode>0</statusCode>
              </soapenv:Body>
              </soapenv:Envelope>';
                break;
            default:
                break;
                
        }
    }

    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['user'];

        $xml = "<authenticate>
                    <username>{$this->sess_key}</username>
                    <productId>1</productId>
                    <clientIP>100.101.102.103</clientIP>
                    <contextId>VIP</contextId>
                    <accessToken>{$this->access_token}</accessToken>
                    <language>en_GB</language>
                    <gameId>{$args['gid']}</gameId>
                    <channel>1</channel>
                </authenticate>";
        
        return $this->_post($xml, '');
    }

    public function balance($args){
        $xml = "<balance>
                    <externalId>{$this->getUsrId($args['uid'])}</externalId>
                    <productId>1</productId>
                    <currency>{$args['currency']}</currency>
                    <gameId>{$args['gid']}</gameId>
                    <accessToken>{$this->access_token}</accessToken>
                    <externalGameSessionId>{$this->getToken($args)}</externalGameSessionId>
                </balance>";
        
        return $this->_post($xml, '');
    }

    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }

        // Playngo sends zero amounts when sending FRB bets.
        $bet_amount = empty($this->frb_id) ? $args['bet'] : 0;
        
        $xml = "<reserve>
                    <externalId>{$this->getUsrId($args['uid'])}</externalId>
                    <productId>1</productId>
                    <currency>{$args['currency']}</currency>
                    <gameId>{$args['gid']}</gameId>
                    <accessToken>{$this->access_token}</accessToken>
                    <externalGameSessionId>{$this->getToken($args)}</externalGameSessionId>
                    <productId>1</productId>
                    <transactionId>{$this->bet_id}</transactionId>
                    <real>$bet_amount</real>
                    <gameSessionId>237842347</gameSessionId>
                    <contextId>VIP</contextId>
                    <roundId>{$this->round_id}</roundId>
                    <channel>1</channel>
                    <actualValue>{$args['bet']}</actualValue>
                </reserve>";
        
        return $this->_post($xml, '');
    }

    public function jpWin($args){
        return $this->win($args, null, true);
    }
    
    public function win($args, $win_id = null, $jp_win = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);
        $this->round_id = $this->round_id ?? uniqid();
        
        $frb_section = '';
        $jp_section = '';
        
        if($jp_win){
            $jp_section = "<jackpots>
                  <jackpot>
                            <id>1</id>
                            <win>1000.00</win>
                    </jackpot>
                    <jackpot>
                            <id>2</id>
                            <win>500.00</win>
                    </jackpot>
               </jackpots>";
        } else {
            if(!empty($this->frb_id)){
                $finished = $this->frb_tot_cnt == $this->frb_cnt ? 1 : 0;
                $frb_section = "<freegameExternalId>{$this->frb_id}</freegameExternalId>
                            <freegameFinished>$finished</freegameFinished>
                            <freegameGain>2.34</freegameGain>
                            <freegameLoss>0.00</freegameLoss>";
            }
        }
        
        $xml = "<release>
                    <externalId>{$this->getUsrId($args['uid'])}</externalId>
                    <productId>1</productId>
                    <currency>{$args['currency']}</currency>
                    <gameId>{$args['gid']}</gameId>
                    <accessToken>{$this->access_token}</accessToken>
                    <externalGameSessionId>{$this->getToken($args)}</externalGameSessionId>
                    <productId>1</productId>
                    <transactionId>{$this->win_id}</transactionId>
                    <real>{$args['win']}</real>
                    <gameSessionId>237842347</gameSessionId>
                    <contextId>VIP</contextId>
                    <roundId>{$this->round_id}</roundId>
                    <channel>1</channel>
                    <actualValue>{$args['win']}</actualValue>
                    $frb_section $jp_section
                </release>";
        
        return $this->_post($xml, '');
    }

     public function rollback($args, $origin_id = null){
         $tr_id = $origin_id ?? $this->bet_id;
         $xml = "<cancelReserve>
                    <externalId>{$this->getUsrId($args['uid'])}</externalId>
                    <productId>1</productId>
                    <currency>{$args['currency']}</currency>
                    <gameId>{$args['gid']}</gameId>
                    <accessToken>{$this->access_token}</accessToken>
                    <externalGameSessionId>{$this->getToken($args)}</externalGameSessionId>
                    <productId>1</productId>
                    <transactionId>$tr_id</transactionId>
                    <real>{$args['win']}</real>
                    <gameSessionId>237842347</gameSessionId>
                    <contextId>VIP</contextId>
                    <roundId>{$this->round_id}</roundId>
                    <channel>1</channel>
                </cancelReserve>";
        
        return $this->_post($xml, '');
    }
    
    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);
        
        $this->balance($args);
        $this->bet($args);

        //$this->rollback($args, $this->bet_id);
        //exit;

        // Idempotency test
        $this->bet($args, $this->bet_id);

        $this->win($args, null);
        //qexit;
        //exit;
        
        // Idempotency test.
        $this->win($args, $this->win_id);
        
    }
    
}

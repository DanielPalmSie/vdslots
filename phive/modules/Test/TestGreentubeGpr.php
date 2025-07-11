<?php

require_once 'TestGpr.php';

class TestGreentubeGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'login':
                return [
                    "GamePresentationURL" => "https://nrgs-b2b-cstg.greentube.com/Nrgs/B2B/Web/Testcasino/V5/Cash/Games/109/Sessions/304831B0-6033-4FCF-9AC2-BE15ACB6D7FE/Show/html5?ClientType=mobile-device"
                ];
                break;
            case 'awardFrb':
                return [
                ];
                break;
            case 'cancelFrb':
                return '';
                break;
            default:
                break;
                
        }
    }

    public function getHeaders(){
        return [
            'DateUtc: '.phive()->hisNow(),
            'NRGS-RequestId: '.uniqid()
        ];
    }
    
    public function balance($args){
        $arr = [
            'PartnerUserSessionKey' => $this->sess_key,
            'CurrencyCode'          => $args['currency'],
            'GameId'                => $args['gid']
        ];

        $args['wallet_url_postfix'] = '/Cash/Users/'.$this->getUsrId($args['uid']);
        
        list($res_body, $res_code, $res_headers) = $this->_get($arr, '', $args);

        echo "\n Result Body: \n";
        print_r($res_body);
        echo "\n\n";

        echo "\n Result Headers: \n";
        print_r($res_headers);
        echo "\n\n";
        
        return $res_body;
    }

    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }

        $args['wallet_url_postfix'] = '/Cash/Users/'.$this->getUsrId($args['uid']).'/Transactions?PartnerUserSessionKey='.$this->sess_key;
        
        $arr = [
            "TransactionType" => "CasinoRound_Stake",
            "TransactionId" => $this->bet_id,
            "TransactionCreationDate" => "2017-11-07T07:38:55.013",
            "TransactionReferenceCreationDate" => "0001-01-01T00:00:00",
            'Game' => ['GameId' => $args['gid']],
            'User' => ['UserId' => $this->getUsrId($args['uid'])],
            'Amount' => -$args['bet'],
            'EntityReferences' => [[
                'EntityType' => 'CasinoRound',
                'EntityId' => $this->round_id
            ]]
        ];

        return $this->_post($arr, '', $args);
    }

    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);

        $args['wallet_url_postfix'] = '/Cash/Users/'.$this->getUsrId($args['uid']).'/Transactions?PartnerUserSessionKey='.$this->sess_key;
        
        $arr = [
            "TransactionType" => "CasinoRound_Win",
            "TransactionId" => $this->win_id,
            "TransactionCreationDate" => "2017-11-07T07:38:55.013",
            "TransactionReferenceCreationDate" => "0001-01-01T00:00:00",
            'Game' => ['GameId' => $args['gid']],
            'User' => ['UserId' => $this->getUsrId($args['uid'])],
            'Amount' => $args['win'],
            'EntityReferences' => [[
                'EntityType' => 'CasinoRound',
                'EntityId' => $this->round_id
            ]]
        ];

        return $this->_post($arr, '', $args);
    }

    
    
    public function endRound($args){
        $args['wallet_url_postfix'] = '/Entities/CasinoRound/'.$this->round_id;
        
        $arr = [
            "EntityType"            => "CasinoRound",
            "EntityId"              => $this->round_id,
            "EntityReferenceId"     => "96347",
            "State"                 => "Finished",
            "EndDate"               => "2017-11-07T07:38:55.013",
            "InitiatorUserId"       => $this->getUsrId($args['uid']),
            "InitiatorCurrencyCode" => $args['currency'],
            'EntityReferences' => [[
                'EntityType' => 'CasinoRound',
                'EntityId'   => $this->round_id
            ]]
        ];

        return $this->_post($arr, '', $args);
    }
    
    public function jpWin($args){
        $this->win_id = $win_id ?? rand(1000000, 10000000);

        $args['wallet_url_postfix'] = '/Cash/Users/'.$this->getUsrId($args['uid']).'/Transactions?PartnerUserSessionKey='.$this->sess_key;
        
        $arr = [
            "TransactionType" => "CasinoRound_Win",
            "TransactionId" => $this->win_id,
            "TransactionCreationDate" => "2017-11-07T07:38:55.013",
            "TransactionReferenceCreationDate" => "0001-01-01T00:00:00",
            'Game' => ['GameId' => $args['gid']],
            'User' => ['UserId' => $this->getUsrId($args['uid'])],
            'Amount' => $args['win'],
            'JackpotHits' => [
                [
                    'JackpotId' => 123
                ]
            ],
            'EntityReferences' => [[
                'EntityType' => 'CasinoRound',
                'EntityId' => $this->round_id
            ]]
        ];

        return $this->_post($arr, '', $args);
        
    } 

    public function rollback($args, $origin_id){
        
        $args['wallet_url_postfix'] = '/Cash/Users/'.$this->getUsrId($args['uid']).'/Transactions?PartnerUserSessionKey='.$this->getToken($args, '123');
        
        $arr = [
            "TransactionType" => "CasinoRound_CancelStake",
            "TransactionId" => $origin_id,
            "TransactionCreationDate" => "2017-11-07T07:38:55.013",
            "TransactionReferenceCreationDate" => "0001-01-01T00:00:00",
            'Game' => ['GameId' => $args['gid']],
            'User' => ['UserId' => $this->getUsrId($args['uid'])],
            'Amount' => $args['bet'],
            'EntityReferences' => [[
                'EntityType' => 'CasinoRound',
                'EntityId' => $this->round_id
            ]]
        ];

        return $this->_post($arr, '', $args);
        
    }

    /*
    public function setupFrb(){
        $bonus = phive('SQL')->loadAssoc("SELECT * FROM bonus_types WHERE id = 2054");
        $bonus['bonus_tag'] = 'evolution';
        $bonus['game_id'] = 'evolution_aloha00000000000';
        $bonus['ext_ids'] = 'MT:MTcampid|GB:GBcampid|CA-ON:ONcampid';
        phive('SQL')->save('bonus_types', $bonus);
    }
     */
    
    public function launchUrl($args){
        $url = parent::launchUrl($args);
        $this->sess_key = $this->getToken($args, $this->gpr->token);
        return $url;
    }
    
    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->balance($args);

        exit;
        
        $this->bet($args);
        //$this->endRound($args);
        //exit;
        
        // Idempotency test
        $this->bet($args, $this->bet_id);

        $this->win($args, null, true);
        //$this->jpWin($args);
        //exit;
        // Idempotency test.
        $this->win($args, $this->win_id);

        //$this->rollback($args, $origin_id ?? $this->bet_id);
        // Idempotency test.
        //$this->rollback($args, $origin_id ?? $this->bet_id);
    }
    
}

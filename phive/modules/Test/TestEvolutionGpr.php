<?php

require_once 'TestGpr.php';

class TestEvolutionGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'login':
                return [
                    "entry" => "/entry?params=c2l0ZT1MjA&JSESSIONID=3ebd95f55",
                    "entryEmbedded" => "/entry?params=c2l0ZjA&JSESSIONID=3ebd95f55a020&embedded"
                ];
                break;
            case 'awardFrb':
                return [
                    'pk' => [
                        'voucherId' => uniqid()
                    ]
                ];
                break;
            case 'cancelFrb':
                return '';
                break;
            default:
                break;
                
        }
    }

    public function insertTestBonuses(){
        $tpl = phive('SQL')->loadAssoc('', 'bonus_types', ['id' => 47931]);

        unset($tpl['id']);
        
        foreach(['aloha00000000000' => 'e54687e8-347f-4ff2-8f8a-9c2e4bf92624'] as $gid => $ext_id){
            $tpl['reward'] = 10;
            $tpl['bonus_name'] = "$gid TEST Bonus";
            $tpl['excluded_countries'] = 'US NL';
            $tpl['ext_ids'] = "MT:$ext_id|ROW:$ext_id";
            $tpl['game_id'] = "evolution_".$gid;
            $tpl['frb_denomination'] = 10;
            $tpl['frb_cost'] = 100;
            phive('SQL')->save('bonus_types', $tpl);
        }

    }
    
    public function insertTestGames(){
        foreach(['aloha00000000000' => 'Aloha Cluster Pays DNT', 'atlantis00000000' => 'Atlantis DNT'] as $gid => $name){

            phive('SQL')->delete('micro_games', ['ext_game_name' => "evolution_".$gid]);
            
            $insert = [
                'game_name' => $name,
                'tag' => 'videoslots',
                'sub_tag' => 'videoslots',
                'game_id' => "evolution_".$gid,
                'languages' => 'de,en,es,fi,hi,it,ja,no,sv',
                'ext_game_name' => "evolution_".$gid,
                'device_type' => 'html5',
                'device_type_num' => 1,
                'operator' => 'Evolution',
                'network' => 'evolution',
                'payout_percent' => '0.96',
                'min_bet' => 10,
                'max_bet' => 10000,
                'width' => 1600,
                'height' => 900,
                'enabled' => 1,
                'volatility' => 5,
                'game_url' => strtolower(str_replace(' ', '-', $name)),
                'meta_descr' => '#game.meta.descr.'.strtolower(str_replace(' ', '-', $name)),
                'html_title' => '#game.meta.title.'.strtolower(str_replace(' ', '-', $name)),
            ];
            $mobile_id = phive('SQL')->insertArray('micro_games', $insert);
            
            $insert['device_type'] = 'flash';
            $insert['device_type_num'] = 0;
            $insert['mobile_id'] = $mobile_id;
            phive('SQL')->save('micro_games', $insert);
            
        }
    }

    
    public function createTestSession($args){
        $arr = [
            'sid' => uniqid(),
            'userId' => $this->getUsrId($args['uid']),
            'channel' => ['type' => 'P']
        ];
        
        $this->sess_key =  explode('-', $this->_post($arr, 'sid', $args)['sid'])[0];
    }
    
    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['token'];

        $arr = [
            'sid' => $this->getToken(),
            'userId' => $this->getUsrId($args['uid']),
            'channel' => ['type' => 'P']
        ];
        
        return $this->_post($arr, 'check', $args);
    }

    public function balance($args){
        $arr = [
            'sid' => $this->getToken(),
            'userId' => $this->getUsrId($args['uid'])
        ];
        
        return $this->_post($arr, 'balance', $args);
    }

    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }
        $arr = [
            'sid' => $this->getToken(),
            'userId' => $this->getUsrId($args['uid']),
            'currency' => $args['currency'],
            'transaction' => [
                'id' => $this->bet_id,
                'refId' => $this->round_id,
                'amount' => $args['bet']
            ],
            'game' => [
                'id' => $args['gid']
            ]
        ];

        return $this->_post($arr, 'debit', $args);
    }

    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);

        $arr = [
            'sid' => $this->getToken(),
            'userId' => $this->getUsrId($args['uid']),
            'currency' => $args['currency'],
            'transaction' => [
                'id' => $this->win_id,
                'refId' => $this->round_id,
                'amount' => $args['win']
            ],
            'game' => [
                'id' => $args['gid']
            ]
        ];

        return $this->_post($arr, 'credit', $args);        
    }

    public function frbWin($args, $entry){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        //echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);
        
        $win_id = rand(1000000, 10000000);
        
        $arr = [
            'sid' => $this->getToken(),
            'userId' => $this->getUsrId($args['uid']),
            'currency' => $args['currency'],
            'promoTransaction' => [
                'id' => $win_id,
                'amount' => 10.500000,
                'voucherId' => $entry['ext_id'],
                'type' => 'FreeRoundPlayableSpent',
                'remainingRounds' => 0
            ]
        ];

        return $this->_post($arr, 'promo_payout', $args);
    } 

    public function jpWin($args){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        //echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);
        
        $win_id = rand(1000000, 10000000);
        
        $arr = [
            'sid' => $this->getToken(),
            'userId' => $this->getUsrId($args['uid']),
            'currency' => $args['currency'],
            'game' => [
                'id' => $args['gid']
            ],
            'promoTransaction' => [
                'id' => $win_id,
                'amount' => 10.550000,
                'type' => 'JackpotWin',
            ]
        ];

        return $this->_post($arr, 'promo_payout', $args);
    } 

    public function rollback($args, $origin_id){
        $arr = [
            'userId' => $this->getUsrId($args['uid']),
            'transaction' => [
                'id' => $this->bet_id,
                'refId' => $this->round_id,
                'amount' => $args['bet']
            ],
            'game' => [
                'id' => $args['gid']
            ],
            'currency' => $args['currency'],
        ];
        return $this->_post($arr, 'cancel', $args);
    }

    public function setupFrb(){
        $bonus = phive('SQL')->loadAssoc("SELECT * FROM bonus_types WHERE id = 2054");
        $bonus['bonus_tag'] = 'evolution';
        $bonus['game_id'] = 'evolution_aloha00000000000';
        $bonus['ext_ids'] = 'MT:MTcampid|GB:GBcampid|CA-ON:ONcampid';
        phive('SQL')->save('bonus_types', $bonus);
    }
    
    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);
        
        $this->balance($args);

        $this->bet($args);

        // Idempotency test
        $this->bet($args, $this->bet_id);

        $this->rollback($args, $origin_id ?? $this->bet_id);
        // Idempotency test.
        // $this->rollback($args, $origin_id ?? $this->bet_id);
        exit;
        
        $this->win($args, null, true);
        // Idempotency test.
        $this->win($args, $this->win_id);

    }
    
}

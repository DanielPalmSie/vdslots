<?php

require_once 'TestGpr.php';

class TestMicrogamingGpr extends TestGpr
{

    public function init($args){
        parent::init($args);
        $this->echo_res_body = false;
        $this->http_data_type = 'xml';
        $this->http_response_data_type = 'xml';
    }

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':

                if($from_gpr['original_url'] == 'https://operatorsecurityuat.valueactive.eu/System/OperatorSecurity/v1/operatortokens'){
                    return ['AccessToken' => uniqid()];
                }

                if(strpos($from_gpr['original_url'], 'checkUserExists') !== false){
                    return ['userId' => 'mg_user_id_123'];
                }

                if(strpos($from_gpr['original_url'], 'registrations/basic') !== false){
                    return ['userId' => 'mg_registered_user_id_456'];
                }
                
                return [
                    'offerId' => 'offerid123',
                    'instanceId' => 'instanceid456'
                ];
                break;
            case 'cancelFrb':
                return [];
                break;
            default:
                break;
                
        }
    }

    public function insertTestBonuses(){
        $tpl = phive('SQL')->loadAssoc('', 'bonus_types', ['id' => 22613]);

        unset($tpl['id']);
        
        foreach(['bookOfOzDesktop' => '332883'] as $gid => $ext_id){
            $tpl['reward'] = 10;
            $tpl['bonus_name'] = "$gid TEST Bonus";
            $tpl['excluded_countries'] = 'US NL';
            $tpl['ext_ids'] = "GB:$ext_id|MT:$ext_id|DE:$ext_id|DK:$ext_id|ROW:$ext_id|SE:$ext_id";
            $tpl['game_id'] = $gid;
            $tpl['frb_denomination'] = 0.01;
            $tpl['frb_coins'] = 1;
            $tpl['frb_cost'] = 30;
            $tpl['frb_lines'] = 30;
            phive('SQL')->save('bonus_types', $tpl);
        }

    }

    public function convertBookOfOzTo94(){
        // Desktop
        $g = phive('SQL')->loadAssoc('', 'micro_games', ['id' => 39380492]);
        $g['game_id'] = 'bookOfOz94Desktop';
        $g['ext_game_name'] = 'MGS_BookOfOzV94Desktop';
        $g['client_id'] = '50300';
        $g['module_id'] = '10820';
        phive('SQL')->save('micro_games', $g);
        
        // Mobile
        $g = phive('SQL')->loadAssoc('', 'micro_games', ['id' => 39380493]);
        $g['game_id'] = 'bookOfOz94';
        $g['ext_game_name'] = 'MGS_BookOfOzV94';
        $g['client_id'] = '40300';
        $g['module_id'] = '10820';
        phive('SQL')->save('micro_games', $g);
        
    }
    
    
    public function getXml($method, $params = [], $token = null){
        $seq = phive()->uuid();
        ob_start();
    ?>
    <pkt>
        <methodcall name="<?php echo $method ?>" timestamp="2011/01/18 14:33:00.000" system="casino">
            <auth login="foo" password="bar" />
            <call seq="<?php echo $seq ?>" token="<?php echo $token ?? $this->sess_key ?>" clienttypeid="40"
                  <?php foreach($params as $key => $val): ?>
                  <?php echo $key ?>="<?php echo $val ?>"
                  <?php endforeach ?>
            >
                <extinfo/>
            </call>
        </methodcall>
    </pkt>
    <?php
        $xml = ob_get_clean();
        return $xml;
    }

    public function refreshToken($args){
        $xml = $this->getXml('refreshtoken');
        return $this->_post($xml, '');
    }

    public function endGame($args){
        $xml = $this->getXml('endgame');
        return $this->_post($xml, '');
    }
    
    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['authtoken'];
        $xml = $this->getXml('login', [], $this->sess_key);
        list($res_xml, $res_code, $res_headers) = $this->_post($xml, '');

        // echo "\n Result XML: \n\n $res_xml \n\n";
        
        preg_match('/token="([^"]+)"/', $res_xml, $matches);
        $this->sess_key = $matches[1];

        echo "\nNew session key: {$this->sess_key}\n";

        return $res_xml;
    }

    public function balance($args){
        $xml = $this->getXml('getbalance');
        return $this->_post($xml, '');
    }

    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }
        $xml = $this->getXml('play', [
            'playtype' => 'bet',
            'gameid' => $this->round_id,
            'gamereference' => $args['gid'],
            'actionid' => $bet_id ?? $this->bet_id,
            'amount' => $args['bet']
        ]);
        return $this->_post($xml, '');
    }

    public function jpWin($args){
        return $this->win($args, null, true);
    }

    public function frbWin($args, $entry){
        $win_id = rand(1000000, 10000000);
        list($offer_id, $instance_id) = explode('-', $entry['ext_id']);
        
        $xml = $this->getXml('play', [
            'playtype' => 'win',
            'gameid' => $this->round_id ?? uniqid(),
            'gamereference' => $args['gid'],
            'actionid' => $win_id,
            'amount' => $args['win'],
            'freegame' => 'TEST FRB Win',
            'freegameofferid' => $offer_id,
            'freegameofferinstanceid' => $instance_id
        ]);
        
        return $this->_post($xml, '');
    }
    
    public function win($args, $win_id = null, $jp_win = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);

        $xml = $this->getXml('play', [
            'playtype' => $jp_win ? 'progressivewin' : 'win',
            'gameid' => $this->round_id,
            'gamereference' => $args['gid'],
            'actionid' => $win_id ?? $this->win_id,
            'amount' => $args['win']
        ]);
        
        return $this->_post($xml, '');
    }

    public function rollback($args, $origin_id = null){
        $tr_id = $origin_id ?? $this->bet_id;
        
        $xml = $this->getXml('play', [
            'playtype' => 'refund',
            'actionid' => $tr_id,
        ]);
        
        return $this->_post($xml, '');
    }

    public function initPlay($args){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);        
        echo "\nLaunch URL: $launch_url \n\n";
        return $launch_url;
    }
    
    public function doFullRun($args, $origin_id = null){
        $launch_url = $this->initPlay($args);
        $this->authorize($args, $launch_url);
        //$this->jpWin($args);
        //exit;
        
        //$this->refreshToken($args);
        //exit;
        //$this->endGame($args);
        $this->balance($args);

        
        $this->bet($args);
        //exit;
        
        //$this->rollback($args, $this->bet_id);
        //exit;

        // Idempotency test
        $this->bet($args, $this->bet_id);
        //exit;
        
        $this->win($args, null);
        //exit;
        //qexit;
        //exit;
        
        // Idempotency test.
        $this->win($args, $this->win_id);
        
    }
    
}

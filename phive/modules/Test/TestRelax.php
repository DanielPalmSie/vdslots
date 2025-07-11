<?php

require_once 'TestStandalone.php';

class TestRelax extends TestStandalone
{
    /**
     * @var string
     */
    public string $token;

    public function __construct($module)
    {
        parent::__construct($module);
    }

    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

    /**
     * @param $uid
     * @param $game
     * @param $token
     * @param string $device
     */
    function setToken($uid, $game, $device = 'web')
    {
        $arr = [
            'user_id'     => $uid,
            'game_ref'    => $game,
            'device_type' => $device == 'web' ? 'flash' : 'html5'
        ];
        $token = phMsetArr(phive()->uuid(), $arr, null, $uid);
        $this->token = $token;
        return $this->token;
    }


    /**
     * @param $url
     * @param $func
     * @param $data
     * @return mixed
     */
    function post($url, $func, $data)
    {
        if (!in_array($func, ['verifyToken', 'rollback'])) {
            $data['cashiertoken'] = $this->token;
        }
        $url = $url . '?action=' . $func;
        echo "Sending $func data: ".json_encode($data);
        return phive()->post($url, $data);
    }


    /**
     * @param $url
     * @param $token
     * @return mixed
     */
    function verifyToken($url)
    {
        $data = ['token' => $this->token];
        $ret = $this->post($url, 'verifyToken', $data);
        echo "\n\n Verify Token Return: $ret \n\n";
        $arr = json_decode($ret, true);
        //print_r($arr);
        $this->token = $arr['cashiertoken'];
        return $ret;
    }

    /**
     * @param $url
     * @param $u
     * @return mixed
     */
    function getBalance($url, $u)
    {
        $data = ['customerid' => $u['id'], 'currency' => 'EUR'];
        $ret = $this->post($url, 'getBalance', $data);
        echo "\n\n Get Balance Return: $ret \n\n";
        return $ret;
    }

    /**
     * @param $url
     * @param $u
     * @param $gid
     * @param $mg_id
     * @param $r_id
     * @param $amount
     * @return mixed
     */
    function withdraw($url, $u, $gid, $mg_id, $r_id, $amount)
    {
        $data = [
            'customerid'    => $u['id'],
            'gameref'       => $gid,
            'txid'          => $mg_id,
            'gamesessionid' => $r_id,
            'amount'        => $amount
        ];
        $ret = $this->post($url, 'withdraw', $data);
        echo "\n\n Withdraw / Bet Return: $ret \n\n";
        return $ret;
    }
    
    /**
     * @param $url
     * @param $u
     * @param $gid
     * @param $mg_id
     * @param $r_id
     * @param $amount
     * @param string $eid
     * @return mixed
     */
    function deposit($url, $u, $gid, $mg_id, $r_id, $amount, $eid = '')
    {
        $data = [
            'customerid'    => $u['id'],
            'gameref'       => $gid,
            'txid'          => $mg_id,
            'gamesessionid' => $r_id,
            'amount'        => $amount
        ];
        if (!empty($eid)) {
            $data['promocode'] = $eid;
            $data['txtype'] = 'freespinspayout';
        }
        $ret = $this->post($url, 'deposit', $data);
        echo "\n\n Deposit / Win Return: $ret \n\n";
        return $ret;
    }

    /**
     * @param $url
     * @param $u
     * @param $gid
     * @param $mg_id
     * @param $r_id
     * @param $win_amount
     * @param $deposit_amount
     * @return mixed
     */
    function withdrawAndDeposit($url, $ud, $gid, $mg_id, $r_id, $win_amount, $deposit_amount)
    {
        $data = [
            'customerid'    => $ud['id'],
            'gameref'       => $gid,
            'withdraw'      => ['txid' => $mg_id, 'amount' => $win_amount],
            'deposit'       => ['txid' => $mg_id, 'amount' => $deposit_amount],
            'gamesessionid' => $r_id
        ];
        $ret = $this->post($url, 'withdrawAndDeposit', $data);
        echo "\n\n Withdraw And Deposit Return: $ret \n\n";
        return $ret;
    }

    function rollback($url, $ud, $mg_id, $o_id)
    {
        $data = ['customerid' => $ud['id'], 'txid' => $mg_id, 'originaltxid' => $o_id];
        return $this->post($url, 'rollback', $data);

    }

    public function cliBos($u_obj, $args){
        $token_data = phMgetArr($args['sid']);
        $token_data['user_id'] = $args['bos_uid'];
        $uid = $token_data['user_id'];
        phMsetArr($args['sid'], $token_data);
        return [];
    }

    public function cliUser($u_obj, $args){
        return [];
    }

    public function doFullRun($args){
        $res = $this->setupAjaxInitGameSession($args);
        print_r($res);
        $args['sid'] = $this->setToken($args['uid'], $args['gid'], $args['channel']); 
        $this->channel = $args['channel'];
        $this->url = $args['url'];
        $ud = $args['u_data'];
        $this->verifyToken($args['url'], $args['sid']);
        $this->getBalance($args['url'], $ud);
        $this->withdraw($args['url'], $ud, $args['gid'], $args['mg_id'], $args['r_id'], $args['bet']);
        $this->deposit($args['url'], $ud, $args['gid'], $args['mg_id'], $args['r_id'], $args['win']);
    }

}

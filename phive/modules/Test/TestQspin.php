<?php

require_once 'TestStandalone.php';

class TestQspin extends TestStandalone
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
    function setToken($uid, $game, $token, $device = 'web')
    {
        $arr = [
            'user_id'     => $uid,
            'game_ref'    => $game,
            'device_type' => $device == 'web' ? 'flash' : 'html5'
        ];
        phMset($token, json_encode($arr));
        $this->token = $token;

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
        echo json_encode($data);
        return phive()->post($url, $data);
    }


    /**
     * @param $url
     * @param $token
     * @return mixed
     */
    function verifyToken($url, $token)
    {
        $data = ['token' => $token];
        $ret = $this->post($url, 'verifyToken', $data);
        $arr = json_decode($ret, true);
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
        return $this->post($url, 'getBalance', $data);
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
        return $this->post($url, 'withdraw', $data);
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
        return $this->post($url, 'deposit', $data);
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
    function withdrawAndDeposit($url, $u, $gid, $mg_id, $r_id, $win_amount, $deposit_amount)
    {
        $data = [
            'customerid'    => $u['id'],
            'gameref'       => $gid,
            'withdraw'      => ['txid' => $mg_id, 'amount' => $win_amount],
            'deposit'       => ['txid' => $mg_id, 'amount' => $deposit_amount],
            'gamesessionid' => $r_id
        ];
        return $this->post($url, 'withdrawAndDeposit', $data);
    }

    function rollback($url, $u, $mg_id, $o_id)
    {
        $data = ['customerid' => $u['id'], 'txid' => $mg_id, 'originaltxid' => $o_id];
        return $this->post($url, 'rollback', $data);

    }

}

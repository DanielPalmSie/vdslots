<?php

abstract class TestCasinoProvider extends TestPhive
{

    /**
     * The user ID or the BoS ID eg.: <userid>e<tournament_id> to test with
     * @var int
     */
    protected $user_id;

    /**
     * The user currency
     * @var string
     */
    protected $user_currency;

    /**
     * The user username
     * @var string
     */
    protected $username;

    /**
     * The user password
     * @var string
     */
    protected $password;

    /**
     * The game ID to test
     * @var mixed
     */
    protected $game_id;

    /**
     * Do we echo the json post data
     * @var bool
     */
    protected $output;

    /**
     * sha1 signature encoded string
     * @var string
     */
    protected $hash;

    /**
     * GP requested method
     * @var string
     */
    protected $gp_method;

    /**
     * class requested method
     * @var string
     */
    protected $method;

    /**
     * URL for the request to send to
     * @var string
     */
    protected $url;

    /**
     * Do we apply freespins
     * @var array
     */
    protected $freespins = [];

    /**
     * Force secure token
     * @var bool
     */
    protected $force_secure_token = false;

    protected $anon_user = false;

    /**
     * Instance of Casino
     * @var Casino
     */
    protected $provider;

    /**
     * Instance of GP
     * @var UserHandler
     */
    protected $user_handler;

    /**
     * Construct: will generate randomly transaction ID for bet, win and trans_id
     */
    public function __construct()
    {
    }

    /**
     * Inject class dependencies
     *
     * @param object $dependency Instance of the dependent class
     * @return mixed TestGp|bool false if dependency couldn't be set
     */
    public function injectDependency($dependency)
    {

        switch ($dependency) {
            case $dependency instanceof Tomhorn:
                $this->provider = $dependency;
                break;

            case $dependency instanceof UserHandler:
                $this->user_handler = $dependency;
                break;
            case $dependency instanceof SQL:
                $this->sql_handler = $dependency;
                break;

            default:
                return false;
        }
        return $this;
    }

    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $action
     * @return mixed Depends on the response of the requested url
     */
    abstract public function exec($action);

    /**
     * Execute the wallet command.*
     * @param array $action_data
     * @return mixed Depends on the response of the requested url
     */
    public function execWallet($action_data)
    {
        foreach ($action_data as $key => $action) {
            if (array_key_exists($action['command'], $this->provider->getMappedWalletMethods())) {
                $this->url = $this->url . '?' . http_build_query(array(
                        'command' => $action['command'],
                        'wallet' => 'true'
                    ));
                unset($action['command']);
                $sValue = json_encode($action);
                if ($this->output === true) {
                    echo 'URL:' . PHP_EOL . $this->url . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
                }
                return phive()->post($this->url, $sValue, 'application/json', '',
                    $this->provider->getGamePrefix() . '_out', 'POST');
            }
        }
        return $this->exec($action_data);
    }

    /**
     * Set the user ID or the BoS ID eg.: <userid>e<tournament_id> to test with
     *
     * @param int $user_id
     * @return TestGp
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
        $user_data = ud($user_id);
        $this->user_currency = $user_data['currency'];
        $this->username = $user_data['username'];
        $this->password = $user_data['password'];
        return $this;
    }

    /**
     * Enable reality check testing
     */
    public function testRc()
    {
        $this->_m_bTestRc = true;
        return $this;
    }

    /**
     * Set the URL to post the json data to
     *
     * @param string $url The url
     * @return TestGp
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Set the game ID
     *
     * @param mixed $game_id The game ID
     * @return TestGp
     */
    public function setGameId($game_id)
    {
        $this->game_id = $game_id;
        return $this;
    }

    /**
     * Set the freespins
     *
     * @param array $freespins The freespins
     * @return TestGp
     */
    public function isFreespins($freespins = [])
    {
        $this->freespins = $freespins;
        return $this;
    }

    /**
     * Output the json post (what normally is send by isoftgame)
     *
     * @param bool $show_output Do we output the post data. Default: false.
     * @return TestGp
     */
    public function outputRequest($show_output = false)
    {
        $this->output = $show_output;
        return $this;
    }

    /**
     * Set the users currency
     * @param string $currency eg. EUR
     * @return TestGp
     */
    public function setUserCurrency($currency)
    {
        $this->user_currency = $currency;
        return $this;
    }

    /**
     * Set hash encoded string instead of using the automatic generated one
     * @return TestGp
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Force secure token so we can test play etc without the session
     * @return TestGp
     */
    public function forceSecureToken($secure_token = false)
    {
        $this->force_secure_token = $secure_token;
        return $this;
    }

    /**
     * Test game play without user ID (so no loggedin user)
     * @param bool TestGp
     */
    public function anonUser($anon_user)
    {
        $this->anon_user = $anon_user;
        return $this;
    }

    protected function _getUserPasswd()
    {
        return $this->provider->getHash($this->user_id . $this->password, 'sha1');
    }

    /**
     * Get the URL to post the json data to
     * @return string
     */
    protected function _getUrl()
    {
        return $this->url;
    }

    /**
     * Get a 64 bit unique hash
     * @return string
     */
    protected function _getHash()
    {
        return uniqid();
    }

    /**
     * Post the data in JSON format
     *
     * @param mixed $data data to post.
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is send to the url upfront.
     *@see outputRequest()
     */
    abstract protected function _post($data);
}
  

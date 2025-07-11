<?php
require_once 'TestCasino.php';
require_once 'TestCasino.php';

abstract class TestGp extends TestCasino
{

    /**
     * Here we store a potential session token so that we send the same token over multiple calls.
     * @varstring
     */
    public string $session_token;
    
    
    /**
     * The user ID or the BoS ID eg.: <userid>e<tournament_id> to test with
     * @var int|string
     */
    protected $_m_iUserId;
    
    /**
     * The user currency
     * @var string
     */
    protected string $_m_sUserCurrency;
    
    /**
     * The user username
     * @var string
     */
    protected string $_m_sUsername;
    
    /**
     * The user password
     * @var string
     */
    protected string $_m_sPassword;
    
    /**
     * The game ID to test
     * @var string
     */
    protected string $_m_mGameId;
    
    /**
     * Do we echo the json post data
     * @var bool
     */
    protected bool $_m_bOutput;
    
    /**
     * sha1 signature encoded string
     * @var string
     */
    protected string $_m_sHash;
    
    /**
     * GP requested method
     * @var string
     */
    protected string $_m_sGpMethod;
    
    /**
     * class requested method
     * @var string
     */
    protected string $_m_sMethod;
    
    /**
     * URL for the request to send to
     * @var string
     */
    protected string $_m_sUrl;
    
    /**
     * Do we apply freespins
     * @var array
     */
    protected array $_m_aFreespins = [];
    
    /**
     * Force secure token
     * @var bool
     */
    protected bool $_m_bForceSecureToken = false;
    
    protected bool $_m_bAnonUser = false;
    
    /**
     * Test reality check
     * @var bool
     */
    protected bool $_m_bTestRc = false;
    
    /**
     * Instance of GP
     * @var UserHandler
     */
    protected UserHandler $_m_oUserHandler;

    /**
     * @var SQL
     */
    protected SQL $_m_oSql;

    /**
     * Inject class dependencies
     *
     * @param object $dependency Instance of the dependent class
     *
     * @throws InvalidArgumentException
     * @return mixed TestGp|bool false if dependency couldn't be set
     */
    public function injectDependency(object $dependency): self
    {
        
        switch ($dependency) {
            case $dependency instanceof Gp:
                $this->_m_oGp = $dependency;
                $this->_m_oGp->setDefaults();
                break;

            case $dependency instanceof UserHandler:
                $this->_m_oUserHandler = $dependency;
                break;

            case $dependency instanceof SQL:
                $this->_m_oSql = $dependency;
                break;

            default:
                throw new InvalidArgumentException(
                    "Injecting object of type " . get_class($dependency) . " is not defined.",
                    333102
                );
        }

        return $this;
    }
    
    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $p_aAction
     * @return mixed Depends on the response of the requested url
     */
    abstract public function exec($p_aAction);
    
    /**
     * Execute the wallet command.*
     * @param array $p_aAction
     * @return mixed Depends on the response of the requested url
     */
    public function execWallet(array $p_aAction)
    {
        foreach ($p_aAction as $key => $aAction) {
            if (array_key_exists($aAction['command'], $this->_m_oGp->getMappedWalletMethods())) {
                $this->_m_sUrl = $this->_m_sUrl . '?' . http_build_query(array(
                        'command' => $aAction['command'],
                        'wallet' => 'true'
                    ));
                unset($aAction['command']);
                $sValue = json_encode($aAction);
                if ($this->_m_bOutput === true) {
                    echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
                }
                return phive()->post($this->_m_sUrl, $sValue, 'application/json', '',
                    $this->_m_oGp->getGamePrefix() . '_out', 'POST');
            }
        }
        return $this->exec($p_aAction);
    }

    /**
     * Gets user id from either username, user email or tournament user identifier
     *
     * @param string $username
     * @return int
     */
    public function getUserIdByUsername(string $username): int
    {
        $user_id = 0;

        $user = $this->_m_oUserHandler
            ->getUserByUsername($username);

        if (!empty($user) && isset($user->data["id"])) {
            $user_id = (int) $user->data["id"];
        }
        //@todo: If needed, implement logic to handle empty user

        return $user_id;
    }

    /**
     * Gets user username from username or tournament user identifier
     *
     * @param string $username
     * @return int|null userId if in tournament mode, null else
     */
    protected function getUserIdFromTournamentId(string $username): ?int
    {
        if (false !== strpos($username, 'e'))
        {
            return (int) substr($username, 0,strpos($username, "e"));
        }
        return null;
    }

    /**
     * @param int $user_id
     * @return array
     */
    private function getUserData(int $user_id): array
    {
        return [];
        }

    /**
     * @param $user_id
     * @return string
     */
    private function getUserCurrency ($user_id): string
    {
        return '';
    }

    /**
     * Set the user ID or the BoS ID eg.: <userid>e<tournament_id> to test with
     *
     * @param int|string $identifier
     * @return TestGp
     */
    public function setUserId($identifier): TestGp
    {
        $this->_m_iUserId = $identifier;

        $user_id = is_int($identifier) ? $identifier : $this->getUserIdFromTournamentId($identifier);
        $user_data = ud(empty($user_id) ? $this->getUserIdByUsername($user_id) : $user_id);

        $this->_m_sUserCurrency = $user_data['currency'];
        $this->_m_sUsername = $user_data['username'];
        $this->_m_sPassword = $user_data['password'];

        return $this;
    }
    
    /**
     * Enable reality check testing
     *
     * @return self
     */
    public function testRc():self
    {
        $this->_m_bTestRc = true;
        return $this;
    }
    
    /**
     * Set the URL to post the json data to
     *
     * @param string $p_sUrl The url
     * @return TestGp
     */
    public function setUrl($p_sUrl)
    {
        $this->_m_sUrl = $p_sUrl;
        return $this;
    }
    
    /**
     * Set the game ID
     *
     * @param string $game_id The game ID
     * @return TestGp
     */
    public function setGameId(string $game_id):self
    {
        $this->_m_mGameId = $game_id;
        return $this;
    }
    
    /**
     * Set the freespins
     *
     * @param array $p_aFreespins The freespins
     * @return TestGp
     */
    public function isFreespins($p_aFreespins = array())
    {
        $this->_m_aFreespins = $p_aFreespins;
        return $this;
    }
    
    /**
     * Output the json post (what normally is send by isoftgame)
     *
     * @param bool $p_bOutput Do we output the post data. Default: false.
     * @return TestGp
     */
    public function outputRequest($p_bOutput = false)
    {
        $this->_m_bOutput = $p_bOutput;
        return $this;
    }
    
    /**
     * Set the users currency
     * @param string $p_sCurrency eg. EUR
     * @return TestGp
     */
    public function setUserCurrency($p_sCurrency)
    {
        $this->_m_sUserCurrency = $p_sCurrency;
        return $this;
    }
    
    /**
     * Set hash encoded string instead of using the automatic generated one
     * @return TestGp
     */
    public function setHash($p_sHash)
    {
        $this->_m_sHash = $p_sHash;
        return $this;
    }
    
    /**
     * Force secure token so we can test play etc without the session
     * @return TestGp
     */
    public function forceSecureToken($p_bSecureToken = false)
    {
        $this->_m_bForceSecureToken = $p_bSecureToken;
        return $this;
    }
    
    /**
     * Test game play without user ID (so no loggedin user)
     * @param bool TestGp
     */
    public function anonUser($p_bAnonUser)
    {
        $this->_m_bAnonUser = $p_bAnonUser;
        return $this;
    }
    
    protected function _getUserPasswd()
    {
        return $this->_m_oGp->getHash($this->_m_iUserId . $this->_m_sPassword, Gp::ENCRYPTION_SHA1);
    }
    
    /**
     * Get the URL to post the json data to
     * @return string
     */
    protected function _getUrl()
    {
        return $this->_m_sUrl;
    }

    /**
     * Get a 64 bit unique hash
     * @return string
     * @throws Exception
     */
    protected function _getHash()
    {
        return uniqid();
    }
    
    /**
     * Post the data in JSON format
     *
     * @param mixed $p_mData data to post.
     * @see outputRequest()
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is send to the url upfront.
     */
    abstract protected function _post($p_mData);


    /**
     *
     * @param $old_balance
     * @param $new_balance
     * @param $bet_amount
     * @param $win_amount
     *
     * @return bool
     */
    public static function didBalanceTransactionOccurred($old_balance, $new_balance, $bet_amount, $win_amount): bool
    {
        $real_win = $win_amount - $bet_amount;
        $balance_difference = $new_balance - $old_balance;

        if (
            $real_win === $balance_difference ||
            ($balance_difference === 0 && $bet_amount > 0 && $win_amount === $bet_amount)
        ) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * @param string $game_provider
     * @param string $prefix
     * @param string $id
     * @param string $uid
     *
     * @return string
     */
    public static function getPrefixedMgId(string $game_provider, string $prefix, string $id, string $uid): string
    {
        $prefixed_id = phive($game_provider)->getLicSetting('add_country_prefix', $uid)
            ? $prefix . strtolower(licJur($uid)) . "_" . $id
            : $prefix . $id;

        return $prefixed_id;
    }

    /**
     * Returns active tournament entries by taking a sub-selection from each shard.
     *
     * @param int $tournament_id
     * @param int $costs
     * @param int|null $players
     * @return array
     */
    public function getActiveUserSessions(int $tournament_id, int $costs, ?int $players = 100): array
    {
        $count_shards = 0;

        if ($this->_m_oSql->isSharded('tournament_entries')) {
            $shards = $this->_m_oSql->getShards();
            $count_shards = empty($shards) ? 0 : count($shards);
        }

        if ($count_shards) {
            return $this-> getShardedActiveUserSessions($tournament_id, $costs, $players, $count_shards);
        } else {
            return $this-> getUnshardedActiveUserSessions($tournament_id, $costs, $players);
        }
    }

    /**
     * Returns active tournament entries.
     *
     * @param int $tournament_id
     * @param int $costs
     * @param int $count
     * @return array
     */
    private function getUnshardedActiveUserSessions(int $tournament_id, int $costs, int $count): array
    {
        $rows = [];
        $limit = min($count, 100);
        $offset = 0;

        while ($limit > 0) {
            $chunk = $this->chunkUnshardedActiveUserSessions($tournament_id, $costs, $limit, $offset);
            if (!empty($chunk)) {
                $rows = array_merge($rows, $chunk);
            }
            if (count($chunk) < $limit) {
                break;
            }
            $offset += $limit;
            $limit = min($limit, $count - count($rows));
        }

        return $rows;
    }

    /**
     * Returns a chunk of active tournament entries, so that high total counts are broken into manageable chunks.
     *
     * @param int $tournament_id
     * @param int $costs
     * @param int $limit
     * @param int $offset
     * @return array
     */
    private function chunkUnshardedActiveUserSessions(int $tournament_id, int $costs, int $limit, int $offset): array
    {
        $sql = <<<EOS
SELECT
    id, user_id
FROM tournament_entries
WHERE
    t_id = {$tournament_id}
    AND status IN ('open', 'late.registration', 'registration.open')
    AND cash_balance >= {$costs}
ORDER BY id
LIMIT {$offset}, {$limit}
EOS;
        $entries = $this->_m_oSql->shs()->loadArray($sql);
        if (empty($entries)) {
            return [];
        }

        $rows = [];
        foreach ($entries as $entry) {
            $rows[$entry['user_id']] = ['tournament_identifier' => "{$entry['user_id']}e{$entry['id']}"];
        }

        return $rows;
    }

    /**
     * Returns active tournament entries by taking a sub-selection from each shard.
     *
     * @param int $tournament_id
     * @param int $costs
     * @param int $count
     * @param int $count_shards
     * @return array
     */
    private function getShardedActiveUserSessions(int $tournament_id, int $costs, int $count, int $count_shards): array
    {
        if ($count < 1) {
            return [];
        }

        $shards = $rows = [];
        $total_limits = 0;

        for ($i = 0; $i < $count_shards; $i++) {
            $limit = max(1, (int)($count / $count_shards));
            $limit = min($limit, $count - $total_limits);
            if ($limit < 1) {
                break;
            }
            $total_limits += $limit;

            $shards[] = [
                'shard_id' => $i,
                'offset' => 0,
                'limit' => $limit,
            ];
        }

        while (true) {
            $count_before = count($rows);

            foreach ($shards as &$shard) {
                if ($shard['limit'] < 1) {
                    continue;
                }

                $chunk = $this->chunkShardedActiveUserSessions(
                    $tournament_id,
                    $costs,
                    $shard['limit'],
                    $shard['offset'],
                    $shard['shard_id']
                );
                if (!empty($chunk)) {
                    $rows = array_merge($rows, $chunk);
                }
                if (count($chunk) < $shard['limit']) {
                    $shard['limit'] = 0;
                    continue;
                }
                $shard['offset'] += $shard['limit'];
                $shard['limit'] = min($shard['limit'], $count - count($rows));
            }

            $n = count($rows);
            if (($n == $count_before) || ($n >= $count)) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @param int $tournament_id
     * @param int $costs
     * @param int $limit
     * @param int $offset
     * @param int|null $shard_id
     * @return array
     */
    private function chunkShardedActiveUserSessions(
        int $tournament_id,
        int $costs,
        int $limit,
        int $offset,
        ?int $shard_id = null
    ): array {
        $sql = <<<EOS
SELECT
    id, user_id
FROM tournament_entries
WHERE
    t_id = {$tournament_id}
    AND status IN ('open', 'late.registration')
    AND cash_balance >= {$costs}
LIMIT {$offset}, {$limit}
EOS;
        $entries = $this->_m_oSql->sh($shard_id)->loadArray($sql);
        if (empty($entries)) {
            return [];
        }

        $rows = [];
        foreach ($entries as $entry) {
            $rows[$entry['user_id']] = ['tournament_identifier' => "{$entry['user_id']}e{$entry['id']}"];
        }

        return $rows;
    }
}

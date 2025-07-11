<?php

interface Gpinterface
{
    
    const HTTP_CONTENT_TYPE_APPLICATION_JSON = 'application/json';
    const HTTP_CONTENT_TYPE_TEXT_XML = 'text/xml';
    const HTTP_CONTENT_TYPE_TEXT_HTML = 'text/html';
    const HTTP_CONTENT_TYPE_APPLICATION_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    
    /**
     * Freespin directly rewarded when finished all
     * @var int
     */
    const FREESPIN_REWARD = 3;
    
    /**
     * Freespin with wager requirement
     * @var int
     */
    const FREESPIN_WAGER = 5;
    
    /**
     * Freespin with deposit requirement
     * @var int
     */
    const FREESPIN_DEPOSIT = 6;
    
    /**
     * Freespin inhouse directly rewarded when finished all
     * @var int
     */
    const FREESPIN_INH_REWARD = 8;
    
    /**
     * Freespin inhouse with wager requirement
     * @var int
     */
    const FREESPIN_INH_WAGER = 9;
    
    /**
     * Freespin inhouse with deposit requirement
     * @var int
     */
    const FREESPIN_INH_DEPOSIT = 10;
    
    /**
     * Table where bets are stored
     * @var string
     */
    const TRANSACTION_TABLE_BETS = 'bets';
    
    /**
     * Tables where wins are stored
     * @var string
     */
    const TRANSACTION_TABLE_WINS = 'wins';
    
    /**
     * Integer represents whole units (EUR/USD/etc)
     * eg. 1 unit == 100 cents
     * @var string
     */
    const COINAGE_UNITS = 'units';
    
    /**
     * Integer represents tenths
     * eg. 1 dismes == 10 cents
     * @var string
     */
    const COINAGE_DISMES = 'dismes';
    
    /**
     * Integer represents thousandths
     * eg. 1 milles == 0.1 cents
     * @var string
     */
    const COINAGE_MILLES = 'milles';
    
    /**
     * Integer represents hundredths
     * eg. 1 cent == 1 cent
     * @var string
     */
    const COINAGE_CENTS = 'cents';
    
    /**
     * SHA1 encryption
     * @var string
     */
    const ENCRYPTION_SHA1 = 'sha1';
    
    /**
     * HMAC encryption
     * @var string
     */
    const ENCRYPTION_HMAC = 'hmac';
    
    /**
     * MD5 encryption
     * @var string
     */
    const ENCRYPTION_MD5 = 'md5';
    
    /**
     * Keeps RC timeout seconds for triggering ingame RC from backend
     * @var string
     */
    const PREFIX_MOB_RC_TIMEOUT = 'mobile_rc_timeout_';
    
    /**
     * Keeps played time for triggering RC from backend 
     * @var string
     */
    const PREFIX_MOB_RC_PLAYTIME = 'mobile_rc_playtime_';
    
    /**
    * Keeps user lang when triggering ingame RC from backend 
    * @var string
    */
    const PREFIX_MOB_RC_LANG = 'mobile_rc_lang_';

    /**
     * Internal Server Error.
     * @var string
     */
    const ER01 = 'ER01';
    
    /**
     * Command not found.
     * @var string
     */
    const ER02 = 'ER02';
    
    /**
     * The authentication credentials for the API are incorrect.
     * @var string
     */
    const ER03 = 'ER03';
    
    /**
     * Bet transaction ID has been cancelled previously.
     * @var string
     */
    const ER04 = 'ER04';
    
    /**
     * Duplicate Transaction ID.
     * @var string
     */
    const ER05 = 'ER05';
    
    /**
     * Insufficient funds.
     * @var string
     */
    const ER06 = 'ER06';
    
    /**
     * Transaction details do not match.
     * @var string
     */
    const ER07 = 'ER07';
    
    /**
     * Invalid refund, transaction ID does not exist.
     * @var string
     */
    const ER08 = 'ER08';
    
    /**
     * Player not found.
     * @var string
     */
    const ER09 = 'ER09';
    
    /**
     * Game is not found.
     * @var string
     */
    const ER10 = 'ER10';
    
    /**
     * Token is not found.
     * @var string
     */
    const ER11 = 'ER11';
    
    /**
     * No freespins remaining.
     * @var string
     */
    const ER12 = 'ER12';
    
    /**
     * Invalid freespins bet amount.
     * @var string
     */
    const ER13 = 'ER13';
    
    /**
     * Stake transaction not found.
     * @var string
     */
    const ER14 = 'ER14';
    
    /**
     * IP Address not authorized
     * @var string
     */
    const ER15 = 'ER15';
    
    /**
     * Invalid request
     * @var string
     */
    const ER16 = 'ER16';
    
    /**
     * Freespin bonus entry ID not found
     * @var string
     */
    const ER17 = 'ER17';
    
    /**
     * Duplicate Transaction ID with same amount does exist already
     * @var string
     */
    const ER18 = 'ER18';
    
    /**
     * Stake transaction not found.
     * @var string
     */
    const ER19 = 'ER19';
    
    /**
     * Failed to create bonus in GP system
     * @var string
     */
    const ER20 = 'ER20';
    
    /**
     * Free spin bonus not create because of exclusivity conflict
     * @var string
     */
    const ER21 = 'ER21';
    
    /**
     * The bonus type to use as source is not found
     * @var string
     */
    const ER22 = 'ER22';
    
    /**
     * The insert failed
     * @var string
     */
    const ER23 = 'ER23';
    
    /**
     * The update failed
     * @var string
     */
    const ER24 = 'ER24';
    
    /**
     * Player is blocked
     * @var string
     */
    const ER25 = 'ER25';
    
    /**
     * Player is banned
     * @var string
     */
    const ER26 = 'ER26';
    
    /**
     * Session player ID doesn't match request Player ID.
     * @var string
     */
    const ER27 = 'ER27';

    /**
     * Transaction ID has been cancelled already.
     * @var string
     */
    const ER28 = 'ER28';

    
    public function __construct();
    
    /**
     * Pre process data received from GP
     * @return object
     */
    public function preProcess();
    
    /**
     * Set the defaults
     * Seperate function so it can be called also from the classes that extend TestGp class
     * @return Gp
     */
    public function setDefaults();
    
    /**
     * Import new games into database from GP provided by a XML file.
     * It will check if the game does exist and if not found it will insert it.
     *
     * @param bool $p_bUseDefaultBkg Do we use the default background on game loading or a specific background for each game. Default: true
     * @param int $p_iActive Set the game to active. Default: 1
     * @param bool $p_bImport Do we import it directly to database or do we output to browser for review first
     * @param array $p_aIds Only import games with these GP-ids
     * @return void
     */
    public function importNewGames(
        $p_bUseDefaultBkg = true,
        $p_iActive = 1,
        $p_bImport = false,
        array $p_aIds = array()
    );
    
    public function parseJackpots();
    
    /**
     * Inject class dependencies
     *
     * @param object $p_oDependency Instance of the dependent class
     * @return mixed Igt|bool false if dependency couldn't be set
     */
    public function injectDependency($p_oDependency);
    
    /**
     * Convert a number from one coinage to another coinage.
     * eg. 1 unit = 100 cents, 1 dismes = 10 cents, 1 cents = 1 cents, 1 milles = 0.1 cents
     *
     * @param int $p_iAmount A number which represents either a unit|dismes|milles|cents
     * @param string $p_sFrom Which coinage has the $p_iAmount. Options are: self::COINAGE_UNITS|self::COINAGE_DISMES|self::COINAGE_MILLES|self::COINAGE_CENTS
     * @param string $p_sTo To which coinage the $p_iAmount has to be converted. Options are: self::COINAGE_UNITS|self::COINAGE_DISMES|self::COINAGE_MILLES|self::COINAGE_CENTS
     * @return int
     */
    public function convertFromToCoinage($p_iAmount, $p_sFrom = self::COINAGE_CENTS, $p_sTo = self::COINAGE_CENTS);
    
    /**
     * Get the hash of a string (salt is added automatically)
     *
     * @param string $p_mValue Value to get the hash from.
     * @param string $p_sEncryption Which encryption method to use
     * @param array $p_aOptions Additional options needed
     * @return string
     */
    public function getHash($p_mValue, $p_sEncryption = self::ENCRYPTION_SHA1, array $p_aOptions = array());
    
    /**
     * Get UUID version 4 value with dashes removed.
     * @param int $p_iUserId The user ID
     * @return string
     */
    public function getGuidv4($p_iUserId = null);
    
    /**
     * Store user ID and game ID under a session key.
     *
     * @param string $p_sKey The key to use to store the data under
     * @param int $p_iUserId The user ID
     * @param mixed $p_mGameId The game ID
     * @param mixed $p_sDevice The device either desktop|mobile
     * @return void
     */
    public function toSession($p_sKey, $p_iUserId, $p_mGameId, $p_sDevice = null);
    
    /**
     * Receive user ID and game ID from session and assign them the a key.
     *
     * @param string $p_sKey The key used to store the session value under
     * @return mixed
     */
    public function fromSession($p_sKey);
    
    /**
     * Get the GP requested method
     *
     * @return string
     */
    public function getGpMethod();
    
    /**
     * Get the GP method name by it's internally mapped method name
     *
     * @param string $p_sMethod The internally mapped method name. eg.: _bet, _win, _cancel etc
     * @return array|boolean
     */
    public function getGpMethodByWalletMethod($p_sMethod);
    
    /**
     * Get the GP received data
     *
     * @return mixed
     */
    public function getGpParams();
    
    /**
     * Get the internally mapped wallet method name by the GP method name
     *
     * @param string $p_sGpMethod The method name as received from the GP
     * @return array|boolean
     */
    public function getWalletMethodByGpMethod($p_sGpMethod);
    
    /**
     * Get a prefix which is the lowercase class name which can be used for different purposes
     *
     * @return string
     */
    public function getGamePrefix();
    
    /**
     * Get a prefix which is the lowercase class name which can be used for different purposes
     *
     * @return string
     */
    public function getGpName();
    
    /**
     * Get a country suffix for Great Britain
     *
     * @param string $p_sCountry
     * @return string
     */
    public function getSuffix($p_sCountry);
    
    /**
     * Strip the prefix from a string
     *
     * @param string $p_sString
     * @return string
     */
    public function stripPrefix($p_sString);
    
    /**
     * Get HTTP Content Type
     * @return object
     */
    public function getHttpContentType();
    
    /**
     * frb_denomination = freespin_bet
     * frb_lines = freespin_lines
     * reward = freespin_num
     * bonus entry id = freespin_tokenID
     * FundMode.id = freespin_tokenID
     * 1. awardfrbonus activates the FRB.
     * 2. player opens a game, we fetch bonus entry with the help of the user id, game ref and status = active to pass along in the launch url, if we dont find any bonus entry we dont add those params
     * 3. during gameplay the count comes in the bet call, we fetch the bonus entry with the help of the FundMode.id
     * 4. once we have the bonus entry in the bet call we set the reward value to the count
     * 5. player can then close the game in the middle but when he opens the game he will only get the amount of spins left since we get that value in #1
     * 6. when count reaches 0 we set the status of the bonus entry to approved
     * @param array $entry
     * @param string $na
     * @param array $bonus
     * @param string $ext_id
     * @return void
     */
    public function activateFreeSpin(&$entry, $na, $bonus, $ext_id);
    
    /**
     * Inform the GP about the amount of freespins available for a player.
     * The player can open the game anytime. The GP already 'knows' about the freespins available to the player.
     * Often if the GP keeps track itself and send the wins at the end when all FRB are finished like with Netent.
     * Overrule this function in the GPxxx class itself
     *
     * frb_denomination = freespin_bet
     * frb_lines = freespin_lines
     * reward = freespin_num
     * bonus entry id = freespin_tokenID
     * FundMode.id = freespin_tokenID
     * 1. awardfrbonus activates the FRB.
     * 2. player opens a game, we fetch bonus entry with the help of the user id, game ref and status = active to pass along in the launch url, if we dont find any bonus entry we dont add those params
     * 3. during gameplay the count comes in the bet call, we fetch the bonus entry with the help of the FundMode.id
     * 4. once we have the bonus entry in the bet call we set the reward value to the count
     * 5. player can then close the game in the middle but when he opens the game he will only get the amount of spins left since we get that value in #1
     * 6. when count reaches 0 we set the status of the bonus entry to approved
     *
     * @param int $p_iUserId The user ID
     * @param string $p_sGameIds The internal gameId's pipe separated
     * @param int $p_iFrbGranted The frb given to play
     * @param string $bonus_name
     * @param array $p_aBonusEntry The entry from bonus_types table
     * @return bool true if bonus was created succesfully. false otherwise (freespins are not actived)
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry);

    /**
     * Get the desktop game launcher URL
     *
     * @param mixed $p_mGameId The game_id (This is an VS internal game ID, which the GP name prefixed to it) from the micro_games table
     * @param string $p_sLang The language code
     * @param array $p_aGame
     * @param bool $show_demo
     * @return string
     * @todo array $p_aGame Array with game data received from MicroGames object
     */
    public function getDepUrl($p_mGameId, $p_sLang, $p_aGame, $show_demo);
    
    /**
     *
     * Get the mobile game launcher URL
     *
     * @param string $p_sGameRef The game_ext_name (used in the launch-url to load the game and this is the game ID as provided by the GP, which the GP name prefixed to it) from the micro_games table
     * @param string $p_sLang The language code
     * @param string $p_sLobbyUrl The lobby url
     * @param array $p_aGame Array with game data received from MicroGames object
     * @param array $args Array with all info passed to onPlay
     * @param bool $show_demo Force the game to launch in demo mode even with loged in user
     * @return string
     */
    public function getMobilePlayUrl($p_sGameRef, $p_sLang, $p_sLobbyUrl, $p_aGame, $args = [], $show_demo = false);
    
    /**
     * Get the bonus entry data by user and game ID
     *
     * @param int $p_iUserId The user ID and if left empty it will take the user ID as received inside the GP request
     * @param string $p_sGameId The NON prefix gp game ID and if left empty it will take the game ID as received inside the GP request. It will automatically prepend the game prefix if not found.
     * @return array
     */
    public function getBonusEntryByGameId($p_iUserId = null, $p_sGameId = null);
    
    /**
     * Set the start execution time
     *
     * @param int $p_iStart
     * @return Gp
     */
    public function setStart($p_iStart);
}

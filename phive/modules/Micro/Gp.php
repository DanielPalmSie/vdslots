<?php
require_once __DIR__ . '/QuickFire.php';
require_once __DIR__ . '/Gpinterface.php';
require_once __DIR__ . '/traits/UnitTestableTrait.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// ALTER TABLE `bonus_entries` ADD COLUMN `frb_remaining` INT(11) NOT NULL AFTER `reward`;
// ALTER TABLE `bonus_entries` ADD COLUMN `frb_granted` INT(11) NOT NULL AFTER `frb_remaining`;
abstract class Gp extends Casino implements Gpinterface
{
    use UnitTestableTrait;

    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is transaction ID)
     * @var string
     */
    protected $_m_sGpName = null;
    
    /**
     * Find a bet by transaction ID or by round ID.
     * Mainly when a win comes in to check if there is a corresponding bet.
     * If the transaction ID used for win is the same as bet set or roundid is not an integer set to false otherwise true.
     * Default null. Make sure that the round ID send by GP is an integer when set to true
     * @var boolean
     */
    protected $_m_bByRoundId = null;
    
    /**
     * Is the bonus_entries::balance updated after each FRB request with the FRW or does the
     * GP keeps track and send the total winnings at the end of the free rounds.
     * Default: null (balance is updated per bet)
     */
    protected $_m_bFrwSendPerBet = null;
    
    /**
     * Update the status in bonus_entries table by the last FRW request from the GP in the _win() method. Default: true.
     * If set to false make sure that $this->_handleFspinWin() is called from the extended class.
     * @var bool
     */
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = null;
    
    /**
     * Insert frb into the bet table so in case a frw comes in we can check if it has a matching frb
     * Must be true when frb can be cancelled and frb_remaining needs to +1 again
     * @var bool
     */
    protected $_m_bConfirmFrbBet = null;

    /**
     * Does the GP keep track of the FRB count on their side (as with Leander) then set as true to let them keep count
     * to avoid conflicts.
     * @var bool
     */
    protected $_m_bInhouseFrbCounter = false;

    /**
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * @var bool
     */
    protected $_m_bConfirmBet = null;

    /**
     * For Wazdan we have special gameplay in which the user can pay extra money for some rounds with extra
     * probability of win the jackpot.
     *
     * Once the user has started in that special gameplay, the Wazdan will call our wallet
     * on each round. Also, Wazdan will send bet and win request with amount 0.
     *
     * For Wazdan, we need to store those bets with amount 0 (only during jackpot gameplay) to be able to handle
     * properly the rounds in our database.
     *
     * @var bool
     */
    protected $_m_bConfirmZeroAmountBet = false;
    
    /**
     * The header content type for the response to the GP
     * @var string
     */
    protected $_m_sHttpContentType = null;
    
    /**
     * Delay the execution of the request processing or response by xx seconds during testing stage
     * so cancel requests and re-sending requests again can be tested.
     * @var string
     */
    protected $_m_iRandomizeWalletTime = 0;
    
    /**
     * Do we force respond with a 200 OK response to the GP
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = null;
    
    /**
     * PHP input stream
     * Note: in place to avoid unexpected behaviour that php input stream can only be read one,
     * even do it should be solved after 5.6 the problem was still there on staging
     * @var string
     */
    protected $_m_sInputStream = '';
    
    /**
     * Do we enable our own frb implementation if the GP doesn't support freespins by them selfs
     * Default: false
     */
    private $_m_bInhouseFrb = false;
    
    /**
     * Map GP methods requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = array();
    
    /**
     * Map Wallet methods requests to the correct class method name
     * Keys are the method names received by the internal wallet request and the values are the class method names
     * @var array
     */
    private $_m_aMapWalletMethods = array(
        'createfrb' => '_createFrb',
        'insertgame' => '_insertGame',
        'clonebonustype' => '_cloneBonusTypes',
        'deletefrb' => '_deleteFrb',
    );
    
    /**
     * GP IP-addresses. Auto enabled if it has GP ip-addresses
     * @var array
     */
    private $_m_aWhitelistedGpIps = array();
    
    /**
     * Print to screen/terminal during debugging instead of to /tmp/xxx.txt
     * @var bool
     */
    protected $_m_bToScreen = false;

    /**
     * When confirm by round is active, if we want to finish rounds when an empty win amount comes. Default behaviour on most providers
     * @var bool
     */
    protected bool $finishRoundOnEmptyWin = true;
    
    /**
     * HTTP Header status codes to match a code from property $_m_aErrors to a status code which can
     * be used in the header response in case 200 OK is not forced
     * @var array
     */
    protected $_m_aHttpStatusCodes = array(
        200 => 'OK',
        400 => 'REQUEST_INVALID',
        401 => 'ACCESS_DENIED',
        402 => 'INSUFFICIENT_FUNDS',
        403 => 'UNAUTHORIZED',
        404 => 'NOT_FOUND',
        405 => 'UNKNOWN_COMMAND',
        498 => 'TOKEN_NOT_FOUND',
        500 => 'INTERNAL_ERROR'
    );
    
    /**
     * Array with all possible response errors to GP
     * @example
     * 'ER05' => array (
     *   'responsecode' => 200, // used to send header to GP if not enforced 200 OK
     *   'status' => 'REJECTED', // used to send header to GP if not enforced 200 OK
     *   'return' => 'default', // if not string 'default' it will response this to GP
     *   'code' => 'ER01', // set this to whatever error-code the GP wants to receive
     *   'message' => 'Duplicate Transaction ID.' // set this to whatever error-message the GP wants to receive
     * )
     * @var array
     */
    private $_m_aErrors = array(
        'ER01' => array(
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => 'ER01', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ),
        'ER02' => array(
            'responsecode' => 405,
            'status' => 'COMMAND_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER02',
            'message' => 'Command not found.'
        ),
        'ER03' => array(
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => 'ER03',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER04' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => 'default',
            'code' => 'ER04',
            'message' => 'Bet transaction ID has been cancelled previously.'
        ),
        'ER05' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => true,
            'code' => 'ER05',
            'message' => 'Duplicate Transaction ID.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => 'ER06',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER07' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_MISMATCH',
            'return' => 'default',
            'code' => 'ER07',
            'message' => 'Transaction details do not match.'
        ),
        'ER08' => array(
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER08',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER09' => array(
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER09',
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER10',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER11',
            'message' => 'Token not found.'
        ),
        'ER12' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_NO_REMAINING',
            'return' => 'default',
            'code' => 'ER12',
            'message' => 'No freespins remaining.'
        ),
        'ER13' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_INVALID',
            'return' => 'default',
            'code' => 'ER13',
            'message' => 'Invalid freespin bet amount.'
        ),
        'ER14' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_UNKNOWN',
            'return' => 'default',
            'code' => 'ER14',
            'message' => 'Freespin stake transaction not found.'
        ),
        'ER15' => array(
            'responsecode' => 403,
            'status' => 'FORBIDDEN',
            'return' => 'default',
            'code' => 'ER15',
            'message' => 'IP Address forbidden.'
        ),
        'ER16' => array(
            'responsecode' => 400,
            'status' => 'REQUEST_INVALID',
            'return' => 'default',
            'code' => 'ER16',
            'message' => 'Invalid request.'
        ),
        'ER17' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER17',
            'message' => 'This free spin bonus ID is not found.'
        ),
        'ER18' => array(
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => true,
            'code' => 'ER18',
            'message' => 'Duplicate Transaction ID with same amount does exist already.'
        ),
        'ER19' => array(
            'responsecode' => 200,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER19',
            'message' => 'Stake transaction not found.'
        ),
        'ER20' => array(
            'responsecode' => 200,
            'status' => 'API_FRB_NOT_CREATED_AT_GP',
            'return' => 'default',
            'code' => 'ER20',
            'message' => "Failed to create bonus in GP system! Consider changing config setting 'no_out' to false."
        ),
        'ER21' => array(
            'responsecode' => 200,
            'status' => 'API_FRB_EXCLUSIVE_CONFLICT',
            'return' => 'default',
            'code' => 'ER21',
            'message' => 'Free spin bonus not create because of exclusivity conflict!'
        ),
        'ER22' => array(
            'responsecode' => 200,
            'status' => 'API_SOURCE_BONUS_TYPE_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER22',
            'message' => 'The bonus type to use as source is not found!'
        ),
        'ER23' => array(
            'responsecode' => 200,
            'status' => 'INSERT_FAILED',
            'return' => 'default',
            'code' => 'ER23',
            'message' => 'The insert failed!'
        ),
        'ER24' => array(
            'responsecode' => 200,
            'status' => 'UPDATE_FAILED',
            'return' => 'default',
            'code' => 'ER24',
            'message' => 'The update failed!'
        ),
        'ER25' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_BLOCKED',
            'return' => 'default',
            'code' => 'ER25',
            'message' => 'Player is blocked.'
        ),
        'ER26' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_BANNED',
            'return' => 'default',
            'code' => 'ER26',
            'message' => 'Player is banned.'
        ),
        'ER27' => array(
            'responsecode' => 200,
            'status' => 'INVALID_USER_ID',
            'return' => 'default',
            'code' => 'ER27',
            'message' => 'Session player ID doesn\'t match request Player ID.'
        ),
        'ER28' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_ALREADY_CANCELLED',
            'return' => true,
            'code' => 'ER28',
            'message' => 'Transaction ID has been cancelled already.'
        ),
        
    );

    /**
     * Whitelist of ip adresses from Panda Media Ltd.
     * @var array
     */
    private $_m_aWhitelistedWalletIps = array(
        '127.0.0.1', // localhost
        '212.56.137.74', // office
        '212.56.151.141',
        '195.158.92.198', //office
        '192.168.30.65',
        '192.168.30.66',
        '217.168.172.44',    //box1
        '217.168.172.45',    //box2
        '217.168.172.33',    //box3
        '217.168.172.34',    //box4
        '217.168.172.50',    //box5/db
        '217.168.172.36',    //www
        '217.174.248.203',   //london
        '88.208.221.127',    //test2old
        '185.89.238.132', // melita 1
        '185.89.238.134',   //test2new
        '172.16.0.134',    //test2
        '172.16.0.32',     //melita1
    );
    
    /**
     * The properties have to be declared when extending this class
     * @var array
     */
    private $_m_aPropertiesToDeclare = array(
        'bByRoundId',
        'bFrwSendPerBet',
        'bUpdateBonusEntriesStatusByWinRequest',
        'bConfirmFrbBet',
        'sHttpContentType',
        'bForceHttpOkResponse',
        'aMapGpMethods',
    );
    
    private $_m_iLogCount = 0;
    
    
    /**
     * Will have the user data as an array if user is found otherwise it will be false
     *
     * @var mixed bool|array
     */
    private $_m_mUserData = null;
    
    /**
     * Will have the game data as an array if game is found otherwise it will be false
     *
     * @var mixed bool|array
     */
    private $_m_mGameData = null;

    /**
     * Will store the key used to retrieve user game session information
     *
     * @var null
     */
    protected $_m_sSessionKey = null;

    /**
     * Will store the game session information
     *
     * @var  null
     */
    protected $_m_sSessionData = null;

    /**
     * The current internal method to execute
     *
     * @var string
     */
    private $_m_sMethod;
    
    /**
     * GP requested method
     * @var string
     */
    private $_m_sGpMethod;
    
    /**
     * The actions received by the GP request and to be executed
     *
     * @var array
     */
    private $_m_aActions = array();
    
    /**
     * GP received data
     * @var mixed
     */
    private $_m_mGpParams;
    
    /**
     * The freespin data. Will be set/contain data when a FRB is received from GP
     * @var array
     */
    protected $_m_aFreespins = array();
    
    /**
     * From which table the transaction was cancelled. bet|win
     * @var string
     */
    private $_m_sCancelTbl = '';
    
    /**
     * Does the request contains only 1 transaction or multiple transaction in 1 request
     * @var bool
     */
    private $_m_bIsMultiTransaction = false;
    
    /**
     * The bet amount in cents as received by the GP
     * @var int
     */
    private $_m_iGpAmountBets = null;
    
    /**
     * The win amount in cents as received by the GP
     * @var int
     */
    private $_m_iGpAmountWins = null;
    
    /**
     * The bet transaction ID of the GP
     * @var mixed
     */
    private $_m_iGpTxnBets = null;
    
    /**
     * The win transaction ID of the GP
     * @var mixed
     */
    private $_m_iGpTxnWins = null;
    
    /**
     * The bet transaction date of the GP
     * @var mixed
     */
    private $_m_iGpDateBets = null;
    
    /**
     * The win transaction date of the GP
     * @var mixed
     */
    private $_m_iGpDateWins = null;
    
    /**
     * The bet amount in cents as received from db
     * @var int
     */
    private $_m_iWalletAmountBets = null;
    
    /**
     * The win amount in cents as received from db
     * @var int
     */
    private $_m_iWalletAmountWins = null;
    
    /**
     * Wallet transaction ID (VS) after a bet has been inserted
     * @var int
     */
    private $_m_iWalletTxnBets = null;
    
    /**
     * Wallet transaction ID (VS) after a win has been inserted
     * @var int
     */
    private $_m_iWalletTxnWins = null;
    
    /**
     * Wallet transaction date (VS) after a bet has been inserted
     * @var int
     */
    private $_m_iWalletDateBets = null;
    
    /**
     * Wallet transaction date (VS) after a win has been inserted
     * @var int
     */
    private $_m_iWalletDateWins = null;
    
    /**
     * Force a bet insert even when the amount is 0.
     * Mainly used for cancel requests where the transaction ID was not found. We insert the bet with a amount 0.
     * @var bool
     */
    private $_m_bForceBet = false;
    
    /**
     * The bonus entry data created by createfrb or a message if it failed
     * @var array
     */
    private $_m_aNewBonusEntry = 0;
    
    /**
     * The bonus type data from which we need to create the frb entries
     * @var array
     */
    private $_m_aNewBonusType = 0;
    
    /**
     * The start microtime.
     * Used for logging.
     * @var int
     */
    private $_m_iStart = 0;
    
    /**
     * The end microtime.
     * Used for logging.
     * @var int
     */
    private $_m_iEnd = 0;
    
    /**
     * The execution duration.
     * Used for logging.
     * @var int
     */
    private $_m_iDuration = 0;
    
    /**
     * Tables where bets and wins are stored
     * order does matter, don't change!!
     * @var array
     */
    private $_TRANSACTION_TABLES = array();
    
    /**
     * Is the GP request based on a freespin
     * @var bool
     */
    protected $_m_bIsFreespin = false;
    
    /**
     * GP request data processed into a json object which is used to process bets/wins/etc
     *
     * @var \stdClass
     */
    protected $_m_oRequest;
    
    /**
     * Instance of Currencer
     *
     * @var Currencer
     */
    protected $_m_oCurrencer;
    
    /**
     * Instance of CasinoBonuses
     *
     * @var CasinoBonuses
     */
    protected $_m_oBonuses;
    
    /**
     * Instance of MicroGames
     *
     * @var MicroGames
     */
    protected $_m_oMicroGames;
    
    /**
     * Instance of SQL
     *
     * @var SQL
     */
    protected $_m_oSQL;
    
    /**
     * Instance of UserHandler
     *
     * @var UserHandler
     */
    protected $_m_oUserHandler;
    
    /**
     * Instance of Localizer
     *
     * @var Localizer
     */
    protected $_m_oLocalizer;
    
    /**
     * Skip the check for bets in _hasBet function
     * By default is false
     * @var bool
     */
    protected $_m_bSkipBetCheck = false;

    /**
     * The user identifier, e.g. 5541343 | 5541343e77 | null.
     *
     * @var string $user_identifier
     */
    protected $user_identifier;

    /**
     * Optional logging messages.
     *
     * @var array
     */
    protected $log_messages;

    /**
     * Constructor
     */
    public function __construct()
    {
        // order does matter
        $this->_TRANSACTION_TABLES = array(self::TRANSACTION_TABLE_BETS, self::TRANSACTION_TABLE_WINS);
        parent::__construct();
    }
    
    /**
     * Pre process data received from GP
     * @return object
     */
    abstract public function preProcess();
    
    /**
     * Set the defaults
     * Seperate function so it can be called also from the classes that extend TestGp class
     * @return Gp
     */
    abstract public function setDefaults();

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The micro_games:game_ext_name without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    abstract protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false);

    /**
     * @return int
     */
    public function getMIWalletTxnWins(): int
    {
        return $this->_m_iWalletTxnWins;
    }

    /**
     * @return bool
     */
    public function doConfirmByRoundId()
    {
        return $this->getSetting('confirm_win', false);
    }

    /**
     * @param int $m_iWalletTxnWins
     */
    public function setMIWalletTxnWins(int $m_iWalletTxnWins)
    {
        $this->_m_iWalletTxnWins = $m_iWalletTxnWins;
    }

    /**
     * Send a response to gp
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details from property $_m_aErrors
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to gp
     */
    abstract protected function _response($p_mResponse);
    
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
    ) {
        // nothing
    }
    
    public function parseJackpots()
    {
    }
    
    /**
     * Inject class dependencies
     *
     * @param object $p_oDependency Instance of the dependent class
     * @return mixed Igt|bool false if dependency couldn't be set
     */
    public function injectDependency($p_oDependency)
    {
        switch ($p_oDependency) {
            case $p_oDependency instanceof Currencer:
                $this->_m_oCurrencer = $p_oDependency;
                break;
            
            case $p_oDependency instanceof Bonuses:
                $this->_m_oBonuses = $p_oDependency;
                break;
            
            case $p_oDependency instanceof MicroGames:
                $this->_m_oMicroGames = $p_oDependency;
                break;
            
            case $p_oDependency instanceof SQL:
                $this->_m_oSQL = $p_oDependency;
                break;
            
            case $p_oDependency instanceof UserHandler:
                $this->_m_oUserHandler = $p_oDependency;
                break;
            
            case $p_oDependency instanceof Localizer:
                $this->_m_oLocalizer = $p_oDependency;
                break;
            
            default:
                return $this;
        }
        return $this;
    }

    /**
     * Convert a number from one coinage to another coinage.
     * eg. 1 unit = 100 cents, 1 dismes = 10 cents, 1 cents = 1 cents, 1 milles = 0.1 cents
     *
     * @param int $p_iAmount A number which represents either a unit|dismes|milles|cents
     * @param string $p_sFrom Which coinage has the $p_iAmount. Options are: self::COINAGE_UNITS|self::COINAGE_DISMES|self::COINAGE_MILLES|self::COINAGE_CENTS
     * @param string $p_sTo To which coinage the $p_iAmount has to be converted. Options are: self::COINAGE_UNITS|self::COINAGE_DISMES|self::COINAGE_MILLES|self::COINAGE_CENTS
     * @param int|null $precision A number which represent the quantity of digits after decimal place in conversion to coinage units
     * @return int
     */
    public function convertFromToCoinage($p_iAmount, $p_sFrom = self::COINAGE_CENTS, $p_sTo = self::COINAGE_CENTS,  ?int $precision = null)
    {
        
        // convert any input amount to whole cents
        switch ($p_sFrom) {
            case self::COINAGE_UNITS: // whole EUR/USD/etc
                $p_iAmount = round($p_iAmount * 100);
                break;
            
            case self::COINAGE_DISMES: // tenths
                $p_iAmount = round($p_iAmount * 10);
                break;
            
            case self::COINAGE_MILLES: // thousandths
                $p_iAmount = round($p_iAmount / 10);
                break;
            
            case self::COINAGE_CENTS: // hundredths
            default:
            
        }
        
        // convert the input amount (in cents) to the requested coinage
        switch ($p_sTo) {
            case self::COINAGE_UNITS: // whole EUR/USD/etc
                if (extension_loaded('bcmath') && !is_null($precision)) {
                    $p_iAmount = bcdiv(trim($p_iAmount), '100', $precision);
                } else {
                    $p_iAmount = ($p_iAmount / 100);
                }
                return $p_iAmount;
                break;
            case self::COINAGE_DISMES: // tenths
                $p_iAmount = ($p_iAmount / 10);
                break;
            
            case self::COINAGE_MILLES: // thousandths
                $p_iAmount = ($p_iAmount * 10);
                break;
            
            case self::COINAGE_CENTS: // hundredths
            default:
        }
        
        return $p_iAmount;
    }
    
    /**
     * Get the hash of a string (salt is added automatically)
     *
     * @param string $p_mValue Value to get the hash from.
     * @param string $p_sEncryption Which encryption method to use
     * @param array $p_aOptions Additional options needed
     * @return string
     */
    public function getHash($p_mValue, $p_sEncryption = self::ENCRYPTION_SHA1, array $p_aOptions = array())
    {
        
        if (is_array($p_mValue)) {
            // We need to sort the array by keys before create the hash of it
            ksort($p_mValue);
            $p_mValue = implode('', $p_mValue);
        }
        
        switch ($p_sEncryption) {
            
            case self::ENCRYPTION_SHA1:
                $this->_logIt([__METHOD__, 'SHA1 encrypted']);
                return sha1($p_mValue . $this->getSetting('secretkey'));
                break;
            
            case self::ENCRYPTION_MD5:
                $this->_logIt([__METHOD__, 'MD5 encrypted']);
                return md5($p_mValue . $this->getSetting('secretkey'));
                break;
            
            case self::ENCRYPTION_HMAC:
                $this->_logIt([__METHOD__, 'HMAC encrypted']);
                // The algorithm to be used. Check PHP manual for available algorithms.
                return hash_hmac((isset($p_aOptions['algorithm']) ? $p_aOptions['algorithm'] : 'sha256'), $p_mValue,
                    $this->getSetting('secretkey'));
                break;
        }
        
        return $p_mValue;
    }
    
    /**
     * Get user id from the token part, it should be prefixed with u, ex: 123uasdf3244...
     * @param string $p_sToken
     * @return string|array
     */
    public function getUidFromToken($p_sToken)
    {
        if (strpos($p_sToken, 'u') === false) {
            return '';
        }
        return explode('u', $p_sToken)[0];
    }
    
    /**
     * Get UUID version 4 value with dashes removed.
     * @param int $p_iUserId The user ID
     * @return string
     */
    public function getGuidv4($p_iUserId = null)
    {
        $prefix = empty($p_iUserId) ? '' : "u$p_iUserId";
        return $prefix . str_replace('-', '', phive()->uuid());
    }
    
    /**
     * Store user ID and game ID under a session key.
     *
     * @param string $p_sKey The key to use to store the data under
     * @param int $p_iUserId The user ID
     * @param mixed $p_mGameId The game ID
     * @param mixed $p_sDevice The device either desktop|mobile
     * @return void
     */
    public function toSession($p_sKey, $p_iUserId, $p_mGameId, $p_sDevice = null)
    {
        phMset(mKey($this->getUidFromToken($p_sKey), $p_sKey), json_encode(array(
            'sessionid' => $p_sKey,
            'userid' => $p_iUserId,
            'gameid' => $p_mGameId,
            'device' => $p_sDevice
        )));
    }

    /**
     * Changes the key used to access the session data.
     *
     * This method can be used in case we're dealing with a GP that requires a session token
     * in order to "login" the player but that does not subsequently send any session tokens
     * an example is Oryx.
     *
     * @param string $old_key The old key.
     * @param string $new_key The new key.
     *
     * @return null
     */
    public function changeSessionKey($old_key, $new_key){
        $data = $this->fromSession($old_key);
        $this->saveSessionData($new_key, $data);
        $this->deleteSession($old_key);
    }

    /**
     * Stores session data with the help of the session key.
     *
     * @see Gp::toSession()
     * @param string $p_sKey The unique key that will be used as a Redis key.
     * @param array|obj $data The data to store.
     *
     * @return xxx
     */
    public function saveSessionData($p_sKey, $data)
    {
        phMset(mKey($this->getUidFromToken($p_sKey), $p_sKey), json_encode($data));
    }


    /**
     * Receive user ID and game ID from session and assign them the a key. A timeout
     * of 15 minutes will be set, more than that and the player will have to be considered not playing anymore.
     *
     * @param string $p_sKey The key used to store the session value under
     * @return mixed
     */
    public function fromSession($p_sKey)
    {
        $sValue = phMget(mKey($this->getUidFromToken($p_sKey), $p_sKey), $this->getSetting('token_lifetime', 1800));
        if (!empty($sValue)) {
            $this->_m_sSessionKey = $p_sKey;
            $session_data = json_decode($sValue, false);
            if (isset($session_data->ext_session_id)) {
              $this->setSessionById($session_data->userid, $session_data->ext_session_id);
            }
            return $session_data;
        }
        return false;
    }
    
    /**
     * Delete a session by key from redis
     * @param $p_sKey
     */
    public function deleteSession($p_sKey)
    {
        phMdel(mKey($this->getUidFromToken($p_sKey), $p_sKey));
    }
    
    public function deleteToken($token)
    {
        $this->deleteSession($token);
    }
    
    public function getRc($uid = '')
    {
        return $this->_getRc($uid);
    }
    
    /**
     * Get the method name as received by the GP
     *
     * @return string
     */
    public function getGpMethod()
    {
        return $this->_m_sGpMethod;
    }
    
    /**
     * Get the GP method name by it's internally mapped wallet method name
     *
     * @param string $p_sMethod The internally mapped method name. eg.: _bet, _win, _cancel etc
     * @return array|boolean
     */
    public function getGpMethodByWalletMethod($p_sMethod)
    {
        if(!empty($this->getGpMethod())){
            foreach($this->_m_aMapGpMethods as $gpMethod => $mappedMethod){
                if($gpMethod === $this->getGpMethod() && $mappedMethod === $p_sMethod){
                    return $gpMethod;
                }
            }
        }
        
        $a = array_flip($this->_m_aMapGpMethods);
        if (isset($a[$p_sMethod])) {
            return $a[$p_sMethod];
        }
        
        return false;
    }
    
    /**
     * Get the internally mapped wallet method name by the GP method name
     *
     * @param string $p_sGpMethod The method name as received from the GP
     * @return array|boolean
     */
    public function getWalletMethodByGpMethod($p_sGpMethod)
    {
        if (isset($this->_m_aMapGpMethods[$p_sGpMethod])) {
            return $this->_m_aMapGpMethods[$p_sGpMethod];
        }
        return false;
    }
    
    /**
     * Get the GP received data
     *
     * @return mixed
     */
    public function getGpParams()
    {
        return $this->_m_mGpParams;
    }

    /**
     * Get a prefix which is the lowercase class name which can be used for different purposes
     *
     * @return string|string[]
     */
    public function getGamePrefix()
    {
        return preg_replace("/\W|_/", "", $this->getGpName()) . '_';
    }

    /**
     * Get a prefix which is the lowercase class name which can be used for different purposes
     *
     * @param bool $is_transaction
     * @return string|string[]|null
     */
    public function getTransactionPrefix($u_obj = null)
    {
        $u_obj  = $u_obj ?? $this->user;
        $prefix = $this->getGamePrefix();

        if(!empty($u_obj)) {
            if ($this->getLicSetting('add_country_prefix', $u_obj)) {
                $prefix  = $prefix . strtolower(licJur($u_obj)) . '_';
            }
        }

        return $prefix;
    }

    public function hasPrefix($ext_tr_id, $u_obj = null){
        $prefix = $this->getTransactionPrefix($u_obj);
        return strpos($ext_tr_id, $prefix) !== false;
    }
    
    /**
     * Used to attach a prefix to an ext id.
     *
     * Used when we want to save a foreign id in bets|wins.mg_id and have it be unique, then we call this method
     * BEFORE we insert the bet|win, on the foreign id. This is to avoid duplicate mg_ids in cased the GP has multiple
     * environments and doesn't care to make sure that its IDs are unique in the aggregate.
     *
     * @param string &$ext_tr_id The GP id as it was sent to us.
     * @param DBUser $u_obj 
     *
     * @return null We pass by reference.
     */    
    public function attachPrefix(&$ext_tr_id, $u_obj = null)
    {
        if(!$this->hasPrefix($ext_tr_id)){
            $ext_tr_id = $this->getTransactionPrefix($u_obj).$ext_tr_id;
        }
    }
    
    /**
     * Get a prefix which is the lowercase class name which can be used for different purposes
     *
     * @return string
     */
    public function getGpName()
    {
        return strtolower($this->_m_sGpName);
    }
    
    /**
     * Get a country suffix for Great Britain
     *
     * @param string $p_sCountry
     * @return string
     */
    public function getSuffix($p_sCountry)
    {
        return strtoupper($p_sCountry) == 'GB' ? 'GB' : '';
    }
    
    /**
     * Strip the prefix from a string
     *
     * @param string $p_sString
     * @return string
     */
    public function stripPrefix($p_sString, $is_transaction = false, $u_obj = null)
    {
        return str_replace($this->getGamePrefix(), '', $p_sString);
    }

    /**
     * Used to detach a prefix from an external id.
     *
     * Used when we want to remove a prefix from a bet|win, can be used in situations when we need to return
     * the external id as it was sent to us by the GP.
     *
     * @see Gp::attachPrefix()
     *
     * @param string &$ext_tr_id The bet|win.mg_id as stored in our database. 
     * @param DBUser $u_obj The player object. 
     *
     * @return null We pass by reference.
     */    
    public function detachPrefix(&$ext_tr_id, $u_obj = null)
    {
        $ext_tr_id = $this->stripPrefix($ext_tr_id, true, $u_obj);
    }
    
    /**
     * Get HTTP Content Type
     * @return string
     */
    public function getHttpContentType()
    {
        return $this->_m_sHttpContentType;
    }
    
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
    public function activateFreeSpin(&$entry, $na, $bonus, $ext_id)
    {
        $entry['ext_id'] = (($ext_id !== false && $ext_id !== 'fail') ? (!empty($ext_id) ? $ext_id : $bonus['ext_ids']) : $bonus['ext_ids']);
        $entry['status'] = 'active';
        // if rake_percent == 0 status must be directly approved in bonus_entries
        // in case of frb/w coming per request ($this_m_bFrwSendPerBet === true) it's possible to see the frb winning under bonus cash balance on the site but only if the status is active, not when its approved upfront like nentent.
        // currently wager/deposit free frb must be set to approved directly because if a user has cash_balance == 0
        // and the frb's have been played all and handleFspinWin is executed the winning amount is not added to the users cash balance but the status is updated to approved.
        // the problem is located in Casino.php -> changeBalance() in the condition if($type == 2 || $type == 7). This line of code return an array: !empty(phive("Bonuses")->onlyBonusBalanceEntries($this->bonus_bet))
        // because of that $inc_result = $user->incrementAttribute('cash_balance', $amount); is not executed
    }
    
    /**
     * Inform the GP about the amount of freespins available for a player.
     * The player can open the game anytime. The GP already 'knows' about the freespins available to the player.
     * Often if the GP keeps track itself and send the wins at the end when all FRB are finished like with Netent.
     * Overrule this function in the GPxxx class itself
     *
     * @param int $p_iUserId The user ID
     * @param string $p_sGameIds The internal gameId's pipe separated
     * @param int $p_iFrbGranted The frb given to play
     * @param string $bonus_name
     * @param array $p_aBonusEntry The entry from bonus_types table
     * @return bool|string|int If not false than bonusId is returned (either from GP or own) otherwise false (freespins are not activated)
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)
    {
        return $p_sGameIds;
    }

    /**
     * Get the desktop game launcher URL
     *
     * @param mixed $p_mGameId The game_id (This is an VS internal game ID, which the GP name prefixed to it) from the micro_games table
     * @param string $p_sLang The language code
     * @param array $p_aGame
     * @param bool $show_demo
     * @return string
     */
    public function getDepUrl($p_mGameId, $p_sLang, $p_aGame = null, $show_demo = false)
    {
        $game = $p_aGame ?: phive('MicroGames')->getByGameId($p_mGameId);
        // Here we override the external launch and game ids if we have a record in the game_country_overrides table.
        if (!empty($_SESSION['token_uid'])) {
            $this->_m_mGameData = phive('MicroGames')->overrideGameForTournaments(null, $game);
        } else {
            $this->_m_mGameData = phive('MicroGames')->overrideGame(null, $game);
        }

        $sUrl = $this->_getUrl(trim($this->stripPrefix($this->_m_mGameData['ext_game_name'])), $p_sLang, 'desktop', $show_demo);
        return $sUrl;
    }
    
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
    public function getMobilePlayUrl($p_sGameRef, $p_sLang, $p_sLobbyUrl, $p_aGame, $args = [], $show_demo = false)
    {
        // Here we override the external launch and game ids if we have a record in the game_country_overrides table.
        if (!empty($_SESSION['token_uid'])) {
            $this->_m_mGameData = phive('MicroGames')->overrideGameForTournaments(null, $p_aGame);
        } else {
            $this->_m_mGameData = phive('MicroGames')->overrideGame(null, $p_aGame);
        }

        $sUrl = $this->_getUrl(trim($this->stripPrefix($this->_m_mGameData['ext_game_name'])), $p_sLang, 'mobile');
        return $sUrl;
    }
    
    /**
     * Get the bonus entry data by user and game ID
     *
     * @param int $p_iUserId The user ID and if left empty it will take the user ID from $this->_m_mUserData['id'] after a GP request
     * @param string $p_sGameId The prefix VS game ID and if left empty it will take the game ID (game_id) from $this->_m_mGameData after a GP request.
     * @return object
     */
    public function getBonusEntryByGameId($p_iUserId = null, $p_sGameId = null)
    {
        return $this->_getBonusEntryBy(
            (empty($p_iUserId) ? $this->_m_mUserData['id'] : $p_iUserId),
            (empty($p_sGameId) ? $this->_m_mGameData['game_id'] : $p_sGameId),
            'game_id'
        );
    }
    
    /**
     * Set the start execution time
     *
     * @param int $p_iStart
     * @return Gp
     */
    public function setStart($p_iStart)
    {
        $this->_m_iStart = $p_iStart;
        return $this;
    }

    /**
     * @return int
     */
    public function getStartedAt()
    {
        return $this->_m_iStart;
    }
    
    /**
     * Set the end execution time
     *
     * @param int $p_iEnd
     * @return Gp
     */
    protected function _setEnd($p_iEnd)
    {
        $this->_m_iEnd = $p_iEnd;
        $this->_m_iDuration = $this->_m_iEnd - $this->_m_iStart;
        return $this;
    }
    
    /**
     * Log the execution time needed to process a request received from a GP
     * @return void
     */
    protected function _logExecutionTime()
    {
        $aId = [];
        foreach($this->_TRANSACTION_TABLES as $key => $value){
            $id = $this->_getTransaction('txn', $value);
            if(!empty($id)){
                $aId[] = $id;
            }
        }

        $insert = [
            'duration' => $this->_m_iDuration,
            'username' => (isset($this->_m_oRequest->playerid) ? $this->_m_oRequest->playerid : null),
            'mg_id' => (!empty(implode('-', $aId)) ? implode('-', $aId) : 0),
            'token' => $this->_m_sGpName,
            'method' => $this->_getMethod(),
            'host' => gethostname(),
        ];

        if ($this->getSetting('log_game_replies') === true) {
            $this->_m_oSQL->insertArray('game_replies', $insert);
        }

        // When method is empty, there was some error which was logged in trans_logs table,
        // so we donot save it here, because this insert with empty method gives no good information
        if ($this->getSetting('log_slow_game_replies') === true && !empty($insert['method'])) {

            phive('MicroGames')->logSlowGameReply($this->_m_iDuration, $insert);
        }
    }
    
    /**
     * Has the request multiple transactions to be processed
     * @return bool
     */
    protected function _hasMultiTransactions(){
        return $this->_m_bIsMultiTransaction;
    }
    
    /**
     * Will initialize the tournament requirements if a tournament is found and returns the user ID participating in it.
     * @param $p_sToken Tournament token eg.: <userid>e<tournamentid>
     * @return int
     */
    protected function _enableTournamentByToken($p_sToken){
        return $this->getUsrId($p_sToken);
    }
    
    /**
     * Does a tournament token exist
     * @return bool
     */
    protected function _hasTournamentToken(){
        return !empty($_SESSION['token_uid']);
    }
    
    /**
     * Get the tournament token if it exists or otherwise optional return the user id if provided
     *
     * @param int $p_mUserId Default ''
     * @return mixed the userID as passed or the tokenID eg.: <userID>e<tournamentID>
     */
    protected function _getTournamentToken($p_mUserId = ''){
        return ($this->_hasTournamentToken() ? $_SESSION['token_uid'] : $p_mUserId);
    }
    
    /**
     * Do we treat the current GP request in the tournament mode or not
     * @return bool
     */
    protected function _isTournamentMode(){
        return !empty($this->t_entry);
    }
    
    protected function _supportInhouseFrb($p_sGpName)
    {
        $this->_m_bInhouseFrb = in_array(strtolower($p_sGpName), phive()->getSetting('inhousefrb_network'));
        return $this;
    }
    
    protected function _isInhouseFrb()
    {
        return $this->_m_bInhouseFrb;
    }
    
    /**
     * Set the RAW GP received data
     *
     * @param mixed array|string The RAW values received from the GP
     * @return void
     */
    protected function _setGpParams($p_mValue)
    {
        $this->_m_mGpParams = $p_mValue;
        $this->_logIt([__METHOD__, $this->_m_sInputStream, print_r($p_mValue, true)]);
    }
    
    
    /**
     * Set the GP requested method
     *
     * @param string $p_sGpMethod The game provider received method name
     * @return void
     */
    protected function _setGpMethod($p_sGpMethod)
    {
        $this->_logIt([__METHOD__, $p_sGpMethod]);
        $this->_m_sGpMethod = $p_sGpMethod;
    }
    
    /**
     * Set the internal wallet method name
     *
     * @param string $p_sMethod The internal wallet method name which is linked to the game provider method name
     * @return void
     */
    protected function _setWalletMethod($p_sMethod)
    {
        $this->_m_sMethod = $p_sMethod;
    }
    
    /**
     * Overrule certain Gp class errors with ones from the GP-specific extended class
     *
     * @param array $p_aErrors The array with errors to overrule the ones defined inside this class
     * @return object
     */
    protected function _overruleErrors(array $p_aErrors = array())
    {
        if (!empty($p_aErrors)) {
            $this->_m_aErrors = array_merge($this->_m_aErrors, $p_aErrors);
        }
        return $this;
    }
    
    /**
     * Check if required class properties are declared and are not null
     * @return object
     */
    protected function _checkDeclaredProperties()
    {
        
        // only during developement needed
        if ($this->getSetting('test') === false) {
            return $this;
        }
        
        $aErrors = array();
        
        foreach ($this->_m_aPropertiesToDeclare as $key => $value) {
            if (is_array($this->{'_m_' . $value}) && empty($this->{'_m_' . $value})) {
                $aErrors[] = 'Please declare _m_' . $value . ' and set the key:value pairs in the class extending Gp.' . PHP_EOL;
            } else {
                if ($this->{'_m_' . $value} === null) {
                    $aErrors[] = 'Please declare _m_' . $value . ' (and set it to true or false) in the class extending Gp.' . PHP_EOL;
                }
            }
        }
        if (!empty($aErrors)) {
            die(implode('', $aErrors));
        }
        return $this;
    }
    
    /**
     * Get the internal method name
     * @return string
     */
    protected function _getMethod()
    {
        return $this->_m_sMethod;
    }
    
    /**
     * Get the bet/win transaction ID, amount or date either as received from the GP or from the db
     * @param string $p_sParams Either amount|txn
     * @param string $p_sTransactionTable Either self::TRANSACTION_TABLE_WINS|self::TRANSACTION_TABLE_BETS|''
     * @param bool $p_bWallet As received from the GP or from the wallet. Default: true (from wallet)
     * @return string|int|bool
     */
    protected function _getTransaction($p_sParams, $p_sTransactionTable = '', $p_bWallet = true){
        if(in_array($p_sParams, array('amount','txn','date'))) {
            $sSource = (($p_bWallet === true) ? 'Wallet' : 'Gp');
            if ($p_sTransactionTable === '' || in_array($p_sTransactionTable, $this->_TRANSACTION_TABLES)) {
                return $this->{'_m_i' . $sSource . ucfirst($p_sParams) . $this->_getTransactionTable($p_sTransactionTable)};
            }
        }
        return null;
    }
    
    /**
     * Get the table name, either bets or wins, from which the transaction ID was cancelled
     * @return string
     */
    protected function _getCancelTbl()
    {
        return $this->_m_sCancelTbl;
    }
    
    /**
     * Force all http responses with 200 OK.
     *
     * @param bool $p_bForceHttpOkResponse
     * @return object
     */
    protected function _forceHttpOkResponse($p_bForceHttpOkResponse)
    {
        $this->_m_bForceHttpOkResponse = $p_bForceHttpOkResponse;
        return $this;
    }

    /**
     * Whitelist game provider ip addresses
     *
     * @param array $p_aWhitelistedGpIps
     * @return object
     */
    protected function _whiteListGpIps(array $p_aWhitelistedGpIps = array())
    {
        if (!$this->getIsCli() && !empty($p_aWhitelistedGpIps) && !$this->clientIpBelongsToWhiteList($p_aWhitelistedGpIps)) {
            $this->_logIt([__METHOD__, print_r($_SERVER,true)], $this->getGpName() . '-error-ipblock', true);
            $this->_setResponseHeaders($this->_getError(self::ER15));
            $this->terminate('ipblock');
        }

        return $this;
    }

    /**
     * Checks if client IP belongs to whitelist
     *
     * @param  array  $p_aWhitelistedGpIps
     * @return bool
     */
    protected function clientIpBelongsToWhiteList(array $p_aWhitelistedGpIps = array())
    {
        $this->_m_aWhitelistedGpIps = $this->transformIps($p_aWhitelistedGpIps);

        return in_array(
            $this->getClientIp(),
            array_merge($this->_m_aWhitelistedGpIps, $this->_m_aWhitelistedWalletIps)
        );
    }

    /**
     * Transform cidr values into array of ips and leaves simple ips untouched
     *
     * @param  array  $ips
     * @return array
     */
    private function transformIps(array $ips): array
    {
        $result = [];
        foreach ($ips as $ip) {
            [$start_ip, $prefix] = explode('/', $ip);
            if(!$prefix) {
                $result[] = $ip;
                continue;
            }

            $ip_count = 1 << (32 - $prefix);
            $start = ip2long($start_ip);
            for ($i = 0; $i < $ip_count; $i++) {
                $result[] = long2ip($start + $i);
            }
        }

        return $result;
    }

    /**
     * Get the IP address of just the wallet, so just videoslots and office ips
     * @return array
     */
    protected function _getWalletIps(){
        return $this->_m_aWhitelistedWalletIps;
    }
    
    /**
     * Set HTTP Content Type
     *
     * @param string $p_sHttpContentType
     * @return object
     */
    protected function _setHttpContentType($p_sHttpContentType)
    {
        $this->_m_sHttpContentType = $p_sHttpContentType;
        return $this;
    }
    
    /**
     * Map the GP external method naming to internal wallet method naming
     * @param array $p_aMethods eg: array('Void' => '_cancel')
     * @return object
     */
    protected function _mapGpMethods(array $p_aMethods = array())
    {
        $this->_m_aMapGpMethods = $p_aMethods;
        
        if ($this->getSetting('log_errors') === true) {
            // we only need the wallet method during testing to be added so we can generate api call to
            // automatically insert new GP game and system, create the bonus_types and create the bonus_entries
            $this->_m_aMapGpMethods = array_merge($this->_m_aMapGpMethods, $this->_m_aMapWalletMethods);
        }
        
        return $this;
    }
    
    /**
     * Get external method naming to internal method naming
     * @return array
     */
    protected function _getMappedGpMethodsToWalletMethods()
    {
        return $this->_m_aMapGpMethods;
    }
    
    /**
     * Get mapped internal method naming
     * @return array
     */
    public function getMappedWalletMethods()
    {
        return $this->_m_aMapWalletMethods;
    }
    
    /**
     * Get the players balance
     * @param array $p_aUserData optional to overrule
     * @param array $p_aGameData optional to overrule
     * @return int
     */
    protected function _getBalance(array $p_aUserData = array(), array $p_aGameData = array(), bool $totalBalance = false)
    {
        if (!$this->_isTournamentMode()) {

            $aUserData = (empty($p_aUserData) ? $this->_m_mUserData : $p_aUserData);
            $aGameData = (empty($p_aGameData) ? $this->_m_mGameData : $p_aGameData);
            // it's not a tournament
            if(!empty($aUserData)) {
                if ($this->hasSessionBalance() && !$totalBalance) {
                    return $this->getSessionBalance($aUserData);
                }
                $real_balance = phive('UserHandler')->getFreshAttr($aUserData['id'], 'cash_balance');
                if (empty($aGameData)) {
                    // we log if game data could not be set as we need it to add the bonus balance to real balance
                    $this->_logIt([__METHOD__,
                        print_r($this->_m_oRequest, true),
                        'Game data for ' . $this->_m_oRequest->skinid . ' could not be set!',
                        'GP Method: ' . $this->getGpMethod(),
                        'Mapped Method: ' . $this->_getMethod()
                    ], $this->getGpName() . '-missing-game-data');
                } else {
                    $this->_logIt([__METHOD__, print_r($aGameData, true)]);
                }
                if (empty($real_balance)) {
                    // if users:cash_balance === 0 any winning will be added to the bonus entry instead so this object needs
                    // to be updated with the latest winning to generate correct balance response.
                    $this->_logIt([__METHOD__, 'real balance: ' . $real_balance]);
                    phive('Bonuses')->resetEntries();
                }
                $bonus_balance = empty($aGameData['ext_game_name']) ? 0 : phive('Bonuses')->getBalanceByRef($aGameData['ext_game_name'],  $aUserData['id']);
                return $real_balance + $bonus_balance;
            }
            return 0;
        } else {
            return $this->tEntryBalance();
        }
    }
    
    /**
     * Generate a random number of xx length
     * @param int $length
     * @return string
     */
    public function randomNumber($length)
    {
        return join('', array_map(function ($value) {
            return $value == 1 ? mt_rand(1, 9) : mt_rand(0, 9);
        }, range(1, $length)));
    }
    
    /**
     * Create a freeround bonus for a given player and game
     * @example {
     *  skinid: <not_prefixed_ext_game_name:required>,
     *  playerid: <userid:required>,
     *  reward: <int:optional>
     *  bonustype: <reward|deposit|wager:required>,
     *  activate: <true|false:optional>
     *  ext_id: <int:optional>
     * }
     * @param stdClass $p_oParameters
     * @return bool|array true on success error otherwise
     */
    protected function _createFrb(stdClass $p_oParameters){

        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;

        $query2 = " AND bt.brand_id = {$brandId} AND bt.expire_time >= curdate() AND bt.ext_ids LIKE '%" .
            phive("SQL")->escape($this->getGamePrefix() . $p_oParameters->skinid, false) . "%'" .
            (isset($p_oParameters->reward) ? ' AND bt.reward = ' . (int)$p_oParameters->reward : '' ) . " order by bt.id asc limit 1";
        
        switch($p_oParameters->bonustype){
            case 'reward': // no wager and no deposit
                $query1 = 'bt.rake_percent = 0';
                break;
            
            case 'deposit': // deposit requirement
                $query1 = 'bt.deposit_limit > 0 AND bt.rake_percent > 0';
                break;
                
            case 'wager': // wager requirement
                $query1 = 'bt.deposit_limit = 0 AND bt.rake_percent > 0';
                break;
                
            default: $query1 = '1 = 1';
        }
        
        $q = '
        select be.id from bonus_entries be
        inner join bonus_types bt on bt.id = be.bonus_id
        where ' . $query1 . ' and curdate() >= be.start_time and
        curdate() < be.end_time and
        be.bonus_tag = ' . phive("SQL")->escape($this->getGpName()) . ' and
        be.frb_remaining > 0 and
        be.user_id = ' . (int)$p_oParameters->playerid . ' and be.status = ' . phive("SQL")->escape((($p_oParameters->bonustype === 'reward') ? 'approved' : 'active' ));
 
        $this->_logIt([__METHOD__, print_r($p_oParameters, true), $q]);
        // check if bonus_entry does exist already
        $data = $this->_m_oSQL->sh($p_oParameters->playerid, 'user_id', 'bonus_entries')->loadAssoc($q); //die($q);

        if(empty($data)) {
            // it doesn't exist so let's check if we have the bonus_type so we can use it to create the bonus entry
            $q2 = 'SELECT * FROM bonus_types bt WHERE ' . $query1 . $query2;
            
            $this->_logIt([__METHOD__, $q2]);
            $data = $this->_m_oSQL->loadAssoc($q2);//die($q2);
            
            if (!empty($data)) {

                // here we actual create the bonus entry based on the bonus_type created above
                $result = $this->_m_oBonuses->addUserBonus($p_oParameters->playerid, $data['id'],
                    (isset($p_oParameters->activate) ? !empty($p_oParameters->activate) : true));

                if(ctype_digit($result)) {
                    // bonus entry created
                    if(!empty($p_oParameters->ext_id)) {
                        $this->attachPrefix($p_oParameters->ext_id);
                        if($this->_m_oSQL
                                ->sh($p_oParameters->playerid)
                                ->updateArray('bonus_entries', ['ext_id' => $p_oParameters->ext_id], "id = ".$result) !== false) {
                            // we have a GP that only returns their own BonusID
                        } else {
                            return $this->_getError(self::ER24);
                        }
                    }
                    $data = $this->_m_oBonuses->getBonusEntry($result, $p_oParameters->playerid);
                } elseif ($result === 'connection.error'){
                    // some error, bonus entry could not be created at GP
                    return $this->_getError(self::ER20);
                } else {
                    // some other string error from handleExclusive() eg.: bonus entry does exist already, can only be played ones etc..
                    preg_match_all('/\d+/',$result, $matches);
                    $aMess = $this->_getError(self::ER21);
                    $aMess['message'] = $result;
                    return array_merge($aMess, array('id' => $matches[0][0]));
                }
            } else {
                // No bonus type found
                return $this->_getError(self::ER22);
            }
        } else {
            // we only want the bonus_entries data
            $data = $this->_m_oBonuses->getBonusEntry($data['id'], $p_oParameters->playerid);
        }
        $this->_m_aNewBonusEntry = $data;
        return true;
    }
    
    /**
     * Create new bonus type from existing bonus_types by id
     * 1948, // netent no wager and no deposit
     * 2122, // netent wager requirement
     * 4351, // netent deposit requirement
     * @example {
     *  skinid: <not_prefixed_ext_game_name:required>,
     *  bonustype: <bonus_types:id|string(reward|wager|deposit):required>,
     *  reward: <int:optional>,
     *  frb_coins:<int:optional>,
     *  frb_denomination:<float:optional>,
     *  frb_lines:<int:optional>,
     *  disable:<bool:optional>
     * }
     * @param stdClass $p_oParameters
     * @return bool|array true on success error otherwise
     */
    protected function _cloneBonusTypes(stdClass $p_oParameters){
        
        $sPrefixedExtGameName = $this->getGamePrefix() . $p_oParameters->skinid;
        $aGameData = $this->_m_oMicroGames->getByGameRef($sPrefixedExtGameName);
        $mCloneFromBonusId = null;
        $aBonusses = array(
            'reward' => 1948,
            'wager' => 2122,
            'deposit' => 4351
        );

        if (!empty($aGameData)) {
            // get an existing bonus where we can copy from
            if(ctype_digit($p_oParameters->bonustype)) {
                // received param is int so we need to copy from specific bonus type ID
                $mCloneFromBonusId = (int)$p_oParameters->bonustype;
            } elseif(array_key_exists($p_oParameters->bonustype, $aBonusses)) {
                // received param is string so we take it from $aBonusses
                $mCloneFromBonusId = $aBonusses[$p_oParameters->bonustype];
            }

            $aBonusType = (!empty($mCloneFromBonusId) ? $this->_m_oBonuses->getBonus($mCloneFromBonusId) : null);
            
            // did we found the bonus to use as source to clone
            if (!empty($aBonusType)) {
    
                // what is the reward for this bonus
                $iReward = (!empty($p_oParameters->reward) ? $p_oParameters->reward : $aBonusType['reward']);
    
                // define the bonus name for this new bonus
                $sBonusName = $iReward . ' Free spins ' . $aGameData['game_name'] . ' ' . $p_oParameters->bonustype;
    
                // check if the cloned bonus type does exist already and if so we don't create it again.
                $aBonus = $this->_m_oBonuses->getBonusByBonusName($sBonusName);
    
                if (empty($aBonus)) {
                    // it doesn't exist so create the new bonus type
                    unset($aBonusType['id']);
                    $aBonusType['bonus_name'] = $sBonusName;
                    $aBonusType['bonus_tag'] = $this->getGpName();
                    $aBonusType['reward'] = $iReward;
                    $aBonusType['ext_ids'] = $aGameData['ext_game_name'];
                    $aBonusType['game_id'] = $aGameData['game_id'];
                    $aBonusType['frb_coins'] = (isset($p_oParameters->frb_coins) ? $p_oParameters->frb_coins : $aBonusType['frb_coins']);
                    $aBonusType['frb_denomination'] = (isset($p_oParameters->frb_denomination) ? $p_oParameters->frb_denomination : $aBonusType['frb_denomination']);
                    $aBonusType['frb_lines'] = (isset($p_oParameters->frb_lines) ? (int)$p_oParameters->frb_lines : $aBonusType['frb_lines']);
                    $aBonusType['frb_cost'] = ($aGameData['min_bet'] * $iReward);
                    $aBonusType['expire_time'] = '2100-12-31';
                    $r = $this->_m_oSQL->insertArray('bonus_types', $aBonusType);
                    if($r !== false){
                        $aBonus = $this->_m_oBonuses->getBonusByBonusName($sBonusName);
                    } else {
                        // creating new bonus type failed
                        return $this->_getError(self::ER23);
                    }
                } else {
                    // it does exist
                    if (isset($p_oParameters->disable) && $p_oParameters->disable === 'true') {
                        // we need to disable the bonus type so we can create another one
                        $r = $this->_m_oSQL->updateArray('bonus_types', array_merge($aBonus, array('expire_time' => '2000-12-31')), array('id' => (int)$aBonus['id']));
                        if($r !== false){
                            $aBonus['expire_time'] = '2000-12-31';
                        } else {
                            // updating bonus type failed
                            return $this->_getError(self::ER24);
                        }
                    } else {
                        if (isset($p_oParameters->disable) && $p_oParameters->disable === 'false') {
                            // we need to re-enable the bonus type again
                            $r = $this->_m_oSQL->updateArray('bonus_types', array_merge($aBonus, array('expire_time' => '2100-12-31')), array('id' => (int)$aBonus['id']));
                            if($r !== false){
                                $aBonus['expire_time'] = '2100-12-31';
                            } else {
                                // updating bonus type failed
                                return $this->_getError(self::ER24);
                            }
                        }
                    }
                }
                $this->_m_aNewBonusType = $aBonus;
                return true;
            } else {
                // no bonus type found to clone from
                return $this->_getError(self::ER22);
            }
        } else {
            // game is not found, game should be inserted first
            return $this->_getError(self::ER10);
        }
    }
    
    /**
     * Insert a game into database/shards
     * @example {
     *  skinid: required,
     *  target: <target:optional(mobile|both|desktop)>
     * }
     * @param stdClass $p_oParameters
     * @return bool|array true on success error otherwise
     */
    protected function _insertGame(stdClass $p_oParameters = null){
        // first check if we have the game already in the db and if not insert it
        $this->_m_mGameData = $this->_m_oMicroGames->getByGameRef($this->getGamePrefix() . $p_oParameters->skinid, (isset($p_oParameters->target) ? (($p_oParameters->target === 'mobile') ? 1 : 0 ) : 0));
        
        if (empty($this->_m_mGameData)) {
            $r = $this->_m_oSQL->insertArray('micro_games', array(
                'game_name' => $this->getGpName() . ' ' . preg_replace('/[^\da-z]/i', ' ', $p_oParameters->skinid),
                'html_title' => strtolower(($p_oParameters->target !== 'mobile') ? '#game.meta.title.' . $this->getGpName() . '-' . preg_replace('/[^\da-z]/i', '-', $p_oParameters->skinid) : ''),
                'meta_descr' => strtolower(($p_oParameters->target !== 'mobile') ? '#game.meta.descr.' . $this->getGpName() . '-' . preg_replace('/[^\da-z]/i', '-', $p_oParameters->skinid) : ''),
                'tag' => 'videoslots',
                'game_id' => $this->getGamePrefix() . preg_replace('/[^\da-z]/i', '_', $p_oParameters->skinid),
                'languages' => 'sv,en,fi,no,de,es',
                'ext_game_name' => $this->getGamePrefix() . $p_oParameters->skinid,
                'client_id' => 0,
                'game_url' => preg_replace('/[^\da-z]/i', '-', $p_oParameters->skinid) . '-' . $this->getGpName(),
                'operator' => ucfirst($this->getGpName()),
                'network' => $this->getGpName(),
                'active' => ((strpos($p_oParameters->skinid, 'system') !== false) ? 0 : 1),
                'device_type' =>  ((isset($p_oParameters->target) && $p_oParameters->target === 'mobile' ) ? 'html5' : 'flash' ),
                'device_type_num' => ((isset($p_oParameters->target) && $p_oParameters->target === 'mobile' ) ? 1 : 0 ),
                'multi_channel' => ((isset($p_oParameters->target) && $p_oParameters->target === 'both' ) ? 1 : 0 ),
            ));
            if($r !== false){
                $this->_insertGame($p_oParameters);
            } else {
                return $this->_getError(self::ER23);
            }
        }
        return true;
    }
    
    /**
     * Delete a free spin bonus entry from the bonus_entries table by bonus entries ID
     * @example {
     *  playerid: <userid:required>,
     *  id: <int:required>
     * }
     * @param stdClass $p_oParameters
     * @return bool true on success false otherwise
     */
    protected function _deleteFrb(stdClass $p_oParameters){
        if(isset($p_oParameters->playerid) && isset($p_oParameters->id) && ctype_digit($p_oParameters->id)) {
            return $this->_m_oBonuses->deleteBonusEntry(array('id' => (int)$p_oParameters->id, 'user_id' => (int)$p_oParameters->playerid));
        }
        return false;
    }
    
    /**
     * Process the response for wallet requests coming from videoslots it self and not those from a GP.
     * @param $p_mResponse
     */
    protected function _responseWallet($p_mResponse)
    {
        
        $a = array('result' => (($p_mResponse !== true) ? 'failed' : 'success'));
        
        if($p_mResponse === true){
            switch($this->_getMethod()){
                case '_insertGame':
                    $a['data'] = $this->_getGameData();
                    break;
                
                case '_createFrb':
                    $a['data'] = $this->getNewBonusEntry();
                    break;
                
                case '_cloneBonusTypes':
                    $a['data'] = $this->_getNewBonusType();
                    break;
            }
            
            $p_mResponse = array(
                'responsecode' => 200,
                'status' => '',
            );
        } else {
            $a['error'] = $p_mResponse;
        }
        
        $this->_setResponseHeaders($p_mResponse);
        $this->_logIt([__METHOD__, $this->_getMethod(), print_r($a,true)]);
        echo json_encode($a);
        die;
    }
    
    /**
     * Get the bonus entry ID which was created by createFrb()
     * @return array either with id or error report
     */
    protected function getNewBonusEntry()
    {
        return $this->_m_aNewBonusEntry;
    }
    
    /**
     * Get the bonus type ID that was newly created by cloning from existing one
     * @return array either with id or error report
     */
    protected function _getNewBonusType()
    {
        return $this->_m_aNewBonusType;
    }
    
    /**
     * Get the game data array
     * @return array either with game data or error report
     */
    protected function _getGameData()
    {
        return $this->_m_mGameData;
    }
    
    /**
     * Get the player data array
     * @return array
     */
    protected function _getUserData()
    {
        return $this->_m_mUserData;
    }
    
    /**
     * Execute wallet actions used during testing and not triggered by GP
     * @return void
     */
    protected function _setWalletActions(){
    
        $this->_m_sInputStream = file_get_contents('php://input');
        $this->_logIt([__METHOD__, print_r($_POST, true), print_r($_GET,true), print_r($_REQUEST,true), $this->_m_sInputStream]);

        $oData = json_decode($this->_m_sInputStream, false);
        
        if ($this->getSetting('log_errors') === true && isset($_GET['wallet'])) {
            
            $this->_m_bForceHttpOkResponse = false;
            $this->_logIt([__METHOD__, print_r($this->_getMappedGpMethodsToWalletMethods(), true)]);
            // $this->_getMappedGpMethodsToWalletMethods() will not return the
            // $this->>_m_aMapWalletMethods key:value pairs if log_errors === false.
            // this to avoid those methods can be executed on production
            if (array_key_exists($_GET['command'], $this->_getMappedGpMethodsToWalletMethods())) {

                $sMethod = $this->getWalletMethodByGpMethod($_GET['command']);
                $this->_logIt([__METHOD__, $sMethod]);
                $this->_setWalletMethod($sMethod);
                if (property_exists($oData, 'parameters')) {
                    $result = $this->$sMethod($oData->parameters);
                    $this->_logIt([__METHOD__, print_r($oData->parameters,true)]);
                } else {
                    $result = $this->$sMethod();
                }
                if (method_exists($this, '_responseWallet')) {
                    $this->_logIt([__METHOD__, '_responseWallet exists', print_r($result,true)]);
                    $this->_responseWallet($result);
                }
            } else {
                // method to execute not found
                $this->_logIt([__METHOD__, 'method to execute not found']);
                $this->_setResponseHeaders($this->_getError(self::ER02));
            }
            die;
        }
    }
    
    /**
     * Get the bonus_bet code to be stored which indicates either what kind of freespin the bet was based on
     * or if it's not a freespin, the bet was made with cash- or bonus balance.
     * @return int
     */
    protected function _getBonusBetCode()
    {
        if ($this->_m_bIsFreespin === false) {
            // $this->bonus_bet => is created in playChgBalance
            // bonus_bet === 0 means played with users:cash_balance
            // bonus_bet === 1 means played with bonus_entries:bonus_balance
            return $this->bonusBetType();
        } else {
            if($this->_getMethod() === '_bet') {
                return $this->_getBonusBetAwardTypeCode();
            } else {
                // it's a freespin win so we return always the same value
                return self::FREESPIN_REWARD;
            }
        }
    }
    
    /**
     * Get the award_type code to be stored which indicates:
     * - to what kind of freespin bet to win relates to or
     * - it relates to a jackpot win or
     * - it relates to a normal win
     *
     * @param \stdClass $p_oParameters
     * @return int
     */
    protected function _getAwardTypeCode(stdClass $p_oParameters)
    {
        if ($this->_m_bIsFreespin === false) {
            return ((isset($p_oParameters->jpw) && $p_oParameters->jpw > 0) ? 4 : 2);
        } else {
            return $this->_getBonusBetAwardTypeCode();
        }
    }

    public function getInsertedBetId(){
        return $this->_m_iWalletTxnBets;
    }

    public function getInsertedWinId(){
        return $this->_m_iWalletTxnWins;
    }

    public function getInsertedTxnId(){
        return $this->getInsertedBetId() ?? $this->getInsertedWinId();
    }

    /**
     *  Process a player bet
     * Returns an error if the game does not exist or is disabled.
     *
     * @param stdClass $p_oParameters Json object with the parameters received from gaming provider
     * @return bool|array true on success or array with error details
     */
    protected function _bet(stdClass $p_oParameters, bool $sessionBalance = true)
    {
        $iAmount = $this->_m_iGpAmountBets = $p_oParameters->amount;
        $iRoundId = $p_oParameters->roundid;
        $this->_m_iGpTxnBets = $p_oParameters->transactionid;
        $this->_m_iGpDateBets = (isset($p_oParameters->transactiondate) ? $p_oParameters->transactiondate : null);
        $bonusBetAmount = 0;
        
        if ($this->_m_bIsFreespin === true && empty($this->_m_aFreespins)) {
            // freespins bet requested but non are left
            return $this->_getError(self::ER17);
        }
        
        // has the transaction been cancelled before
        if (!empty($this->_getTransactionById($p_oParameters->transactionid, self::TRANSACTION_TABLE_BETS, true))) {
            return $this->_getError(self::ER04);
        }
        
        // check if we have already a bet with the same transaction ID
        $result = $this->_getTransactionById($p_oParameters->transactionid, self::TRANSACTION_TABLE_BETS);
        
        if (!empty($result)) {
            
//            $this->_m_iWalletTxnBets = $result['id'];
//            $this->_m_iWalletAmountBets = $result['amount'];
//            $this->_m_iWalletDateBets = $result['created_at'];
            
            if ($iAmount == $result['amount']) {
                // duplicate transaction ID with same amount
                return $this->_getError(self::ER18);
            } else {
                // duplicate transaction ID
                return $this->_getError(self::ER05);
            }
        }

        // or the amount is not empty (which should not if its not a frb)
        // or we enforce a bet (happens when a bet is cancelled but it didn't exist)
        // or it's a freespin and the frb are send 1 by 1 and frw requires a confirmed frb
        if (
            //!empty($iAmount) ||
            // some gp send not frb-amount 0 but the actual frb amount, so frb was still inserted when $this->_m_bConfirmFrbBet === false
            ($this->_m_bIsFreespin === false && !empty($iAmount)) ||
            $this->_m_bForceBet === true ||
            ($this->_m_bIsFreespin === true && $this->_m_bConfirmFrbBet === true) ||
            ($this->_m_bIsFreespin === false && $this->_m_bConfirmZeroAmountBet === true)
        ) {

            if (!$this->allowWalletMethodForGame($this->_m_sMethod, $this->_m_mGameData)) {
                return $this->_getError(self::ER06);
            }

            if($sessionBalance) {
                $balance = $this->_getBalance();
            }else{
                $balance = $this->_getBalance([],[],true);
            }

            // Get the fresh user data to avoid bonus_bet set to 1, when the `cash_balance` in _m_mUserData is not fresh
            $this->_m_mUserData = cu($this->_m_mUserData['id'])->getData() ?? $this->_m_mUserData;

            $this->_m_bForceBet = false;

            if ($this->_m_bIsFreespin === false && $balance <= 0) {
                return $this->_getError(self::ER06);
            }

            $jp_contrib = (!empty($p_oParameters->jpc) ? $p_oParameters->jpc : 0);

            $balance = $this->lgaMobileBalance($this->_m_mUserData, $this->_m_mGameData['ext_game_name'], $balance,
                $this->_m_mGameData['device_type'], (($this->_m_bIsFreespin === false) ? $iAmount : 0));

            // some GP do send amount in FRB we dont want an error on those.
            if ($this->_m_bIsFreespin === false && $balance < $iAmount) {
                return $this->_getError(self::ER06);
            }

            if ($this->_m_bIsFreespin === false) {
                // we playChgBalance() first because it's safer, since we deduct money first on the bet we won't "miss" any deductions
                // if the insertBet below doesn't happen and we get a retry, the player will complain of course but then we investigate and credit back the cash.
                $balance = $this->playChgBalance($this->_m_mUserData, "-$iAmount", $iRoundId, 1);
                if ($balance === false) {
                    return $this->_getError(self::ER06);
                }
            } else {
                
                $aFreespins = $this->_getFreespinData();
                
                // Check if the coin value from the GP matches with the one from the db, otherwise it could result in a high Win
                if (empty($aFreespins) || (!empty($aFreespins) && $iAmount > 0 && $iAmount != $this->_getFreespinValue())) {
                    $this->_logIt([
                        __METHOD__,
                        'bet amount mismatch: ' . $iAmount . ' : ' . $this->_getFreespinValue()
                    ]);
                    if ($this->_isInhouseFrb()) {
                        // inform the player about not altering the bet amount and return insuff-funds to gp
                        phive('Casino')->wsInhouseFrb($this->_m_mUserData['id'], $this->_m_mUserData['preferred_lang'],
                            'frb.amount-msg.html',
                            array_merge(
                                $this->_m_mGameData,
                                array(
                                    'frb_amount' => $this->_getFreespinValue()
                                )
                            )
                        );
                        return $this->_getError(self::ER06);
                    } else {
                        return $this->_getError(self::ER13);
                    }
                } else {
                    // We save the value of a free spin so we can send it to history messages and use for reporting
                    $bonusBetAmount = $iAmount;
                    // it's a free spin so we 0 the amount and save the bet so we have the txnId to check later if the bet exists in case we receive a win
                    $iAmount = $jp_contrib = $bonus_bet = 0;

                }
            }

            $bonus_bet = $this->_getBonusBetCode();

            $this->attachPrefix($p_oParameters->transactionid);
            
            $GLOBALS['mg_id'] = $p_oParameters->transactionid;

            // If GP is sending a roundid as alfanumeric chars, set it to 0 as database only except integers
            // This will be used ONLY for the insertion of the WIN table.
            $iRoundId_bet = $iRoundId;
            $this->_m_iWalletTxnBets = $this->insertBet($this->_m_mUserData, $this->_m_mGameData, $iRoundId_bet,
                $p_oParameters->transactionid, $iAmount, $jp_contrib, $bonus_bet, $balance, '', (int) $bonusBetAmount);

            if($this->doConfirmByRoundId() === true) {
                // if check_bet flag is true then we are checking in the rounds table,
                // hence the round table should be updated with the win id value appropriately
                $this->attachPrefix($iRoundId);
                $this->insertRound($this->_m_mUserData['id'], $this->_m_iWalletTxnBets, $iRoundId);
            }

            if ($this->_m_iWalletTxnBets === false) {
                return $this->_getError(self::ER01);
            } else {
                $this->_m_iWalletAmountBets = $iAmount;
                $this->_m_iWalletDateBets = date('Y-m-d H:i:s', time());
            }
            
            //FRB logic this will trigger FRB progress when there is a wager/deposit requirement
            // Make sure that when the last FRB request is send by the GP they also send a FRW request (or other hook), even with amount 0
            // so $this->_handleFspinWin() will be executed which is required to update to bonus_entries status correctly.
            $balance = $this->betHandleBonuses($this->_m_mUserData, $this->_m_mGameData, $iAmount, $balance, $bonus_bet,
                $iRoundId_bet, $p_oParameters->transactionid);
        }
        
        // reduce the frb_remaining if its a frb
        if ($this->_m_bIsFreespin && !empty($this->_m_aFreespins) && $this->_m_bFrwSendPerBet === true && $this->_m_bInhouseFrbCounter === false) {
            
            if ($this->_m_aFreespins['frb_remaining'] > 0) {
                if ($this->_handleFspinBet($this->_m_mUserData['id'], $this->_m_aFreespins['id']) === false) {
                    return $this->_getError(self::ER01);
                }
            } else {
                return $this->_getError(self::ER12);
            }
            
        }
        
        return true;
    }
    
    /**
     * Update the bonus entries status after all freespins have been played and a final FRW request is send.
     * @param int $amount
     * @param array $e
     * @param string $descr
     * @return bool
     */
    protected function _handleFspinWin($amount = 0, $e = null, $descr = 'Freespin win', $free_spin_id = NULL)
    {
        // set the property again with updated data as it might be needed in the response method
        $this->_m_aFreespins = $this->_getBonusEntryBy($this->_m_mUserData['id'], $free_spin_id ?? $this->_m_oRequest->freespin->id);
        
        if ($e === null) {
            $e = phive('Bonuses')->getBonusEntry($free_spin_id ?? $this->_m_oRequest->freespin->id, $this->_m_mUserData['id']);
        }

        $minimum_fs_wager = (int) licSetting('minimum_fs_wager', cu($this->_m_mUserData['id'])) * 100;
        if (!empty($minimum_fs_wager) && !empty($this->_m_aFreespins)) {
            $this->_m_aFreespins['rake_percent'] = max($minimum_fs_wager, $this->_m_aFreespins['rake_percent']);
        }

        if (empty($e) || ($this->_m_aFreespins['rake_percent'] > 0 && $e['status'] === 'approved')) {
            //phive()->dumpTbl($this->getGpName() . '_frbwin_failure', $e . " Type: ext bonus id connection failure or already approved: " . $e['id'], $this->_m_mUserData);
            if (empty($e)) {
                return false;
            }
            $this->_logIt([__METHOD__, $e . " Type: ext bonus id connection failure or already approved: " . $e['id']], $this->getGpName() . '-frbwin-failure', true);
        }
        
        // needed for inhouse frb
        $e['gpname'] = $this->getGpName();
        $e['gamedata'] = $this->_m_mGameData;
        // we also check if cost hasnt been calculated yet in case we get the same request we dont want to calculate again
        // which would update the entry.
        if ($e['cost'] <= 0 && ($this->_m_bFrwSendPerBet === false || ($this->_m_bFrwSendPerBet === true && $e['frb_remaining'] <= 0)) || $this->_m_bInhouseFrbCounter === true) {
            if ($this->_m_bFrwSendPerBet === true) {
                // if _m_bFrwSendPerBet === true than the winning is added to the bonus_entries::balance per request so we
                // need to send the $e['balance'] to 0 and the $amount is added to 0 again below as the winning amount
                // each freespin win has been added to bonus_entries::balance straight after each freespin bet.
                // So when freespins are finished this balance represents the total winning
                $tmp_balance = $e['balance'];
                $e['balance'] = 0;
                $amount = $tmp_balance;
            } else {
                $e['balance'] = 0;
            }
            
            $this->_logIt([__METHOD__, $amount, print_r($e, true)]);

            if (empty($amount)) {
                $this->_logIt([__METHOD__, 'Free spin bonus without winnings']);
                $device_type = phive()->isMobile() ? 1 : 0;
                $bonus_type = phive('Bonuses')->getBonus($e['bonus_id']);

                phive()->pexec('Casino', 'propagateFreeSpinsBets', [$e['id'], $e['user_id']]);
                phive()->pexec('Casino', 'propagateFreeSpinsEndSession', [$e['user_id'], $bonus_type['game_id'], $device_type, strtotime($e['activated_time']), time()]);

                phive('Bonuses')->fail($e, 'Free spin bonus without winnings');

                $this->extSessionFreespinsFinished(cu($this->_m_mUserData['id']));
            } else {
                if (
                    ($this->_m_aFreespins['rake_percent'] > 0 && $e['status'] != 'active') || // bonus with wager or deposit requirement
                    ($this->_m_aFreespins['rake_percent'] <= 0 && $e['status'] != 'approved') // bonus without wager or deposit requirement
                ) {
                    $this->_logIt([__METHOD__, "Forfeited $amount cents due to failed FRB bonus."]);
                    phive('UserHandler')->logAction($e['user_id'], "Forfeited $amount cents due to failed FRB bonus.",
                        $this->getGpName() . '-frb-fail');

                    $this->extSessionFreespinsFinished(cu($this->_m_mUserData['id']));
                } else {
                    $this->_logIt([
                        __METHOD__,
                        'handle the frw',
                        'username:' . $this->_m_mUserData['id'],
                        $descr,
                        $e,
                        $amount           
                    ]);
                    $this->handleFspinWin($e, $amount, $this->_m_mUserData['id'], $descr);
                }
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * Alter the total frb_remaining the user has left after the user placed 1 FRB
     * This method should be called by the bet method each time a FRB occurs or when a cancel of frb happens
     * @param int $p_iUserId
     * @param int $p_iBonusEntryId
     * @param bool $p_bDeduct
     * @return bool
     */
    protected function _handleFspinBet($p_iUserId, $p_iBonusEntryId, $p_bDeduct = true)
    {
        $frbAction = (($p_bDeduct === false) ? '+' : '-');
        $result = phive('SQL')->sh($p_iUserId, '',
            'bonus_entries')->query("
        UPDATE bonus_entries
        SET frb_remaining = frb_remaining " . $frbAction . " 1
        WHERE id = " . phive("SQL")->escape($p_iBonusEntryId) . "
        AND user_id = " . phive("SQL")->escape($p_iUserId) . "
        AND bonus_type = 'freespin' LIMIT 1");
        // set the property as it might be needed in the response method
        $this->_m_aFreespins = $this->_getBonusEntryBy($p_iUserId, $p_iBonusEntryId);
        if ($this->_isInhouseFrb() && $this->_m_aFreespins['frb_remaining'] > -1) {
            // inform the player about remaining inhouse frb in the progress bar shown under the game
            phive('Casino')->wsInhouseFrb($this->_m_mUserData['id'], $this->_m_mUserData['preferred_lang'],
                'frb.remaining-msg.html',
                array_merge(
                    $this->_m_mGameData,
                    array(
                        'frb_remaining' => $this->_m_aFreespins['frb_remaining'],
                        'frb_granted' => $this->_m_aFreespins['frb_granted']
                    )
                )
            );
        }
        return $result;
    }
    
    /**
     *  Do a player win and get the player balance after it
     * Returns an error if the game does not exist. No error is returned if the game is disabled
     * because win requests can be sent days later and the game might not be active anymore.
     *
     * @param stdClass $p_oParameters A json object with the parameters received from gaming provider
     * @return bool|array true on success or array with error details
     */
    protected function _win(stdClass $p_oParameters)
    {
        $iAmount = $this->_m_iGpAmountWins = $p_oParameters->amount;
        $iRoundId = $p_oParameters->roundid;
        $this->_m_iGpDateWins = (isset($p_oParameters->transactiondate) ? $p_oParameters->transactiondate : null);
        $this->_m_iGpTxnWins = $p_oParameters->transactionid;
        
        // check if we have already a win with the same transaction ID
        $result = $this->_getTransactionById($p_oParameters->transactionid, self::TRANSACTION_TABLE_WINS);
        
        if (!empty($result)) {
            if ($iAmount == $result['amount']) {
                return $this->_getError(self::ER18);
            } else {
                return $this->_getError(self::ER05);
            }
        }
        
        $this->frb_win = $this->_m_bIsFreespin;
        
        if (!empty($iAmount)) {
            // if it's a freespin round, the frb comes in 1 by 1 and we need to confirm a corresponding bet txnId
            // hasBet will check by roundID or by transactionID if these are the same for win and bet. Set $this->_m_bByRoundId accordingly
            if ($this->_m_bIsFreespin === true) {
                if ($this->_m_bFrwSendPerBet === true && $this->_m_bConfirmFrbBet === true && $this->_hasBet($p_oParameters) === false) {
                    return $this->_getError(self::ER14);
                }
                // we check if this win has a registered bet (This check nothing to do with freespins)
            } else {
                if ($this->_m_bConfirmBet === true && $this->_hasBet($p_oParameters) === false) {
                    return $this->_getError(self::ER19);
                }
            }

            $this->attachPrefix($p_oParameters->transactionid);

            // we insertWin first so if the playChgBalance() below doesn't happen we have the win and won't double credit repeatedly
            if (
                $this->_m_bIsFreespin === false ||
                ($this->_m_bIsFreespin === true && $this->_m_bFrwSendPerBet === true && $this->_m_aFreespins['frb_remaining'] >= 0)
            ) {

                // If GP is sending a roundid as alfanumeric chars, set it to 0 as database only except integers
                // This will be used ONLY for the insertion of the WIN table.
                $iRoundId_win = $iRoundId;
                $this->attachPrefix($iRoundId);

                // check if we have a bet
                if ($this->doConfirmByRoundId() === true){
                    $confirm = $this->confirmWin($this->_m_mUserData['id'], $iRoundId);

                    if ($confirm === false) {
                        phive()->dumpTbl('bet-not-found', [$this->_m_mUserData['id'], $iRoundId]);
                        return $this->_getError(self::ER19);
                    }
                }

                if (!$this->allowWalletMethodForGame($this->_m_sMethod, $this->_m_mGameData)) {
                    return $this->_getError(self::ER10);
                }


                $user_data = cu($this->_m_mUserData)->getData(); // get fresh user data
                $balance = $this->_getBalance($user_data, $this->_m_mGameData); // cash_balance + bonus_balance;

                // we only insert win if not a freespin
                // or when a freespin but the remaining_frb is 0 or more
                $this->_m_iWalletTxnWins = $this->insertWin($this->_m_mUserData, $this->_m_mGameData,
                    $balance, $iRoundId_win, $iAmount, $this->_getBonusBetCode(),
                    $p_oParameters->transactionid, $this->_getAwardTypeCode($p_oParameters),null);
            }

            if($this->doConfirmByRoundId() === true) {
                // if check_bet flag is true then we are checking in the rounds table,
                // hence the round table should be updated with the win id value appropriately
                // if there is no round against this win id then we need to insert one before we update it
                // this is to handle use case happens when, we received a jackpot win, and a win consecutively
                // what happens in this case is that the existing round gets updated because jackpot win, so when normal win request comes there is no unfinished round
                // which means updateRound() function just returns false, in order to solve this we add an entry in rounds table for this win
                // this should also solve the issue where wins are coming as a part of free spins for which a bet was made
                $round = $this->getLastUnfinishedRound($this->_m_mUserData['id'], $iRoundId);
                if(!$this->_isTournamentMode && empty($round)){

                    //get last finished round, we need it to get bet_id
                    $last_finished_round = $this->getLastFinishedRound($this->_m_mUserData['id'], $iRoundId);
                    if($last_finished_round){
                        $this->insertRound($this->_m_mUserData['id'], $last_finished_round['bet_id'], $iRoundId);
                    }
                }

                $this->updateRound($this->_m_mUserData['id'], $iRoundId, $this->_m_iWalletTxnWins);
            }

            if ($this->_m_iWalletTxnWins === false) {
                return $this->_getError(self::ER01);
            } else {
                $this->_m_iWalletAmountWins = $iAmount;
                $this->_m_iWalletDateWins = date('Y-m-d H:i:s', time());
                if ($this->_m_bIsFreespin === true && $this->_m_iWalletTxnWins !== null) {
                    // win was inserted succesfully (we have the inserted new ID) and its a freespin so lets update the freespin won amount by increasing the bonus_entries balance
                    phive('SQL')->incrValue('bonus_entries', 'balance', ['id' => (int)$this->_m_oRequest->freespin->id], $iAmount, [], $this->_m_mUserData['id']);
                    phive('Bonuses')->resetEntries();
                }
            }
            
            // if a freespin we don't want to change user cash balance with the winning only the bonus_entries::balance.
            $balance = (($this->_m_bIsFreespin === true) ? $this->_getBalance() : $this->playChgBalance($this->_m_mUserData,
                $iAmount, $iRoundId_win, 2));
            
            $this->handlePriorFail($this->_m_mUserData, $p_oParameters->transactionid, $balance,
                $iAmount);

        } else { // empty amount win
            if ($this->doConfirmByRoundId() && $this->finishRoundOnEmptyWin) {
                $this->attachPrefix($iRoundId);
                $this->updateRound($this->_m_mUserData['id'], $iRoundId);
            }
        }
        
        // the last FRB should be followed up with a last FRW by the GP even if the win amount is 0 so the status can be updated
        if ($this->_m_bUpdateBonusEntriesStatusByWinRequest === true) {
            $this->_handleFspinWin($iAmount);
        }
        
        return true;
    }
    
    /**
     * Cancel a player bet or a win by transaction ID
     *
     * @param stdClass $p_oParameters An json object with the parameters received from gaming provider
     * @return bool|array true on success or array with error details
     */
    protected function _cancel(stdClass $p_oParameters)
    {

        $bHasTransaction = $bIsCancelled = false;
        
        foreach ($this->_TRANSACTION_TABLES as $sTable) {
            
            $sTransactionTable = $this->_getTransactionTable($sTable);
            
            // has the transaction been cancelled before
            if(!empty($this->_getTransactionById($p_oParameters->transactionid, $sTable, true))){
                $bIsCancelled = true;
                $this->_m_sCancelTbl = $sTable;
                // set these only when transaction actually has been cancelled, otherwise results in wrong cancel amount on 1 request with multi cancel (bet and win)
                $this->{'_m_iGpAmount' . $sTransactionTable} = (isset($p_oParameters->amount) ? $p_oParameters->amount : null);
                $this->{'_m_iGpTxn' . $sTransactionTable} = $p_oParameters->transactionid;
                $this->{'_m_iGpDate' . $sTransactionTable} = (isset($p_oParameters->transactiondate) ? $p_oParameters->transactiondate : null);
            }
            
            //$this->_m_iWalletTxnBet and $this->_m_iWalletTxnWin are set in the foreach loop inside the cancel method by $this->_getTransactionById()
            if ($bIsCancelled === true && $sTable === self::TRANSACTION_TABLE_WINS) {
                // we only return true here because the win can have the same txnId as the bet
                // and if the bet has it the win would not be cancelled otherwise
                return $this->_getError(self::ER28);
            }
            
            // does this transactionid exists in the db?
            $aResult = $this->_getTransactionById($p_oParameters->transactionid, $sTable);

            if (!empty($aResult) && ($aResult['amount'] > 0 || $this->_m_bIsFreespin === true)) {
                
                // we found the transactionid
                $bHasTransaction = true;

                $this->attachPrefix($p_oParameters->transactionid);

                $sPrefixedTransactionId = $p_oParameters->transactionid;

                // do the received transaction details match with the transaction details from the db
                if (
                    (isset($p_oParameters->amount) && sha1($aResult['amount'] . $aResult['mg_id']) === sha1($p_oParameters->amount . $sPrefixedTransactionId))
                    || (!isset($p_oParameters->amount) && sha1($aResult['mg_id']) === sha1($sPrefixedTransactionId))
                ) {

                    if ($sTable === self::TRANSACTION_TABLE_BETS) {
                        $iType = 7;
                        $amount = $aResult['amount'];
                    } else {
                        $iType = 1;
                        $amount = -$aResult['amount'];
                    }

                    // we do the rollback in bets/wins table here
                    if ($this->doRollbackUpdate($sPrefixedTransactionId, $sTable, $this->_m_mUserData['cash_balance'], $amount) === false) {
                        // it failed but we return true anyhow so GP will not send the cancel request anymore like what happened with stakelogic in the past
                        return true;//$this->_getError(self::ER01);
                    } else {
                        if (
                            $sTable === self::TRANSACTION_TABLE_BETS && // we only want to execute frb_remaining + 1 ones as we are in a foreach loop
                            $this->_m_bConfirmFrbBet === true && // the frb must exist in the bets table
                            $this->_m_bIsFreespin === true // the request is based on existing bonus_entry
                        ) {
                            $this->_handleFspinBet($this->_m_mUserData['id'], $this->_m_aFreespins['id'], false);
                        } else {
                            // if a freespin win it will be cancelled
                            // we update only the user cash balance of the $this->doRollbackUpdate() was successfull
                            // if instead $this->doRollbackUpdate() fails that we will have to cancel the bet manually
                            // which is better than that the GP keeps on sending cancel requests and wallet gets refunded all the time
                            $this->playChgBalance($this->_m_mUserData, $amount, $aResult['trans_id'], $iType);
                        }
                        $this->_m_sCancelTbl = $sTable;
                        $this->{'_m_iGpAmount' . $sTransactionTable} = (isset($p_oParameters->amount) ? $p_oParameters->amount : null);
                        $this->{'_m_iGpTxn' . $sTransactionTable} = $p_oParameters->transactionid;
                        $this->{'_m_iGpDate' . $sTransactionTable} = (isset($p_oParameters->transactiondate) ? $p_oParameters->transactiondate : null);
                        
                        if ($sTable === self::TRANSACTION_TABLE_WINS) {
                            // without this check, bet is cancelled but a win wouldn't as the foreach will be broken of the return
                            return true;
                        }
                    }
                } else {
                    // transactionID doesn't match
                    return $this->_getError(self::ER07);
                }
            }
        }
        
        // transaction was not found in foreach loop
        if ($bHasTransaction === false) {
            // transactionID doesn't exist, the bet has never arrived on our server, maybe because it timed-out,
            // so we insert the bet with amount 0 to avoid that it will be processed on a later moment again if the bet request goes through.
            // If /bet request times-out (so our server doesn't answer it) a /win request will never be send by the GP
            $p_oParameters->amount = 0;
            $p_oParameters->transactionid = $p_oParameters->transactionid . 'ref';
            $this->_m_bForceBet = true;
            $this->_bet($p_oParameters);
            return $this->_getError(self::ER08);
        }
        return true;
    }
    
    /**
     * Get the freespin bet value calculated by the frb_denomination, frb_lines and frb_coins
     *
     * @param int $p_iUserId The user ID
     * @param mixed $p_mId The game ID (with GP prefix) or the BonusEntryId
     * @param bool $mc
     * @return int the amount in cents
     */
    protected function _getFreespinValue($p_iUserId = null, $p_mId = null, $mc = true)
    {
        
        if (!$this->_isFreespin()) {
            if (empty($p_iUserId)) {
                $p_iUserId = $this->_m_oRequest->playerid;
            }
            
            if (empty($p_mId)) {
                $p_mId = $this->getGamePrefix() . $this->_m_oRequest->skinid;
            }
        }
        
        $aFreespins = $this->_isFreespin() ? $this->_getFreespinData() : $this->_getBonusEntryBy($p_iUserId, $p_mId);

        $ud = ud($aFreespins['user_id']);
        
        $iCoinValueCts = $aFreespins['frb_denomination'];
        $iCoins = $aFreespins['frb_coins'];
        $iLines = $aFreespins['frb_lines'];
        
        if (!empty($iCoins)) {
            $iCoinValueCts = $iCoinValueCts * $iCoins;
        }
        
        if (!empty($iLines)) {
            $iCoinValueCts = $iCoinValueCts * $iLines;
        }
        
        $this->_logIt([
            __METHOD__,
            'userID ' . $p_iUserId,
            'paramID ' . $p_mId,
            'freespin ' . print_r($aFreespins, true),
            'frb_denomination ' . $iCoinValueCts,
            'frb_coins ' . $iCoins,
            'frb_lines ' . $iLines
        ]);

        return ($mc) ? mc($iCoinValueCts, $ud['currency'], 'multi', false) : $iCoinValueCts;
    }
    
    /**
     * Strip the postfix from a string
     *
     * @param string $p_mGameId
     * @return string
     */
    protected function _stripPostfix($p_mGameId)
    {
        return str_replace('@' . strtoupper(substr($this->getGamePrefix(), 0, -1)), '', $p_mGameId);
    }
    
    /**
     * Get an instance of the PHP DateTime class
     * @param null $p_sDateTime Any valid strtotime() string
     * @link http://php.net/manual/en/class.datetime.php
     * @return DateTime
     */
    protected function _getDateTimeInstance( $p_sDateTime = null)
    {
        $sDateTime = (empty($p_sDateTime) ? null : date('U.u', strtotime($p_sDateTime)));
        $now = DateTime::createFromFormat('U.u',(empty($sDateTime) ? number_format(microtime(true), 6, '.', '') : $sDateTime) );
        return $now->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    }
    
    /**
     * Get an error by it's key
     *
     * @param string $p_sKey The constant key eg. ER{XX}
     * @return mixed false if error was not found
     */
    protected function _getError($p_sKey)
    {
        if (isset($this->_m_aErrors[$p_sKey])) {
            $aMessage = $this->_m_aErrors[$p_sKey];
            if (self::ER06 == $p_sKey && !empty(t('insufficient.funds'))) {
                // overwrite message with a localized string
                $aMessage['message'] = t('insufficient.funds');
            }
            if (isset($aMessage['return']) && $aMessage['return'] !== 'default') {
                return $aMessage['return'];
            }
            unset($aMessage['return']);
            return $aMessage;
        }
        return false;
    }

    public function getErrorArr(){
        return $this->_m_aErrors;
    }

    protected function _isError($arr){
        if(empty($arr['code'])){
            return false;
        }

        if(empty($this->_getError($arr['code']))){
            return false;
        }

        return true;
    }

    /**
     * Does the win transaction has a valid bet.
     * We use the txnId coming in with the win request and check for a bet entry.
     * @param stdClass $p_oParameters Json object with the parameters received from gaming provider
     * @param bool $p_bReturnResult Get the result instead of boolean. Default false
     * @return bool|array
     */
    protected function _hasBet(stdClass $p_oParameters, $p_bReturnResult = false)
    {
        if ($this->_m_bSkipBetCheck !== true ){
            $this->attachPrefix($p_oParameters->transactionid);


            if ($this->_m_bByRoundId === true) {
                $game_ref = (isset($this->_m_mGameData['ext_game_name']) ? $this->_m_mGameData['ext_game_name'] : $this->getGamePrefix() . $this->_m_oRequest->skinid);
                $query = "SELECT * FROM bets WHERE user_id = " . (int)$this->_m_oRequest->playerid . " AND game_ref = " . phive("SQL")->escape($game_ref) . " AND trans_id = " . (int)$p_oParameters->roundid;
                $result = phive('SQL')->sh($this->_m_oRequest->playerid, '', 'bets')->loadAssoc($query);
                $this->_logIt([__METHOD__, $query, 'BET ' . (!empty($result) ? 'ID: ' . $result['id'] . ' confirmed' : ' with ID: ' . $this->_m_oRequest->playerid . ' & round ID: ' . $p_oParameters->roundid . '  => failed')]);
            } else {
                $query = "SELECT * FROM bets WHERE mg_id = " . phive("SQL")->escape($p_oParameters->transactionid);
                $result = phive('SQL')->sh($this->_m_mUserData, 'id')->loadAssoc($query);
                $this->_logIt([__METHOD__, $query, 'BET ' . (!empty($result) ? 'ID: ' . $result['id'] . ' confirmed' : ' with ID: ' . $p_oParameters->transactionid . ' failed')]);
            }
            if ($p_bReturnResult === true) {
                return $result;
            }
            return (!empty($result) ? true : false);
        } 
    }
    
    /**
     * Set the freespin data by user ID and bonus entry ID
     * This means that the GP request has a bonus_entries:id and if a bonus is found $this->_isFreespin() return true
     * but frb_remaining could still be 0
     * @param int $p_iUserId
     * @param int $p_iBonusEntryId
     * @param string $p_sFilter game_id|ext_id|''
     * @return void
     */
    protected function _setFreespin($p_iUserId, $p_iBonusEntryId, $p_sFilter = '')
    {
        $this->_m_aFreespins = $this->_getBonusEntryBy($p_iUserId, $p_iBonusEntryId, $p_sFilter);
        $this->_logIt([__METHOD__, print_r($this->_m_aFreespins, true)]);
        if (!empty($this->_m_aFreespins) && $this->_m_aFreespins['frb_remaining'] >= 0) {
            $this->_m_bIsFreespin = true;
        }
    }

    /**
     * Selects the first 'available' free spin bonus reward for the player and game.
     * A bonus reward is 'available' if it has not expired and still has remaining free spins.
     *
     * @param mixed $user_id. The user ID. This should be an INT but the request might send a stringified INT so avoid type hinting the function parameter to prevent issues.
     * @param mixed $game_identifier. db.bonus_types.game_id, which must include the game provider prefix.
     * @param bool $sort_ascending. If true returns the oldest available free spin bonus reward, otherwise the most recent.
     */
    protected function setAvailableFreespin($user_id, $game_identifier, bool $sort_ascending = true)
    {
        $this->attachPrefix($game_identifier);
        $this->_m_aFreespins = $this->getAvailableFreeSpinBonusEntry($user_id, $game_identifier, $sort_ascending);

        if (!empty($this->_m_aFreespins) && ($this->_m_aFreespins['frb_remaining'] >= 0)) {
            $this->_m_bIsFreespin = true;
        }
    }

    /**
     * @param $user_id
     * @param string $game_identifier. db.bonus_types.game_id, which must include the game provider prefix.
     * @param bool $sort_ascending
     * @return array|false
     */
    private function getAvailableFreeSpinBonusEntry($user_id, string $game_identifier, bool $sort_ascending = true)
    {
        $user_id = (int)$user_id;    // cast prevents SQL injection.
        $date = phive()->today();
        $game_identifier = phive("SQL")->escape($game_identifier);
        $sort = ($sort_ascending ? 'ASC' : 'DESC');
        $minimum_fs_wager = (int) licSetting('minimum_fs_wager', cu($user_id)) * 100;

        $sql = "SELECT be.id, be.bonus_id, be.user_id, be.balance, be.start_time, be.end_time, be.status, be.reward, 
                        be.cost, be.frb_remaining, be.frb_granted, bt.frb_denomination, bt.frb_lines, 
                        IFNULL(NULLIF(bt.rake_percent, 0), {$minimum_fs_wager})  as rake_percent, 
                        bt.frb_coins, bt.game_id
                    FROM bonus_entries AS be
                    INNER JOIN bonus_types AS bt ON be.bonus_id = bt.id 
                    WHERE bt.bonus_type = 'freespin' AND be.user_id = {$user_id}
                        AND IF (IFNULL (NULLIF (bt.rake_percent, 0), {$minimum_fs_wager}) > 0, be.status = 'active', be.status = 'approved')
                        AND (be.start_time IS NULL OR (be.start_time IS NOT NULL AND be.start_time <= '{$date}'))
                        AND (be.end_time IS NULL OR (be.end_time IS NOT NULL AND be.end_time >= '{$date}'))
                        AND be.frb_remaining > 0 AND bt.game_id = {$game_identifier}
                    ORDER BY be.id $sort ";

        $bonus_entry = $this->db->sh($user_id)->loadAssoc($sql, null, null, true /* LIMIT 1 */);

        phive('Logger')->getLogger('game_providers')->debug(__METHOD__, [
            'user_id' => $user_id,
            'date' => $date,
            'game' => $game_identifier,
            'minimum_fs_wager' => $minimum_fs_wager,
            'bonus_entry' => $bonus_entry['id'] ?? null
        ]);

        return $bonus_entry;
    }
    
    /**
     * get the freespin data
     * @param string $p_sKey The array key from the value to get
     * @return array|mixed
     */
    protected function _getFreespinData($p_sKey = null)
    {
        if (!empty($p_sKey) && isset($this->_m_aFreespins[$p_sKey])) {
            return $this->_m_aFreespins[$p_sKey];
        }
        return $this->_m_aFreespins;
    }
    
    /**
     * Is the current transaction GP request based on a freespin
     * @return bool true if freespin
     */
    protected function _isFreespin()
    {
        return $this->_m_bIsFreespin;
    }
    
    /**
     * Log messages to the trans_log table in the database.
     * This method only logs to the database on staging and not on production server, except if force parameter is overruled.
     *
     * @param array  $p_aData   The data that should be logged
     * @param string $p_sKey    Which key to use as a reference for this message
     * @param bool   $p_bForce  Overrule config setting 'log_errors' which is only true on staging and not production
     * @return void
     */
    protected function _logIt($p_aData, $p_sKey = '', $p_bForce = false)
    {
        $sData = '';
        
        if ($this->getSetting('log_errors') === true || $p_bForce === true) {
            $sData .= PHP_EOL . implode(PHP_EOL, $p_aData) . PHP_EOL;
            if ($this->_m_bToScreen === true) {
                echo $sData;
            } else {
                $this->_m_iLogCount++;
                phive()->dumpTbl((empty($p_sKey) ? $this->getGpName() . '-error-log'.$this->_m_iLogCount : $p_sKey), $this->_m_iLogCount . ' ' . $sData, $this->_m_mUserData);
            }
        }
    }
    
    /**
     * Get bonus entry data
     * @param int $p_iUserId The user ID
     * @param mixed $p_mId bonus_entries:game_id (with GP prefix), bonus_entries:ext_id or the bonus_entries:id
     * @param string $p_sFilter game_id|ext_id|''
     * @return object the query result
     */
    protected function _getBonusEntryBy($p_iUserId, $p_mId, $p_sFilter = '')
    {
        $result = phive('Bonuses')->getBonusEntryBy($p_iUserId, $p_mId, $p_sFilter, $this->getGpName());
        $this->_logIt([__METHOD__, print_r($result, true), '$p_sFilter: ' . $p_sFilter, 'userID: ' . $p_iUserId, 'gameID: ' . $p_mId]);
        return $result;
    }
    
    /**
     * Delay a response to the GP for xx seconds randomly of time so the are able to trigger certain behaviour
     */
    protected function _delayResponseTime()
    {
        if($this->getSetting('test') === true && !empty($this->getSetting('delaysec')) && $this->getSetting('delaysec') > 0){
            if(rand(1,5) === 4) {
                sleep((int)$this->getSetting('delaysec'));
            }
        }
    }
    
    /**
     * Set correct response headers for the response to GP
     * Depending on $p_mResponse, choose to response always with a 200 OK or different response header depending on GP needs
     * @param mixed $p_mResponse On failure: an array with message and code about what failed or true on success
     * @param bool $p_bReturnStatusError Return the status from the array $this->_m_aErrors or the code message from $this->_m_aHttpStatusCodes. Default: false (so $this->_m_aHttpStatusCodes)
     * @return void
     */
    protected function _setResponseHeaders($p_mResponse = null, $p_bReturnStatusError = false)
    {
        $this->_delayResponseTime();
        if ((!empty($p_mResponse) && $this->_m_bForceHttpOkResponse === false) || $p_mResponse['status'] == 'UNAUTHORIZED') {
            if (in_array(substr(php_sapi_name(), 0, 3), array('cgi', 'fpm'))) {
                header('Status: ' . $p_mResponse['responsecode'] . ' ' . (($p_bReturnStatusError === true) ? $p_mResponse['status'] : $this->_m_aHttpStatusCodes[$p_mResponse['responsecode']]));
            } else {
                $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
                header($protocol . ' ' . $p_mResponse['responsecode'] . ' ' . (($p_bReturnStatusError === true) ? $p_mResponse['status'] : $this->_m_aHttpStatusCodes[$p_mResponse['responsecode']]));
            }
        } else {
            // Send a 200 OK response
            header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK');
        }
        
        if ($p_mResponse['status'] !== 'UNAUTHORIZED') {
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Content-type: ' . $this->_m_sHttpContentType . '; charset=utf-8');
        }
    }
    
    /**
     * This is the first call of every session. Requires token from the Game Start Communication Process.
     * Required parameters are mandatory to start Game Client.
     *
     * @return bool true on success
     */
    protected function _init()
    {
        $user = cu($this->_getUserData());

        if ($this->getLicSetting('external_game_session_enabled', $user)) {
            $this->setNewExternalSession($user);
        }
        return true;
    }

    /**
     * Starts and stores the external session into the session token
     *
     * @param DBUser|null $user
     * @param $session_data
     * @return void
     */
    public function setNewExternalSession(DBUser $user = null, $session_data = null)
    {
        $session_data = $session_data ?? $this->fromSession($this->_m_sSessionKey);

        if (lic('hasGameplayWithSessionBalance', [], $user) == false || empty($session_data) || $this->_isTournamentMode()) {
            return;
        }

        $game_data = $this->_getGameData();
        if(empty($game_data)){
            // Game identifier was not present in the request so we go for the session data instead.
            $game_data = $this->_m_oMicroGames->getByGameRef($session_data->gameid, $session_data->device ?? 0, $session_data->userid);
        }

        $session_data->ext_session_id = lic('initGameSessionWithBalance', [$user, $this->_m_sSessionKey, $game_data], $user);

        if (!empty($session_data->ext_session_id)) {
            $this->saveSessionData($this->_m_sSessionKey, $session_data);
            $this->setSessionById($session_data->userid, $session_data->ext_session_id);
        }
    }
    
    /**
     * Gets current Player’s balance.
     * @return bool true on success
     */
    protected function _heartbeat()
    {
        return true;
    }
    
    /**
     * Gets current Player’s balance.
     * @return bool true on success
     */
    protected function _balance()
    {
        return true;
    }
    
    /**
     * Gets current Player’s currency.
     * @return bool true on success
     */
    protected function _currency()
    {
        return true;
    }
    
    /**
     * This method is used to inform the operator about the end session state.
     * It passes all necessary information about a session to the operator which should
     * then internally close the session and return the successful result of this operation.
     *
     * @return bool|array true on success or array with error details
     */
    protected function _end()
    {
        $user = cu($this->_getUserData());

        if ($this->getLicSetting('external_game_session_enabled', $user)) {
            lic('endOpenParticipation', [$user], $user);
        }

        return true;
    }
    
    /**
     * Get a bet or win data array by the transaction ID
     *
     * @param int $p_iTransactionId The transaction ID as received from the GP without our prefix
     * @param string $p_sTable Is it the bets|wins table
     * @param bool $p_bCheckIsCancelled Do we check if it's a cancelled bet or win
     * @return array|bool False if no entry found
     */
    protected function _getTransactionById($p_iTransactionId, $p_sTable, $p_bCheckIsCancelled = false)
    {
        $this->attachPrefix($p_iTransactionId);
        $mResult = $this->getBetByMgId($p_iTransactionId . (!empty($p_bCheckIsCancelled) ? 'ref' : ''), $p_sTable, 'mg_id', $this->_m_mUserData['id']);
        
        
        if (!empty($mResult) && in_array($p_sTable,$this->_TRANSACTION_TABLES)) {
            $this->{'_m_iWalletTxn' . $this->_getTransactionTable($p_sTable)} = $mResult['id'];
            $this->{'_m_iWalletAmount' . $this->_getTransactionTable($p_sTable)} = $mResult['amount'];
            $this->{'_m_iWalletDate' . $this->_getTransactionTable($p_sTable)} = $mResult['created_at'];
    
//            echo ($this->{'_m_iWalletTxn' . $this->_getTransactionTable($p_sTable)}) . PHP_EOL;
//            echo ($this->{'_m_iWalletAmount' . $this->_getTransactionTable($p_sTable)}) . PHP_EOL;
//            echo ($this->{'_m_iWalletDate' . $this->_getTransactionTable($p_sTable)}) . PHP_EOL;

        }
        
        return $mResult;
    }

    /**
     * Set the player data array by the player ID received from gp
     *
     * If <b>_m_oRequest->playerid</b> is a tournament identifier, e.g. 1234e555, then executing <b>_setUserData</b>
     * sets <b>t_entry</b> but other methods using <b>_m_oRequest->playerid</b> must
     * be careful because it is not an INT user id.
     *
     * If <b>_m_oRequest->playerid</b> is an INT user id yet this is a tournament entry, then <b>getUsrId</b>
     * must be called before <b>_setUserData</b> so that <b>t_entry</b> is set.
     *
     * @return void
     */
    protected function _setUserData()
    {
        $uid = $this->getUsrId($this->_m_oRequest->playerid);
        $this->user = cu($uid);
        $this->_m_mUserData = $this->user->data;
        if (empty($this->_m_mUserData)) {
            // the user doesn't exist or is missing??
            $this->_response($this->_getError(self::ER09));
        } else {
            $setting_results = $this->user->getBlocksAndRestrictions();
            if (!empty($setting_results['super-blocked'])) {
                // Blocked
                $this->_response($this->_getError(self::ER25));
            } elseif (!empty($setting_results['play_block']) || !empty($setting_results['restrict'])) {
                // Banned
                $this->_response($this->_getError(self::ER26));
            }
        }
        $this->uid = $this->_m_mUserData['id'];
    }

    protected function _setRequestObj($req_data, $sess_data){
        $req_data = (object)$req_data;

        $this->_m_oRequest = (object)[
            'playerid' => $req_data->playerid ?? $sess_data->userid,
            'skinid'   => $req_data->skinid ?? $this->stripPrefix($sess_data->gameid),
            'state'    => $req_data->state ?? 'single',
            'action'   => (object)($req_data->action ?? []),
            'device'   => $req_data->device ?? ($sess_data->device ?? 0)
        ];
    }

    /**
     * Like _setUserData() but without terminating the script if the user is blocked.
     *
     * @param string|null $user_id '5541343' || '5541343e111'
     * @return int|null The db.users.id or null if not found.
     */
    protected function setUserDataWithoutResponse(string $user_id = null)
    {
        // Reloads t_eid if it changed.
        $this->uid = $this->t_eid = $this->t_entry = null;

        $uid = $this->getUsrId($user_id);
        $this->user = cu($uid);
        if ($this->user && ($this->user->data ?? null)) {
            $this->_m_mUserData = $this->user->data;
            $this->uid = $this->_m_mUserData['id'];
        }
        return $this->uid;
    }

    /**
     * Set the game data array by the game ID received from gp
     * @param bool $p_bAddPrefix Add GP prefix or not. default true
     * @return void
     */
    protected function _setGameData($p_bAddPrefix = true, $game = null)
    {
        if (!empty($game)) {
            $this->_m_mGameData = $game;
            $this->new_token['game_ref'] = $this->_m_mGameData['ext_game_name'];
            return;
        }

        if ($this->_m_mGameData === null) {

            // We pass in the potentially existing user id in order to get the real game in case the passed ext id matches an id in the game_country_overrides table.
            $this->_m_mGameData = $this->_m_oMicroGames->getByGameRef(
                (($p_bAddPrefix === false) ? '' : $this->getGamePrefix()) . $this->_m_oRequest->skinid,
                $this->getDeviceTypeNum($this->getGpMethod(), $this->_m_oRequest),
                $this->_m_oRequest->playerid ?? null
            );

            $this->dumpTst(
                sprintf("%s-error-log-%s", strtolower(get_class($this)), __FUNCTION__),
                ['method' => $this->getGpMethod(), 'request' => $this->_m_oRequest, 'game' => $this->_m_mGameData]
            );

            if (empty($this->_m_mGameData)) {
                // the game is missing so we try to set a system game
                $this->_logIt([__METHOD__, $this->_m_oRequest->skinid], $this->getGpName() . '_missing_game', true);

                $this->_m_mGameData = $this->_m_oMicroGames->getByGameRef(
                    $this->getGpName() . '_system',
                    $this->getDeviceTypeNum($this->getGpMethod(), $this->_m_oRequest)
                );
                if (empty($this->_m_mGameData)) {
                    $this->_response($this->_getError(self::ER10));
                }
            }
            $this->new_token['game_ref'] = $this->_m_mGameData['ext_game_name'];
        }
    }

    /**
     * Check if game being processed for the current game request is active
     * @return bool
     */
    public function isCurrentGameActive()
    {
        return !empty($this->_m_mGameData) && $this->_m_mGameData['active'];
    }
    
    /**
     * Get the methods to be executed from one GP request
     *
     * @return array
     */
    protected function _getActions()
    {
        return $this->_m_aActions;
    }
    
    /**
     * Collect the methods to be executed from one GP request
     *
     * @see _response()
     * @return bool|array true on success or response error if request contains invalid method call
     */
    protected function _setActions()
    {
        switch ($this->_m_oRequest->state) {
            case 'single':
                array_push($this->_m_aActions, $this->_m_oRequest->action);
                break;
            
            case 'multi':
                $this->_m_bIsMultiTransaction = true;
                foreach ($this->_m_oRequest->actions as $oAction) {
                    array_push($this->_m_aActions, $oAction);
                }
                break;
        }
        // if one of the commands do not exist return an error
        foreach ($this->_m_aActions as $key => $oAction) {
            if (!method_exists($this, $oAction->command)) {
                return $this->_response($this->_getError(self::ER02));
            }
        }

        // Actions were set without incident so we return true.
        return true;
    }
    
    protected function _getUserGameSession($p_iUserId, $p_sGameRef)
    {
        return [];
        // This method doesn't seem to be used anywhere and it should stay that way, we can't do asArr, it kills Redis.
        /*
        $sql = "
        SELECT * FROM `users_game_sessions`
        WHERE `user_id` = " . phive("SQL")->escape($p_iUserId) . "
        AND `game_ref` = " . phive("SQL")->escape($p_sGameRef) . "
        order by id desc limit 1";
        $starts = phM('asArr', "gsess-stime-$p_iUserId*");
        $this->_logIt([__METHOD__, $sql, print_r($starts, true)]);
        return phive('SQL')->sh($p_iUserId, '', 'users_game_sessions')->loadAssoc($sql);
         */
    }
    
    /**
     * Get an array with the history link and reality check interval in seconds
     * execute the following query on the db: INSERT into config SET config_name ='<gameProviderTag>', config_tag='reality-check-mobile', config_value='on';
     * @param int $p_iUserId
     * @return array with to key history_link (url to history) and reality_check_interval (in sec)
     */
    protected function _getRc($p_iUserId = null)
    {
        $a = array();
        $reality_check_interval = phive('Casino')->startAndGetRealityInterval($p_iUserId);
        if (!empty($reality_check_interval) && phive("Config")->getValue('reality-check-mobile',
                $this->getGpName()) === 'on') {
            $reality_check_interval = $reality_check_interval * 60;
            $user = ud();
            $a['history_link'] = $this->getHistoryUrl();
            $a['reality_check_interval'] = $reality_check_interval; // sec
            // confirmed with Henrik: elapsed time is always reset on start of a new game in same login session so basically this is always 0
            //$a['elapsed_time'] = 0;
            unset($user);
        }
        return $a;
    }
        
    /**
     * Get the code used for bets/wins:bonus_bet and wins:award_type
     * @return int
     */
    private function _getBonusBetAwardTypeCode()
    {
        if ($this->_m_aFreespins['rake_percent'] > 0) {
            if ($this->_m_aFreespins['deposit_limit'] > 0) {
                // deposit
                return ($this->_isInhouseFrb() ? self::FREESPIN_INH_DEPOSIT : self::FREESPIN_DEPOSIT);
            } else {
                // wager
                return ($this->_isInhouseFrb() ? self::FREESPIN_INH_WAGER : self::FREESPIN_WAGER);
            }
        } else {
            // reward frb
            return ($this->_isInhouseFrb() ? self::FREESPIN_INH_REWARD : self::FREESPIN_REWARD);
        }
    }
    
    /**
     * Get the transaction table for this request either wins or bets
     * @param string $p_sTransactionTable Either self::TRANSACTION_TABLE_WINS|self::TRANSACTION_TABLE_BETS|''
     * @return string
     */
    private function _getTransactionTable($p_sTransactionTable = ''){
        if(!empty($p_sTransactionTable)){
            $sTable = $p_sTransactionTable;
        } else if($this->_m_sMethod === '_cancel'){
            $sTable = $this->_m_sCancelTbl;
        } else {
            $sTable = substr($this->_m_sMethod, 1) . 's';
        }
        return ucfirst(!empty($p_sTransactionTable) ? $p_sTransactionTable : $sTable);
    }

    /**
     * This function should be called only inside the single GPs implementation
     * it will check for the "rc-popup__xxx" settings in *providerName*.config.php
     * If nothing is defined in the setting it will default to false (our own implementation)
     *
     * TODO improve this using the "swedish popup" too. @antonio
     *
     * @return boolean - if we shuold use our own implementation (false) or the provider one (true)
     */
    protected function _useProviderRealityCheck()
    {
        $platform = phive()->isMobile() ? 'mobile' : 'desktop';        
        return $this->getLicSetting('rc-popup')[$platform] ?? false;
    }

    /**
     * Returns all the db.rounds rows for the specified user and external round id.
     *
     * @param int|null $user_id
     * @param string|null $ext_round_id
     * @return array Array of rows. Each row is an array.
     */
    protected function getRoundsByExtRoundId(int $user_id = null, string $ext_round_id = null): array
    {
        if (!empty($user_id)) {
            $this->cur_user = cu($user_id);
        }

        return $this->getUserGameSessionBets($ext_round_id);
    }

    /**
     * Returns all the db.bets or db.wins rows for the specified user and row IDs.
     *
     * @param int $user_id
     * @param string $table 'bets' | 'wins' | 'bets_mp' | 'wins_mp'
     * @param array $id Array of INT ids
     * @return array Array of rows. Each row is an array.
     */
    protected function getTransactionsById(int $user_id, string $table, array $id): array
    {
        $db_rows = [];

        if (empty($user_id) || empty($id) || !in_array($table, ['bets', 'wins', 'bets_mp', 'wins_mp'])) {
            if ($this->getSetting('log-verbose')) {
                $file = __METHOD__ . '::' . __LINE__;
                $this->logResponseInfo(compact('file', 'user_id', 'table', 'id', 'db_rows'), true);
            }
            return $db_rows;
        }

        $id2 = array_filter($id, function ($n) {
            return is_numeric($n);
        });

        if (!empty($id2)) {
            $s = phive('SQL')->makeIn($id2);
            $sql = "SELECT * FROM {$table} WHERE user_id = {$user_id} AND id IN ({$s})";
            $db_rows = phive('SQL')->sh($user_id)->loadArray($sql);
        }
        if ($this->getSetting('log-verbose')) {
            $file = __METHOD__ . '::' . __LINE__;
            $this->logResponseInfo(compact('file', 'user_id', 'table', 'id2', 'id', 'db_rows'), true);
        }
        return empty($db_rows) ? [] : $db_rows;
    }

    /**
     * Returns all the db.bets or db.wins rows for the specified user and mg_ids.
     *
     * @param int $user_id
     * @param string $table 'bets' | 'wins' | 'bets_mp' | 'wins_mp'
     * @param array $mg_id Array of STRING mg_id
     * @return array Array of rows. Each row is an array.
     */
    protected function getTransactionsByMgid(int $user_id, string $table, array $mg_id): array
    {
        $db_rows = [];
        $sql = '';

        if (empty($user_id) || empty($mg_id) || !in_array($table, ['bets', 'wins', 'bets_mp', 'wins_mp'])) {
            if ($this->getSetting('log-verbose')) {
                $file = __METHOD__ . '::' . __LINE__;
                $this->logResponseInfo(compact('file', 'user_id', 'table', 'mg_id', 'sql', 'db_rows'), true);
            }
            return $db_rows;
        }

        $s = phive('SQL')->makeIn($mg_id);
        $sql = "SELECT * FROM {$table} WHERE user_id = {$user_id} AND mg_id IN ({$s})";
        $db_rows = phive('SQL')->sh($user_id)->loadArray($sql);

        if ($this->getSetting('log-verbose')) {
            $file = __METHOD__ . '::' . __LINE__;
            $this->logResponseInfo(compact('file', 'user_id', 'table', 'mg_id', 'sql', 'db_rows'), true);
        }
        return empty($db_rows) ? [] : $db_rows;
    }

    /**
     * Clears all log messages.
     *
     * @return Gp
     */
    protected function clearLogMessages()
    {
        $this->log_messages = [];
        return $this;
    }

    /**
     * Adds a log message.
     *
     * @return Gp
     */
    protected function addLogMessage(string $message)
    {
        $this->log_messages[] = $message;
        return $this;
    }


    /**
     * Logs the response to db.trans_log.
     * Rename this method to 'logResponse' when the Stakelogic::logResponse signature is compatible.
     *
     * @param mixed|null $response The response to log.
     * @param bool $is_custom_log If true then logs just the $response parameter.
     */
    protected function logResponseInfo($response = null, bool $is_custom_log = false)
    {
        if (!$this->getSetting('log-session-debug')) {
            return;
        }

        $gp_method = $this->getGpMethod();
        $backtrace = null;
        if (empty($gp_method) || !empty($this->log_messages)) {
            // This has no performance impact because GpMethod is usually set and we also limit to 2 stack frames.
            $limit = empty($this->log_messages) ? 2 : 7;
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);
            $gp_method = $gp_method ?: $backtrace[1]['function'] ?? '';
        }
        $tag = $this->getGpName() . "-info-" . (empty($this->log_messages) ? '' : 'warning-') . $gp_method;

        $arr = explode('e', $this->user_identifier ?: '');
        $uid = empty($arr) ? 0 : (int)($arr[0] ?? 0);

        $duration = round(microtime(true) - ($this->getStartedAt() ?: 0), 4);

        if ($is_custom_log) {
            $log_data = $response;
        } else {
            $raw_request = json_decode($this->getGpParams(), true) ?: $this->getGpParams();

            $log_data = [
                'method' => $this->_getMethod(),
                'response' => $response,
                'actions' => $this->_m_oRequest ? json_decode(json_encode($this->_m_oRequest), true) : null,
                'request' => $raw_request,
                'duration' => $duration,
                'is_slow_query' => ($duration > 1),
            ];
            if (!empty($this->log_messages)) {
                $log_data = array_merge(['log_messages' => $this->log_messages], $log_data);
                if (!empty($backtrace)) {
                    $log_data['backtrace'] = $backtrace;
                }
            }
        }

        phive()->dumpTbl($tag, $log_data, $uid);
    }

    /**
     * Returns the original game ref (db.micro_games.ext_game_name) if overridden else the game ref parameter.
     * The method cannot be in MicroGames.php because we need the game provider config setting.
     *
     * @param string|null $game_ref. The game ref (micro_games.ext_game_name or game_country_overrides.ext_game_id).
     * @param string|null $country.
     * @return string|null The original game ref if overridden else the game ref parameter.
     */
    protected function getOriginalGameRefIfOverridden(string $game_ref = null, string $country = null): ?string
    {
        if (empty($game_ref)) {
            return $game_ref;
        }

        if (empty($country)) {
            $country = $this->isTournamentMode() ? $this->getLicSetting('bos-country', $this->user) : null;
        }
        $this->attachPrefix($game_ref);
        $gref = phive('MicroGames')->getOriginalRefIfOverridden($game_ref, $this->user, $country);
        return $this->stripPrefix($gref);
    }

    /**
     * Returns true if the wallet method can proceed according to the game status.
     * A bet request typically requires the game to exist and be active.
     * A win request can sometimes be sent or re-sent several days later so the game might not be active anymore.
     * @param string|null $method <p>
     * The wallet method.
     * </p>
     * @param array|null $game <p>
     * The game row.
     * </p>
     * @return bool <p>
     * True if the wallet method can proceed.
     * </p>
     */
    protected function allowWalletMethodForGame(?string $method, ?array $game = null): bool
    {
        if ($method == '_bet') {
            return !empty($game['active']);
        } elseif ($method == '_win') {
            return !empty($game);
        } else {
            return !empty($game);
        }
    }

    /**
     * Returns the device type num corresponding to the device string.
     *
     * @param string|null $gp_method <p>
     * The GP method name. This can be useful if overriding this method.
     * </p>
     * @param stdClass|null $request <p>
     * The request object.
     * </p>
     * @return int|null <p>
     * The device type num or null if there is no match.
     * </p>
     */
    protected function getDeviceTypeNum(?string $gp_method = null, ?stdClass $request = null): ?int
    {
        return $this->string2DeviceTypeNum($request->device ?? null);
    }

    /**
     * Returns the device type num corresponding to the device string.
     * See <b>Phive.base.php::deviceType</b> for typical device type values.
     *
     * @param mixed|null $device <p>
     * The device type ('flash', 'html5', 'android' etc) or device type num (0, 1, 2 etc).
     * </p>
     * @return int|null <p>
     * The device type num.
     * </p>
     * @example <p>
     * string2DeviceTypeNum('html5')    // returns 1
     * </p>
     */
    protected function string2DeviceTypeNum($device = null): ?int
    {
        if (!is_string($device) && !is_numeric($device)) {
            return null;
        }

        switch (strtolower($device)) {
            case 'flash':
            case 'desktop':
            case 'pc':
            case 'win32':
            case 'macintosh':
                return 0;

            case 'mobile':
            case 'html5':
            case 'iosmobile':
            case 'ipad':
            case 'iphone':
            case 'ipod':
            case 'blackberry':
            case 'symbian':
            case 'nokia':
                return 1;

            case 'android':
                return 2;

            case 'windows':
            case 'windowsphone':
                return 3;

            case 'live':
                return 9;

            default:
                return is_numeric($device) ? (int)$device : null;
        }
    }

    /** get bets/wins tables
     * @return array
     */
    public function getTransactionsTables()
    {
        return $this->_TRANSACTION_TABLES;
    }

    /**
     *  At the moment when a bet/win is cancelled, we attach 'ref' and the end of 'mg_id'.
     *  Checking presence of this ref and adding txn_length and gp_name in the check aswell to have more strict criteria
     */
    public function getCancelledTransactionRegex($txn_length)
    {
        return "/^(".$this->getGpName().")_([a-zA-Z0-9]{".$txn_length."})ref$/";
    }
}

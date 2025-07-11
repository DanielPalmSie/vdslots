<?php

require_once __DIR__ . '/Casino.php';

class CasinoProvider extends Casino
{
    /**
     * @var int|string The user ID or user + tournament ID, e.g. 5541343 or 5541343e28278214.
     */
    protected $user_identifier;

    /**
     * @var string. The currency of the user or the tournament user.
     */
    protected $user_identifier_currency;

    /**
     * The header content type for our response to the game provider request.
     *
     * @var string $http_content_type
     */
    protected $http_content_type;

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

    const NORMAL_WIN_AWARD_TYPE = 2;

    const JACKPOT_WIN_AWARD_TYPE = 4;

    const ROLLBACK_WIN_AWARD_TYPE = 7;

    const CHANGE_BALANCE_TYPE_BET = 1;

    const CHANGE_BALANCE_TYPE_WIN = 2;

    const CHANGED_BALANCE_TYPE_ROLLBACK = 7;

    const BONUS_AWARD_TYPE_FREESPIN_DEPOSIT = 6;

    const BONUS_AWARD_TYPE_FREESPIN_WAGER = 5;

    const BONUS_AWARD_TYPE_FREESPIN_REWARD = 8;

    const BETS_TABLE = 'bets';

    const WINS_TABLE = 'wins';


    /**
     * Do we want to execute more than one function
     * @var bool
     */
    public $is_multi_call = false;

    /**
     * Will have the game data as an array if game is found otherwise it will be false
     *
     * @var mixed bool|array
     */
    protected $game_data = [];

    /**
     * Do we force respond with a 200 OK response to the GP
     * @var bool
     */
    protected $force_http_ok_response = null;

    /**
     * Map GP methods requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    protected $map_gp_methods = [];
    /**
     * HTTP Header status codes to match a code from property $_m_aErrors to a status code which can
     * be used in the header response in case 200 OK is not forced
     * @var array
     */
    protected $http_status_codes = [
        200 => 'OK',
        400 => 'REQUEST_INVALID',
        401 => 'ACCESS_DENIED',
        402 => 'INSUFFICIENT_FUNDS',
        403 => 'UNAUTHORIZED',
        404 => 'NOT_FOUND',
        405 => 'UNKNOWN_COMMAND',
        498 => 'TOKEN_NOT_FOUND',
        500 => 'INTERNAL_ERROR'
    ];
    /**
     * This is used to see if win is already present in rounds table.
     * @var bool
     */
    protected $confirm_win = null;
    /**
     * When this is true we want to check for duplicate round ids in the bets
     * @var bool
     */
    protected $confirm_round = null;

    /**
     * TODO review all the below with @Ricardo and name them properly.
     * This is used for a multi-request where we want to see if we catch an error before the loop ends
     * @var bool
     */
    protected $stop_execution_at_error = true;
    /**
     * TODO explain me how this work /Paolo
     * When false the bonus_entries::balance updated after each FRB request with the FRW or does the
     * GP keeps track and send the total winnings at the end of the free rounds.
     * Default: null (balance is updated per bet)
     */
    protected $receive_final_win = null;

    /**
     * Instance of CasinoBonuses
     *
     * @var CasinoBonuses
     */
    protected $module_casino_bonuses;

    /**
     * Instance of MicroGames
     *
     * @var MicroGames
     */
    protected $module_micro_games;

    /**
     * Instance of UserHandler
     *
     * @var UserHandler
     */
    protected $module_user_handler;

    /**
     * This will be overridden in the child class if needed
     *
     * @var bool
     */
    protected $partial_refund = false;
    /**
     * This is used if we want to force a bet with a 0 amount
     *
     * @var bool
     */
    protected $bet_forced = false;

    protected $platform;

    protected $demo_or_real;

    /**
     * Our method name as requested by the provider
     * @var string
     */
    protected $wallet_method = '';

    const EXCEPTION_CODE_INTERNAL_ERROR = 10001;
    const EXCEPTION_CODE_UNAUTHORIZED = 10002;
    const EXCEPTION_CODE_METHOD_NOT_FOUND = 10003;
    const EXCEPTION_CODE_USER_NOT_FOUND = 10004;
    const EXCEPTION_CODE_USER_BANNED = 10005;
    const EXCEPTION_CODE_USER_BLOCKED = 10006;
    const EXCEPTION_CODE_USER_INACTIVE = 10007;
    const EXCEPTION_CODE_GAME_NOT_FOUND = 10008;
    const EXCEPTION_CODE_DUPLICATE_TRANSACTION = 10009;
    const EXCEPTION_CODE_INVALID_REQUEST = 10010;
    const EXCEPTION_CODE_IDEMPOTENCY = 10011;
    const EXCEPTION_CODE_DUPLICATE_ROUND = 10012;
    const EXCEPTION_CODE_DUPLICATE_WIN_FOR_ROUND = 10013;
    const EXCEPTION_CODE_INSUFFICIENT_FUNDS = 10014;
    const EXCEPTION_CODE_MATCHING_TRANSACTION_NOT_FOUND = 10015;
    const EXCEPTION_CODE_TRANSACTION_NOT_FOUND = 10016;
    const EXCEPTION_CODE_TRANSACTION_ALREADY_ROLLED_BACK = 10017;
    const EXCEPTION_CODE_TRANSACTION_DETAILS_MISMATCH = 10018;
    const EXCEPTION_CODE_ROLLBACK_INSUFFICIENT_FUNDS = 10019;
    const EXCEPTION_CODE_FREESPIN_NOT_FOUND = 10020;
    const EXCEPTION_CODE_NO_FREESPINS_REMAINING = 10021;
    const EXCEPTION_CODE_INVALID_SESSION = 10022;
    const EXCEPTION_CODE_INVALID_CURRENCY = 10023;

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
    private $errors = [
        'ER01' => [
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responded to GP
            'code' => 'ER01', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ],
        'ER02' => [
            'responsecode' => 405,
            'status' => 'COMMAND_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER02',
            'message' => 'Command not found.',
            'exception_code' => self::EXCEPTION_CODE_METHOD_NOT_FOUND,
        ],
        'ER03' => [
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => 'ER03',
            'message' => 'The authentication credentials are incorrect.',
            'exception_code' => self::EXCEPTION_CODE_UNAUTHORIZED,
        ],
        'ER04' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => 'default',
            'code' => 'ER04',
            'message' => 'Bet transaction ID has been cancelled previously.'
        ],
        'ER05' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => true,
            'code' => 'ER05',
            'message' => 'Duplicate Transaction ID.',
            'exception_code' => self::EXCEPTION_CODE_DUPLICATE_TRANSACTION,
        ],
        'ER06' => [
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => 'ER06',
            'message' => 'Insufficient money in player\'s account to fulfill operation.',
            'exception_code' => self::EXCEPTION_CODE_INSUFFICIENT_FUNDS,
        ],
        'ER07' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_MISMATCH',
            'return' => 'default',
            'code' => 'ER07',
            'message' => 'Transaction details do not match.',
            'exception_code' => self::EXCEPTION_CODE_TRANSACTION_DETAILS_MISMATCH,
        ],
        'ER08' => [
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER08',
            'message' => 'Invalid refund, transaction ID does not exist.',
            'exception_code' => self::EXCEPTION_CODE_TRANSACTION_NOT_FOUND,
        ],
        'ER09' => [
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER09',
            'message' => 'Player not found.',
            'exception_code' => self::EXCEPTION_CODE_USER_NOT_FOUND,
        ],
        'ER10' => [
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER10',
            'message' => 'Game is not found.',
            'exception_code' => self::EXCEPTION_CODE_GAME_NOT_FOUND,
        ],
        'ER11' => [
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER11',
            'message' => 'Invalid session token.',
            'exception_code' => self::EXCEPTION_CODE_INVALID_SESSION,
        ],
        'ER12' => [
            'responsecode' => 200,
            'status' => 'FREESPIN_NO_REMAINING',
            'return' => 'default',
            'code' => 'ER12',
            'message' => 'No freespins remaining.',
            'exception_code' => self::EXCEPTION_CODE_NO_FREESPINS_REMAINING,
        ],
        'ER13' => [
            'responsecode' => 200,
            'status' => 'FREESPIN_INVALID',
            'return' => 'default',
            'code' => 'ER13',
            'message' => 'Invalid freespin bet amount.'
        ],
        'ER14' => [
            'responsecode' => 200,
            'status' => 'FREESPIN_UNKNOWN',
            'return' => 'default',
            'code' => 'ER14',
            'message' => 'Freespin stake transaction not found.'
        ],
        'ER15' => [
            'responsecode' => 403,
            'status' => 'FORBIDDEN',
            'return' => 'default',
            'code' => 'ER15',
            'message' => 'IP Address forbidden.'
        ],
        'ER16' => [
            'responsecode' => 400,
            'status' => 'REQUEST_INVALID',
            'return' => 'default',
            'code' => 'ER16',
            'message' => 'Invalid request.',
            'exception_code' => self::EXCEPTION_CODE_INVALID_REQUEST,
        ],
        'ER17' => [
            'responsecode' => 200,
            'status' => 'FREESPIN_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER17',
            'message' => 'This free spin bonus ID is not found.',
            'exception_code' => self::EXCEPTION_CODE_FREESPIN_NOT_FOUND,
        ],
        'ER18' => [
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => true,
            'code' => 'ER18',
            'message' => 'Duplicate Transaction ID with same amount does exist already.',
            'exception_code' => self::EXCEPTION_CODE_IDEMPOTENCY,
        ],
        'ER19' => [
            'responsecode' => 200,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER19',
            'message' => 'Stake transaction not found.',
            'exception_code' => self::EXCEPTION_CODE_MATCHING_TRANSACTION_NOT_FOUND,
        ],
        'ER20' => [
            'responsecode' => 200,
            'status' => 'API_FRB_NOT_CREATED_AT_GP',
            'return' => 'default',
            'code' => 'ER20',
            'message' => "Failed to create bonus in GP system! Consider changing config setting 'no_out' to false."
        ],
        'ER21' => [
            'responsecode' => 200,
            'status' => 'API_FRB_EXCLUSIVE_CONFLICT',
            'return' => 'default',
            'code' => 'ER21',
            'message' => 'Free spin bonus not create because of exclusivity conflict!'
        ],
        'ER22' => [
            'responsecode' => 200,
            'status' => 'API_SOURCE_BONUS_TYPE_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER22',
            'message' => 'The bonus type to use as source is not found!'
        ],
        'ER23' => [
            'responsecode' => 200,
            'status' => 'INSERT_FAILED',
            'return' => 'default',
            'code' => 'ER23',
            'message' => 'The insert failed!'
        ],
        'ER24' => [
            'responsecode' => 200,
            'status' => 'UPDATE_FAILED',
            'return' => 'default',
            'code' => 'ER24',
            'message' => 'The update failed!'
        ],
        'ER25' => [
            'responsecode' => 200,
            'status' => 'PLAYER_BLOCKED',
            'return' => 'default',
            'code' => 'ER25',
            'message' => 'Player is blocked.',
            'exception_code' => self::EXCEPTION_CODE_USER_BLOCKED,
        ],
        'ER26' => [
            'responsecode' => 200,
            'status' => 'PLAYER_BANNED',
            'return' => 'default',
            'code' => 'ER26',
            'message' => 'Player is banned.',
            'exception_code' => self::EXCEPTION_CODE_USER_BANNED,
        ],
        'ER27' => [
            'responsecode' => 200,
            'status' => 'PLAYER_INACTIVE',
            'return' => 'default',
            'code' => 'ER27',
            'message' => 'Player is inactive.',
            'exception_code' => self::EXCEPTION_CODE_USER_INACTIVE,
        ],
        'ER28' => [
            'responsecode' => 200,
            'status' => 'INVALID_USER_ID',
            'return' => 'default',
            'code' => 'ER28',
            'message' => 'Session player ID doesn\'t match request Player ID.'
        ],
        'ER29' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_ALREADY_CANCELLED',
            'return' => true,
            'code' => 'ER29',
            'message' => 'Transaction ID has been cancelled already.',
            'exception_code' => self::EXCEPTION_CODE_TRANSACTION_ALREADY_ROLLED_BACK,
        ],
        'ER30' => [
            'responsecode' => 200,
            'status' => 'ROUND_ALREADY_CANCELLED',
            'return' => true,
            'code' => 'ER30',
            'message' => 'Round Id has been cancelled already.'
        ],
        'ER31' => [
            'responsecode' => 200,
            'status' => 'ROUND_WAS_NOT_FOUND',
            'return' => true,
            'code' => 'ER31',
            'message' => 'Round Id does not exist in our database.'
        ],
        'ER32' => [
            'responsecode' => 200,
            'status' => 'ROUND_WIN_EXISTS',
            'return' => 'default',
            'code' => 'ER32',
            'message' => 'Win was already inserted into round.',
            'exception_code' => self::EXCEPTION_CODE_DUPLICATE_WIN_FOR_ROUND,
        ],
        'ER33' => [
            'responsecode' => 200,
            'status' => 'ROUND_EXISTS',
            'return' => 'default',
            'code' => 'ER33',
            'message' => 'Round already exists in database.',
            'exception_code' => self::EXCEPTION_CODE_DUPLICATE_ROUND,
        ],
        'ER34' => [
            'responsecode' => 200,
            'status' => 'TOO_LARGE_ROLLBACK',
            'return' => 'default',
            'code' => 'ER34',
            'message' => 'Cannot cancel transaction amount, not enough remaining to rollback',
            'exception_code' => self::EXCEPTION_CODE_ROLLBACK_INSUFFICIENT_FUNDS,
        ],
        'ER35' => [
            'responsecode' => 200,
            'status' => 'INVALID_CURRENCY',
            'return' => 'default',
            'code' => 'ER35',
            'message' => 'Invalid currency',
            'exception_code' => self::EXCEPTION_CODE_INVALID_CURRENCY,
        ]
    ];

    /**
     * The raw request received from the game provider after being json decoded, XML decoded etc.
     *
     * @var mixed $raw_request
     */
    protected $raw_request;

    /**
     * @var
     */
    protected $request_data_type;

    /**
     * The game provider's name for the request method.
     *
     * @var string $game_provider_method_name
     */
    protected $game_provider_method_name;

    /**
     * Array of all methods to be executed for this request. Each element is an array of normalized parameter names
     * and values for executing the method.
     * For example if the game provider sends a 'betAndWin' request then '$wallet_methods' should contain
     * 2 elements. One with all the parameters for the 'bet' method and another with all the parameters for the 'win_method'.
     * Derived classes must implement the 'addNormalizedWalletMethods' method and in this example the implementation would be similar to:
     *
     * protected function addNormalizedWalletMethods()
     * {
     *   $this->addWalletMethod('bet', ['user_id' => 1, 'amount' => 10,  'game_id' => 2560 ...]]);
     *   $this->addWalletMethod('win', ['user_id' => 1, 'amount' => 860, 'game_id' => 2560 ...]]);
     * }
     *
     * @var array
     */
    protected $wallet_methods;

    /**
     * The freespin data. I.E. bonus entry. Will be set/contain data when a FRB is received from GP
     * @var array
     */
    private $freespins = [];
    /**
     * Wallet transaction ID (VS) after a bet has been inserted
     * @var int
     */
    protected $wallet_txn_bets = null;
    /**
     * Wallet transaction ID (VS) after a win has been inserted
     * @var int
     */
    protected $wallet_txn_wins = null;
    /**
     * Wallet transaction ID (VS) after a win or bet has been cancelled
     * @var int
     */
    protected $wallet_txn_rollbacks = null;

    /**
     * The start microtime.
     * Used for logging.
     * @var int
     */
    private $microtime_start = 0;

    /**
     * Determines which table the transaction is taking place ex bets/wins
     *
     * @var string
     */
    private $current_table = '';

    /**
     * CasinoProvider constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->module_casino_bonuses = phive('CasinoBonuses');
        $this->module_user_handler = phive('DBUserHandler');
        $this->module_micro_games = phive('MicroGames');

        $this->setHttpContentType('application/json');
    }

    /**
     * The main method for this class. Processes the request from the game provider and returns a response.
     */
    public function execute()
    {
        $this->clear();

        try {
            $this->parseRequest();
            $this->validateRequest();
            $this->executeWalletMethods();
            $response = $this->response(true);

            $this->logResponse($response);
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($e->getPrevious()) {
                $message = $e->getPrevious()->getMessage() . " >>> {$message}";
            }
            $this->logDebug(['status' => 'error', 'file' => $e->getFile() . '::' . $e->getLine(), 'message' => $message]);

            $response = $this->getExceptionResponse($e);
            $this->logResponse($response, $e);
        }

        $this->logExecutionTime();

        echo $response;
        die;
    }

    /**
     * Clears internal properties before parsing a new request.
     */
    protected function clear()
    {
        $this->microtime_start = microtime(true);
        $this->raw_request = null;
        $this->request_data_type = null;
        $this->wallet_methods = [];
        $this->game_data = null;
    }

    /**
     * Parses the request into one or more methods that we will execute, e.g. 'bet', 'win'.
     */
    protected function parseRequest()
    {
        $this->decodeRequest();
        $this->addWalletMethods();
    }

    /**
     * Validates the request authentication credentials.
     * We load the user first because some authentication credentials depend upon the user's jurisdiction.
     *
     * @throws Exception
     */
    protected function validateRequest()
    {
        $user_identifier = $this->getUserIdFromRequest();
        $this->loadUser($user_identifier);
        $this->validateRequestAuthentication();
    }

    /**
     * Executes all the wallet methods specified in the game provider's request.
     *
     * @throws Exception
     */
    protected function executeWalletMethods()
    {
        if (empty($this->wallet_methods)) {
            throw new Exception("No wallet method to execute.", self::EXCEPTION_CODE_METHOD_NOT_FOUND);
        }

        $this->loadOriginalGame();

        foreach ($this->wallet_methods as $wallet_method_parameters) {
            $method = $this->wallet_method = $wallet_method_parameters['__wallet_method__'];

            $response = null;
            try {
                $this->validateWalletMethodExists($this->wallet_method);
                $response = $this->$method($wallet_method_parameters);
            } catch (Exception $e) {
                $details = $this->getExceptionDetails($e);
                $details['wallet_method'] = $this->wallet_method;
                $details['wallet_parameters'] = $wallet_method_parameters;

                if ($this->isFatalError($e)) {
                    $this->logError($details);
                } else {
                    $this->logDebug($details);
                }

                if ($this->stop_execution_at_error) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Saves the raw decoded request received from the game provider.
     */
    protected function decodeRequest()
    {
        $request = file_get_contents('php://input');

        $this->raw_request = json_decode($request, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->request_data_type = 'json';
        } else {
            $this->raw_request = simplexml_load_string($request);
            if ($this->raw_request !== false) {
                $this->request_data_type = 'xml';
            } else {
                $this->raw_request = $request;
                $this->request_data_type = 'unknown';
            }
        }

        $this->game_provider_method_name = $_GET['action'] ?? '';

        $this->logRequest();
    }

    /**
     *
     */
    protected function logRequest()
    {
        $log_data = ['url' => $this->getRequestUrl()];

        if ($this->request_data_type == 'json') {
            $log_data['request'] = $this->raw_request;
            $log = json_encode($log_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } elseif ($this->request_data_type == 'xml') {
            $log_data['request'] = $this->raw_request->asXML();
            $log = $log_data;
        } else {
            $log_data['request'] = $this->raw_request;
            $log = $log_data;
        }

        $this->logInfo($log);
    }

    /**
     * @return string
     */
    protected function getRequestUrl(): string
    {
        $http_or_https = ((!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] !=='off')) || ($_SERVER['SERVER_PORT'] == 443)) ? 'https':'http';            //in some cases, you need to add this condition too: if ('https'==$_SERVER['HTTP_X_FORWARDED_PROTO'])  ...
        return sprintf("%s://%s%s", $http_or_https, $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
    }

    /**
     * @param $response
     * @param mixed $e
     */
    protected function logResponse($response, $e = null)
    {
        $is_fatal_error = $this->isFatalError($e);

        $log_data = ['url' => $this->getRequestUrl()];

        if ($e) {
            $message = $e->getMessage();
            if ($e->getPrevious() && $e->getPrevious()->getMessage()) {
                $message = $e->getPrevious()->getMessage();
            }
            $log_data['error'] = [
                'message' => $message,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        $include_request_in_logged_response = $this->getSetting('log_add_request');

        if ($include_request_in_logged_response) {
            if ($is_fatal_error) {
                $log_data['request_headers'] = array_intersect_key($_SERVER, array_flip(preg_grep('/^HTTP_/', array_keys($_SERVER), 0)));
            }

            if ($this->request_data_type == 'json') {
                $log_data['request'] = $this->raw_request;
            } elseif ($this->request_data_type == 'xml') {
                $log_data['request'] = $this->raw_request->asXML();
            } else {
                $log_data['request'] = $this->raw_request;
            }
        }

        if ($is_fatal_error) {
            $log_data['response_headers'] = headers_list();
        }

        if ($this->request_data_type == 'json') {
            $log_data['response'] = json_decode($response);
            $log = json_encode($log_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } elseif ($this->request_data_type == 'xml') {
            $log_data['response'] = $response;
            $log = $log_data;
        } else {
            $log_data['response'] = $response;
            $log = $log_data;
        }

        $log_key = sprintf(
            "%s_res%s-%s",
            $this->getGameProviderName(),
            $is_fatal_error ? '-error' : '',
            $this->game_provider_method_name ?: ''
        );
        if ($is_fatal_error) {
            $this->logError($log, $log_key);
        } else {
            $this->logInfo($log, $log_key);
        }
    }

    /**
     * @param mixed $e
     * @return bool
     */
    protected function isFatalError($e = null)
    {
        if (!$e) {
            return false;
        }

        switch ($e->getCode()) {
            case self::EXCEPTION_CODE_IDEMPOTENCY:
            case self::EXCEPTION_CODE_INSUFFICIENT_FUNDS:
            case self::EXCEPTION_CODE_TRANSACTION_ALREADY_ROLLED_BACK:
                return false;

            default:
                return true;
        }
    }

    /**
     * Returns the game provider's name for the request method.
     *
     * @return string|null
     */
    protected function getGameProviderMethodName()
    {
        return $this->game_provider_method_name;
    }

    /**
     * Overridden by the child class to add all wallet methods and parameters which need to be executed for this request.
     * For example if the game provider sends a 'betAndWin' request then the child class should call
     *   $this->addWalletMethod('bet', ['user_id' => 1, 'amount' => 10,  'game_id' => 2560 ...]]);
     *   $this->addWalletMethod('win', ['user_id' => 1, 'amount' => 860, 'game_id' => 2560 ...]]);
     * This method should validate that required parameters exist.
     */
    protected function addWalletMethods()
    {
    }

    /**
     * Adds the normalized parameters and normalized method name for one of our wallet methods.
     * For example if the game provider sends a 'betAndWin' request then the child class should call
     *   $this->addWalletMethod('bet', ['user_id' => 1, 'amount' => 10,  'game_id' => 2560 ...]]);
     *   $this->addWalletMethod('win', ['user_id' => 1, 'amount' => 860, 'game_id' => 2560 ...]]);
     * Validation of these parameters is done later.
     *
     * @param mixed $wallet_method_name
     * @param mixed $wallet_method_parameters
     */
    protected function addWalletMethod($wallet_method_name = null, $wallet_method_parameters = null)
    {
        if (!$wallet_method_parameters) {
            $wallet_method_parameters = [];
        }
        $wallet_method_parameters['__wallet_method__'] = $wallet_method_name;

        $this->wallet_methods []= $wallet_method_parameters;

        $this->logDebug(['status' => 'success', 'file' => __METHOD__ . '::' . __LINE__, 'parameters' => $wallet_method_parameters]);
    }

    /**
     * Validates the authentication of the request. Can be overridden by child classes.
     *
     * @throws Exception
     */
    protected function validateRequestAuthentication()
    {
    }

    /**
     * Loads the user from the user identifier.
     * This method avoids unnecessary database calls if the user is already loaded. It is public so that it can be called from unit tests.
     *
     * @param mixed $user_identifier. A normal user ID (e.g. '5541343') or a Tournament user ID (e.g. '5541343e777777').
     */
    public function loadUser($user_identifier = null)
    {
        $this->user_identifier = trim($user_identifier) ?: null;
        $uid = $this->getUsrId($this->user_identifier) ?: null;
        $this->user = cu($uid) ?: null;
        if (!empty($this->user)) {
            $ud = $this->user->getData();
            $this->uid = $ud['id'];
            $this->user_identifier_currency = $this->getPlayCurrency($ud, $this->t_eid);
        }
    }

    /**
     * Returns the user identifier from the game provider request, either a normal user ID (e.g. '5541343') or a tournament user ID (e.g. '5541343e777777').
     *
     * @return false|mixed
     */
    protected function getUserIdFromRequest()
    {
        if (empty($this->wallet_methods)) {
            return false;
        }
        return $this->wallet_methods[0]['user_id'] ?? false;
    }

    /**
     * @param mixed $wallet_method
     * @param mixed $wallet_method_parameters
     * @throws Exception
     */
    protected function validateUserExists($wallet_method = null, $wallet_method_parameters = null)
    {
        $this->loadUser($wallet_method_parameters['user_id'] ?? null);
        if (!$this->uid) {
            throw new Exception("User not found.", self::EXCEPTION_CODE_USER_NOT_FOUND);
        }

        $this->logDebug(['status' => 'success', 'file' => __METHOD__ . '::' . __LINE__, 'user_identifier' => $this->user_identifier, 't_entry' => $this->t_entry, 't_eid' => $this->t_eid]);
    }

    /**
     * For wallet methods 'bet', 'win', 'freespinBet' and 'freespinWin' we verify that the user is enabled.
     * For wallet methods 'balance' and 'rollback' the user can be disabled.
     *
     * @param mixed $wallet_method
     * @param mixed $wallet_method_parameters
     * @throws Exception
     */
    protected function validateUserIsEnabled($wallet_method = null, $wallet_method_parameters = null)
    {
        if (!in_array($wallet_method, ['bet', 'win', 'freespinBet', 'freespinWin'])) {
            $this->logDebug(['status' => 'success', 'file' => __METHOD__ . '::' . __LINE__, 'user' => $this->user_identifier, 'wallet_method' => $wallet_method]);
            return;
        }

        if ($this->isEnabledVerifyIfPlayerIsBlocked()) {
            if ($this->user->isSuperBlocked()) {
                throw new Exception("User is banned.", self::EXCEPTION_CODE_USER_BANNED);
            }
            if ($this->user->isPlayBlocked()) {
                throw new Exception("User is blocked.", self::EXCEPTION_CODE_USER_BLOCKED);
            }
            if (!$this->user->getAttribute("active")) {
                throw new Exception("User is inactive.", self::EXCEPTION_CODE_USER_INACTIVE);
            }
        }

        $this->logDebug(['status' => 'success', 'file' => __METHOD__ . '::' . __LINE__, 'user' => $this->user_identifier, 'wallet_method' => $wallet_method]);
    }

    /**
     * Verifies the method exists.
     *
     * @param mixed $method_name The normalized method name, e.g. 'bet'.
     * @throws Exception
     */
    protected function validateWalletMethodExists($method_name = null)
    {
        static $wallet_methods = [
            'balance',
            'bet',
            'win',
            'rollback',
            'freespinBet',
            'freespinWin',
        ];
        if (!in_array($method_name, $wallet_methods)) {
            throw new Exception("Method not found: {$method_name}.", self::EXCEPTION_CODE_METHOD_NOT_FOUND);
        }
        $this->logDebug(['status' => 'success', 'file' => __METHOD__ . '::' . __LINE__, 'wallet_method' => $method_name]);
    }

    /**
     * Validates the parameters for the 'bet' method.
     *
     * @param mixed $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateBet(&$parameters = null)
    {
        $this->validateUserExists($parameters['__wallet_method__'], $parameters);
        $this->validateUserIsEnabled($parameters['__wallet_method__'], $parameters);

        $required = [
            'user_id',
            'session_id',
            'game_id',
            'round_id',
            'amount',
            'currency',
            'transaction_id',
        ];
        foreach ($required as $v) {
            if (!array_key_exists($v, $parameters) || !($parameters[$v])) {
                throw new Exception("Missing request parameter: {$v}.", self::EXCEPTION_CODE_INVALID_REQUEST);
            }
        }

        if (!is_numeric($parameters['amount']) || (($amount = (int)$parameters['amount']) < 0)) {
            throw new Exception("Invalid amount ({$parameters['amount']}).", self::EXCEPTION_CODE_INVALID_REQUEST);
        }

        if ($parameters['currency'] != $this->user_identifier_currency) {
            throw new Exception("Invalid currency.", self::EXCEPTION_CODE_INVALID_CURRENCY,
                new Exception("Invalid currency. Expected {$this->user_identifier_currency} for user {$this->user_identifier} but received {$parameters['currency']}.")
            );
        }

        $transaction_id = $parameters['transaction_id'];
        foreach ([true, false] as $check_cancelled_transactions) {
            $transaction = $this->getTransactionById($transaction_id, 'bets', $check_cancelled_transactions);
            if ($transaction) {
                if ($this->isIdempotentTransaction($parameters, $transaction)) {
                    throw new Exception("Idempotent transaction.", self::EXCEPTION_CODE_IDEMPOTENCY);
                } else {
                    throw new Exception("Duplicate transaction.", self::EXCEPTION_CODE_DUPLICATE_TRANSACTION);
                }
            }
        }

        if (!empty($parameters['amount']) || $this->isBetForced()) {
            if ($this->confirm_round === true) {
                $round_exists = $this->doesGameSessionBetExist($parameters['round_id']);
                if ($round_exists) {
                    throw new Exception("Duplicate round ID.", self::EXCEPTION_CODE_DUPLICATE_ROUND);
                }
            }
        }

        if (empty($this->game_data['active'] ?? null)) {
            throw new Exception("Game not found.", self::EXCEPTION_CODE_GAME_NOT_FOUND, new Exception("Game not active."));
        }
        if ($this->module_micro_games->isBlocked($this->game_data)) {
            throw new Exception("Game not found.", self::EXCEPTION_CODE_GAME_NOT_FOUND, new Exception("Game is blocked."));
        }
    }

    /**
     * Validates the parameters for the 'get_balance' method.
     *
     * @param mixed $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateGetBalance(&$parameters = null)
    {
        $this->validateUserExists($parameters['__wallet_method__'], $parameters);
        $this->validateUserIsEnabled($parameters['__wallet_method__'], $parameters);

        $required = [
            'user_id',
            'currency',
        ];
        foreach ($required as $v) {
            if (!array_key_exists($v, $parameters) || !($parameters[$v])) {
                throw new Exception("Missing request parameter: {$v}.", self::EXCEPTION_CODE_INVALID_REQUEST);
            }
        }

        if ($parameters['currency'] != $this->user_identifier_currency) {
            throw new Exception("Invalid currency.", self::EXCEPTION_CODE_INVALID_CURRENCY,
                new Exception("Invalid currency. Expected {$this->user_identifier_currency} for user {$this->user_identifier} but received {$parameters['currency']}.")
            );
        }
    }

    /**
     * Returns true if the new transaction is idempotent to the original transaction, otherwise false.
     * The parameters used to check idempotency are "user_id", "amount", "currency" if they are present in the new transaction.
     *
     * @param array $new_transaction
     * @param array $original_transaction
     * @return bool
     */
    protected function isIdempotentTransaction(array $new_transaction, array $original_transaction): bool
    {
        if (($new_transaction['amount'] ?? false) && (bcsub($new_transaction['amount'], $original_transaction['amount']) != 0)) {
            $this->logDebug(['status' => "Not idempotent (amount)", 'file' => __METHOD__ . '::' . __LINE__, 'new_transaction' => $new_transaction, 'original_transaction' => $original_transaction]);
            return false;
        }
        foreach (['user_id', 'currency'] as $k) {
            if (($new_transaction[$k] ?? false) && ($new_transaction[$k] != $original_transaction[$k])) {
                $this->logDebug(['status' => "Not idempotent ({$k})", 'file' => __METHOD__ . '::' . __LINE__, 'new_transaction' => $new_transaction, 'original_transaction' => $original_transaction]);
                return false;
            }
        }

        $this->logDebug(['status' => "Idempotent", 'file' => __METHOD__ . '::' . __LINE__, 'new_transaction' => $new_transaction, 'original_transaction' => $original_transaction]);
        return true;
    }

    /**
     * Validates the parameters for the 'win' method.
     *
     * @param mixed $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateWin(&$parameters = null)
    {
        $this->validateUserExists($parameters['__wallet_method__'], $parameters);
        $this->validateUserIsEnabled($parameters['__wallet_method__'], $parameters);

        $required = [
            'user_id',
            'session_id',
            'game_id',
            'round_id',
            'amount',
            'currency',
            'transaction_id',
        ];
        foreach ($required as $v) {
            if (!array_key_exists($v, $parameters) || (($v != 'amount') && !$parameters[$v])) {
                throw new Exception("Missing request parameter: {$v}.", self::EXCEPTION_CODE_INVALID_REQUEST);
            }
        }

        $amount = intval($parameters['amount']);
        if ($amount < 0) {
            throw new Exception("Invalid request parameter: amount.", self::EXCEPTION_CODE_INVALID_REQUEST);
        }

        if ($parameters['currency'] != $this->user_identifier_currency) {
            throw new Exception("Invalid currency.", self::EXCEPTION_CODE_INVALID_CURRENCY,
                new Exception("Invalid currency. Expected {$this->user_identifier_currency} for user {$this->user_identifier} but received {$parameters['currency']}.")
            );
        }

        $transaction_id = $parameters['transaction_id'];
        foreach ([true, false] as $check_cancelled_transactions) {
            $transaction = $this->getTransactionById($transaction_id, 'wins', $check_cancelled_transactions);
            if ($transaction) {
                if ($this->isIdempotentTransaction($parameters, $transaction)) {
                    throw new Exception("Idempotent transaction.", self::EXCEPTION_CODE_IDEMPOTENCY);
                } else {
                    throw new Exception("Duplicate transaction.", self::EXCEPTION_CODE_DUPLICATE_TRANSACTION);
                }
            }
        }

        if (empty($this->game_data)) {
            throw new Exception("Game not found.", self::EXCEPTION_CODE_GAME_NOT_FOUND, new Exception("Game not active."));
        }
    }

    /**
     * Validates the parameters for the 'rollback' method.
     *
     * @param mixed $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateRollback(&$parameters = null)
    {
        $this->validateUserExists($parameters['__wallet_method__'], $parameters);
        $this->validateUserIsEnabled($parameters['__wallet_method__'], $parameters);

        $required = [
            'user_id',
            'transaction_id',
        ];
        foreach ($required as $v) {
            if (!array_key_exists($v, $parameters) || !($parameters[$v])) {
                throw new Exception("Missing request parameter: {$v}.", self::EXCEPTION_CODE_INVALID_REQUEST);
            }
        }
    }

    /**
     * Validates the parameters for the 'freespinBet' method.
     *
     * @param mixed $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateFreespinBet(&$parameters = null)
    {
        $this->validateUserExists($parameters['__wallet_method__'], $parameters);
        $this->validateUserIsEnabled($parameters['__wallet_method__'], $parameters);
    }

    /**
     * Validates the parameters for the 'freespinWin' method.
     *
     * @param mixed $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateFreespinWin(&$parameters = null)
    {
        $this->validateUserExists($parameters['__wallet_method__'], $parameters);
        $this->validateUserIsEnabled($parameters['__wallet_method__'], $parameters);
    }

    /**
     * Sets the header content type for our response to the game provider request.
     *
     * @param string $http_content_type. Supported values are:
     *  - 'application/json'
     *  - 'text/xml'
     *  - 'text/html'
     *  - 'application/x-www-form-urlencoded'
     */
    protected function setHttpContentType(string $http_content_type)
    {
        $supported = [
            'application/json',
            'text/xml',
            'text/html',
            'application/x-www-form-urlencoded',
        ];

        $assert_description = sprintf(
            "Invalid \$http_content_type. Received [%s]. Supported values: [%s].",
            $http_content_type,
            implode(', ', $supported)
        );
        assert(in_array($http_content_type, $supported), $assert_description);

        $this->http_content_type = $http_content_type;
    }

    /**
     * @return bool
     */
    public function isForceHttpOkResponse(): bool
    {
        return $this->force_http_ok_response;
    }

    /**
     * @param bool $force_http_ok_response
     */
    public function setForceHttpOkResponse(bool $force_http_ok_response)
    {
        $this->force_http_ok_response = $force_http_ok_response;
    }

    /**
     * Inform the GP about the amount of free spins available for a player.
     * The player can open the game anytime.
     * Often if the GP keeps track itself and send the wins at the end when all FRB are finished like with Netent.
     *
     * @param $uid
     * @param $game_ids
     * @param $frb_granted
     * @param $bonus_name
     * @param $bonus_entry
     * @return mixed
     */
    public function awardFRBonus($uid, $game_ids, $frb_granted, $bonus_name, $bonus_entry)
    {
        return $game_ids;
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
     *
     * if rake_percent == 0 status must be directly approved in bonus_entries
     * in case of frb/w coming per request ($this_m_bFrwSendPerBet === true) it's possible to see the frb winning under bonus cash balance on the site but only if the status is active, not when its approved upfront like nentent.
     * currently wager/deposit free frb must be set to approved directly because if a user has cash_balance == 0
     * and the frb's have been played all and handleFspinWin is executed the winning amount is not added to the users cash balance but the status is updated to approved.
     * the problem is located in Casino.php -> changeBalance() in the condition if($type == 2 || $type == 7). This line of code return an array: !empty(phive("Bonuses")->onlyBonusBalanceEntries($this->bonus_bet))
     * because of that $inc_result = $user->incrementAttribute('cash_balance', $amount); is not executed
     *
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
    }

    /**
     * Wrapper to the game prefix which can be used for mg_ids and round_ids that may or may not append the country to the prefix
     *
     * @return string|null
     */
    public function getTransactionPrefix()
    {
        $prefix = $this->getGamePrefix();
        if ($this->getLicSetting('add_country_prefix', $this->user)) {
            $prefix = $prefix . strtolower(licJur($this->user)) . '_';
        }

        return $prefix;
    }

    /**
     * Get a prefix which is the lowercase class name which can be used for game ids mostly
     *
     * @return string|string[]
     */
    public function getGamePrefix()
    {
        return $this->getGameProviderName() . '_';
    }

    /**
     * Prefixes the game provider name to the string, if not already present.
     *
     * @param mixed $string
     * @return string
     */
    protected function addGamePrefix($string = null): string
    {
        $prefix = $this->getGamePrefix();
        if (strpos($string, $prefix) === 0) {
            return $string;
        }
        return $prefix . $string;
    }

    /**
     * Strips the game provider name from the string, if present.
     *
     * @param mixed $string
     * @return string|null
     */
    protected function stripGamePrefix($string = null): ?string
    {
        $prefix = $this->getGamePrefix();
        if (strpos($string, $prefix) !== 0) {
            return $string;
        }
        return substr($string, strlen($prefix));
    }

    /**
     * This should return the lower cased provider name, primarily used for getPrefix
     * @return string
     */
    protected function getGameProviderName(): string
    {
        return preg_replace("/\W|_/", '', strtolower(get_class($this)));
    }

    /**
     * Get the desktop game launcher URL.
     * This method must be implemented because it is invoked by phive/modules/Micro/MicroGames.php::2221
     *
     * @param mixed $game_id The external game ref.
     * @param string $lang The language code
     * @param bool $show_demo Force game to load in demo mode
     * @param array $game
     * @return string
     */
    public function getDepUrl($game_id, $lang, $game = null, $show_demo = false)
    {
        $game = $this->module_micro_games->getByGameId($game_id);
        return $this->getDesktopMobileUrl($game, 'desktop', $lang, $show_demo);
    }

    /**
     *
     * Get the mobile game launcher URL
     * Overrides the external launch and game ids if we have a record in the game_country_overrides table.
     *
     * @param string $ext_game_name The game_ext_name (used in the launch-url to load the game and this is the game ID as provided by the GP, which the GP name prefixed to it) from the micro_games table
     * @param string $lang The language code
     * @param string $lobby_url The lobby url
     * @param array $game Array with game data received from MicroGames object
     * @param array $args Array with all info passed to onPlay
     * @param bool $show_demo Force the game to launch in demo mode even with loged in user
     * @return string
     */
    public function getMobilePlayUrl($ext_game_name, $lang, $lobby_url, $game, $args = [], $show_demo = false)
    {
        return $this->getDesktopMobileUrl($game, 'mobile', $lang, $show_demo);
    }

    /**
     * @param array $game
     * @param string $device
     * @param string $lang
     * @param bool|null $show_demo
     * @return false|mixed
     */
    protected function getDesktopMobileUrl(array $game, string $device, string $lang, bool $show_demo = null)
    {
        if (!empty($_SESSION['token_uid'])) {
            $this->game_data = phive('MicroGames')->overrideGameForTournaments(null, $game);
        } else {
            $this->game_data = phive('MicroGames')->overrideGame(null, $game);
        }

        $url = $this->getUrl($this->game_data, $lang, $device);
        $this->logDebug(
            [
                'url' => $url,
                'game_id' => $this->game_data['id'] ?? '',
                'game_ref' => $this->game_data['game_id'] ?? '',
                'original_game_ref' => $this->game_data['original_game_id'] ?? $this->game_data['game_id'] ?? '',
                'device' => $device,
                'lang' => $lang,
            ],
            $this->getGameProviderName() . '_' . __FUNCTION__
        );
        return $url;
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param array $game This will be the entire game object, overridden or not
     * @param string $lang The lang code
     * @param string $device The target. desktop|mobile
     * @param bool $show_demo Force the game to launch on demo mode
     * @return mixed The url to open the game
     */
    protected function getUrl($game, $lang = '', $device = '', $show_demo = false)
    {
        return null;
    }

    /**
     * @param string $external_session_id
     * @param $user_id
     * @param string $game_id
     */
    public function setExternalSessionId(string $external_session_id, $user_id, string $game_id)
    {
        $session_key = $user_id . 'u' . $game_id;
        $session_data = [
            'sessionid' => $session_key,
            'gpsessionid' => $external_session_id,
        ];
        $user_uid = $this->getUserUidFromUserId($user_id);
        phMsetShard($session_key, $session_data, $user_uid);
    }

    /**
     * Returns the external (game provider's) session ID.
     *
     * @param int|string $user_id The user ID or tournament user ID.
     * @param string $game_id
     * @return string|bool The game provider's session identifier.
     */
    protected function getExternalSession($user_id, string $game_id)
    {
        $user_uid = $this->getUserUidFromUserId($user_id);
        $session_key = $user_id . 'u' . $game_id;
        $value = phMgetShard($session_key, $user_uid);
        if (empty($value)) {
            $this->logDebug([
                'status' => 'success',
                'message' => "External game provider session not found.",
                'user uid' => $user_uid,
                'session key' => $session_key,
            ]);
            return false;
        }
        $value = json_decode($value, false);
        $value = $value->gpsessionid ?? false;
        $this->logDebug([
            'status' => 'success',
            'message' => "External game provider session found OK.",
            'user uid' => $user_uid,
            'session key' => $session_key,
            'session data' => $value,
        ]);
        return $value;
    }

    /**
     * @param $user_id
     * @param string $game_id
     */
    public function deleteExternalSessionId($user_id, string $game_id)
    {
        $user_uid = $this->getUserUidFromUserId($user_id);
        $session_key = $user_id . 'u' . $game_id;
        phMdelShard($session_key, $user_uid);
        $this->logDebug([
            'status' => 'success',
            'method' => __METHOD__,
            'message' => "Deleted external session ID",
            'session key' => $session_key,
            'user uid' => $user_uid,
        ]);
    }

    /**
     *
     * @param mixed|string $user_id
     * @return mixed|string
     */
    protected function getUserUidFromUserId($user_id)
    {
        foreach (['e', 'u'] as $c) {
            if (strpos($user_id, $c) !== false) {
                $user_id = explode($c, $user_id)[0];
            }
        }
        return $user_id;
    }

    /**
     * Convert a number from one coinage to another coinage.
     * eg. 1 unit = 100 cents, 1 dismes = 10 cents, 1 cents = 1 cents, 1 milles = 0.1 cents
     *
     * @param int $amount A number which represents either a unit|dismes|milles|cents
     * @param string $from Which coinage has the $p_iAmount. Options are: self::COINAGE_UNITS|self::COINAGE_DISMES|self::COINAGE_MILLES|self::COINAGE_CENTS
     * @param string $to To which coinage the $p_iAmount has to be converted. Options are: self::COINAGE_UNITS|self::COINAGE_DISMES|self::COINAGE_MILLES|self::COINAGE_CENTS
     * @return float
     */
    public function convertCoinage($amount, $from = self::COINAGE_CENTS, $to = self::COINAGE_CENTS)
    {
        switch ($from) {
            case self::COINAGE_UNITS: // whole EUR/USD/etc
                $converted_amount = $amount * 100;
                break;

            case self::COINAGE_DISMES: // tenths
                $converted_amount = $amount * 10;
                break;

            case self::COINAGE_MILLES: // thousandths
                $converted_amount = $amount / 10;
                break;

            case self::COINAGE_CENTS: // hundredths
            default:
                $converted_amount = $amount;
                break;
        }

        switch ($to) {
            case self::COINAGE_UNITS: // whole EUR/USD/etc
                $converted_amount = round($converted_amount / 100, 2);
                break;

            case self::COINAGE_DISMES: // tenths
                $converted_amount = round($converted_amount / 10, 2);
                break;

            case self::COINAGE_MILLES: // thousandths
                $converted_amount = round($converted_amount * 10, 2);
                break;

            case self::COINAGE_CENTS: // hundredths
            default:
                break;
        }

        return $converted_amount;
    }

    /**
     * Processes the exception, sets the response headers and returns the json_encoded response body.
     * Child classes can override this method to customize error handling.
     *
     * @param Exception $e
     * @return string. The json_encoded response body.
     */
    protected function getExceptionResponse(Exception $e): string
    {
        $default_error = [
            'code' => 'ER01',
            'message' => $e->getMessage(),
        ];
        $error_setting = $this->getErrorSettingFromException($e, $default_error);

        return $this->response($error_setting);
    }

    /**
     * Returns an array of detailed information about the exception.
     *
     * @param Exception $e
     * @return array
     */
    protected function getExceptionDetails(Exception $e): array
    {
        $details = ['exception' => $e->getMessage()];
        if ($e->getPrevious() && $e->getPrevious()->getMessage()) {
            $details['exception details'] = $e->getPrevious()->getMessage();
        }
        $details['exception code'] = $e->getCode();
        $details['provider'] = sprintf("%s::%s", get_class($this), $this->game_provider_method_name);
        $details['user_id'] = $this->uid;
        $details['stack trace'] = sprintf("%s::%s. %s", $e->getFile(), $e->getLine(), $e->getTrace());
        $details['request'] = $this->raw_request;
        if ($this->request_data_type == 'json') {
            $details['request'] = json_encode($this->raw_request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } elseif ($this->request_data_type == 'xml') {
            $details['request'] = $this->raw_request->asXML();
        } else {
            $details['request'] = $this->raw_request;
        }
        $details['wallet methods'] = $this->wallet_methods;

        return $details;
    }

    /**
     * Returns the error setting which matches the specified exception.
     *
     * @param Exception $e
     * @param mixed $default_error
     */
    protected function getErrorSettingFromException(Exception $e, $default_error = false)
    {
        $code = $e->getCode();
        if ($code) {
            foreach ($this->errors as $error_setting) {
                if ($code == ($error_setting['exception_code'] ?? null)) {
                    if ($code == self::EXCEPTION_CODE_INVALID_REQUEST) {
                        $error_setting['message'] = $e->getMessage();
                    }
                    return $error_setting;
                }
            }
        }
        return $default_error;
    }

    /**
     * Log messages to the trans_log table in the database.
     * This method only logs to the database on staging and not on production server, except if force parameter is overruled.
     *
     * @param mixed $data The data that should be logged
     * @param string $key The key to use as a reference for this message
     * @param bool $force Overrule config setting 'log_errors' which is only true on staging and not production
     */
    public function logError($data, string $key = '', bool $force = false)
    {
        if (($this->getSetting('log_errors') === true) || ($force === true)) {
            if (empty($key)) {
                $key = sprintf("%s_error-%s", $this->getGameProviderName(), $this->game_provider_method_name);
            }
            $this->logToDatabase($data, $key);
        }
    }

    /**
     * @param mixed $data
     * @param string $key
     */
    public function logInfo($data, string $key = '')
    {
        if ($this->getSetting('log_info')) {
            $this->logToDatabase($data, $key);
        }
    }

    /**
     * @param mixed $data
     * @param string $key
     */
    public function logDebug($data = null, string $key = '')
    {
        if ($this->getSetting('log_debug')) {
            $this->logToDatabase($data, $key);
        }
    }

    /**
     * The default log method is equivalent to logDebug.
     * @param $data
     * @param string $key
     */
    public function log($data, string $key = '')
    {
        if ($this->getSetting('log_info')) {
            $this->logToDatabase($data, $key);
        }
    }

    /**
     * @param mixed $data
     * @param string $key
     */
    private function logToDatabase($data = null, string $key = '')
    {
        static $step = 0;
        $step++;

        if (empty($key)) {
            $key = sprintf("%s_%s", $this->getGameProviderName(), $this->game_provider_method_name ?: '');
        }
        $key .= " {$step}";

        phive()->dumpTbl($key, $data, $this->uid ?: 0);
    }

    /**
     * Overridden by the child class to send the response to the game provider.
     *
     * @param mixed $response true if command was executed successfully or an array with the error details from property $_m_aErrors
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to gp
     */
    protected function response($response)
    {
        return false;
    }

    /**
     * Get an error by it's key
     *
     * @param string $key The constant key eg. ER{XX}
     * @return mixed false if error was not found
     */
    protected function getError($key)
    {
        if (isset($this->errors[$key])) {
            $message = $this->errors[$key];
            if ('ER06' == $key && !empty(t('insufficient.funds'))) {
                $message['message'] = t('insufficient.funds');
            }
            if (isset($message['return']) && $message['return'] !== 'default') {
                return $message['return'];
            }
            unset($message['return']);
            return $message;
        }
        return false;
    }

    /**
     * Returns a boolean indicating whether or not to verify if the player is blocked.
     * Typically we should not verify this for rollback actions.
     *
     * @return bool
     */
    protected function isEnabledVerifyIfPlayerIsBlocked(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isMultiCall(): bool
    {
        return $this->is_multi_call;
    }

    public function getWalletMethod(): string
    {
        return $this->wallet_method;
    }

    /**
     * Log the execution time needed to process a request received from a GP
     * @return void
     */
    protected function logExecutionTime()
    {
        $ended_at = microtime(true);
        $duration = $ended_at - $this->microtime_start;
        $insert = [
            'duration' => $duration,
            'username' => $this->uid,
            'mg_id' => (isset($this->game_data['id']) ? $this->game_data['id'] : 0),
            'token'  => $this->getGameProviderName(),
            'method' => $this->game_provider_method_name,
            'host' => gethostname(),
        ];

        if ($this->getSetting('log_game_replies') === true) {
            $this->db->insertArray('game_replies', $insert);
        }

        if ($this->getSetting('log_slow_game_replies') === true) {
            phive('MicroGames')->logSlowGameReply($duration, $insert);
        }
    }

    /**
     * Executes the 'balance' wallet method.
     * Currently this method implementation does nothing because other wallet methods such as 'bet' and 'win'
     * also need to get the player balance, by calling $this->getPlayerBalance().
     */
    protected function balance(array $wallet_method_parameters)
    {
    }

    /**
     * Here we don't actually insert any rows or affect the player's balance what we do is deduct the frb_remaining
     * from that bonus_entry (free spin) by the user and the game
     *
     * @param array $request_data
     * @return mixed
     * @throws Exception
     */
    protected function freeSpinBet(array $request_data)
    {
        $this->validateFreespinBet($request_data);

        $freespins = $this->getFreespins();

        if (empty($freespins)) {
            $this->logError([
                'message' => "Free spin not found.",
                'method' => 'freeSpinBet',
                'parameters' => $request_data,
            ]);
            throw new Exception("Free spin not found.", self::EXCEPTION_CODE_FREESPIN_NOT_FOUND);
        }
        $ud = $this->user->getData();

        if ($freespins['frb_remaining'] > 0) {
            $bonus_entry_id = $freespins['id'];
            $result = $this->db->sh($ud['id'])->incrValue('bonus_entries', 'frb_remaining', ['id' => $bonus_entry_id], -1, [], $ud['id']);

            if ($result === false) {
                $this->logError([
                    'message' => "Failed to decrement free spins.",
                    'method' => 'freeSpinBet',
                    'parameters' => $request_data,
                ]);
                throw new Exception("Failed to decrement free spins.", self::EXCEPTION_CODE_INTERNAL_ERROR);
            }

            // reset the free spin object as it might be needed in response
            $freespins = phive('Bonuses')->getBonusEntryBy($ud['id'], $bonus_entry_id, '', $this->getGameProviderName());
            $this->setFreespins($freespins);

            // here we check if we we're on the last spin and call the win to credit player
            if ($this->isFreespinRoundEnded() && $this->receive_final_win === false) {
                $this->freeSpinWin($request_data);
            }

            return $result;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getFreespins(): array
    {
        return $this->freespins;
    }

    /**
     * @param array $freespins
     */
    public function setFreespins(array $freespins)
    {
        $this->freespins = $freespins;
    }

    /**
     * This is simply a check to see if all of the current bonus_entry['frb_remaining'] has been used up
     *
     * @return bool
     */
    protected function isFreespinRoundEnded()
    {
        $free_spins = $this->getFreespins();
        if (!is_array($free_spins) || !array_key_exists('frb_remaining', $free_spins)) {
            return false;
        }
        return $free_spins['frb_remaining'] < 1;
    }

    /**
     * Handler for the insertion of any wins during a freespin round.
     *
     * First we have the $this->freespins holding the bonus_entry of the player and game i.e. currenct freespin.
     * We need to have '$bonus_entry_type' holding the inner join of the bonus_entry with the bonus type.
     *
     * If it's a regular round with an actual freespin win transaction that we need to store.
     * If we are not at the end of the freespins round then we update the balance with the win
     *
     * @param array $request_data
     * @return bool|mixed
     * @throws Exception
     */
    protected function freeSpinWin(array $request_data)
    {
        $this->validateFreespinWin($request_data);

        $freespins = $this->getFreespins();
        if (empty($freespins)) {
            $this->logError([
                'message' => "Free spin not found.",
                'method' => 'freeSpinWin',
                'parameters' => $request_data,
            ]);
            throw new Exception("Free spin not found.", self::EXCEPTION_CODE_FREESPIN_NOT_FOUND);
        }

        $amount = $request_data['amount'];
        $mg_id = $request_data['transaction_id'];
        $this->prefixTransaction($mg_id);
        $ud = $this->user->getData();

        $bonus_entry_type = $this->module_casino_bonuses->getBonusEntry($freespins['id'], $ud['id']);

        $final_request = $this->isFreespinRoundEnded();

        if ($bonus_entry_type['frb_remaining'] < 0) {
            $this->logError([
                'message' => "No freespins remaining.",
                'method' => 'freeSpinWin',
                'parameters' => $request_data,
            ]);
            throw new Exception("No freespins remaining.", self::EXCEPTION_CODE_NO_FREESPINS_REMAINING);
        }

        if (!$final_request && !$this->receive_final_win) {
            $this->db->incrValue('bonus_entries', 'balance', ['id' => (int)$bonus_entry_type['id']], $amount, [], $ud['id']);
            $this->module_casino_bonuses->resetEntries();
        } else {
            if (($final_request && $bonus_entry_type['cost'] <= 0) || $this->receive_final_win) {
                $bonus_bet_award_type = $this->getBonusBetAwardTypeCode();
                $balance = $this->getPlayerBalance();
                
                $this->wallet_txn_wins = $this->insertWin($ud, $this->game_data, $balance, 0, $amount,
                    $bonus_bet_award_type, $mg_id, 3, null);

                if (!$this->receive_final_win) {
                    //if _receive_final_win === true than the winning is added to the bonus_entries::balance per request so we
                    // need to send the $e['balance'] to 0 and the $amount is added to 0 again below as the winning amount
                    // each freespin win has been added to bonus_entries::balance straight after each freespin bet.
                    // So when freespins are finished this balance represents the total winning
                    $tmp_balance = $bonus_entry_type['balance'];
                    $bonus_entry_type['balance'] = 0; // we want to save the bonus_entry with 0 balance
                    $amount = $tmp_balance; // we're moving the balance into the player's transactions
                } else {
                    $bonus_entry_type['balance'] = 0;
                }

                if (empty($amount)) {
                    $this->logInfo('Free spin bonus without winnings');
                    $this->module_casino_bonuses->fail($bonus_entry_type, 'Free spin bonus without winnings');
                } else {
                    if (($freespins['rake_percent'] > 0 && $bonus_entry_type['status'] != 'active') || // bonus with wager or deposit requirement
                        ($freespins['rake_percent'] <= 0 && $bonus_entry_type['status'] != 'approved') // bonus without wager or deposit requirement
                    ) {
                        $this->logInfo("Forfeited $amount cents due to failed FRB bonus.");
                        $this->module_user_handler->logAction($bonus_entry_type['user_id'],
                            "Forfeited $amount cents due to failed FRB bonus.",
                            $this->getGameProviderName() . '-frb-fail');
                    } else {
                        $this->logInfo([
                            'handle the frw',
                            'user id :' . $ud['id'],
                            'Freespin win',
                            $bonus_entry_type,
                            $amount
                        ]);

                        $this->handleFspinWin($bonus_entry_type, $amount, $ud['id'], 'Freespin win');
                    }
                }

            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the code used for bets/wins:bonus_bet and wins:award_type
     * @return int
     */
    private function getBonusBetAwardTypeCode()
    {
        $freespins = $this->getFreespins();
        if ($freespins['rake_percent'] > 0) {
            if ($freespins['deposit_limit'] > 0) {
                return self::BONUS_AWARD_TYPE_FREESPIN_DEPOSIT;
            } else {
                return self::BONUS_AWARD_TYPE_FREESPIN_WAGER;
            }
        } else {
            return self::BONUS_AWARD_TYPE_FREESPIN_REWARD;
        }
    }

    /**
     * Get a bet or win data array by the transaction ID
     *
     * @param int $mg_id The transaction ID as received from the GP without our prefix
     * @param string $table Is it the bets|wins table
     * @param bool $check_is_cancelled Do we check if it's a cancelled bet or win
     * @return array|bool False if no entry found
     */
    protected function getTransactionById($mg_id, $table, $check_is_cancelled = false)
    {
        $this->prefixTransaction($mg_id);
        return $this->getBetByMgId($mg_id . (!empty($check_is_cancelled) ? 'ref' : ''), $table, 'mg_id', $this->uid);
    }

    /**
     * Used to attach a prefix to an ext id.
     *
     * Used when we want to save a foreign id in bets|wins.mg_id and have it be unique, then we call this method
     * BEFORE we insert the bet|win, on the foreign id. This is to avoid duplicate mg_ids in cased the GP has multiple
     * environments and doesn't care to make sure that its IDs are unique in the aggregate.
     *
     * @param string &$ext_tr_id The GP id as it was sent to us.
     */
    public function prefixTransaction(&$ext_tr_id)
    {
        if (strpos($ext_tr_id, $this->getTransactionPrefix()) === false) {
            $ext_tr_id = $this->getTransactionPrefix() . $ext_tr_id;
        }
    }

    /**
     * This should return the award_type_code
     * @param $jackpot_contribution
     * @return int
     */
    protected function getAwardTypeCode($jackpot_contribution)
    {
        return isset($jackpot_contribution) && $jackpot_contribution > 0 ? self::JACKPOT_WIN_AWARD_TYPE : self::NORMAL_WIN_AWARD_TYPE;
    }

    /**
     * @return int
     */
    public function getWalletTxnWins(): int
    {
        return $this->wallet_txn_wins;
    }

    /**
     * @param int $wallet_txn_wins
     */
    public function setWalletTxnWins(int $wallet_txn_wins)
    {
        $this->wallet_txn_wins = $wallet_txn_wins;
    }

    /**
     * Here we pass the usual process params with the mg_id and the table we want to cancel.
     * Note that the amount we want to rollback can possibly be only part of the amount saved in the bet/win row.
     *
     * If the transactionID/mg_id doesn't exist, the bet has never arrived on our server, maybe because it timed-out,
     * so we insert the bet with amount 0 to avoid that it will be processed on a later moment again if the bet
     * request goes through.
     * If /bet request times-out (so our server doesn't answer it) a /win request will never be send by the GP
     *
     * @param array $request_data
     * @throws Exception
     */
    protected function rollback(array $request_data)
    {
        $this->logDebug(['status' => 'starting', 'file' => __METHOD__ . '::' . __LINE__, 'parameters' => $request_data]);

        $this->validateRollback($request_data);

        $amount = $request_data['amount'];

        $mg_id = $request_data['transaction_id'];
        $this->prefixTransaction($mg_id);

        $existing_transaction = $this->getExistingTransaction($mg_id);
        if (empty($existing_transaction)) {
            throw new Exception("Transaction not found.", self::EXCEPTION_CODE_TRANSACTION_NOT_FOUND);
        }

        $is_cancelled = (strpos($existing_transaction['mg_id'], 'ref') !== false);
        if ($is_cancelled) {
            throw new Exception("Transaction already rolled back.", self::EXCEPTION_CODE_TRANSACTION_ALREADY_ROLLED_BACK);
        }

        if (!$this->isPartialRefund() && $this->isTransactionMismatch($amount, $mg_id, $existing_transaction)) {
            throw new Exception(
                "Transaction already rolled back.",
                self::EXCEPTION_CODE_TRANSACTION_DETAILS_MISMATCH,
                new Exception("Transaction details mismatch.")
            );
        } else if ($this->isPartialRefund()) {
            $new_amount = ($existing_transaction['amount'] - $amount);
            if ($new_amount < 0) {
                throw new Exception("Insufficient funds for rollback.", self::EXCEPTION_CODE_ROLLBACK_INSUFFICIENT_FUNDS);
            }
        }

        $table = $this->getCurrentTable();

        if ($table == self::BETS_TABLE) {
            $type = self::CHANGED_BALANCE_TYPE_ROLLBACK;
        } else {
            $type = self::CHANGE_BALANCE_TYPE_BET;
            $amount = -$amount;
        }

        if ($table == self::BETS_TABLE) {
            $typeof_original_transaction = $is_cancelled ? 'rolled_back_bet' : 'bet';
        } else {
            $typeof_original_transaction = $is_cancelled ? 'rolled_back_win' : 'win';
        }
        $this->allowRollback($request_data, $existing_transaction, $typeof_original_transaction);

        $current_balance = $this->getPlayerBalance();
        $existing_transaction = $this->doRollbackUpdate($mg_id, $table, $current_balance, $amount);
        if ($existing_transaction === false) {
            throw new Exception("Rollback failed.", self::EXCEPTION_CODE_INTERNAL_ERROR);
        }

        $this->playChgBalance($this->uid, $amount, '', $type);
        $this->setWalletTxnRollbacks($existing_transaction);

        $this->logDebug([
            'status' => 'success',
            'file' => __METHOD__ . '::' . __LINE__,
            'transaction_id' => (($table == self::BETS_TABLE) ? "bet " : "win ") . ($existing_transaction['id'] ?? 0),
            'parameters' => $request_data,
        ]);
    }

    /**
     * If a child class overrides this method it should throw an exception if the rollback is not allowed.
     *
     * @param array $rollback. The wallet parameters for the rollback transaction.
     * @param array $original_transaction. The transaction to roll back, which is perhaps already rolled back.
     * @param string $typeof_original_transaction. 'bet', 'rolled_back_bet', 'win', 'rolled_back_win'
     * @throws Exception
     */
    protected function allowRollback(array $rollback, array $original_transaction, string $typeof_original_transaction)
    {
    }

    /**
     * @param array $wallet_method_parameters
     * @throws Exception
     */
    protected function insertEmptyBet(array $wallet_method_parameters)
    {
        $parameters = array_merge($wallet_method_parameters, [
            'amount' => 0,
            'transaction_id' => $wallet_method_parameters['transaction_id'] . 'ref',
        ]);

        $this->setBetForced(true);
        $this->bet($parameters);
    }

    /**
     * This returns the row of the transaction if it's been cancelled or not.
     * We don't care if e transaction is cancelled or not.
     *
     * @param $mg_id
     * @return array|bool|mixed|string|string[]|null
     */
    protected function getExistingTransaction($mg_id)
    {
        $uid = $this->uid;

        $sql_str = "SELECT * FROM bets WHERE mg_id = '{$mg_id}' OR mg_id = '{$mg_id}ref'";

        $txn = $this->db->sh($uid)->loadAssoc($sql_str);

        if (!empty($txn)) {
            $this->setCurrentTable(self::BETS_TABLE);
            return $txn;
        }

        $sql_str = "SELECT * FROM wins WHERE mg_id = '{$mg_id}' OR mg_id = '{$mg_id}ref'";

        $txn = $this->db->sh($uid)->loadAssoc($sql_str);

        $this->setCurrentTable(self::WINS_TABLE);

        return $txn;
    }

    /**
     * This is the method used to validate the processed request received byt the provider for only cash bets and mp bets
     * Basically anything that isn't Freespin bets
     * @param array $request_data
     * @throws Exception
     */
    protected function bet(array &$request_data)
    {
        $this->logDebug(['status' => 'starting', 'file' => __METHOD__ . '::' . __LINE__, 'parameters' => $request_data]);

        if (empty($this->user) || empty($this->user->data)) {
            throw new Exception("User not found.", self::EXCEPTION_CODE_USER_NOT_FOUND);
        }

        $this->validateBet($request_data);

        $current_balance = $this->getPlayerBalance();
        $new_balance = $this->lgaMobileBalance($this->user->data, $this->game_data['ext_game_name'], $current_balance, $this->game_data['device_type'], $request_data['amount']);
        if ($new_balance < $request_data['amount']) {
            throw new Exception(
                "Insufficient funds.",
                self::EXCEPTION_CODE_INSUFFICIENT_FUNDS,
                new Exception("Insufficient funds. User: {$request_data['user_id']}, current balance: {$current_balance}, lgaBalance: {$new_balance}.")
            );
        }
        if (($request_data['amount'] == 0) && !$this->isBetForced()) {
            throw new Exception(
                "Insufficient funds.",
                self::EXCEPTION_CODE_INSUFFICIENT_FUNDS,
                new Exception("Bet has null amount. User: {$request_data['user_id']}, amount: {$request_data['amount']}.")
            );
        }

        $mg_id = $request_data['transaction_id'];
        $this->prefixTransaction($mg_id);
        $round_id = $request_data['round_id'];
        $amount = $request_data['amount'];
        $bonus_bet_type = $this->bonusBetType();

        // TODO: The original line was "$jp_contrib = $processed_params['jackpot_contribution'] ?: 0;" but what is 'jackpot_contribution' ??
        $jp_contrib = 0;

        $log = [
            'status' => 'starting',
            'file' => __METHOD__ . '::' . __LINE__,
            'method' => 'insertBet',
            'user_id' => $this->user->data['id'],
            'game_ref' => $this->game_data['ext_game_name'],
            'round_id' => $round_id,
            'mg_id' => $mg_id,
            'amount' => $amount,
            'jp_contrib' => $jp_contrib,
            'bonus_bet_type' => $bonus_bet_type,
            'new_balance' => $new_balance,
        ];
        $this->logDebug($log);
        
        // Take from the balance and after insert the bet
        $new_balance = $this->playChgBalance($this->user->data, -$amount, null, 1);
        if ($new_balance === false) {
            throw new Exception("Error inserting new bet into DB.", self::EXCEPTION_CODE_INSUFFICIENT_FUNDS);
        }

        $bet_id = $this->insertBet($this->user->data, $this->game_data, $round_id, $mg_id, $amount, $jp_contrib, $bonus_bet_type, $new_balance);
        if (!$bet_id) {
            throw new Exception("Error inserting new bet into DB.", self::EXCEPTION_CODE_INTERNAL_ERROR);
        }
        $this->setWalletTxnBets($bet_id);

        $game_provider_round_id = $request_data['round_id'];
        $this->prefixTransaction($game_provider_round_id);
        $log = [
            'status' => 'starting',
            'file' => __METHOD__ . '::' . __LINE__,
            'method' => 'insertRound',
            'user_id' => $this->user->data['id'],
            'bet_id' => $bet_id,
            'round_id' => $game_provider_round_id,
        ];
        $this->logDebug($log);

        $this->logDebug([
            'status' => 'success',
            'file' => __METHOD__ . '::' . __LINE__,
            'message' => "Inserted bet into DB.",
            'transaction_id' => $bet_id,
            'parameters' => $request_data,
        ]);
    }

    /**
     * This is the win request for regular wins and mp wins that insert into their respective tables
     *
     * @param array $request_data
     * @throws Exception
     */
    protected function win(array $request_data)
    {
        $this->logDebug(['status' => 'starting', 'file' => __METHOD__ . '::' . __LINE__, 'parameters' => $request_data]);

        if (empty($this->user) || empty($this->user->data)) {
            throw new Exception("User not found.", self::EXCEPTION_CODE_USER_NOT_FOUND);
        }

        $this->validateWin($request_data);

        if (empty($request_data['amount'])) {
            $this->logDebug(['status' => 'success', 'message' => "Ignoring win with null amount.", 'method' => 'win', 'parameters' => $request_data]);
            return;
        }

        if ($this->getSetting('confirm_wins')) {
            $round_id = $request_data['round_id'];
            $this->prefixTransaction($round_id);
            $db_round = $this->confirmWin($this->user->data['id'], $round_id);
            if (!$db_round) {
                throw new Exception(
                    "Matching bet not found for this round.",
                    self::EXCEPTION_CODE_MATCHING_TRANSACTION_NOT_FOUND,
                    new Exception("Matching bet not found for user [{$this->user->data['id']}] and round [{$round_id}].")
                );
            }

            if ($this->confirm_win) {
                if ($db_round['win_id']) {
                    throw new Exception(
                        "Win already exists for this round.",
                        self::EXCEPTION_CODE_DUPLICATE_WIN_FOR_ROUND,
                        new Exception("Win [{$db_round['win_id']}] already exists for user [{$this->user->data['id']}] and round [{$round_id}].")
                    );
                }
            }
        }

        $balance = $this->getPlayerBalance();
        $trans_id = is_numeric($request_data['round_id']) ? (int)$request_data['round_id'] : 0;
        $mg_id = $request_data['transaction_id'];
        $this->prefixTransaction($mg_id);
        $amount = $request_data['amount'];
        $bonus_bet_type = $this->bonusBetType();
        // TODO: what is 'jackpot_contribution' ??
        $awardTypeCode = $this->getAwardTypeCode($request_data['jackpot_contribution'] ?? '');

        $win_id = $this->insertWin($this->user->data, $this->game_data, $balance, $trans_id, $amount, $bonus_bet_type, $mg_id, $awardTypeCode, null);
        if (!$win_id) {
            throw new Exception("Error inserting win into DB.", self::EXCEPTION_CODE_INTERNAL_ERROR);
        }
        $this->setWalletTxnWins($win_id);

        $round_id = $request_data['round_id'];
        $this->prefixTransaction($round_id);

        // if this is getting called from the freespins method we don't want to change the player's balance here
        if (empty($this->freespins)) {
            $new_balance = $this->playChgBalance($this->user->data, $amount, null, 2);
        } else {
            $new_balance = $this->getBalance();
        }

        $this->logDebug([
            'status' => 'success',
            'file' => __METHOD__ . '::' . __LINE__,
            'message' => "Inserted win into DB. New balance: {$new_balance}.",
            'transaction_id' => $win_id,
            'parameters' => $request_data,
        ]);
    }

    /**
     * @return bool
     */
    public function isBetForced(): bool
    {
        return $this->bet_forced;
    }

    /**
     * @param bool $bet_forced
     */
    public function setBetForced(bool $bet_forced)
    {
        $this->bet_forced = $bet_forced;
    }

    /**
     * @param $ext_round_id
     * @param $user_id
     * @return bool
     */
    public function doesGameSessionBetExist($ext_round_id)
    {
        if (empty($this->user)) {
            return false;
        }
        
        $this->cur_user = $this->user;
        $this->prefixGameId($ext_round_id);

        $rounds = $this->getUserGameSessionBets($this->db->escape($ext_round_id));
        if (empty($rounds)) {
            return false;
        }

        return true;
    }

    /**
     * Return the game with the prefix Ex. netent_asdasdasd
     * This is needed in different places, cause we want to keep the GP prefix in front of the game name.
     *
     * @param $game_id
     */
    public function prefixGameId(&$game_id)
    {
        if (strpos($game_id, $this->getGamePrefix()) === false) {
            $game_id = $this->getGamePrefix() . $game_id;
        }
    }

    /**
     * @return mixed
     */
    public function getGameData()
    {
        return $this->game_data;
    }

    /**
     * Initializes '$this->game_data' with the details of the specified game.
     * This method checks for game_country_overrides according to the current user.
     *
     * @param string $game_id
     * @param mixed $device
     * @throws Exception if the game is not found.
     */
    public function setGameData(string $game_id, $device = null)
    {
        if (empty($this->uid)) {
            throw new Exception("User not found.", self::EXCEPTION_CODE_USER_NOT_FOUND);
        }

        if (is_null($device)) {
            $device = phMget(mKey($this->uid, 'current-client')) ?: 0;
            $this->logDebug(['message' => "Getting device type from session", 'device' => $device], $this->getGameProviderName() . "-set-game-data");
        }

        $this->prefixGameId($game_id);
        $this->game_data = $this->module_micro_games->getByGameRef($game_id, $device, $this->uid);

        if (empty($this->game_data)) {
            $this->logError(['status' => 'error', 'message' => "Game not found: {$game_id}.", 'file' => __METHOD__ . '::' . __LINE__, 'game_ref' => $game_id, 'device' => $device]);
            throw new Exception("Game not found: '{$game_id}'.", self::EXCEPTION_CODE_GAME_NOT_FOUND);
        }

        $this->logDebug(['status' => 'success', 'file' => __METHOD__ . '::' . __LINE__, 'game_id' => $game_id, 'device' => $device]);
    }

    /**
     * Loads the original game.
     */
    protected function loadOriginalGame(string $game_ref = null)
    {
        if (empty($this->game_data)) {
            $game_ref = $game_ref ?: $this->wallet_methods[0]['game_id'] ?? null;
            $this->game_data = $this->getOriginalGame($game_ref);
        }
    }

    /**
     * @param string|null $game_ref
     * @param string|null $device_type
     * @return array|null
     */
    protected function getOriginalGame(string $game_ref = null, string $device_type = null): ?array
    {
        if (empty($game_ref)) {
            return null;
        }

        $this->prefixGameId($game_ref);
        $country = $this->isTournamentMode() ? $this->getLicSetting('bos-country', $this->user) : null;
        $org_game_ref = $this->module_micro_games->getOriginalRefIfOverridden($game_ref, $this->user, $country);

        $device_type = $device_type ?: phMget(mKey($this->uid, 'current-client')) ?: 0;
        $game = $this->module_micro_games->getByGameId($org_game_ref, $device_type, $this->uid);
        if (empty($game)) {
            $game = $this->module_micro_games->getByGameRef($org_game_ref, $device_type, $this->uid);
        }
        $this->logDebug(['original_game_ref' => $game['ext_game_name'] ?? '', 'game_ref' => $game_ref], $this->getGameProviderName() . '_' . __FUNCTION__);
        return $game;
    }

    /**
     * Validates that the game exists and is enabled.
     *
     * @param string $game_id
     * @param $device
     * @throws Exception
     */
    protected function validateGameIsEnabled(string $game_id, $device = null)
    {
        $this->setGameData($game_id, $device);

        if (!$this->game_data['active']) {
            throw new Exception("Game not found.", self::EXCEPTION_CODE_GAME_NOT_FOUND, new Exception("Game not active."));
        }

        if (!$this->game_data['enabled']) {
            throw new Exception("Game not found.", self::EXCEPTION_CODE_GAME_NOT_FOUND, new Exception("Game not enabled."));
        }

        if ($this->game_data['retired']) {
            throw new Exception("Game not found.", self::EXCEPTION_CODE_GAME_NOT_FOUND, new Exception("Game is retired."));
        }

        if ($this->module_micro_games->isBlocked($this->game_data)) {
            throw new Exception("Game not found.", self::EXCEPTION_CODE_GAME_NOT_FOUND, new Exception("Game is blocked."));
        }
    }

    /**
     * Get the players balance.
     * if users:cash_balance === 0 any winning will be added to the bonus entry instead so this object needs
     * to be updated with the latest winning to generate correct balance response.
     * This method is public so that it can be called from unit tests.
     *
     * @return int
     */
    public function getPlayerBalance()
    {
        $log_data = ['status' => 'success', 'file' => __METHOD__ . '::' . __LINE__, 'user_identifier' => $this->user_identifier, 't_entry' => $this->t_entry, 't_eid' => $this->t_eid];

        if ($this->t_eid || $this->t_entry) {
            $tournament_balance = $this->tEntryBalance();
            $this->logDebug(array_merge($log_data, ['tournament_balance' => $tournament_balance]));
            return $tournament_balance;
        }

        $real_balance = $this->module_user_handler->getFreshAttr($this->uid, 'cash_balance');
        if (empty($real_balance)) {
            $this->module_casino_bonuses->resetEntries();
        }

        $bonus_balance = '';
        if ($this->game_data['ext_game_name'] ?? false) {
            $bonus_balance = $this->module_casino_bonuses->getBalanceByRef($this->game_data['ext_game_name'], $this->uid);
            $real_balance += $bonus_balance;
        }

        $this->logDebug(array_merge($log_data, ['real_balance' => $real_balance, 'bonus_balance' => $bonus_balance]));
        return $real_balance;
    }

    /**
     * @return int
     */
    public function getWalletTxnBets(): int
    {
        return $this->wallet_txn_bets;
    }

    /**
     * @param int $wallet_txn_bets
     */
    public function setWalletTxnBets(int $wallet_txn_bets)
    {
        $this->wallet_txn_bets = $wallet_txn_bets;
    }

    /**
     * @return bool
     */
    public function isPartialRefund(): bool
    {
        return $this->partial_refund;
    }

    /**
     * Returns true if the transaction doesn't match the one we have stored. For example when we have the mg_id but a
     * different amount they want to cancel, here we cater for providers that cancel the entire transaction weather
     * they send us the amount or not, not applicable for partial refunds
     *
     * @param $amount
     * @param $mg_id
     * @param $existing_transaction
     * @return bool
     */
    private function isTransactionMismatch($amount, $mg_id, $existing_transaction)
    {
        return (!empty($amount) && (sha1($existing_transaction['amount'] . $existing_transaction['mg_id']) !==
                    sha1($amount . $mg_id))) ||
            (empty($amount) && sha1($existing_transaction['mg_id']) !== sha1($mg_id));
    }

    /**
     * @return string
     */
    public function getCurrentTable(): string
    {
        return $this->current_table;
    }

    /**
     * @param string $current_table
     */
    public function setCurrentTable(string $current_table)
    {
        $this->current_table = $current_table;
    }

    /**
     * @return int
     */
    public function getWalletTxnRollbacks(): int
    {
        return $this->wallet_txn_rollbacks;
    }

// TODO @Paolo finish checking from here below

    /**
     * @param int $wallet_txn_rollbacks
     */
    public function setWalletTxnRollbacks(int $wallet_txn_rollbacks)
    {
        $this->wallet_txn_rollbacks = $wallet_txn_rollbacks;
    }

    /**
     * This will get the round and then get the associating bet and win transactions
     *
     * @param $round_id
     * @param bool $check_is_cancelled
     * @return array
     */
    protected function getTransactionsByRound($round_id, $check_is_cancelled = false)
    {
        $this->prefixTransaction($round_id);

        $user_id = $this->uid;

        $query = "SELECT * FROM rounds WHERE user_id = {$user_id} AND ext_round_id = {$round_id}";

        $result = $this->db->sh($user_id)->loadAssoc($query);

        return [
            'bets' => $this->getTransactionById($result['bet_id'], 'bets', $check_is_cancelled),
            'wins' => $this->getTransactionById($result['wins_id'], 'wins', $check_is_cancelled)
        ];
    }

    /**
     * Depending on the string we want to get the recently inputted row in either the bets or wins table
     *
     * @param string $table
     * @return mixed
     */
    protected function getSavedTransactionFromProperty($table = 'bet')
    {
        return $this->{"wallet_txn_{$table}s"};
    }

    /**
     * Get an instance of the PHP DateTime class
     * @param string $date_time Any valid strtotime() string
     * @return DateTime
     * @link http://php.net/manual/en/class.datetime.php
     */
    protected function getDateTimeInstance($date_time = "")
    {
        $date_time = (empty($date_time) ? "" : date('U.u', strtotime($date_time)));
        $now = DateTime::createFromFormat('U.u',
            (empty($date_time) ? number_format(microtime(true), 6, '.', '') : $date_time));
        return $now->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    }

    /**
     * Parse xml file and replace the placeholders with their value.
     *
     * @param array $params
     * @param string $filename
     * @return string
     */
    private function parseFile($params, $filename)
    {
        $needles = array_map(function ($key) {
            return '{{' . $key . '}}';
        }, array_keys($params));

        $method = $this->game_provider_method_name;

        $xml = file_get_contents(realpath(dirname(__FILE__)) . '/../Test/' . $filename . '/response/' . $method . '.xml');

        return str_replace($needles, $params, $xml);
    }

    /**
     * Set correct response headers for the response to GP
     * Depending on $response, choose to response always with a 200 OK or different response header depending on GP needs
     * @param mixed $response On failure: an array with message and code about what failed or true on success
     * @param bool $return_status_error Return the status from the array $this->errors or the code message from $this->http_status_codes. Default: false (so $this->http_status_codes)
     * @return void
     */
    protected function setResponseHeaders($response = null, $return_status_error = false)
    {
        $this->delayResponseTime();
        if ((!empty($response) && $this->force_http_ok_response === false) || ($response['status'] == 'UNAUTHORIZED')) {
            if (in_array(substr(php_sapi_name(), 0, 3), ['cgi', 'fpm'])) {
                header('Status: ' . $response['responsecode'] . ' ' . (($return_status_error === true) ? $response['status'] : $this->http_status_codes[$response['responsecode']]));
            } else {
                $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
                header($protocol . ' ' . $response['responsecode'] . ' ' . (($return_status_error === true) ? $response['status'] : $this->http_status_codes[$response['responsecode']]));
            }
        } else {
            header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK');
        }

        if ($response['status'] !== 'UNAUTHORIZED') {
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Content-type: ' . $this->http_content_type . '; charset=utf-8');
        }
    }

    /**
     * Delay a response to the GP for xx seconds randomly of time so the are able to trigger certain behaviour
     */
    protected function delayResponseTime()
    {
        if ($this->getSetting('test') === true && !empty($this->getSetting('delaysec')) && $this->getSetting('delaysec') > 0) {
            if (rand(1, 5) === 4) {
                sleep((int)$this->getSetting('delaysec'));
            }
        }
    }

    /**
     * Overrule certain CasinoProvider class errors with ones from the CasinoProvider-specific extended class
     *
     * @param array $overriding_errors The array with errors to overrule the ones defined inside this class
     * @return object
     */
    protected function overruleErrors(array $overriding_errors = [])
    {
        if (!empty($overriding_errors)) {
            $this->errors = array_merge($this->errors, $overriding_errors);
        }
        return $this;
    }

    /**
     *  @param $platform  desktop | mobile
     *  @param $user
     *
     *  @return if nothing is specified in the XXXX.config.php (Ex. Thunderkick) we fallback to our own implementation "videoslots"
     *  - ingame - The game will use the implementation from the provider
     *  - videoslots - We will show our popup and stop/resume gameplay via postMessages
     *  - redirect  - We will reload the page to stop the game and then show our popup
     */
    public function getRcPopup($platform = '', $user = null)
    {
        return $this->getLicSettingWithPlatform('rc-popup', $platform, $user) ?: 'videoslots';
    }

    /**
     * Prepare the shared variables that will be used when creating the launcher URL
     * The license iso by default will be taken from the current User in session, but if a command comes via CLI we can force it (Ex. Bsg.php)
     * if some custom parameter need to be overridden / defined for a single Provider use the "setProviderInfoForUrl"
     *
     * @param $forceIso - iso code for the regulation, from User session or forced Ex. 'MT' / 'SE'
     */
    function initCommonSettingsForUrl($forceIso = null)
    {
        $this->setCommonInfoForUrl($forceIso); // KEEP THIS FIRST!!!
        $this->setLaunchUrl();
    }

    /**
     * Set common settings to be available in all the class.
     * We check that iso is not false (License module not loaded)
     * @param $forceIso - if this param is passed the "iso" will be forced to the value provided (Ex. Bsg parse jackpot for 2 jurisdiction)
     */
    protected function setCommonInfoForUrl($forceIso = null)
    {
        $this->iso = licJur(cu());
        if(!empty($this->iso) && phive('Licensed')->isActive($forceIso)) {
            $this->iso = $forceIso;
        }
        $this->platform = phive()->isMobile() ? 'mobile' : 'desktop';
        $this->demo_or_real = isLogged() ? 'real' : 'demo';
    }

    /**
     * Determine which launch url should be used.
     * 1) desktop / mobile  ['launch_url'=> ['desktop' => 'url']]
     * 2) real / demo       ['launch_url'=> ['desktop' => ['real' => 'url']]]
     * Some providers have a desktop and a mobile version.
     * Others have a different link based on platform for the demo and real version
     *
     * if extra logic need to be applied this can be extended/overridden into the Provider class
     */
    protected function setLaunchUrl()
    {
        $launchUrl = $this->getLicSetting('launch_url');

        if (is_array($launchUrl)) {
            if (key_exists($this->platform, $launchUrl)) {
                $launchUrl = $launchUrl[$this->platform];
            } else if (key_exists($this->demo_or_real, $launchUrl)) {
                $launchUrl = $launchUrl[$this->demo_or_real];
            }
        }

        $this->launch_url = $launchUrl;
    }

    /**
     * Shortcut for getting country / jurisdiction settings
     *
     * @param string $key The config setting to fetch.
     * @param DBUser $user The user object to work with.
     *
     * @return mixed The setting.
     */
    // TODO This is what we replace the above getLicSetting with once the configs have been fixed.
    public function getLicSetting($key, $user = null)
    {
        $user = $user ?: $this->user;
        $user = $this->getUsrId($user);
        $lic_jur = licJur($user);
        $def_jur = 'DEFAULT';
        $def_ss = (array)$this->getSetting('licensing')[$def_jur];
        $jur_ss = (array)$this->getSetting('licensing')[$lic_jur];
        return $jur_ss[$key] ?? $def_ss[$key];
    }

    /**
     * Originally this method was pushed to phive/api/PhConfigurable.php and added the '$from_child' parameter to the 'getSetting' method.
     * It is cleaner however to leave PhConfigurable.php unmodified and move the desired functionality to this class.
     *
     * @param $setting
     * @param mixed $default
     * @param bool $from_child
     * @return mixed|null
     */
    protected function getSettingFromChild($setting, $default = null, $from_child = false){
        if ($this->settings_data === null)
            $this->loadSettingsFromChild($from_child);
        if(is_array($this->settings_data))
            return array_key_exists($setting, $this->settings_data) ? $this->settings_data[$setting] : $default;
        return $default;
    }

    /**
     * Originally this method was pushed to phive/api/PhConfigurable.php and added the '$from_child' parameter to the 'loadSettings' method.
     * It is cleaner however to leave PhConfigurable.php unmodified and move the desired functionality to this class.
     *
     * @param bool $from_child
     */
    protected function loadSettingsFromChild($from_child = false): void
    {
        $file_name = $from_child ? get_called_class() : get_class($this);
        $file = getConfigFile($file_name . ".config.php");
        if (file_exists($file)) {
            include $file;
        }
    }

    /**
     * Helper method which returns the value of an XML element attribute.
     *
     * @param SimpleXMLElement|null $xml_element
     * @param string|null $attribute. The attribute name.
     * @param string $data_type. The data type to cast the value to. If null then the XML attribute object is returned.
     * @return int|mixed|string|null
     *
     * @example getXmlAttribute($this->response, "amt")
     */
    protected function getXmlAttribute(SimpleXMLElement $xml_element = null, $attribute = null, $data_type = 'string')
    {
        if ($xml_element instanceof SimpleXMLElement) {
            foreach ($xml_element->attributes() as $k => $v) {
                if ($k == $attribute) {
                    switch ($data_type) {
                        case 'string':
                            return (string)$v;
                        case 'int':
                            return (int)$v;
                        case 'float':
                            return (float)$v;
                        default:
                            return $v;
                    }
                }
            }
        }
        return null;
    }
}

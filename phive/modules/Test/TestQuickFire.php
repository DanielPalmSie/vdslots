<?php
// TODO this class needs to be rewored to work without QfTest
class TestQuickFire extends TestPhive{
    /**
     * @var endpoint diamondbet
     */
    public $url;
    public $bonus_type_id;
    public $ext_offer_id;
    /**
     * @var array
     */
    public $bonus_entry;
    /**
     * @var array
     */
    public $bonus_type;

    public $smicro;

    public \DBUser $user;
    
    /**
     * @var int game round
     */
    public $action_id;
    public $instance_id;
    /**
     * @var string
     */
    public $fsTestUrl;
    /**
     * @var bool|object
     */
    public $qf;

    function __construct(){
        $this->qf = phive('QuickFire');        
        $this->url = "http://www.videoslots.loc/diamondbet/soap/ggi_quickfire.php";
    }
    
  function ggiPost($method, $params = array()){
    $ss = phive('QuickFire')->allSettings();
    $seq = phive()->uuid();
    ob_start();
    ?>
    <pkt>
      <methodcall name="<?php echo $method ?>" timestamp="2011/01/18 14:33:00.000" system="casino">
        <auth login="<?php echo $ss['vanguard_username'] ?>" password="<?php echo $ss['vanguard_password'] ?>" />
        <call seq="<?php echo $seq ?>"
              token="<?php echo $this->token ?>"
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
    $options = array(
      'http' => array(
        'method'  	=> 'POST',
        'header'  	=> 'Content-type: soap/xml',
        'content' 	=> $xml));
    echo "Sending: $xml\n\n To: {$this->url}\n\n";
    $r = file_get_contents($this->url, false, stream_context_create($options));
    echo "Result: \n\n $r \n\n";
    return $r;
  }

  function prepare($user, $game, $url = ''){
      if (!empty($url)){
        $this->url = $url;
      }
      $this->qf->insertToken($user->getId(), $game['ext_game_name'], '', 'xyz');
      $this->token = $_SESSION['mg_token'];  
  }

  function testIdempotency($user, $game, $bamount, $wamount){
      $bet_mg_id = $this->randId();
      $win_mg_id = $this->randId();
      $this->ggiPlay('bet', $bet_mg_id, $bet_mg_id, $game['ext_game_name'], $bamount);
      $this->ggiPlay('bet', $bet_mg_id, $bet_mg_id, $game['ext_game_name'], $bamount);
      $this->ggiPlay('win', $win_mg_id, $win_mg_id, $game['ext_game_name'], $wamount);
      $this->ggiPlay('win', $win_mg_id, $win_mg_id, $game['ext_game_name'], $wamount);
  }

  
  function ggiLogin(){
    return $this->ggiPost('login');
  }

  function ggiEndgame(){
    return $this->ggiPost('endgame');
  }
  
  function ggiGetbalance(){
    return $this->ggiPost('getbalance');
  }

  function ggiRefreshtoken(){
    return $this->ggiPost('refreshtoken');
  }

  public function ggiPlay($type, $action_id, $game_id, $game_ref, $amount, $fspin_id = '', $instance_id= ''){
    $req = array('playtype' => $type, 'actionid' => $action_id, 'gameid' => $game_id, 'gamereference' => $game_ref, 'amount' => $amount);
    if(!empty($fspin_id)){
      $req['freegame'] = $fspin_id;
      $req['freegameofferid'] = $fspin_id;
      $req['freegameofferinstanceid'] = $instance_id;
    }
    return $this->ggiPost('play', $req);
  }
 
  function runDefaultTests($url){
    $this->user = $user = phive("UserHandler")->getUserByUsername('hsarvell');
    phive("QuickFire")->insertToken($user->getId(), 'mgs_tombraiderii');
    $this->token = $token = phive("SQL")->getValue('', 'token', 'tokens', "user_id = {$user->getId()}");
    //echo $qf->postRefreshToken($url, $token); 
    //echo "\n\n";
    /*
    $qf->postBet($url, $token, '1', 1000, 'mgs_tombraiderii');
    print_r(phive("SQL")->loadArray("SELECT * FROM bets")); 
    echo "\n\n";
    echo "User balance: ".phive("SQL")->getValue('', 'cash_balance', 'users', "user_id = {$user->getId()}");
    echo "\n\n";
    $qf->postWin($url, $token, '1', 1000, 'mgs_tombraiderii');
    print_r(phive("SQL")->loadArray("SELECT * FROM wins"));
    echo "\n\n";
    echo "User balance: ".phive("SQL")->getValue('', 'cash_balance', 'users', "user_id = {$user->getId()}");
    echo "\n\n";
     */
  }

  function runSessionTests($url){
    $this->user = $user = phive("UserHandler")->getUserByUsername('hsarvell');
    $this->token = $token = phive()->uuid();	
    phM('hmset', $token, array('user_id' => $user->getId()), 3600);
    
    //echo $qf->postGetAccDetails($url, $token);
    //echo "\n\n";
    
    //echo $qf->postGetBalance($url, $token); 
    //echo "\n\n";
    
    //echo $qf->postRefreshToken($url, $token); 
    //echo "\n\n";
    
    
    $this->qf->postBet($url, $token, '1', 1000, 'mgs_tombraiderii');
    print_r(phive("SQL")->loadArray("SELECT * FROM bets")); 
    echo "\n\n";
    echo "User balance: ".phive("SQL")->getValue('', 'cash_balance', 'users', "user_id = {$user->getId()}");
    echo "\n\n";
    $this->qf->postWin($url, $token, '1', 1000, 'mgs_tombraiderii');
    print_r(phive("SQL")->loadArray("SELECT * FROM wins"));
    echo "\n\n";
    echo "User balance: ".phive("SQL")->getValue('', 'cash_balance', 'users', "user_id = {$user->getId()}");
    echo "\n\n";    
  }
  
  function testRemote($url, $token, $func = 'postGetBalance', $mg_id, $amount, $game_ref, $bonus_ref, $trans_id){
    echo "\n\n";
  }
  
  function testPlayCheck($url, $uid, $token){
    phive("SQL")->insertArray('playcheck_tokens', array('token' => $token, 'user_id' => $uid));
    echo $this->qf->postGetAccDetails($url, $token);
  }
  
  function testDepUrl($username, $gid){
    $user = phive("UserHandler")->getUserByUsername($username);
    $_SESSION['mg_username'] = $username;
    $_SESSION['mg_id'] = $user->getId();
    echo "Dep Url: ".phive("QuickFire")->getDepUrl($gid, 'en');
    echo "\n\n";
    $token = phM('hgetall', $_SESSION['mg_token']);
    return $token;
  }

  function ggiParseRes($xml){
    $r = new SimpleXMLElement($xml);
    $result = array_pop($r->xpath('//result'));
    return  $result->attributes();
  }



    public function itShouldGetAFreeSpinToken()
    {
        $this->qf->refreshFsToken($this->user);
        $token = $this->qf->getFsToken($this->user);
        return is_string($token);
    }

    public function itShouldGetTheApiUrl()
    {
        $url = $this->qf->getFsUrl($this->user->getId(), 'check_user_exists_uri');

        return $url === 'https://api32.api.valueactive.eu/Account/v1/accounts/checkUserExists';
    }

    public function itShoulNotExistAsUserInProvider()
    {
        $fake_user = phive('UserHandler')->getUserByUsername('devtestit');
        $product_id = $this->qf->getLicSetting('server_id', $this->user->getId());
        return $this->qf->checkExtUser($fake_user, $product_id) == false;
    }
    public function itShouldExistAsUserInProvider()
    {
        $product_id = $this->qf->getLicSetting('server_id', $this->user->getId());
        return $this->qf->checkExtUser($this->user, $product_id) !== false;
    }

//    public function it_should_register_user()
//    {
//        $response = $this->qf->registerExtUser($this->user);
//        return $response != false;
//    }

    public function itShouldGetTheProviderUser()
    {
        $response = $this->qf->getExtUserId($this->user);
        return is_integer($response);
    }

    public function itShoulGetAnOffer()
    {
        $response = $this->qf->getFsOfferById($this->user, $this->ext_offer_id);
        return isset($response['offerId']) && $response['offerId'] == $this->ext_offer_id;
    }

    public function itShoulHaveBonusTypeInDatabase()
    {

        $bonus      = phive('Bonuses');
        $this->bonus_type = $bonus->getBonus($this->bonus_type_id);
        return !empty($this->bonus_type);
    }

    public function itShouldCreateBonusEntry()
    {
            $bonus      = phive('Bonuses');
            // create bonus_entry
            $start = date("Y-m-d");

            $bonus_entry = [
                'bonus_id'      => $this->bonus_type['id'],
                'user_id'       => $this->user->getId(),
                'start_time'    => $start,
                'end_time'      => date("Y-m-d", strtotime("+ ".$this->bonus_type['num_days']." days", strtotime($start))),
                'status'        => 'approved',
                'bonus_type'    => 'freespin',
                'ext_id'        => $this->bonus_type['ext_ids'],
            ];

            $keys = ["user_id", "cost", "reward"];

            foreach(['game_tags', 'game_percents', 'loyalty_percent', 'bonus_tag', 'progress_type', 'allow_race'] as $f) {
                $keys[] = $f;
            }

            foreach($keys as $key) {
                $bonus_entry[$key] = $this->bonus_type[$key];
            }

            $bonus_entry['frb_granted'] = $bonus_entry['frb_remaining'] = $bonus_entry['reward'];

            $bonus_entry['user_id'] = $this->user->getId();
            $new_bonus_entry = phive("SQL")->sh($this->user->getId(), '', 'bonus_entries')->insertArray('bonus_entries', $bonus_entry);

            $this->bonus_entry = $bonus->getBonusEntry($new_bonus_entry, $this->user->getId());

            return !empty($this->bonus_entry) ;
    }

    public function itShouldAwardTheUserFreespins()
    {
        $campaign_ids = $this->bonus_type['ext_ids'];

        $ext_id = $this->qf->awardFRBonus($this->user->getId(), $campaign_ids, $this->bonus_type['reward'], $this->bonus_type['bonus_name'], $this->bonus_entry);

        $updates = ['ext_id' => $ext_id];
        $this->instance_id = explode('-',$ext_id)[1];
        return phive('Bonuses')->editBonusEntry($this->bonus_entry['id'], $updates, $this->user->getId());
    }

    public function itShouldHaveFreespinWin()
    {
        $game 	= phive('MicroGames')->getByGameId(4963, 0); // dragonz
        $this->fsTestUrl = "https://antonio.videoslots.com/diamondbet/soap/ggi_quickfire.php";
        $this->prepare($this->user,$game, $this->fsTestUrl);
        $this->action_id = rand(1111111,9111111);
        $before_balance = $this->user->getBalance();

        $response = $this->ggiPlay('win', $this->action_id, 1422, 'MGS_Dragonz', $this->amount, $this->bonus_type['ext_ids'], $this->instance_id);
        $params = $this->ggiParseRes($response);
//        var_dump([$this->test->params, $this->test->params['balance'],$before_balance, ($before_balance + $this->amount)]);
        return isset($params['balance']) && $params['balance'] == ($before_balance + $this->amount);
    }

    public function itShouldPreserveIdempotency()
    {
        $before_balance = $this->user->getBalance();
        $response = $this->ggiPlay('win', $this->action_id, 1422, 'MGS_Dragonz', $this->amount, $this->bonus_type['ext_ids'], $this->instance_id);
        $params = $this->ggiParseRes($response);
        $after_balance = $this->user->getBalance();
        //var_dump([$before_balance, $after_balance]);
        return $before_balance == $after_balance && isset($params['errorcode']);
    }

    public function itShouldPreserveIdempotency2()
    {
        $before_balance = $this->user->getBalance();
        $response = $this->ggiPlay('win', rand(1111111,9111111), 1422, 'MGS_Dragonz', $this->amount, $this->bonus_type['ext_ids'], $this->instance_id);
        $params = $this->ggiParseRes($response);
        $after_balance = $this->user->getBalance();
        //var_dump([$before_balance, $after_balance]);
        return $before_balance == $after_balance && isset($params['errorcode']);
    }

    protected function startTest() {
        $this->smicro = microtime(true);
    }

    protected function endTest( $function_name, Bool $success, $trace)
    {
        $duration 	= microtime(true) - $this->smicro;
        $colors = [
            true => "\e[32m", // green
            false => "\e[31m" // red
        ];

        echo str_pad("[Test] {$colors[$success]} {$function_name}", 55) . "Duration: {$duration} \033[0m" . PHP_EOL;
        if (!$success && $trace) {
            var_dump($trace);
        }
    }

    /**
     *  Entry point for automated tests
     *
     * @param string $url               Diamondbet vs endpoint
     * @param string $username          Username that will be granted freespins
     * @param int    $bonus_type_id     bonus_id in trophy_awards table
     * @param int    $ext_offer_id      ext_id in bonus_types table that corresponds to the providers offer id
     * @param int    $amount            The win amount the user will receive at the end of freespins
     */
    public function testFreespins($url = '', $username = 'devtestse', $bonus_type_id = 15817, $ext_offer_id = 41133, $amount = 3625)
    {
        $this->fsTestUrl = $url;
        $this->user = phive('UserHandler')->getUserByUsername($username);
        $this->bonus_type_id = $bonus_type_id;
        $this->ext_offer_id = $ext_offer_id;
        $this->amount = $amount;

        echo  "-------------------------------------------------------------------------------" . PHP_EOL;
        echo  str_pad("\e[35m[INFO]  Testing Microgaming Freespins API ",55) . date('d-m-Y H:i:s')."\033[0m" . PHP_EOL;
        echo  "-------------------------------------------------------------------------------" . PHP_EOL;
        $methods = get_class_methods($this);
        $count = $good = 0;
        foreach ($methods as $method) {
            if (strpos($method,'it') === 0) {
                $this->startTest();
                $result = $this->$method();
                $success  = ($result === true);
                $this->endTest($method, $success , $success ? NULL : $result);
                if ($success) $good++;
                $count++;
            }
        }
        echo  "-------------------------------------------------------------------------------" . PHP_EOL;
        $color = $count == $good ? "\e[32m" : "\e[31m";
        echo  "[INFO]  {$color}Total: {$count}  Passed: {$good} \033[0m" . PHP_EOL.PHP_EOL;
    }
}

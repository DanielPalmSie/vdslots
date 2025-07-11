<?php 
require_once __DIR__ . '/../../phive/phive.php';
require_once __DIR__ . '/../../phive/modules/Test/TestPhive.php';
require_once __DIR__ . '/BoSTester.php';
/**
 * BoSTestTournament class for testing bos tournament
 */
class BoSTestTournament
{
    protected $tournament;
    protected $players = [];

    public function __construct($tournamentId, $new='')
    {
        $t = phive('Tournament');
        if (empty($new)) {            
            // Check if tournament exists
            $this->tournament = $t->byId($tournamentId);
            if (empty($this->tournament['id'])) {
                throw new Exception("Tournament [id:{$tournamentId}] doesn't exist", 1);
            }
        } else {
            // Create new tournament            
          $this->tournament = $this->create($new);           
        }

        $this->getPlayers();  // load tournament players
    }

    public function create($aRequest = [])
    {
        if ( !in_array($aRequest['category'], ['normal', 'added', 'guaranteed','jackpot','freeroll'])) {
            throw new Exception("Tournament type {$aRequest['category']} not in types allowed", 1);            
        }
        // load the params for this type
        if (empty($aRequest['game_ref'])) {
          $aGame = $this->getRandomGame(); // [ref, game_name]
        }else{
          $aGame = $this->getGame($aRequest['game_ref']);// [ref, game_name]
        }
        $aGame[1] = $aGame[1] . " - " . $aRequest['category'];
        $aTournamentTemplate = $this->getTemplate($aRequest,$aGame);
        unset($aTournamentTemplate['id']);
        
        if (!phive('SQL')->save('tournament_tpls', $aTournamentTemplate)) {
          throw new Exception("Error Tournament could not be created", 1);
        }
        $iTournamentTplId = phive('SQL')->insertId();
         //activate the tournament with cron job instead of manually
//        return [];

        // only activate this tournament
         return $this->activateTournamentIfReady($iTournamentTplId);
    }

    // We create a new SNG tournament if:
    // 1.) We do not already have tournaments which are not cancelled or finished.
    // 2.) The start stamp is less than now.
    public function activateTournamentIfReady($iTournamentTplId)
    {
      $oTestTournament = phive('Tournament');
      
      $aTournamentTemplate = $oTestTournament->_getTplsWhere(['id' => $iTournamentTplId])[0]; 
      if($oTestTournament->hasExpired($aTournamentTemplate)){
        return false;
      }

      $aInactiveStatuses  = $oTestTournament->getInactiveStatuses(true);
      $bActive = $oTestTournament->getAllWhere("tpl_id = {$aTournamentTemplate['id']} AND status NOT IN($aInactiveStatuses)"); 
      $sSngStart = $aTournamentTemplate['mtt_start_date'].' '.$aTournamentTemplate['mtt_start_time'];

      if(  !(empty($bActive) && strtotime($sSngStart) <= time())  ){
        return false;
      }
      $iTournamentId = $oTestTournament->insertTournament($aTournamentTemplate, $sSngStart);
      return $oTestTournament->byId($iTournamentId);
    }



    /* Available games to create a tournament from */
    public function getGames()
    {
      return self::getAvailableBoSGames();
    }

    public function getNamedGames()
    {
      $aGamesArray = self::getGames();
      $aGames = []; 

      foreach ($aGamesArray as $game) {
        $aGames[$game['game_id']] = $game['game_name'];
      }
      return $aGames;
    }

    /* Returns a single game from the available ones */
    public function getGame($sGameRef='')
    {
      $games = $this->getGames();
      foreach ($games as $game) {
        if ($game['game_id'] == $sGameRef) {
          // echo '<pre>'; var_dump($game); echo "</pre>"; die;
          return [$game['ext_game_name'],$game['game_name'] ];
        }
      }
      // echo '<pre>'; var_dump($sGameRef); echo "</pre>"; die;
      return [];
    }

    /* Gets a random game from the available ones */
    public function getRandomGame()
    {
        $aGames = $this->getNamedGames();
        $aKeys = $aGames;
        $sGameRef = $aKeys[mt_rand(0, count($aGames) - 1)];
        return [$sGameRef, $aGames[$sGameRef]];
    }

    public function getTemplate($request, $game)
    {     
        unset($request['game_ref']);
        unset($request['tournament_name']);
        $template = [
          'game_ref' => $game[0],
          'tournament_name' => $game[1],
          'category' => '',
          'start_format' => 'mtt',
          'win_format' => 'tht',
          'play_format' => 'xspin',
          'cost' => '100',
          'pot_cost' => '0',
          'xspin_info' => '10',
          'min_players' => '2',
          'max_players' => '100',
          'mtt_show_hours_before' =>  '',
          'duration_minutes' => '',
          'mtt_start_time' => '',
          'mtt_start_date' => '',
          'mtt_reg_duration_minutes' => '',
          'mtt_late_reg_duration_minutes' => '',
          'mtt_recur_type' => '',
          'mtt_recur_days' => '',
          'recur_end_date' => '2037-12-31 10:00:00',
          'recur' => '',
          'guaranteed_prize_amount' => '0',
          'prize_type' => 'win-fixed',
          'created_at' => '0000-00-00 00:00:00',
          'max_bet' => '10',
          'min_bet' => '10',
          'house_fee' => '100',
          'get_race' => '1',
          'get_loyalty' => '0',
          'get_trophy' => '1',
          'rebuy_times' => '2',
          'rebuy_cost' => '100',
          'award_ladder_tag' => '',// 'sng-sburst-2-people',
          'duration_rebuy_minutes' => '0',
          'reg_wager_lim' => 0,
          'reg_dep_lim' => 0,
          'reg_lim_period' => 0,
          'turnover_threshold' => 0,
          'ladder_tag' => 'default',
          'included_countries' => '',
          'excluded_countries' => '',
          'prize_calc_wait_minutes' => 0,
          'free_pot_cost' => 0,
          'total_cost' => 90000,
          'rebuy_house_fee' => 0,
          'spin_m' => 1,
          'pwd' => '',
          'number_of_jokers'    => 1,
          'bounty_award_id'     => 0,
          'bet_levels'          => '',
          'desktop_or_mobile'  => 'both',
          'blocked_provinces' => ''
        ];

        foreach ($request as $key => $value) {
          if ( isset($template[$key]) ){
            $template[$key] = $value;
          }
        }
        // echo '<pre>'; var_dump($template); echo "</pre>"; die;
        return $template;
        
    }

    public function getChatMessages()
    {
        $t = phive('Tournament');
        return $t->getChatContents($this->tournament);
    }

    /*
        getPlayers- Gets the tournament players
        @param n: the number of players to fetch, if not already avalable, it will
        register some more
        @returns players
    */
    public function getPlayers($n = 0)
    {
        $t_id = $this->tournament['id'];
        $this->players= []; // we reload always the array
        $query = "SELECT u.* FROM users u, tournament_entries te WHERE te.user_id = u.id AND te.t_id = $t_id;";
        $usersIds = phive('SQL')->shs()->loadArray($query);
        foreach ($usersIds as $user) {
            $this->players[] =  new BoSTester($t_id, $user['id']);
        }
        if (count($this->players) < $n) {
            // we need to createe some more players and register them into the tournament
            for ($i=0; $i < (count($this->players) - $n); $i++) {
                $newPlayer = new BoSTester($t_id);
                $newPlayer->registerUserInTournament();
                $this->players[] = $newPlayer;
            }
        }
        // returns n players or all
        if ($n > 0) {
            return array_slice($this->players, 0, $n);
        }
        return $this->players;
    }

    public function getLeaderboard()
    {
      $t = phive('Tournament');
      $raw_leaderboard = $t->getLeaderBoard($this->tournament, false, false);

      $this->players = [];

      foreach ($raw_leaderboard as $tournament_entry) {
        try {
          $this->players[] =  new BoSTester($this->tournament['id'], $tournament_entry['user_id']);          
        } catch (Exception $e) { 
          // If we have an exception here is some sync problem between tournament_entries and users
          // remove the tournament entry to TRY to fix this
          $this->deleteEntry($tournament_entry);
        }
      }
      return $this->players;
    }


    public function deleteEntry($e){
      return phive('SQL')->delete('tournament_entries', array('id' => $e['id']), $e['user_id']);
    }
    

    public function getTournament()
    {
        return $this->tournament;
    }

    public function simulatePlayAndFinish($cash_balance = 0)
    {
        $t = phive('Tournament');
        $es = $t->entries($this->tournament);
        foreach ($es as $e) {
            $e['win_amount']   = rand(0, 100);
            $e['cash_balance'] = $cash_balance;
            $e['spins_left']   = 0;
            $e['turnover']     = rand(10, 1000);
            phive("SQL")->sh($e)->save('tournament_entries', $e);
        }
        $t->endTournament($this->tournament);
    }

    public function hasStarted()
    {
      return phive('Tournament')->hasStarted($this->tournament);
    }

    public function startTournament()
    {
      if ($this->tournament['mtt_reg_duration_minutes'] > 0) {
        $this->tournament['status'] = 'late.registration';
      }else{
        $this->tournament['status'] = 'in.progress';        
      }
      $this->tournament['start_time'] = phive()->hisNow();
      // $this->tournament['duration_minutes'] = 36000;
      phive('Tournament')->setEntriesStatus($this->tournament, 'open');
      phive('SQL')->save('tournaments', $this->tournament);
    }

    public static function getAvailableBoSGames()
    {
      $device_type_num = 0; //we want the desktop games here
      $sql = "SELECT game_id, game_name, ext_game_name, network FROM micro_games
                WHERE device_type_num = {$device_type_num}
                AND active = 1 
                AND mobile_id != 0
                AND tag IN ('slots', 'videoslots')";
      // var_dump($sql);
      $games = phive('SQL')->loadArray($sql);
      return $games;
    }
}

<?php 
    require_once __DIR__ . '/../../phive/phive.php';
    require_once __DIR__ . '/../../phive/modules/Test/TestPhive.php';
    require_once __DIR__ . '/BoSChatMessage.php';

    /**
     * Makes aUser ready to test a tournament
     */
    class BoSTester
    {
        protected $user;
        protected $tournament;
        protected $tournamenEntry;
        protected $to_register;
        /*
            pre:
                - The user exists
                - The tournament exists
        */
        public function __construct($tournamentId, $userId = '', $n_to_register = 3000)
        {
            $t = phive('Tournament');
            $uh = phive('UserHandler');
            $this->to_register = $n_to_register + 500;

            // Check if tournament exists
            $this->tournament = $t->byId($tournamentId);
            if (empty($this->tournament['id'])) {
                throw new Exception("Tournament [id:{$tournamentId}] doesn't exist", 1);
            }
            // If userId is empty, get a new userId
            if (empty($userId)) {
                $userId = $this->getFreeUserId();
            }
            $this->user = $uh->newUser($userId);
            // Check if user exists and load user object
            if (!$this->user->preload()) {
                throw new Exception("User [id:{$userId}] can't be instantiated ", 1);
            }
            // echo '<pre>'; var_dump($this->user); echo "</pre>"; die;
        }

        public function registerUserInTournament($use_queue = false)
        {
            $t = phive('Tournament');
            // Check if user is registered in tournament
            if (!$t->isRegistered($this->tournament['id'], $this->user->userId)) {
                $use_queue = $this->tournament['start_format'] == 'sng';
                // if is not registered we will make sure he does
                return $this->registerUser($use_queue);
            }
        }

        /*
            Gets a user that is not registered for the tournament
        */
        public function getFreeUserId()
        {
            $t = phive('Tournament');
            // Changed "phive("SQL")->shs()->loadArray" to "phive("SQL")->loadArray" because otherwise it returns only rows from the 1st shard.
            $limit = $this->to_register < 3000 ? 3000 : $this->to_register + 1000;
            $userIds = phive("SQL")->shs()->loadArray("SELECT u.id FROM users u ORDER BY RAND() LIMIT 0,$limit");
            for ($i=0; $i < count($userIds); $i++) {
                if (!$t->isRegistered($this->tournament['id'], $userIds[$i]['id'])) {
                    return $userIds[$i]['id'];
                }
            }
            return false;
        }
        /**
            Prepares the user with some generic attributes like having money, being active etc,
            there are some cases that might throw an exception however as wager requisites
        */
        public function registerUser($use_queue = false)
        {
            $t = phive('Tournament');
            $amount =  100000;// add some money to the user
            if ($this->user->getBalance() < $amount) {
                $this->addMoneyToUser($amount);
            }
            // Remove restrictions to play and add some "super" settings
            $this->user->setSetting('freeroll-tester', 1);
            $this->user->setAttr('active', 1);
            $this->user->deleteSetting('play_block');
            $this->user->deleteSetting('restrict');
            if ($use_queue) {
                $this->user->setAttr('logged_in', 1);
                unset($_SESSION['mg_id']);
            }
            // Check if we can register now
            if (!$t->canRegister($this->tournament, $this->user)) {
                throw new Exception("User [id:{$this->user->userId}] can't be registered in tournament {$this->tournament['id']} ", 1);
            }

            // Register user
            $use_queue ? $t->queueReg($this->tournament['id'], $this->user->getId(), []) : $t->register($this->user->userId, $this->tournament);
        }

        public function getTournamentEntry()
        {
            $this->tournamenEntry = phive('Tournament')->entryByTidUid($this->tournament['id'], $this->user->userId);            
            return $this->tournamenEntry;
        }

        public function sendChatMessage($msg = '')
        {
            $chatMessage = new BoSChatMessage($this->tournament['id'], $this->user->userId, $msg);
            return $chatMessage->send();
        }

        public function sendSystemMessage($msg = '')
        {
            $chatMessage = new BoSChatMessage($this->tournament['id'], $this->user->userId, $msg);        
            return $chatMessage->sendSystemMessage();
        }

        /**
            @param int - amount
            Adds money to user balance and logs all into the system
        */
        public function addMoneyToUser($amount=0)
        {
            $uh = phive('UserHandler');
            $tr_id = phive('Cashier')->transactUser($this->user->userId, $amount, "CHAT-Register User to BoS", null, null, 29, false);
            if (!$tr_id) {
                throw new Exception("NO money added to user [id: {$this->user->userId}]", 1);
            }
            $descr = $this->user->getUsername()." transferred {$amount} to ".$this->user->getUsername();
            $uh->logIp($this->user->getId(), $this->user->getId(), 'cash_transactions', $descr, $tr_id);
            $uh->logAction($this->user->userId, $descr, 'money_transfer');
        }

        public function getUser()
        {
            return $this->user;
        }

        public function getUserId()
        {
            return $this->user->userId;
        }

        public function getUsername()
        {
            return $this->user->getUsername();
        }

        public function getAlias()
        {
            return empty($this->user->getData()['alias']) ? $this->user->getData()['firstname'] : $this->user->getData()['alias'];
        }

        public function getTournamentEntryField($field='',$update='false')
        {
            if (empty( $this->tournamenEntry ) || $update) {
                return $this->getTournamentEntry()[$field];
            }
            return $this->tournamenEntry[$field];
        }

        public function getTotalWin($update = true) 
        {
            return $this->getTournamentEntryField('win_amount',$update);
        }

        public function getSpinsLeft($update = false)
        {
            return $this->getTournamentEntryField('spins_left',$update);
        }

        public function getCashBalance($update = false)
        {
            return $this->getTournamentEntryField('cash_balance',$update);
        }

        public function getCurrency()
        {
            return $this->user->getData()['currency'];
        }

        public function getTournamentEntryId($update = true)
        {
            return $this->getTournamentEntryField('id',$update);
        }
        public function spin()
        {            
            $netent_extUid = phive('Netent')->getExtUname("{$this->getUserId()}e{$this->getTournamentEntryId()}");            
            $betAmount = $this->tournament['max_bet'] / 100;
            $winAmount = $betAmount * rand(0,10);
            $this->testMpSpin($netent_extUid, $this->user, 'normal', str_replace('netent_', '', $this->tournament['game_ref']), $betAmount, $winAmount);

            return true;
        }

        public function testMpSpin($uid, $u, $type, $gid, $bamount, $wamount, $close_ground = true){
            $testNetent = TestPhive::getModule('Netent');
            $testNetent->url = "https://".phive()->getSetting('full_domain')."/diamondbet/soap/netent.php";
            switch($type){
              case 'normal':
                toWs('gameRoundStarted', 'mpextendtest', $u->getId());
                toWs('spinStarted', 'mpextendtest', $u->getId());
                $testNetent->withdrawAndDeposit($uid, $gid, $testNetent->randId(), $testNetent->randId(), $bamount, $wamount,false);
                toWs('spinEnded', 'mpextendtest', $u->getId());
                toWs('gameRoundEnded', 'mpextendtest', $u->getId());
                break;
              case 'spin-open':
                toWs('gameRoundStarted', 'mpextendtest', $u->getId());
                toWs('spinStarted', 'mpextendtest', $u->getId());
                toWs('spinEnded', 'mpextendtest', $u->getId());
                break;
              case 'bonus-start':
                toWs('bonusGameStarted', 'mpextendtest', $u->getId());
                break;
              case 'bonus-end':
                toWs('bonusGameEnded', 'mpextendtest', $u->getId());
                if($close_ground)
                  toWs('gameRoundEnded', 'mpextendtest', $u->getId());
                break;
              case 'frb-start':
                toWs('freeSpinStarted', 'mpextendtest', $u->getId());
                break;
              case 'bonus-spin':
                toWs('spinStarted', 'mpextendtest', $u->getId());
                toWs('spinEnded', 'mpextendtest', $u->getId());
                break;
              case 'frb-end':
                $testNetent->deposit($uid, $gid, $testNetent->randId(), $testNetent->randId(), $wamount);
                usleep(100000);
                toWs('gameRoundEnded', 'mpextendtest', $u->getId());
                toWs('freeSpinEnded', 'mpextendtest', $u->getId());
                break;
            }
          }


    }

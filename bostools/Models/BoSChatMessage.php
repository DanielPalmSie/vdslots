<?php
require_once __DIR__ . '/../../phive/phive.php';
/**
 * Handle the creation of a new message for the chat
 */
class BoSChatMessage
{
    protected $tournament;
    protected $user;
    protected $msg;
    
    /**
        @param Int - tournamentId
        @param Ing - userId
        @param String (optional)- message
        prerrequisites:
        - userId exists
        - user is registered in tournament
        - msg is valid
     */
    public function __construct($tournamentId, $userId, $msg = '')
    {
        $t = phive('Tournament');
        $uh = phive('UserHandler');
        $this->user = $uh->newUser($userId);
        // Check if user exists
        if ($this->user->userId == null) {
            throw new Exception("User [id:{$userId}] can't be instantiated ", 1);
        }
        // Check if user is registered in tournament
        if (!$t->isRegistered($tournamentId, $userId)) {
            throw new Exception("User [id:{$userId}] is not registered in tournament [id: {$tournamentId}}] ", 1);
        }
        $this->user->preload();
        $this->tournament = $this->getTournamentData($tournamentId);

        $this->msg = $msg;
        if (empty($this->msg)) {
            $this->msg = $this->generateMsg();
        }
    }

    /**
        gets data from tounament
        @param $tournamentId tournament id
        @returns array tournament data
    */
    public function getTournamentData($tournamentId)
    {
        $t = phive('Tournament');
        $tournamentData = $t->_getOneWhere(['id' => $tournamentId]);
        if (count($tournamentData) == 0) {
            throw new Exception("Tournament [id:{$tournamentId}] can't be found ", 1);
        }
        return $tournamentData;
    }

    /*
        Let's generate some dummy content from a file with quotes
    */
    public function generateMsg($filename = 'quotes.txt')
    {
        if (!file_exists($filename)) {
            throw new Exception("File [{$filename}] doesn't exists", 1);
        }
        $lines = explode("\n", file_get_contents($filename));
        $line = $lines[mt_rand(0, count($lines) - 1)];
        return $line;
        // list($text, $author) = explode('|', $line);
        // return $text;
    }

    /*
        Inserts the chat message into the tounament chat messages
        and publish the message to the WS in order to receive live updates
    */
    public function send()
    {
        $t = phive('Tournament');
        $hi       = date('H:i');
        $tournamentId = $this->tournament['id'];
        $tournamentEntry = $t->entryByTidUid($tournamentId, $this->user->userId);
        $msg      = [
            'user_id' => $this->user->userId, 'firstname' => $this->user->getAlias(true),
            'msg' => $this->msg, 'hi' => $hi, 'tid' => $tournamentId,
            'wstag' => 'umsg', 'entry_id' => $tournamentEntry['id']];
        $t->addToChatContents($this->tournament, $msg);

        toWs($msg, 'mp'.$tournamentId, 'na');
        toWs($msg, 'lobbychat'.$tournamentId, 'na');
        // toWs($msg, 'mp-chat-admin', 'na');
        
        return $msg;
    }

    /*
        Sends a system message 
    */
    public function sendSystemMessage()
    {
        $t = phive('Tournament');
        $tournamentId = $this->tournament['id'];
        $alias = mt_rand(0,1) ?  'mp.freespin.system.msg' : 'mp.bonus.system.msg';
        $msg = ['firstname' => tAll('mp.system'), 'msg' => tAll($alias, [$this->user->getUsername()]), 'hi' => date('H:i'), 'tid' => tournamentId, 'wstag' => 'smsg'];

        $t->addToChatContents($this->tournament, $msg);
        toWs($msg, 'mp'.$tournamentId, 'na');
        toWs($msg, 'lobbychat'.$tournamentId, 'na');

        return $msg;
    }
}

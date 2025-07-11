<?php
class TestClash extends TestPhive {

    private $microGames;
    private $settings = [];

    function __construct() {
        $this->microGames                       = phive('MicroGames');
        $this->settings['game_ref']             = "netent_starburst_not_mobile_sw";
        $this->settings['amount_users']         = 10;
        $this->settings['amount_bets_per_user'] = 10;
        //$this->settings['min_bet']              = 200;
        //$this->settings['max_bet']              = 2500;
        $this->settings['possible_bets']        = [20, 100, 200]; // This is in cents.
        $this->settings['users_offset']         = 0;
    }

    function setSettings(Array $settings) {
        foreach($settings as $key => $value) {
            $this->settings[$key] = $value;
        }
    }

    function testClash() {

        //$entries = phive('Tournament')->getEntriesByStatus('open', 'user_id');
        $entries = $this->microGames->getOpenGameSessions('user_id');

        $entries = array_slice($entries, $this->settings['users_offset'], $this->settings['amount_users']);

        $cur_game = $this->microGames->getByGameRef($this->settings['game_ref']);

        $bigwin_distrubution['bigwin']       = 50;
        $bigwin_distrubution['megawin']      = 90;
        $bigwin_distrubution['supermegawin'] = 100;

        foreach($entries as $index => $entry) {

            $u = cu($entry['user_id']);

            for ($i = 0; $i < $this->settings['amount_bets_per_user']; $i++) {
                $r = random_int(0, 100);
                //$bet = random_int($this->settings['min_bet'], $this->settings['max_bet']);

                $selected_bet_index = random_int(0, count($this->settings['possible_bets'])-1);
                $bet = $this->settings['possible_bets'][$selected_bet_index];

                $ext_id = $this->settings['game_ref']."-dev-".TestPhive::randId()."-".TestPhive::randId();
                $tr_id = TestPhive::randId();

                echo "InsertBet [".($index+1)."/".count($entries)."]: ".$u->data['id']." Bet: ".$bet."\n";
                // insertBet($user, $cur_game, $tr_id, $ext_id, $bet_amount, $jp_contrib, $bonus_bet, $balance, $stamp = '')
                //$p = $c->insertBet($u->data, $cur_game, $tr_id, $ext_id, 100, 0.0, 0, $u->getBalance());
                //phive('Casino')->insertBet($u->data, $cur_game, $tr_id, $ext_id, $bet, 0.0, 0, $u->getBalance());
                phive()->pexec('Casino', 'insertBet', [$u->data, $cur_game, $tr_id, $ext_id, $bet, 0.0, 0, $u->getBalance()], 200, $u->data['id']);

                $win = 0;
                if ($r <= $bigwin_distrubution['bigwin']) {
                    $win = $bet * 15;
                } else if ($r <= $bigwin_distrubution['megawin']) {
                    $win = $bet * 30;
                } else if ($r <= $bigwin_distrubution['supermegawin']) {
                    $win = $bet * 60;
                }

                echo "InsertWin [".($index+1)."/".count($entries)."]: ".$u->data['id']." Win: ".$win." (".(int)$win/$bet."x) (r:".$r.")\n";
                // insertWin($user, $cur_game, $balance, $tr_id, $win, $bonus_bet, $ext_id, $award_type, $stamp = '')
                //phive('Casino')->insertWin($u->data, $cur_game, $u->getBalance(), $tr_id, $win, 0, $ext_id, 2);
                phive()->pexec('Casino', 'insertWin', [$u->data, $cur_game, $u->getBalance(), $tr_id, $win, 0, $ext_id, 2], 500, $u->data['id']);
            }
            echo "\n";
        }
    }
}

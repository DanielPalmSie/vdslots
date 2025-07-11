<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringsForWeekendBooster extends Migration
{

    private string $table = 'localized_strings';
    private Connection $connection;

    private array $string = [
            'alias' => 'weekend.booster.external.info.vault',
            'language' => 'en',
            'new_value' => '<h1>Rainbow Fridays - Win up to {{csym}} {{modm:300}} per day!</h1> <p justify="" quot="">At Mr Vegas we got a boosted payout to all our players, so every Friday we accumulate all the spins you would have made during the previous week on our video slots, slots, jackpot games and live casino, and pay you a guaranteed win we call the Rainbow Treasure. The Rainbow Treasure is based on the total amount of bets made during the week and the RTP&rsquo;s of the specific games you have played.</p> <p justify="" quot="">How much will you win? It&rsquo;s easy, the more you bet throughout the week, the more you get in Rainbow Treasure on Friday!</p> <p justify="" quot="">Guess what, the Rainbow Treasure is real winnings that do not need to be wagered!&nbsp;</p> <h2>How does the Rainbow Treasure work?</h2> <p justify="" quot="">You win daily Rainbow Treasure&nbsp;that are paid out to you on a weekly basis based on all the bets you place on video slots, slots, jackpot games and live casino.</p> <p justify="" quot="">The Rainbow Treasure &nbsp;is paid out every Friday one week in arrears. So if you happen to be playing during week 40, your Rainbow Treasure will be paid out on Friday of week 41.</p> <p justify="" quot="">You can keep track of your account activity anytime you like. Simply go to My Account &rarr; My Rainbow Treasure. The Rainbow Treasure&nbsp;statistics are updated once per day.</p> <hr /> <p>&nbsp;<strong>Terms and Conditions</strong></p> <ul> <li justify="" quot="">You need to have made at least one deposit in order to receive Rainbow Treasure&nbsp;payments. &nbsp;&nbsp;</li> <li justify="" quot="">Rainbow Treasure cannot be combined with any other bonus or promotion. If you activate or have an activated bonus, no Rainbow Treasure can be earned until completion or failure of the active bonus or promotion.&nbsp; &nbsp;</li> <li justify="" quot="">Minimum Rainbow Treasure payout is {{csym}} {{modm:0.50}}. Maximum Rainbow Treasure payout in one day (00:00 - 23:59 GMT) is limited to {{csym}} {{modm:300}}. All values below {{csym}} {{modm:0.50}} will not be paid out and will be forfeited.</li> <li justify="" quot="">Rainbow Treasure&nbsp;is calculated on theoretical RTP, we boost the RTP of the game with following formula 0.06(100-TRTP)= Boosted RTP. The extra winnings are paid to the player once a week as a guaranteed win on each bet.</li> <li justify="" quot="">Rainbow Treasure&nbsp;payments will be paid out every Friday, one week in arrears.</li> <li justify="" quot="">Mr Vegas reserves the right to withdraw any wrong paid Rainbow Treasure and to change or end this promotion without giving a notice to the customer at any given time.</li> <li justify="" quot="">These Terms and Conditions form part of and are an extension of the General Terms and Conditions at Mr Vegas. If and where there is a conflict between these Terms & Conditions and the General Terms and Conditions which relates specifically to the Rainbow Fridays, these Terms & Conditions will take precedence.</li> <li justify="" quot="">This version of Rainbow Fridays is excluded for customers from Sweden.</li> <li justify="" quot="">Full&nbsp;Terms and Conditions&nbsp;on the website <a href="https://www.mrvegas.com/terms-and-conditions/mga-games-specific/">apply</a>.</li> </ul>',
            'cur_value' => '<h1>Rainbow Fridays - Win up to {{csym}} {{modm:300}} per day!</h1> <p justify="" quot="">At Mr Vegas we got a boosted payout to all our players, so every Friday we accumulate all the spins you would have made during the previous week on our video slots, slots, jackpot games and live casino, and pay you a guaranteed win we call the Rainbow Treasure. The Rainbow Treasure is based on the total amount of bets made during the week and the RTP&rsquo;s of the specific games you have played.</p> <p justify="" quot="">How much will you win? It&rsquo;s easy, the more you bet throughout the week, the more you get in Rainbow Treasure on Friday!</p> <p justify="" quot="">Guess what, the Rainbow Treasure is real winnings that do not need to be wagered!&nbsp;</p> <h2>How does the Rainbow Treasure work?</h2> <p justify="" quot="">You win daily Rainbow Treasure&nbsp;that are paid out to you on a weekly basis based on all the bets you place on video slots, slots, jackpot games and live casino.</p> <p justify="" quot="">The Rainbow Treasure &nbsp;is paid out every Friday one week in arrears. So if you happen to be playing during week 40, your Rainbow Treasure will be paid out on Friday of week 41.</p> <p justify="" quot="">You can keep track of your account activity anytime you like. Simply go to My Account &rarr; My Rainbow Treasure. The Rainbow Treasure&nbsp;statistics are updated once per day.</p> <hr /> <p>&nbsp;<strong>Terms and Conditions</strong></p> <ul> <li justify="" quot="">You need to have made at least one deposit in order to receive Rainbow Treasure&nbsp;payments. &nbsp;&nbsp;</li> <li justify="" quot="">Rainbow Treasure cannot be combined with any other bonus or promotion. If you activate or have an activated bonus, no Rainbow Treasure can be earned until completion or failure of the active bonus or promotion.&nbsp; &nbsp;</li> <li justify="" quot="">Minimum Rainbow Treasure payout is {{csym}} {{modm:0.50}}. Maximum Rainbow Treasure payout in one day (00:00 - 23:59 GMT) is limited to {{csym}} {{modm:300}}. All values below {{csym}} {{modm:0.50}} will not be paid out and will be forfeited.</li> <li justify="" quot="">Rainbow Treasure&nbsp;is calculated on theoretical RTP, we boost the RTP of the game with following formula 0.06(100-TRTP)= Boosted RTP. The extra winnings are paid to the player once a week as a guaranteed win on each bet.</li> <li justify="" quot="">Rainbow Treasure&nbsp;payments will be paid out every Friday, one week in arrears.</li> <li justify="" quot="">Mr Vegas reserves the right to withdraw any wrong paid Rainbow Treasure and to change or end this promotion without giving a notice to the customer at any given time.</li> <li justify="" quot="">These Terms and Conditions form part of and are an extension of the General Terms and Conditions at Mr Vegas. If and where there is a conflict between these Terms & Conditions and the General Terms and Conditions which relates specifically to the Rainbow Fridays, these Terms & Conditions will take precedence.</li> <li justify="" quot="">This version of Rainbow Fridays is excluded for customers from Sweden.</li> <li justify="" quot="">Full&nbsp;Terms and Conditions&nbsp;on the website <a href="https://www.mrvegas.com/terms-and-conditions/mga-games-specific/>apply</a>.</li> </ul>'
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection->table($this->table)
            ->where('alias', '=', $this->string['alias'])
            ->where('language', '=', $this->string['language'])
            ->update([
                'value' => $this->string['new_value']
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table($this->table)
            ->where('alias', '=', $this->string['alias'])
            ->where('language', '=', $this->string['language'])
            ->update([
                'value' => $this->string['cur_value']
            ]);
    }
}

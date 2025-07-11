<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForSeasonalPromotion extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $data = [
        'en' => [
            'register.to.participate' => 'Register to Participate',
            'user-details.phone-placeholder' => 'Mobile Number',

            'seasonal.form.submitted.successfully' => 'Form submitted successfully',
            'seasonal.privacy.agree.personal.data.marketing' => 'I agree to my personal data being processed to marketing communications from {{1}} in accordance with the',
            'seasonal.conform.18.years' => "I confirm I'm 18 years or older.",
            'seasonal.winner.prize.agree' => 'I understand that if I am a winner of one of the prize draws my name will be provided by Mr Vegas to West Bromwich Albion Football Club to allow them to announce the winner at the relevant WBA home match.',
            'seasonal.promotion.content.main.html' => '<div class="promotion-partner__content">
                <h3>Enter your details to take part in the Megariches Half Time Draw at <b>WBA</b> home game</h3>
            </div>',

            'seasonal.promotion.content.contact.html' => "<h4>Contact Details</h4>
            <p>Share your details and we'll send you an <b>email</b> to complete the registration and participate in the promotion.</p>",

            'seasonal.promotion.term.condition.html' => " <div class='promotion-partner__term-condition'>
                <h4>Terms & Conditions</h4>
                <p>1. The promoter of the Mega Riches Half Time Activation (the “Prize Draw”) is Videoslots Limited (“Videoslots”), Telghet Gwardiamangia 105, Tal Pieta, Malta. For the avoidance of the doubt, the Prize Draw is not being offered or run by West Bromwich Albion Football Club Limited (“WBA”) and WBA has no responsibility or obligation in respect of the prize draw. </p>
                <p>2. Participation in the Prize Draw is only offered to people resident in the United Kingdom and aged 18 or over. Employees or agencies of WBA or Videoslots or any of their respective group companies and their family members, or anyone else connected with the Prize Draw are not eligible to participate in this promotion. </p>
                <p>3. To participate in the Prize Draw, prospective customers need to: (i) register with www.megariches.com/wba; and (ii) have not opted-out from the promotion; and (iii) hold an active account at the time of the draw. No qualifying deposit or wager is required. Where such conditions are met, customers will be awarded one (1) entry to the Draw. </p>
                <p>4. Each customer may only be awarded one entry. Entries on behalf of another person will not be accepted and joint submissions are not allowed and Videoslots accepts no responsibility for entries that are lost, delayed, misdirected or incomplete or cannot be delivere12. d or entered for any technical or other reason. </p>
                <p>5. The closing date of the Prize Draw is 23:59 on 30th November 2024. Entries received after this time will be automatically entered into the next draw. </p>
                <p>6. Following the closing date, two customers (the “Contestants”) will be randomly drawn from a pool that gathers all the entries awarded in accordance with these Terms and Conditions. The draw will be performed by a random computer process. </p>
                <p>7. The Contestants will be notified by email on Monday 2nd December 2024. If a Contestant does not respond to Videoslots within 7 days of being notified, a new Contestant will be drawn from the same pool and accordingly notified. </p>
                <p>8. The half time activation will take place during the match WBA v Coventry, scheduled on Wednesday 11th December. </p>
                <p>9. The Contestants’ name and contact details will be shared with WBA so that they can be contacted in respect of being part of the half time activation. Contestants’ names will be displayed on the screens at The Hawthorns football stadium. </p>
                <p>10. The winner of the half time activation will win: (i) two (2) hospitality tickets for a game of their choice (subject to availability), at any West Bromwich Albion fixture scheduled between January 2025 and the end of the EFL Season 2024/25; and (ii) an official WBA jersey signed by the players. The winner will also automatically be automatically entered into a prize draw to win a season ticket for 25/26 season. </p>
                <p>11. Each prize winner will be notified by Videoslots courtesy of an email. The prize itself can be redeemed with Videoslots who will liaise with West Bromwich Albion to arrange the prize. </p>
                <p>12. Where the winner is prohibited from attending a WBA match at the Hawthorns by any judgement or order in law, or by WBA, the English Football League, FIFA, UEFA or any other relevant competent authority in this context, the winner’s prize will be forfeited. </p>
                <p>13. Videoslots shall accept no responsibility for any costs associated with the prize and not specifically included in the prize (including, without limitation, travel to and from the stadium). </p>
                <p>14. The prize is non-exchangeable, non-transferable, and is not redeemable for cash or other prizes. </p>
                <p>15. Videoslots retains the right to substitute the prize with another prize of similar value in the event the original prize offered is not available. </p>
                <p>16. Participants into the Prize Draw shall be deemed to have accepted these Terms and Conditions. </p>
                <p>17. Participants into the Prize draw further acknowledge and agree that the winner may be required to take part in promotional activity related to the Prize Draw and the winner shall participate in such activity on Videoslots’ reasonable request during the course of the EFL Season 2024/25, including but not limited to granting the consent to the use of the winner's voice, image, photograph and name for marketing and advertising purposes (in any medium, including still photographs and films, and on the internet, including any websites hosted by Videoslots and its related companies) without additional compensation or prior notice and, in entering the Prize Draw, all entrants consent to the same. </p>
                <p>18. By participating in the Prize Draw, you agree to the collection, retention, usage and distribution of your personal information in order to process and contact you about your Prize Draw entry, and for the purposes outlined in paragraph 13 above. </p>
                <p>19. Videoslots reserves the right at any time and from time to time to modify or discontinue, temporarily or permanently, this Prize Draw with or without prior notice due to reasons outside its control (including, without limitation, in the case of anticipated, suspected or actual fraud) or the interruption of the sponsorship agreement with West Bromwich Albion. The decision of Videoslots in all matters under its control is final and binding and no correspondence will be entered into </p>
            </div> ",
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $brandSpecificTranslations = [
            'seasonal.winner.prize.agree' => 'I understand that if I am a winner of one of the prize draws my name will be provided by MrVegas to Sheffield Wednesday Football Club to allow them to announce the winner at the relevant SWFC home match.',
            'seasonal.promotion.content.main.html' => '<div class="promotion-partner__content">
                <h3>Enter your details to take part in MrVegas Half Time Draw at <b>SWFC</b> home game</h3>
            </div>',
            'seasonal.promotion.term.condition.html' => ' <div class="promotion-partner__term-condition">
                <h4>Terms & Conditions</h4>
               <p>1. The Mr Vegas Weekly Draw (the “Prize Draw“) is open to people resident in the United Kingdom aged 18 and over who provide their email address, phone number, and complete the registration on www.mrvegas.com/swfc from Monday 9th September 2024.</p>
               <p>2. Employees or agencies of Sheffield Wednesday Football Club Limited (“SWFC”) or Videoslots (“Videoslots”) or any of their respective group companies or their family members, or anyone else connected with the Prize Draw may not enter the Prize Draw. People whose Videoslots account is closed, suspended, or blocked are not eligible to win the prize.</p>
               <p>3. Entrants into the Prize Draw shall be deemed to have accepted these Terms and Conditions.</p>
               <p>4. The Prize Draw is not operated or run by Sheffield Wednesday Football Club Limited, and Sheffield Wednesday Football Club has no responsibility in respect of the Prize Draw.</p>
               <p>5. By submitting your personal information, you agree to receive emails from Videoslots containing offers and developments that we think may interest you. You will be given the opportunity to unsubscribe in every email that we send.</p>
               <p>6. To enter the Prize Draw, you must complete the form above and then complete the Mega Riches registration. No purchase is necessary.</p>
               <p>7. Only one entry per person. Entries on behalf of another person will not be accepted, and joint submissions are not allowed.</p>
               <p>8. The weekly Prize Draws will be made every Monday from 16th September 2024 and will use a random-generated system from all the entries; prizes to be given out include:</p>
                   <ul>
                      <li>Pair of Home Match Tickets</li>
                      <li>Pair of Hospitality Tickets to Home Games</li>
                      <li>Entry to the Half Time Activation Game at Sheffield Wednesday v Norwich on 5th November 2024</li>
                     <li>Tickets to Mr Vegas Grand Slam of Darts between 9th -17th November 2024</li>
                   </ul>
               <p>9. The prize winner’s name and contact details will be shared with SWFC so that the prize winner can be contacted regarding redeeming the respective prize.</p>
               <p>10. Videoslots accepts no responsibility for entries that are lost, delayed, misdirected, incomplete, or cannot be delivered or entered for any technical or other reason.</p>
               <p>11. The closing date of the final prize draw will be Monday 16th December 2024.</p>
               <p>12. One winner will be chosen from a random draw of entries received in accordance with these Terms and Conditions. The draws will be performed by a random computer process.</p>
              <p>13. The half-time activation game will take place during SWFC v Norwich on Tuesday 5th November 2024.</p>
              <p>14. Two winners will go head-to-head at half-time by the side of the SWFC pitch, hosted by the SWFC matchday host, who will run the activation. Both winners will be asked SWFC-related questions and partake in an activation game.</p>
              <p>15. The winner of the half-time activation will win:</p>
                 <ul>
                   <li>A pair of hospitality tickets for a game of their choice between December 2024 and the end of the 24/25 season.</li>
                   <li>A signed shirt by the players.</li>
                   <li>1,000 free spins at MrVegas.com.</li>
                   <li>Automatic entry into a prize draw to win a season ticket for the 25/26 season.</li>
                 </ul>
              <p>16. Videoslots accepts no responsibility for any costs associated with the prize that are not specifically included in the prize (including, without limitation, travel to and from the stadium).</p>
              <p>17. The winner will be notified by email on Monday 28th October 2024. If a winner does not respond to Videoslots within 7 days of being notified, a new winner will be notified.</p>
              <p>18. Each prize winner will be notified by Videoslots via email. The prizes can be redeemed with Sheffield Wednesday Football Club Limited or Videoslots.</p>
              <p>19. The prize is non-exchangeable, non-transferable, and is not redeemable for cash or other prizes.</p>
              <p>20. Videoslots retains the right to substitute the prize with another prize of similar value if the original prize offered is not available.</p>
              <p>21. The winners may be required to participate in promotional activity related to the Prize Draw upon Videoslots’ reasonable request during the 2024/2025 English Football League season. The winner consents to the use of their voice, image, photograph, and name for publicity purposes without additional compensation or prior notice.</p>
              <p>22. Videoslots shall handle all personal information supplied as described in its privacy policy and in compliance with data protection legislation. By entering the Prize Draw, you agree to the collection, retention, usage, and distribution of your personal information for processing and contact purposes.</p>
              <p>23. Videoslots accepts no responsibility for damage, loss, liabilities, injury, or disappointment incurred or suffered as a result of entering the Prize Draw or accepting the prize. Videoslots further disclaims liability for any injury or damage to participants’ or others’ computers resulting from participation in the Prize Draw.</p>
              <p>24. Videoslots reserves the right to modify or discontinue the Prize Draw at any time due to reasons outside its control, including suspected or actual fraud, or the interruption of the sponsorship agreement with Sheffield Wednesday.</p>
              <p>25. Videoslots shall not be liable for any failure to comply with its obligations caused by circumstances outside its reasonable control, including, but not limited to, weather conditions, fire, flood, strikes, or other force majeure events.</p>
              <p>26. If the winner is prohibited from attending a SWFC match at Hillsborough due to legal or organizational restrictions, their prize will be forfeited.</p>
              <p>27. The Prize Draw is governed by English and Welsh law, and entrants submit to the exclusive jurisdiction of the English and Welsh courts.</p>
              <p>28. Promoter: Videoslots, Telghet Gwardiamangia 105, Tal Pieta, Malta. The Prize Draw is not operated or run by Sheffield Wednesday Football Club Limited, which holds no responsibility or obligation in respect of the draw.</p>
              <p>29. Only those who register and complete all the required steps will be eligible and entered into the Prize Draw.</p>
             </div>',

        ];

        // If the brand is 'mrvegas', apply the brand-specific translations
        if ($this->brand === 'mrvegas') {
            foreach ($brandSpecificTranslations as $key => $value) {
                $this->data['en'][$key] = $value;
            }
        }


    }

    public function up()
    {
        if (!in_array($this->brand, ['mrvegas', 'megariches'])) {
            return;
        }

        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $exists = $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->first();

                if (!empty($exists)) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $language)
                        ->update(['value' => $value]);
                } else {
                    $this->connection
                        ->table($this->table)
                        ->insert([
                            [
                                'alias' => $alias,
                                'language' => $language,
                                'value' => $value,
                            ]
                        ]);
                }

            }
        }
    }

    public function down()
    {
        if (!in_array($this->brand, ['mrvegas', 'megariches'])) {
            return;
        }

        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->delete();
            }
        }
    }
}

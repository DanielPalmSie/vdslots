<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringsForWba extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $data = [
        'en' => [
            'promotion.images' => '<div class="promotion-partner__image-container">
                <img src="{{cdn}}/file_uploads/wba_landing_neons.png" alt="wba.landing.neons"/>
            </div>
            <div class="promotion-partner__image-container promotion-partner__full-image">
                <img src="{{cdn}}/file_uploads/wba_fullimage_banner.png" alt="wba.fullimage.banner"/>
            </div>',

            'promotion.content.main' => '<div class="promotion-partner__content">
                <h3>Sign up now for the Mr Vegas Half Time Draw.</h3>
                <h3>Every game at The Hawthorns we raffle a <b>WINNER!</b></h3>
            </div>',

            'promotion.content.contact' => "<h4>Contact Details</h4>
            <p>Share your details and we'll send you an <b>email</b> to complete the registration and participate in the promotion.</p>",

            'promotion.term.condition' => " <div class='promotion-partner__term-condition'>
                <h4>Terms & Conditions</h4>
                <p>1. The MrVegas Half Time Draw (the “Prize Draw“) is open to people resident in the United Kingdom aged 18 and over who provide their email address, phone number and complete the registration on www.mrvegas.com. </p>   
                <p>2. Employees or agencies of West Bromwich Albion (“WBA”) or Videoslots  (“Videoslots”)  or any of their respective group companies or their family members, or anyone else connected with the Prize Draw may not enter the Prize Draw. People whose Videoslots account is closed, suspended or blocked are not eligible to win the prize. </p>
                <p>3. Entrants into the Prize Draw shall be deemed to have accepted these Terms and Conditions.</p>
                <p>4. By submitting your personal information you agree to receive emails from Videoslots containing offers and developments that we think may interest you. You will be given the opportunity to unsubscribe on every email that we send.</p>  
                <p>5. To enter the Prize Draw you must complete the form above and then complete the MrVegas registration.  No purchase is necessary.  </p>
                <p>6. Only one entry per person. Entries on behalf of another person will not be accepted and joint submissions are not allowed.</p>
                <p>7. Videoslots accepts no responsibility is taken for entries that are lost, delayed, misdirected or incomplete or cannot be delivered or entered for any technical or other reason.</p>
                <p>8. The closing date of the Prize Draw is 23:59 two days before the draw.  Entries received after this time period will be automatically entered into the following draw.  </p>
                <p>9. One winner will be chosen from a random draw of entries received in accordance with these Terms and Conditions.  The draw will be performed by a random computer process.  The draw will take place on the day before each EFL Championship scheduled game of West Brom Albion at the Hawthorns stadium  . </p>
                <p>10. The winners will receive a WBA season ticket adult (Smethwick End or Birmingham Rd). </p>
                <p>11. The winners will be required to verify that they are over 18 years old before collecting their WBA season ticket. </p>
                <p>12. Videoslots accepts no responsibility for any costs associated with the prize and not specifically included in the prize (including, without limitation, travel to and from the stadium).</p>
                <p>13. The winner will be notified by email on or before the day of the match during which the winner will be announced.   The name will be displayed on the megascreen at the stadium . Accordingly ,and as necessary, the winners’ name will be shared with WBA for this purpose, all entrants consent to the same.  If a winner does not respond to Videoslots within 14 days of being notified, the winner's prize will be forfeited. </p>
                <p>14. The prize will be sent to the winner by Videoslots by   email.</p>
                <p>15. The prize is non-exchangeable, non-transferable, and is not redeemable for cash or other prizes.</p>
                <p>16. Videoslots retains the right to substitute the prize with another prize of similar value in the event the original prize offered is not available.</p>
                <p>17. The winner may be required to take part in promotional activity related to the Prize Draw and the winner shall participate in such activity on Videoslots’ reasonable request during the course of the 2023/2024 English Football League season.  The winner consents to the use by Videoslots and its related companies, for an unlimited time, of the winner's voice, image, photograph and name for publicity purposes (in any medium, including still photographs and films, and on the internet, including any websites hosted by Videoslots and its related companies) and in advertising, marketing or promotional material without additional compensation or prior notice and, in entering the Prize Draw, all entrants consent to the same. </p> 
                <p>18. Videoslots shall use and take care of any personal information you supply to it as described in its privacy policy, and in accordance with data protection legislation.  By entering the Prize Draw, you agree to the collection, retention, usage and distribution of your personal information in order to process and contact you about your Prize Draw entry, and for the purposes outlined in paragraph 14 above.</p>
                <p>19. Videoslots accepts no responsibility for any damage, loss, liabilities, injury or disappointment incurred or suffered by you as a result of entering the Prize Draw or accepting the prize. Videoslots further disclaims liability for any injury or damage to your or any other person's computer relating to or resulting from participation in or downloading any materials in connection with the Prize Draw. Nothing in these Terms and Conditions shall exclude the liability of Videoslots for death, personal injury, fraud or fraudulent misrepresentation as a result of its negligence.</p>
                <p>20. Videoslots reserves the right at any time and from time to time to modify or discontinue, temporarily or permanently, this Prize Draw with or without prior notice due to reasons outside its control (including, without limitation, in the case of anticipated, suspected or actual fraud) or the interruption of the sponsorship agreement with West Bromwich Albion. The decision of Videoslots in all matters under its control is final and binding and no correspondence will be entered into.</p>
                <p>21. Videoslots shall not be liable for any failure to comply with its obligations where the failure is caused by something outside its reasonable control. Such circumstances shall include, but not be limited to, weather conditions, fire, flood, hurricane, strike, industrial dispute, war, hostilities, political unrest, riots, civil commotion, inevitable accidents, supervening legislation or any other circumstances amounting to force majeure.</p>
                <p>22. Where the winner is prohibited from attending a WBA match at the Hawthorns by any judgement or order in law, or by WBA, the Championship, the English Football League, FIFA, UEFA or any other relevant competent authority in this context, the winner’s prize will be forfeited.</p>
                <p>23. The Prize Draw will be governed by English and Welsh law and entrants to the Prize Draw submit to the exclusive jurisdiction of the English and Welsh courts.</p>
                <p>24. Promoter: Videoslots, Telghet Gwardiamangia 105, Tal Pieta, Malta. </p>
                <p>26. Only those who register in accordance with clause 1 above and complete all the required steps will be eligible for and entered into the Prize Draw.</p>  
            </div>",

            'form.submitted.successfully' => 'Form submitted successfully',
            'privacy.agree.personal.data.marketing' => 'I agree to my personal data being processed to marketing communications from {{1}} in accordance with the',
            'conform.18.years' => "I confirm I'm 18 years or older.",
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
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
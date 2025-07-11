<?php
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;


class AddPdcSeasonalPromotionLocalizedString extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $data = [
        'en' => [
            'pdc.seasonal.winner.prize.agree' => 'I understand that if I am a winner of one of the prize draws my name will be provided by Mr Vegas Slam Darts Promotion.',
            'pdc.seasonal.promotion.content.main.html' => '<div class="promotion-partner__content">
                <p><b>Win the package</b> <br/>Mr Vegas Grand Slam of Darts</p>
            </div>',
            'pdc.seasonal.promotion.term.condition.html' => '<div class="promotion-partner__term-condition">
  <h4><strong>Terms & Conditions</strong></h4>

  <p>1. The Mr Vegas Grand Slam of Darts Ultimate Prize (the “Prize Draw“) is open to residents in the United Kingdom aged 18 who complete the registration at www.mrvegas.com/pdc and make a deposit of £10 or more into their account from Monday 8th June 2025.</p>

  <p>2. The Mr Vegas Grand Slam of Darts Ultimate Prize will consist of 5 winners receiving:</p>
  <ul>
    <li>Pair of VIP Tickets to a session at Mr Vegas Grand Slam of Darts 2025 between Saturday 8th and Sunday 16th November 2025</li>
    <li>Participation in a 9 dart challenge prior to the session on the Mr Vegas Grand Slam of Darts stage</li>
    <li>Signed dart board by players at Mr Vegas Grand Slam of Darts 2025</li>
    <li>Drinks token on arrival with programme</li>
  </ul>

<p>3. Employees or agencies of Professional Darts Corporation (“PDC”) or ImmenseGroup (“ImmenseGroup”) or any of their respective group companies or their family members, or anyone else connected with the Prize Draw may not enter the Prize Draw. People whose ImmenseGroup account is closed, suspended or blocked are not eligible to win the prize.</p>

<p>4. Entrants into the Prize Draw shall be deemed to have accepted these Terms and Conditions.</p>

<p>5. The Prize Draw is not operated or run Professional Darts Corporation Limited and Professional Darts Corporation has no responsibility in respect of the Prize Draw. </p>

<p>6. By submitting your personal information, you agree to receive emails from ImmenseGroup containing offers and developments that we think may interest you. You will be given the opportunity to unsubscribe on every email that we send.</p>

<p>7. To enter the Prize Draw you must complete the registration form, open your account with Mr Vegas and make a £10 or more deposit into your account between Monday 8th June 2025 and Sunday 29th June 2025.</p>

<p>8. Only one entry per person. Entries on behalf of another person will not be accepted and joint submissions are not allowed.</p>

<p>9. The closing date of the Prize Draw is 23:59 on Sunday 29th June 2025. The winners will be contacted on Wednesday 2nd July 2025.</p>

<p>10. 5 winners will be chosen from a random draw of entries received in accordance with these Terms and Conditions. The draw will be performed by a random computer process. The winners will be presented with a list of available dates to redeem the VIP tickets and once confirmed, they will be shared with their tickets.</p>

<p>11. The closing date of the Prize Draw is 23:59 on Sunday 29th June 2025. The winners will be contacted on Wednesday 2nd July 2025.</p>

<p>12. 5 winners will be chosen from a random draw of entries received in accordance with these Terms and Conditions. The draw will be performed by a random computer process.</p>

<p>13. ImmenseGroup accepts no responsibility for any costs associated with the prize and not specifically included in the prize (including, without limitation, travel to and from the event).</p>

<p>14. The winners will be notified by email from that one used at account opening stage.If a winner does not respond to within 14 days of being notified, the winner’s prize will be forfeited.</p>

<p>15. The prize is non-exchangeable, non-transferable, and is not redeemable for cash or other prizes.</p>

<p>16. ImmenseGroup retains the right to substitute the prize with another prize of similar value in the event the original prize offered is not available.</p>

<p>17. The winner may be required to take part in promotional activity related to the Prize Draw and the winner shall participate in such activity on Videoslots’ reasonable request. The winner consents to the use by ImmenseGroup and its related companies, for an unlimited time, of the winner’s voice, image, photograph and name for publicity purposes (in any medium, including still photographs and films, and on the internet, including any websites hosted by ImmenseGroup and its related companies) and in advertising, marketing or promotional material without additional compensation or prior notice and, in entering the Prize Draw, all entrants consent to the same.</p>

<p>18. ImmenseGroup shall use and take care of any personal information you supply to it as described in its privacy policy, and in accordance with data protection legislation. By entering the Prize Draw, you agree to the collection, retention, usage and distribution of your personal information in order to process and contact you about your Prize Draw entry.</p>

<p>19. ImmenseGroup accepts no responsibility for any damage, loss, liabilities, injury or disappointment incurred or suffered by you as a result of entering the Prize Draw or accepting the prize. ImmenseGroup further disclaims liability for any injury or damage to your or any other person’s computer relating to or resulting from participation in or downloading any materials in connection with the Prize Draw. Nothing in these Terms and Conditions shall exclude the liability of ImmenseGroup for death, personal injury, fraud or fraudulent misrepresentation as a result of its negligence.</p>

<p>20. ImmenseGroup reserves the right at any time and from time to time to modify or discontinue, temporarily or permanently, this Prize Draw with or without prior notice due to reasons outside its control (including, without limitation, in the case of anticipated, suspected or actual fraud) or the interruption of the sponsorship agreement with Professional Darts Coperation (PPDC). The decision of ImmenseGroup in all matters under its control is final and binding and no correspondence will be entered into.</p>

<p>21. ImmenseGroup shall not be liable for any failure to comply with its obligations where the failure is caused by something outside its reasonable control. Such circumstances shall include, but not be limited to, weather conditions, fire, flood, hurricane, strike, industrial dispute, war, hostilities, political unrest, riots, civil commotion, inevitable accidents, supervening legislation or any other circumstances amounting to force majeure. </p>

<p>22. The Prize Draw will be governed by English and Welsh law and entrants to the Prize Draw submit to the exclusive jurisdiction of the English and Welsh courts. </p>

<p>23. Promoter: ImmenseGroup, Telghet Gwardiamangia 105, Tal Pieta, Malta.The prize draw is not being operated or run by Professions Darts Corporation (PDC) and that Professions Darts Corporation (PDC) has no responsibility or obligation in respect of the prize draw.</p>

<p>24. Only those who register in accordance with clause 1 above and complete all the required steps will be eligible for and entered into the Prize Draw.</p>

</div>
',
        ]
    ];
}

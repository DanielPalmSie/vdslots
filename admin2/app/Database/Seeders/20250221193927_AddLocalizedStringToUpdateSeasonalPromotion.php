<?php
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringToUpdateSeasonalPromotion extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $data = [
        'en' => [
            'seasonal.promotion.already-participated' => 'Oops! It looks like you have already taken part in this seasonal offer.',
            'seasonal.promotion.form.submitted' => 'Your submission was successful! Please check your email for further details',
            'seasonal.promotion.content.main.html' => '<div class="promotion-partner__content">
                <h3>Win a <b>WBA</b> Season Ticket for 2025/26 with Mega Riches</h3>
            </div>',
            'seasonal.promotion.term.condition.html' => " <div class='promotion-partner__term-condition'>
                <h4>Terms and Conditions</h4>
                    <p>1. Promoter: Videoslots Ltd (“Immense Group”) Telghet Gwardiamangia 105, Tal Pieta, Malta. The prize draw is not being operated or run by West Bromwich Albion Football Club Limited and that West Bromwich Albion Football Club Limited has no responsibility or obligation in respect of the prize draw.</p>
                    <p>2. The Mega Riches Half Time Draw (the “Prize Draw“) is open to people resident in the United Kingdom aged 18 and over who provide their email address, phone number and complete the registration on URL for landing page here from Monday 3rd March 2025.</p>
                    <p>3. Employees or agencies of West Bromwich Albion Football Club Limited (“WBA”) or Immense Group (“Immense Group”) or any of their respective group companies or their family members, or anyone else connected with the Prize Draw may not enter the Prize Draw. People whose Immense Group account is closed, suspended or blocked are not eligible to win the prize.</p>
                    <p>4. Entrants into the Prize Draw shall be deemed to have accepted these Terms and Conditions.</p>
                    <p>5. The Prize Draw is not operated or run by West Bromwich Albion Football Club Limited and West Bromwich Albion Football Club has no responsibility in respect of the Prize Draw.</p>
                    <p>6. By submitting your personal information, you agree to receive emails from Immense Group containing offers and developments that we think may interest you. You will be given the opportunity to unsubscribe on every email that we send.</p>
                    <p>7. To enter the Prize Draw you must complete the form provided and then complete the Mega Riches registration. No purchase is necessary.</p>
                    <p>8. Only one entry per person. Entries on behalf of another person will not be accepted and joint submissions are not allowed. Entrants to Mega Riches’ previous promotion https://www.megariches.com/wba/ will automatically be entered into this competition.</p>
                    <p>9. The prize winner’s name and contact details will be shared with WBA so that the prize winner can be contacted in respect of the prize draw and that the name of the winner will be displayed on the screens at the Stadium.</p>
                    <p>10. Immense Group accepts no responsibility is taken for entries that are lost, delayed, misdirected or incomplete or cannot be delivered or entered for any technical or other reason.</p>
                    <p>11. Each Prize Draw will be closed two days before the draw, at 23:59. Entries received after this time will be automatically entered into the following draw. There will be 6 prize draws in total.</p>
                    <p>12. One winner will be chosen from a random draw of entries received in accordance with these Terms and Conditions. The draw will be performed by a random computer process. The draw will take place on the day before each EFL Championship scheduled game of West Bromwich Albion at the Hawthorns stadium.</p>
                    <p>13. The first draw will take place on Thursday 6th March 2025 ahead of the WBA v QPR match on Saturday 8th March 2025 and the final draw will take place on Thursday 1st May 2025 ahead of WBA v Luton on Saturday 3rd May 2025.</p>
                    <p>14. Winners will receive a West Bromwich Albion adult’s season ticket for season 2025/2026 (Smethwick End or Birmingham Rd). Should the winner already have a season ticket, their season ticket for season 2025/2026 will be paid for by Mega Riches. Mega Riches will be giving away 6 season tickets for WBA for season 2025/2026 in total.</p>
                    <p>15. The winners will be required to verify that they are over 18 years old before collecting their WBA season ticket (for season 2025/2026).</p>
                    <p>16. Immense Group accepts no responsibility for any costs associated with the prize and not specifically included in the prize (including, without limitation, travel to and from the stadium).</p>
                    <p>17. The winner will be notified by email on or before the day of the match during which the winner will be announced. The name will be displayed on the megascreen at the stadium. Accordingly, and as necessary, the winners’ name will be shared with WBA for this purpose, all entrants consent to the same. If a winner does not respond to Immense Group within 14 days of being notified, the winner's prize will be forfeited.</p>
                    <p>18. Each prize winner will be notified by Immense Group, courtesy of an email. The prize itself will be redeemed with West Bromwich Albion Football Club Limited.</p>
                    <p>19. The prize is non-exchangeable, non-transferable, and is not redeemable for cash or other prizes.</p>
                    <p>20. Immense Group retains the right to substitute the prize with another prize of similar value in the event the original prize offered is not available.</p>
                    <p>21. The winner may be required to take part in promotional activity related to the Prize Draw and the winner shall participate in such activity on Immense Group’s reasonable request during the course of the 2024/2025 English Football League season. The winner consents to the use by Immense Group and its related companies, for an unlimited time, of the winner's voice, image, photograph and name for publicity purposes (in any medium, including still photographs and films, and on the internet, including any websites hosted by Immense Group and its related companies) and in advertising, marketing or promotional material without additional compensation or prior notice and, in entering the Prize Draw, all entrants consent to the same.</p>
                    <p>22. Immense Group shall use and take care of any personal information you supply to it as described in its privacy policy, and in accordance with data protection legislation. By entering the Prize Draw, you agree to the collection, retention, usage and distribution of your personal information in order to process and contact you about your Prize Draw entry, and for the purposes outlined in paragraph 13 above.</p>
                    <p>23. Immense Group accepts no responsibility for any damage, loss, liabilities, injury or disappointment incurred or suffered by you as a result of entering the Prize Draw or accepting the prize. Immense Group further disclaims liability for any injury or damage to your or any other person's computer relating to or resulting from participation in or downloading any materials in connection with the Prize Draw. Nothing in these Terms and Conditions shall exclude the liability of Immense Group for death, personal injury, fraud or fraudulent misrepresentation as a result of its negligence.</p>
                    <p>24. Immense Group reserves the right at any time and from time to time to modify or discontinue, temporarily or permanently, this Prize Draw with or without prior notice due to reasons outside its control (including, without limitation, in the case of anticipated, suspected or actual fraud) or the interruption of the sponsorship agreement with West Bromwich Albion. The decision of Immense Group in all matters under its control is final and binding and no correspondence will be entered into.</p>
                    <p>25. Immense Group shall not be liable for any failure to comply with its obligations where the failure is caused by something outside its reasonable control.</p>
                    <p>26. Where the winner is prohibited from attending a WBA match at the Hawthorns by any judgement or order in law, the winner’s prize will be forfeited.</p>
                    <p>27. The Prize Draw will be governed by English and Welsh law and entrants to the Prize Draw submit to the exclusive jurisdiction of the English and Welsh courts.</p>
                    <p>28. Only those who register in accordance with clause 2 above and complete all the required steps will be eligible for and entered into the Prize Draw.</p>
            </div> ",


        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $brandSpecificTranslations = [
            'seasonal.promotion.content.main.html' => '<div class="promotion-partner__content">
                <h3>Win a <b>SWFC</b> Season Ticket for 2025/26 with Mr Vegas</h3>
            </div>',
            'seasonal.promotion.term.condition.html' => " <div class='promotion-partner__term-condition'>
                <h4>Terms and Conditions</h4>
                    <p>1. Promoter: Videoslots Ltd (‘Immense Group’), Telghet Gwardiamangia 105, Tal Pieta, Malta. The prize draw is not being operated or run by Sheffield Wednesday Football Club Limited and that Sheffield Wednesday Football Club Limited has no responsibility or obligation in respect of the prize draw.</p>
                    <p>2. The Mr Vegas Half Time Draw (the “Prize Draw“) is open to people resident in the United Kingdom aged 18 and over who provide their email address, phone number and complete the registration on URL for landing page here from Monday 3rd March 2025.</p>
                    <p>3. Employees or agencies of Sheffield Wednesday Football Club Limited (“SWFC”) or Immense Group (“Immense Group”) or any of their respective group companies or their family members, or anyone else connected with the Prize Draw may not enter the Prize Draw. People whose Immense Group account is closed, suspended or blocked are not eligible to win the prize.</p>
                    <p>4. Entrants into the Prize Draw shall be deemed to have accepted these Terms and Conditions.</p>
                    <p>5. The Prize Draw is not operated or run by Sheffield Wednesday Football Club Limited and Sheffield Wednesday Football Club Limited has no responsibility in respect of the Prize Draw.</p>
                    <p>6. By submitting your personal information, you agree to receive emails from Immense Group containing offers and developments that we think may interest you. You will be given the opportunity to unsubscribe on every email that we send.</p>
                    <p>7. To enter the Prize Draw you must complete the form above and then complete the Mega Riches registration. No purchase is necessary.</p>
                    <p>8. Only one entry per person. Entries on behalf of another person will not be accepted and joint submissions are not allowed. Entrants to Mr Vegas previous promotion www.mrvegas.com/shw will automatically be entered into this competition.</p>
                    <p>9. The prize winner’s name and contact details will be shared with SWFC so that the prize winner can be contacted in respect of the prize draw and that the name of the winner will be displayed on the screens at the Stadium.</p>
                    <p>10. Immense Group accepts no responsibility is taken for entries that are lost, delayed, misdirected or incomplete or cannot be delivered or entered for any technical or other reason.</p>
                    <p>11. Each Prize Draw will be closed two days before the draw, at 23:59. Entries received after this time will be automatically entered into the following draw. There will be 6 prize draws in total.</p>
                    <p>12. One winner will be chosen from a random draw of entries received in accordance with these Terms and Conditions. The draw will be performed by a random computer process. The draw will take place on the day before each EFL Championship scheduled game of Sheffield Wednesday Football Club Limited.</p>
                    <p>13. The first draw will take place on Thursday 16th March 2025 ahead of the Sheffield Wednesday v Sheffield United match on Sunday 19th March 2025 and the final draw will take place on Thursday 1st May ahead of xxx v Sheffield Wednesday on Saturday 3rd May 2025.</p>
                    <p>14. Winners will receive a Sheffield Wednesday Adult’s season ticket adult for season 2025/2026. Should the winner already have a season ticket, their season ticket for season 2025/2026 will be paid for by Mr Vegas. Mr Vegas will be giving away 6 season tickets for SWFC for season 2025 / 2026 in total.</p>
                    <p>15. The winners will be required to verify that they are over 18 years old before collecting their SWFC season ticket (for season 2025/2026).</p>
                    <p>16. Immense Group accepts no responsibility for any costs associated with the prize and not specifically included in the prize (including, without limitation, travel to and from the stadium).</p>
                    <p>17. The winner will be notified by email on or before the day of the match during which the winner will be announced. The name will be displayed on the megascreen at the stadium. Accordingly, and as necessary, the winners’ name will be shared with SWFC for this purpose, all entrants consent to the same. If a winner does not respond to Immense Group within 14 days of being notified, the winner's prize will be forfeited.</p>
                    <p>18. Each prize winner will be notified by Immense Group courtesy of an email. The prize itself will be redeemed with Sheffield Wednesday Football Club Limited.</p>
                    <p>19. The prize is non-exchangeable, non-transferable, and is not redeemable for cash or other prizes.</p>
                    <p>20. Immense Group retains the right to substitute the prize with another prize of similar value in the event the original prize offered is not available.</p>
                    <p>21. The winner may be required to take part in promotional activity related to the Prize Draw and the winner shall participate in such activity on Immense Group’s reasonable request during the course of the 2024/2025 English Football League season. The winner consents to the use by Immense Group and its related companies, for an unlimited time, of the winner's voice, image, photograph and name for publicity purposes (in any medium, including still photographs and films, and on the internet, including any websites hosted by Immense Group and its related companies) and in advertising, marketing or promotional material without additional compensation or prior notice and, in entering the Prize Draw, all entrants consent to the same.</p>
                    <p>22. Immense Group shall use and take care of any personal information you supply to it as described in its privacy policy, and in accordance with data protection legislation. By entering the Prize Draw, you agree to the collection, retention, usage and distribution of your personal information in order to process and contact you about your Prize Draw entry, and for the purposes outlined in paragraph 13 above.</p>
                    <p>23. Immense Group accepts no responsibility for any damage, loss, liabilities, injury or disappointment incurred or suffered by you as a result of entering the Prize Draw or accepting the prize. Immense Group further disclaims liability for any injury or damage to your or any other person's computer relating to or resulting from participation in or downloading any materials in connection with the Prize Draw. Nothing in these Terms and Conditions shall exclude the liability of Immense Group for death, personal injury, fraud or fraudulent misrepresentation as a result of its negligence.</p>
                    <p>24. Immense Group reserves the right at any time and from time to time to modify or discontinue, temporarily or permanently, this Prize Draw with or without prior notice due to reasons outside its control.</p>
                    <p>25. Immense Group shall not be liable for any failure to comply with its obligations where the failure is caused by something outside its reasonable control.</p>
                    <p>26. Where the winner is prohibited from attending a SWFC match at the Hillsborough by any judgement or order in law, the winner’s prize will be forfeited.</p>
                    <p>27. The Prize Draw will be governed by English and Welsh law and entrants to the Prize Draw submit to the exclusive jurisdiction of the English and Welsh courts.</p>
                    <p>28. Only those who register in accordance with clause 2 above and complete all the required steps will be eligible for and entered into the Prize Draw.</p>
            </div> ",
        ];

        // If the brand is 'mrvegas', apply the brand-specific translations
        if ($this->brand === phive('BrandedConfig')::BRAND_MRVEGAS) {
            foreach ($brandSpecificTranslations as $key => $value) {
                $this->data['en'][$key] = $value;
            }
        }
    }

    public function up()
    {
        if (!in_array($this->brand, [phive('BrandedConfig')::BRAND_MRVEGAS, phive('BrandedConfig')::BRAND_MEGARICHES])) {
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
        if (!in_array($this->brand, [phive('BrandedConfig')::BRAND_MRVEGAS, phive('BrandedConfig')::BRAND_MEGARICHES])) {
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

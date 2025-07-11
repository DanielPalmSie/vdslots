<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForKungaslottetLandingPage extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'ks.landing.page.footer.description' => ' <p>The website Kungaslottet.se is operated in Sweden, by Kungaslottet Ltd which is a company based in Malta with registration number C104392 and registered address at 105 Gwaradamangia Hill, Pieta, PTA 1313, Malta. Kungaslottet Ltd is licensed and regulated by the Spelinspektionen (the Swedish Gambling Authority) under the license number, issued on and valid until it. Gambling can be addictive. Play responsibly. If you need support regarding your gambling habits, you can find more information at Stödlinjen.se.</p>.
                <p class="tnc">© Kungaslottet 2023. all rights reserved.</p>',
            'ks.landing.page.footer.icons' => '
            <div id="middle" style=" text-align: center;">
                            <table>
                                <tbody>
                                <tr class="icons-container">
                                    <td class="customer-links"><a href="/customer-service/" target="_blank">
                                            <div class="customer-service-section">
                                                <img src="/diamondbet/images/kungaslottet/mobile/cust-service-color.svg" alt="customer service icon" />
                                            </div>
                                        </a>&nbsp; &nbsp;</td>
                                    <td class="vertical-separator-container"><div class="vertical-separator"></div></td>
                                    <td class="trustly-icon"><a href="https://www.trustly.com/" target="_blank"><img src="/diamondbet/images/kungaslottet/trustly-logo.svg" alt="trustly link"/></a></td>
                                    <td class="logo-icon"><a href="https://www.spelinspektionen.se/" target="_blank"><img src="/file_uploads/Spelinspektionen_logotyp.svg" alt=""/></a></td>
                                    <td class="eighteen-plus-icon"><a href="/responsible-gambling/#underage-gaming"><img title="Underage Gaming" src="/diamondbet/images/kungaslottet/18-plus-icon.png" alt="Underage Gaming" /></a></td>
                                </tr>
                                </tbody>
                            </table>
                    </div>
            ',
            'ks.landing.page.footer.icons.mobile' => '
            <div id="middle" style=" text-align: center;">
                            <table>
                                <tbody>
                                <tr class="icons-container">
                                    <td class="customer-links"><a href="/customer-service/" target="_blank">
                                            <div class="customer-service-section">
                                                <img src="/diamondbet/images/kungaslottet/mobile/cust-service-color.svg" alt="customer service icon" />
                                            </div>
                                        </a>&nbsp; &nbsp;</td>
                                    <td class="vertical-separator-container"><div class="vertical-separator"></div></td>
                                    <td class="trustly-icon"><a href="https://www.trustly.com/" target="_blank"><img src="/diamondbet/images/kungaslottet/trustly-logo.svg" alt="trustly link"/></a></td>
                                    <td class="logo-icon"><a href="https://www.spelinspektionen.se/" target="_blank"><img src="/file_uploads/Spelinspektionen_logotyp.svg" alt=""/></a></td>
                                    <td class="eighteen-plus-icon"><a href="/mobile/responsible-gambling/#underage-gaming"><img title="Underage Gaming" src="/diamondbet/images/kungaslottet/18-plus-icon.png" alt="Underage Gaming" /></a></td>
                                </tr>
                                </tbody>
                            </table>
                    </div>
            ',
            'ks.landing.page.game-cards' => ' <ul>
                        <li><a href="/?show_login=true"><img alt="" src="/diamondbet/images/kungaslottet/game-icon.png" ></a></li>
                        <li><a href="/?show_login=true"><img alt="" src="/diamondbet/images/kungaslottet/game-icon.png" ></a></li>
                        <li><a href="/?show_login=true"><img alt="" src="/diamondbet/images/kungaslottet/game-icon.png" ></a></li>
                        <li><a href="/?show_login=true"><img alt="" src="/diamondbet/images/kungaslottet/game-icon.png" ></a></li>
                        <li><a href="/?show_login=true"><img alt="" src="/diamondbet/images/kungaslottet/game-icon.png" ></a></li>
                    </ul>',
            'ks.landing.page.game-section-title' => 'Our Top Performing Games',

            'ks.landing.page.game-section-offer' => '<div class="top">
                <div class="top-title">
                    <div class="text-gradient">100%</div>
                    <div class="text-desc">UP TO</div>
                    <div class="text-gradient currency-section">
                        <div class="currency-sign">€ </div>
                        <div class="currency-value">200</div>
                    </div>
                </div>
                <div class="top-desc">on your first deposit</div>
                <div class="top-action"><a href="/?show_login=true"><button>Start</button></a></div>
            </div>',
            'ks.landing.page.opening-soon.description' => '<div class="top-game-section opening-soon-section">
                    <div class="opening-soon-container">
                        <div class="opening-soon">
                            Opening Soon!
                        </div>
                        <img src="/diamondbet/images/kungaslottet/underline.svg">
                    </div>
                </div>'

        ]
    ];
}

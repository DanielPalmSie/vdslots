<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class vegaWalletUpdateTranslation extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $data = [
        'en' => [
            'deposit.vega.headline' => 'Vega Wallet',
            'deposit.start.vega.html' => '<p>Deposit with Vega Wallet, your funds are immediately available. Withdrawals are processed within 5 minutes around the clock.</p>',
            'mail.vegapic.picrejected.content'=> '<p><strong>Hello&nbsp;</strong><strong>__FIRSTNAME__</strong><strong>,</strong></p>
                        <p><br /> The security check on your screenshot of your Vega Wallet account was not approved.<br /><br /> Your uploaded files must be in any of the following formats:</p>
                        <ul>
                        <li>File type JPG, PNG, GIF or PDF</li>
                        <li>not larger than 3MB (three megabytes) in size</li>
                        <li>all documents need to have all 4 corners visible</li>
                        </ul>
                        <p>Please upload a new image or contact <a href="mailto:support@videoslots.com">support@videoslots.com</a> in order to conclude the security check.<br /><br /> Kind regards,<br /> Support<br /><a href="/" target="_self">Videoslots.com</a></p>
                        <p><br /> <img style="border: 0px; margin: 0px;" src="{{cdn}}/file_uploads/video-slots.png" alt="" width="220" height="160" /></p>',
            'mail.vegapic.verified.content'=> '<p><strong>Hello __FIRSTNAME__,</strong><br /> <br /> Thank you for concluding the security check at Videoslots.com<br /> <br /> Your account Vega Wallet has now been verified.<br /> <br /> Best regards,<br /> Support<br /> <a href="/" target="_self">Videoslots.com</a></p>
                                                <p><br /> <img style="border: 0px; margin: 0px;" src="{{cdn}}/file_uploads/video-slots.png" alt="" width="220" height="160" /></p>',
            'mail.vegapic.verified.subject' => 'Your document for Vega Wallet has been verified',
            'register.vega.accountid' => 'Vega Account ID',
            'withdraw.vega.headline' => 'Vega Wallet',
            'withdraw.start.vega.html' => '<p>Withdrawals with Vega Wallet are processed within 5 minutes around the clock.</p></p>',
            'vegapic.section.headline' => 'Vega Wallet',
            'vegapic.section.confirm.info' => 'Accepted file is a screenshot of your Vega Wallet account showing your full name and account number / e-mail.',
            'vega.verifyfirst.html'=> '<p>You need to verify your Vega Wallet account by uploading a screenshot of it, press the button below to start the verification process.</p>',
            'vega.password' => 'Vega Wallet password',
            'vega.username' => 'Vega Wallet username',
            'register.vega.amount' => 'Amount in {{ciso}}:',
            'mail.vegapic.picrejected.subject'=> 'Vega Wallet document was not approved',
            'simple.571.html' => '<h1><span style="color: #888888;">Videoslots.com Banking</span></h1>
                        <p style="text-align: justify;"><span style="color: #888888;">Videoslots.com offers several different payment options for online payments. We strive to make it easy for you to both deposit and withdraw money in a secure and easy way. <br /><br />All deposits and withdrawals are made directly on our website. <br /></span></p>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;"><strong><a href="#payment-methods">Payment methods</a></strong></span></li>
                            <ul>
                                <li><span style="color: #888888;"><strong><a href="#free-withdrawals">Free Withdrawals</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#visa-mastercard">Visa and Mastercard</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#applepay">Apple Pay</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#cashtocode">CashtoCode</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#citadel">Citadel - Instant Payments</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#ecopays">ecoPayz</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#euteller">Euteller</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#flexepin">Flexepin</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#giropay">Giropay</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#instadebit">Instadebit</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#interac">Interac e-Transfer</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#interac-online">Interac Online</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#jcb">JCB</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#klarna">Klarna</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#kwickgo">KWICK GO</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#mifinity">MiFinity</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#muchbetter">MuchBetter</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#neosurf">Neosurf</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#neteller">Neteller</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#paypal">PayPal</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#paysafecard">paysafecard</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#rapid-transfer">Rapid Transfer</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#siru-mobile">SIRU Mobile</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#skrill">SKRILL</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#smsvoucher">SMS Voucher</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#Trustly">Trustly</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#venuspoint">Vega Wallet</a></strong></span></li>
                                <li><span style="color: #888888;"><strong><a href="#zimpler">Zimpler</a></strong></span></li>
                            </ul>
                            <li><span style="color: #888888;"><strong><a href="#general">General and Security Information</a></strong></span></li>
                        </ul>
                        <p style="text-align: justify;">&nbsp;</p>
                        <p><strong><span style="color: #888888; text-align: justify;">Please note that customers residing in the UK are no longer able to use credit cards.</span></strong></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="payment-methods"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Payment methods</span></h2>
                        <table border="0">
                            <tbody>
                            <tr>
                                <td width="15%">&nbsp;</td>
                                <td width="70%">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="text-align: justify;"><img src="{{cdn}}/file_uploads/new_visa_mastercard_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Visa / Mastercard</span></strong><br /><span style="color: #888888;">All transactions using Visa and Mastercard are immediate, processed by one of the world leaders in payment processing, named Wirecard.&nbsp;</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/applepay.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Apple Pay</span></strong><br /><span style="color: #888888;">Make instant payments by easily using Apple Pay through the Apple devices (iPhone, Apple Watch, iPad, and Mac) you use every day. Apple Pay is a mobile payment option and digital wallet service by Apple Inc. that allows customers to make purchases in person. It&rsquo;s simple as using your physical card, and safer too.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/cashtocode.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">CashtoCode</span></strong><br /><span style="color: #888888;">Make a secure payment online with cash. Get the paycode and confirm your purchase. Easy, safe and everywhere.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/intsantpayment_big.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Citadel - Instant Payments</span></strong><br /><span style="color: #888888;">Citadel gives you a better payment experience. A full range of products are offered by this payment provider which makes your transactions safe and secure!</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/ecopayz.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">ecoPayz</span></strong><br /><span style="color: #888888;">Freedom to spend your money anytime, anywhere with total peace of mind. The ecoAccount brings you safe and hassle-free ways of receiving, sending, and spending money worldwide.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/euteller_deposit_dc.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Euteller</span></strong><br /><span style="color: #888888;">Instant payments without using your cards. Euteller only works with Finnish bank accounts.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/flexepinl_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Flexepin</span></strong><br /><span style="color: #888888;">Flexepin is a fast, secure, and private method of funding your account by using a prepaid voucher. The vouchers can be purchased at over 1,000 sales outlets.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/GiroPay_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Giropay</span></strong><br /><span style="color: #888888;">Purchase securely online using direct transfers from your bank account.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/instantdebit-deposit.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Instadebit</span></strong><br /><span style="color: #888888;">Make instant payments directly from your bank account with Instadebit, the simple way to deposit and withdraw funds online without sharing your private information with merchants.<br /></span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/Interac_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Interac e-Transfer</span></strong><br /><span style="color: #888888;">Interac is the best payment solution for all Canadian people who choose the security and convenience of this brand for their money transactions.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/Interac-Online__colour_big.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Interac Online</span></strong><br /><span style="color: #888888;">Use Interac Online without sharing your financial information with any third parties. Your payment is immediate, secure and easy to process.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/JCB_131by65.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">JCB</span></strong><br /><span style="color: #888888;">JCB provides a secure, simple, and fast way to pay. JCB has been promoting QUICPay for the Japan market.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/Klarna_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Klarna</span></strong><br /><span style="color: #888888;">Direct Bank transfer - use your online banking login details, both convenient and secure.<br /></span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/kwickgo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">KWICK GO</span></strong><br /><span style="color: #888888;">A real time payment solution to fund your account in a fast and secure way.<br /></span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/mifinity.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">MiFinity</span></strong><br /><span style="color: #888888;">MiFinity is the online payments provider behind the MiFinity eWallet, a means to transfer money to almost anyone in the world via the MiFinity bank network and local payment options. It also issues and manages bespoke virtual card programmes.<br /></span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/muchbetter.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">MuchBetter</span></strong><br /><span style="color: #888888;">Make an instant deposit just using your fingertip. MuchBetter is a new payment app, innovative and modern!</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/neosurf_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Neosurf</span></strong><br /><span style="color: #888888;">With Neosurf, you can purchase a prepaid code with cash and pay online on over 20,000 websites and 135.000 points of sale worldwide.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/neteller_deposit_dc.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Neteller</span></strong><br /><span style="color: #888888;">A secure online money transfer service is one of the leading service providers in the industry. Opening an account is free of charge and takes less than 5 minutes.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/PayPal_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">PayPal</span></strong><br /><span style="color: #888888;">A simpler and safer way to pay and get paid. PayPal is fast, secure while you manage your money with your account.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/paysafecard_deposit.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">paysafecard</span></strong><br /><span style="color: #888888;">paysafecard is the European market leader in online prepaid solutions. Make safe online payments without a bank account or card.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/RapidTransfer_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Rapid Transfer</span></strong><br /><span style="color: #888888;">Pay directly from your bank account without leaving your website, instantly and conveniently.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/sirumobile-deposit.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">SIRU Mobile</span></strong><br /><span style="color: #888888;">SIRU Mobile is a quick and reliable micro-payment service &ndash; which offers players the ability to make deposits through their mobile phones from Finland, Norway, and Sweden.<br /></span></td>
                            </tr>
                            <tr>
                                <td style="text-align: justify;"><img src="{{cdn}}/file_uploads/moneybookers_deposit_dc.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">SKRILL&nbsp;</span></strong><br /><span style="color: #888888;">A secure and popular E-wallet available to customers worldwide with the advantage of making instant deposits and withdrawals in over 30 currencies.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/smsvoucher_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">SMS Voucher</span></strong><br /><span style="color: #888888;">SMS Voucher is a mobile coupon code solution that allows you to top up your account through a secure and easy online payment.</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/trustly_Logo.png" alt="" /></td>
                                <td style="text-align: justify;"><span style="color: #888888;"><strong>Trustly</strong></span><br /><span style="color: #888888;">Deposit or withdraw money directly from your online bank account with Trustly. It is easy, fast and secure!</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/vega.png" alt="" /></td>
                                <td style="text-align: justify;"><span style="color: #888888;"><strong>Vega Wallet</strong></span><br /><span style="color: #888888;">Register your account quickly and convert your points into Japanese Yen. It is easy and secure!</span></td>
                            </tr>
                            <tr>
                                <td><img src="{{cdn}}/file_uploads/Zimpler_Logo2.png" alt="" /></td>
                                <td style="text-align: justify;"><strong><span style="color: #888888;">Zimpler</span></strong><br /><span style="color: #888888;">Simplify your gaming experience with Zimpler; a mobile payment service that allows you to make quicker, more efficient, and secure deposits from the palm of your hand. Anytime, anyplace!<br /></span></td>
                            </tr>
                            </tbody>
                        </table>
                        <p style="text-align: justify;">&nbsp;</p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="free-withdrawals"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Free Withdrawals</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">At <a href="/" target="_self">Videoslots.com</a>, you have one free withdrawal per day with no fee. You can, of course, withdraw more frequent if you would like to. Those withdrawals after the free first one will then come with a&nbsp;charge of&nbsp;{{csym}} {{modm:2.5}}. </span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">We also reserve the right if there is no, or minimal turnover, since the time of the last deposit we might charge a fee of 3.9%.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/bankwire.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="visa-mastercard"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Visa and Mastercard</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Approved transactions are automatically credited to the Player\'s Videoslots account to allow uninterrupted playing. Your card will be billed immediately after purchase. It will appear on your monthly statement as Videoslots Ltd.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">All our deposit and withdrawals with VISA or Mastercard is processed through Wirecard. Wirecard is a card transaction service provider who is a highly trusted, publicly listed payment service provider, traded on the DAX stock exchange. Videoslots.com is also secured with 3D secure.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">We accept Visa Debit, Visa Electron, Maestro and Mastercard for depositing.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Unfortunately, it is not possible to make withdrawals to cards in all countries. We cannot process card withdrawals to the following countries: </span></p>
                        <ul>
                            <li><span style="color: #888888;">Hong Kong</span></li>
                            <li><span style="color: #888888;">India</span></li>
                            <li><span style="color: #888888;">Indonesia</span></li>
                            <li><span style="color: #888888;">Japan</span></li>
                            <li><span style="color: #888888;">Korea</span></li>
                            <li><span style="color: #888888;">Malaysia</span></li>
                            <li><span style="color: #888888;">Singapore</span></li>
                            <li><span style="color: #888888;">UK</span></li>
                            <li><span style="color: #888888;">USA</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">All other countries it is possible to make withdrawals onto the same card that has been used to deposit and subject to our standard anti-fraud and security checks. However, older cards may not have the possibility to receive money, contact your bank for further information</span><span style="color: #888888;">.</span></p>
                        <h3 style="text-align: justify;"><span style="color: #888888;">Industry standard protocol</span></h3>
                        <p style="text-align: justify;"><span style="color: #888888;">Our verification system ensures that no one, other than yourself, can use your card details and deposit on your behalf. All approved deposits are then automatically credited to your Videoslots Account to allow uninterrupted playing. When you wish to withdraw any amount from your Videoslots Account, following the standard industry practice, to protect you against fraud, we will ask you to verify yourself by uploading under Settings and Identification within your Documents section:</span></p>
                        <ul>
                            <li><span style="color: #888888;">A scanned version or photocopy of your card</span></li>
                            <li><span style="color: #888888;">A recent utility bill (matching to your current registered address)&nbsp;</span></li>
                            <li><span style="color: #888888;">A scanned version or photocopy of your driving license, passport or proof of age card</span></li>
                        </ul>
                        <div><span style="color: #888888;">You can find more information about Visa <a href="https://www.visa.co.uk/about-visa/visa-in-europe.html" rel="nofollow" target="_blank">here</a> and Mastercard <a href="https://www.mastercard.us/en-us.html" rel="nofollow" target="_blank">here</a>.</span></div>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/new_visa_mastercard_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="applepay"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Apple Pay</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Apple Pay is a contactless payment technology which allows customers to make payments simply by using their Apple devices. It was designed to move away from physical wallets into a world where your cards can be literally your smartphone or your smartwatch.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">This technology enables you to set up your fingerprint as a way of securing your device and authenticating your transaction request.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Apple Pay is very easy to use:</span></p>
                        <ul>
                            <li><span style="color: #888888;">install the app on your device.</span></li>
                            <li><span style="color: #888888;">enter you card details manually or scan your card with your device camera.</span></li>
                            <li><span style="color: #888888;">enter your name and the CVV associated to the card.</span></li>
                            <li><span style="color: #888888;">activate your card.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want to add funds to your Videoslots account, go to the cashier and choose Apple Pay. Simply by using the Touch ID function your payment will be immediately authorized and approved.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">This payment option is available in the following countries: HR&rsquo;, &lsquo;DK&rsquo;, &lsquo;AX&rsquo;, &lsquo;FI&rsquo;, &lsquo;FO&rsquo;, &lsquo;PF&rsquo;, &lsquo;DE&rsquo;, &lsquo;GI&rsquo;, &lsquo;GG&rsquo;, &lsquo;IM&rsquo;, &lsquo;JE&rsquo;, &lsquo;LU&rsquo;, &lsquo;MT&rsquo;, &lsquo;NL&rsquo;, &lsquo;NZ&rsquo;, &lsquo;SM&rsquo;, &lsquo;SE&rsquo;, &lsquo;CH&rsquo;, &lsquo;GB&rsquo;, &lsquo;VA&rsquo;, &lsquo;CA&rsquo;, &lsquo;LV&rsquo;, &lsquo;EE&rsquo;, &lsquo;GE&rsquo;, &lsquo;HU&rsquo;, &lsquo;CY&rsquo;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want to read more about this payment method, you can click <a href="https://www.apple.com/uk/apple-pay/" rel="nofollow" target="_blank">here</a>.</span>&nbsp;</p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/apple-pay-logo.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="cashtocode"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">CashtoCode</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">CashtoCode is an online platform for instant cash payments. Launched in Germany and Austria, now it is expanding significantly to other European countries.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">You will not need your cards, bank accounts or extra steps like additional registrations. This payment method is extremely convenient and safe for online gaming, since it adheres to very strict anti-money laundering policies.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Use CashtoCode to make your payments online with cash. It&rsquo;s very simple and quick.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">In few steps your payment is immediately credited to your account:</span></p>
                        <ul>
                            <li><span style="color: #888888;">Select CashtoCode at checkout and get the paycode;</span></li>
                            <li><span style="color: #888888;">Download the paycode in your mobile or print it;</span></li>
                            <li><span style="color: #888888;">Go to the nearest CashtoCode outlet and show the paycode;</span></li>
                            <li><span style="color: #888888;">Pay the amount with cash and your online payment is confirmed.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want to read more information about CashtoCode, please click <a href="https://www.cashtocode.de/en/home.html" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/cashtocode.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="citadel"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Citadel - Instant Payments</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Citadel Commerce is one of the most trusted and reliable payment providers offering simple payment solutions giving you an easier and more valuable experience. Since launching in 2000, Instant Payments has continued to grow and enable merchants and customers to use the best online payment technology.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;"> It is very simple to use: </span></p>
                        <ul>
                            <li><span style="color: #888888;">Choose Instant Payments while checking out your payment;</span></li>
                            <li><span style="color: #888888;">Select your bank from the dropdown menu;</span></li>
                            <li><span style="color: #888888;">Log into your bank account, approve the payment and finalise your transaction.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;"> Start using Instant Payments immediately and merchants will receive notification of your successful payments.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;"> Instant Payments&nbsp;by Citadel keeps customers safe by adhering to authentication and privacy protection standards required by supported banks. Your credentials will not be stored in any of Citadel systems which follow the KYC and Anti-Money Laundering requirements. Your transactions can be completed whenever and wherever with the easy and secure desktop and mobile-friendly experience.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;"> For further information about this payment provider click <a href="http://www.citadelcommerce.com/en/#sl_4" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/intsantpayment_big.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="ecopayz"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">ecoPayz</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Freedom to spend your money anytime, anywhere with total peace of mind The ecoAccount brings you so many safe and hassle-free ways of receiving, sending and spending money worldwide. It&rsquo;s free to open an account, and no bank account or credit check is required.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Just click <a href="http://www.ecopayz.com/" rel="nofollow" target="_blank">here</a> to sign up for your free ecoPayz account now.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/ecopayz.png" alt="" width="131" height="65" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="euteller"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Euteller</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Euteller is a safe, quick and easy way to make deposits online. It offers instant bank transfers. To use this method you need a Finnish bank account with the online banking.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Euteller can currently be used with these banks: Nordea, Siirto, Danske Bank, Osuuspankki, S&auml;&auml;st&ouml;pankki, Aktia, POP Pankki, S-Pankki, Handelsbanken and &Aring;landsbanken.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">No cards are required and no registration is needed.&nbsp;</span></p>
                        <h3 style="text-align: justify;"><span style="color: #888888;">How to deposit with Euteller at Videoslots.com:</span></h3>
                        <ul>
                            <li><span style="color: #888888;">Log in, click on "Deposit" and choose a plate.</span></li>
                            <li><span style="color: #888888;">Enter the desired amount and click on "Next".</span></li>
                            <li><span style="color: #888888;">Choose your bank and you will be redirected to the website of your bank, where you have to confirm your deposit.</span></li>
                        </ul>
                        <div>
                            <div><span style="color: #888888;">After confirmation, you will be directed back to Videoslots.com, where your funds will be displayed in your account balance. A&nbsp;</span><span style="color: #888888;">1.95% fee, up to&nbsp;</span><span style="color: #888888;">{{csym}} {{modm: 2.95}},</span><span style="color: #888888;">&nbsp;will be added to the final amount. The deposit will be charged directly from your bank account and will be displayed there as Euteller Oy.</span></div>
                        </div>
                        <p style="text-align: justify;"><span style="color: #888888;">Learn more about Euteller by clicking&nbsp;<a href="https://www.euteller.com/en/" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/euteller_deposit_dc.png" alt="" width="131" height="65" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="flexepin"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Flexepin</span></h2>
                        <p style="text-align: justify;"><span><span><span style="color: #888888;">Flexepin is a fast, secure and private method of funding your account by using a prepaid voucher. Vouchers can be purchased at over 1,000&rsquo;s sales outlets. To locate your nearest outlet or for further information, please visit www.flexepin.com.</span></span></span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Once you have purchased your Flexepin voucher featuring a unique 16-digit code, you can deposit funds into your account. To do this, click on Deposit and select Flexepin from the list of available payment methods. Enter the 16 digit Flexepin code and click Make Deposit. Once complete, the full voucher amount will be instantly credited to your account.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">There are no charges for using this method.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">This payment method does not support Withdrawals.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want tor read more about Flexepin, you can do it <a href="https://www.flexepin.com/" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/flexepinl_Logo.png" alt="" width="131" height="65" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="giropay"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Giropay</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Being the official implementation of German banks, Giropay is a very popular online payment system which allows more than 40 million shoppers to make transactions online.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">It is very simple to use:</span></p>
                        <ul>
                            <li><span style="color: #888888;">Enter your bank details;</span></li>
                            <li><span style="color: #888888;">Enter the generated code;</span></li>
                            <li><span style="color: #888888;">Confirm your purchase;</span></li>
                        </ul>
                        <div><span style="color: #888888;">Giropay is a reliable payment method which uses your banking details and a TAN (Transaction Authentication Number) to finalise your purchase.</span></div>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want tor read more about Giropay, you can do it <a href="https://www.giropay.de/" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/GiroPay_Logo.png" alt="" width="131" height="65" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="instadebit"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Instadebit<br /></span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Make instant payments directly from your bank account with INSTADEBIT, the simple way to deposit and withdraw funds online without sharing your private information with merchants. </span></p>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;">Sign up for free and transact at the same time &ndash; no waiting for activation or approval.</span></li>
                            <li><span style="color: #888888;">Use INSTADEBIT anytime, anywhere with mobile-friendly payment pages.</span></li>
                            <li><span style="color: #888888;">Get immediate access to your funds.</span></li>
                            <li><span style="color: #888888;">INSTADEBIT supports every bank and financial institution in Canada.</span></li>
                            <li><span style="color: #888888;">Withdraw winnings directly to your bank account or redeposit to merchants from your INSTADEBIT account at a later date.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">Learn more about Instadebit by clicking <a href="http://www.instadebit.com" rel="nofollow" target="_blank">here</a>.&nbsp;</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/instantdebit-deposit.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="interac"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Interac e-Transfer<br /></span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Interac is a secure and convenient platform for money transfers, with an average daily usage of 16 million in Canada.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">This payment provider is constantly working to innovate and evolve across multiple devices and platforms. Interac offers world class security, strong consumer protections, zero liability, and leading fraud detection systems; as well as simple and fast solutions that allow you to pay by using your own money.</span><span style="color: #888888;">&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you wish to read more about Interac, you can do it <a href="https://www.interac.ca/en/interac-e-transfer-consumer.html" rel="nofollow" target="_blank">here</a>.</span><span style="color: #888888;">&nbsp;</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/Interac_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="interac-online"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Interac Online<br /></span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Offered by hundreds of merchants for online payments, Interac Online allows you to pay for your purchases easily, directly from your bank account.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Interac Online is an immediate, secure, and easy-to-use online payment method. All you need is access to online banking through a Participating Financial Institution. All the transactions are conducted through the convenience of your existing online banking access. There will not be the need to create a new account, user name or password.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you wish to read more about Interac Online, you can do it <a href="https://www.interac.ca/en/interac-online-consumer.html" rel="nofollow" target="_blank">here</a>.</span><span style="color: #888888;">&nbsp;</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/Interac-Online__colour_big.png" alt="" /></p>
                        <p style="text-align: justify;"><a name="jcb"></a></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">JCB</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">JCB International Card Co. Ltd is the only international payment provider developed in Japan. They have been leading the Japanese card industry since 1961, concentrating on providing value to the international customer.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Based on EMV(R) Contactless Communication Protocols, the global standard for contactless payment, JCB makes your online transactions easy and fast. JCB also offers a high level of security, due to its global chip standard EMV(R).</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">The number of merchants supporting JCB Contactless is expanding in various regions and countries throughout the world.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Depositing with JCB is very simple: go to the cashier and choose this method. You will be able to process your payment after specifying how much you would like to credit into your account. After that, you will need to input your name and card number, the expiration date, and typically the CVV verification code that appears on the reverse side.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Click <a href="https://www.global.jcb/en/index.html" rel="nofollow" target="_blank">here</a> to read more about this payment provider.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/JCB_131by65.png" alt="" /></p>
                        <p style="text-align: justify;"><a name="klarna"></a></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Klarna</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Klarna works much like a bank wire transfer or Trustly. With Klarna&nbsp;online payments are easy. You can use your own online banking login details &ndash; convenient and secure.</span></p>
                        <p style="text-align: justify;"><span><span style="color: #888888;"><strong>Firstly</strong> you select your country and with the help of the bank&rsquo;s sort code, choose the bank that will carry out the transfer.</span></span></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><strong>Secondly</strong>, you\'ll see the login section of Klarna&acute;s secure payment form. Login with your own online banking login details. &nbsp;This information will be sent to your bank in an encrypted form.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><strong>Last but not least</strong>, you will be asked for a verification code. Once the transfer has been authorised, it&rsquo;s complete.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">With Klarna, you can deposit money directly from your online bank account. So complex yet so easy and swift, no registration or software installation is required.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Klarna Banking is an online direct payment method and works by tried-and-tested online banking. The big advantage is: You don\'t need to register or open a virtual account, known as a wallet. It is an immediate and direct transfer of funds.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Read more about Klarna <a href="https://www.klarna.com/international/" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/Klarna_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><a name="kwickgo"></a></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">KWICK GO</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">KWICK GO is a real time payment option provided by EMP Corp to online web merchants.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">The use is very simple! When making an online payment on a merchant website, you actually purchase e-vouchers with your&nbsp; cards. After that, you can redeem your vouchers, which can have a value between&nbsp;</span><span style="color: #888888;">{{csym}} {{modm: 0.01}} and&nbsp;</span><span style="color: #888888;">{{csym}} {{modm: 250}}, in order for EMP Corp to make the payment to the partner owning the merchant website.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Making a deposit at Videoslots.com is very easy and secure:</span></p>
                        <ul>
                            <li><span style="color: #888888;">Enter your cards information and s</span><span style="color: #888888;">elect KWICK GO;</span></li>
                            <li><span style="color: #888888;">Select the amount of the e-voucher you want to purchaase;</span></li>
                            <li><span style="color: #888888;">Being redirected to the website hosted by EMP Corp, you will be able to complete your transaction.&nbsp;</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">Read more about EMP Corp&nbsp;<a href="https://www.empcorp.com/" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/kwickgo.png" alt="" /></p>
                        <p style="text-align: justify;"><a name="mifinity"></a></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">MiFinity</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">MiniFinity provides the MiFinity eWallet, a fast, simple and secure way for customers to perform transactions. It offers multiple local payment options that support deposit, withdrawal and transfer functionality in different currencies. This eWallet does not require you to share your bank account details, and it supports money transfers to almost anywhere in the world. Transfers and payments are instant and available in multiple currencies. ;</span><span style="color: #888888;">quicker, more efficient and secure deposits from the palm of your hand. Anytime, anyplace!</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Once you create a MiFinity eWallet using the MiFinity Platform website, making a deposit on Videoslots Casino is very easy: </span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want to read more about MiFinity, click <a href="https://www.mifinity.com/" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/mifinity.png" alt="" /></p>
                        <p style="text-align: justify;"><a name="muchbetter"></a></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">MuchBetter</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">MuchBetter is a modern payment app which includes features such as dynamic customer authentication and innovative pricing. You can select it to deposit and cash out at Videoslots. No more redirects, personal details sharing and extra devices to carry &ndash; simply confirm your transaction from your smartphone and enjoy.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Mobile Payment Solution of the Year award winner in 2019, MuchBetter is also an ultra-secure method, thanks to the dynamic CVV which eliminates online fraud.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">It is very easy to use, just download the app from your mobile store and follow a quick registration process.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">At the Videoslots cashier choose MuchBetter, select your mobile number and confirm your deposit. A notification will be sent to your app and simply with your fingertip you will be able to proceed and finalize the operation.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Click <a href="https://muchbetter.com/en/" rel="nofollow" target="_blank">here</a> to read more about this payment method.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/muchbetter.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="neosurf"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Neosurf</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Neosurf is an online payment method where you can purchase a prepaid code with cash and pay online on more than 20,000 websites and 135.000 points of sale worldwide. The process is both fast and simple.&nbsp;</span></p>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;">Step 1; Find a shop in the location finder <a href="http://www.neosurf.com/en_GB/application/findcard" target="_blank">here</a><span style="color: #888888;">&nbsp;to see where you can buy your Neosurf voucher. Simply type in where you are, and all locations available will be displayed. &nbsp;</span></span></li>
                            <li><span style="color: #888888;">Step 2; Simply ask for a Neosurf cash voucher in the shop for a worth of your choice. No registration or further details are required. Pay with cash and receive a 16-character voucher code which you need to use to be able to make your payment online.&nbsp;</span></li>
                            <li><span style="color: #888888;">Step 3; Go to your favourite website and simply choose to pay via Neosurf. You need to enter the code on the receipt and the deposit is then in place.&nbsp;</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">Neosurf does not use any personal information, there is no need to register beforehand, and your voucher is always ready to be used the moment after purchase.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want to read more information about this payment option, you can do it&nbsp;<a href="https://www.neosurf.com/en_GB" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/neosurf_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="neteller"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Neteller</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Neteller provides an efficient and secure method to make online transactions. It offers same-day payments and virtually instant cash transfers.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">After you open your free Neteller account, you can use it to make direct deposits at Videoslots.com and many other online businesses. <br /><br />Additional Neteller account benefits are:</span></p>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;">Neteller keeps your card details and payment history confidential.</span></li>
                            <li><span style="color: #888888;">You can fund your Neteller account via Visa, Mastercard, Instacash, EFT, F-Cash and Bank Deposit</span></li>
                            <li><span style="color: #888888;">You can access your account balance and transaction history anytime, anywhere, via the Neteller website.</span></li>
                        </ul>
                        <h3 style="text-align: justify;"><span style="color: #888888;">InstaCash</span></h3>
                        <p style="text-align: justify;"><span style="color: #888888;">Sign up for a bank account with Neteller, and you will get instant access to funds in your bank account which will enable you to apply for InstaCash Deposits. For further details, please log into your Neteller account, and click on the InstaCash Logo.</span></p>
                        <h3 style="text-align: justify;"><span style="color: #888888;">Just follow these easy steps to transfer funds with InstaCash:</span></h3>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;"><strong>Register your Bank Account:</strong><br />The relevant information required: bank name, bank phone number, bank routeing number and bank account number.</span></li>
                            <li><span style="color: #888888;"><strong>Verify your identity:</strong><br />To confirm your identity, you will be asked to confirm three facts about your financial history. Once your identity has been verified, this information will be destroyed. Neteller does not keep, retain or file this information. You will now qualify for a {{csym}} {{modm:750}}deposit limit through Neteller. Neteller will make a \'micro deposit\' to your bank account for you to verify. If you confirm this amount correctly, you can increase your deposit limit.</span></li>
                        </ul>
                        <h3 style="text-align: justify;"><span style="color: #888888;">Here\'s how to deposit credits at Videoslots using NETeller:</span></h3>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;">Once you\'ve signed-up at the Neteller website, you\'ll receive a unique personal Account ID and Secure ID.</span></li>
                            <li><span style="color: #888888;">Log in and choose the Deposit tab on the main menu and select Neteller.</span></li>
                            <li><span style="color: #888888;">Enter your Neteller Account ID, Secure ID and the amount you wish to deposit. To make sure you will benefit from the quick and easy InstaCash process don\'t forget to enter the last 4 digits of the registered bank account in the required field to initiate your InstaCash transaction. Otherwise, your transaction will go through as a normal Neteller funding request. Now hit "Next".</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">That\'s it - your deposit should be instantly credited to your main account.</span></p>
                        <h3 style="text-align: justify;"><span style="color: #888888;">Bank Deposits</span></h3>
                        <p style="text-align: justify;"><span style="color: #888888;">Bank deposits to Neteller accounts can be made through any bank. Please notify Neteller by fax with the deposit slip once you made your deposit. The funds will be deposited into your Neteller account as soon as Neteller gets confirmation from the bank. <br /><br />Just click <a href="http://www.neteller.com/" rel="nofollow" target="_blank">here</a> to sign up for your free Neteller account now. It\'s quick, easy and confidential</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/neteller_deposit_dc.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="paypal"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">PayPal</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Sign up and get started. PayPal is a safer and secure payment method which connects everyday hundreds of millions of people around the world, spending, sending and receiving money.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">It is simple and convenient: you can pay online skip entering your financial info and with just one account, you can shop at millions of websites worldwide. Opening a new account is free and there are no PayPal fees if you use your balance, bank account or debit card.</span></p>
                        <h3 style="text-align: justify;"><span style="color: #888888;">How does it work?</span></h3>
                        <p style="text-align: justify;"><span style="color: #888888;">Sign up quickly with few personal details, add your bank account, debit or card, and checkout your shopping with your PayPal credentials. </span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">You can also easily send money to your friend: enter your email address and the amount, select the currency and send your money more securely. </span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Download the PayPal app from the store of your smartphone and manage your account simply from home. </span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want to know more about PayPal, you can visit the website <a href="https://www.paypal.com/uk/home" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/PayPal_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="paysafecard"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">paysafecard<br /></span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">paysafecard is the European market leader in online prepaid solutions. Prepaid means that you buy paysafecard from one of 450.000 sales outlets available and use it to make safe online payments without a bank account or card at 4.000 participating online web shops. paysafecard is a popular payment method with service providers in the fields of gaming, social media &amp; online communities, music, film &amp; entertainment and more.</span></p>
                        <h3 style="text-align: justify;"><span style="color: #888888;">paysafecard is simple to use:</span></h3>
                        <ul>
                            <li><span style="color: #888888;">Buy paysafecard from a local sales outlet.&nbsp;</span></li>
                            <li><span style="color: #888888;">When you buy paysafecard, you can choose between the following amounts: {{csym}} {{modm:10}}, {{csym}} {{modm:25}}, {{csym}} {{modm:50}}&nbsp;or {{csym}} {{modm:100}}.</span></li>
                            <li><span style="color: #888888;">Pay with paysafecard at web shops by simply entering the 16-digit paysafecard PIN.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">You can make larger payments by combining up to 10 paysafecard PINs. Combining PINs also allows you to use up any remaining credit on a PIN.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Get paysafecard&nbsp;<a href="https://www.paysafecard.com/en-gb/" rel="nofollow" target="_blank">here</a>!</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/paysafecard_deposit.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="rapid-transfer"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Rapid Transfer<br /></span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Rapid Transfer is an instant online payment method that currently supports over 150 banks and reaches more than 200 million European consumers. It is simple, familiar and quick to use!</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;"> Simply login and authorise your payment directly through your online bank account.&nbsp;</span><span style="color: #888888;">During checkout, the interface will connect you with your bank.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">This payment option is highly secure and convenient. It helps increasing customer conversions and it doesn\'t require additional costs.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Read more about Rapid Transfer&nbsp;<a href="https://www.skrill.com/en/business/rapid-transfer/" rel="nofollow" target="_blank">here</a>!</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/RapidTransfer_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><a name="siru-mobile"></a></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">SIRU Mobile</span></h2>
                        <h3 style="text-align: justify;"><span style="color: #888888;">Make a quick deposit via your mobile phone!</span></h3>
                        <p style="text-align: justify;"><span style="color: #888888;">Siru Mobile is a fast and reliable micro-payment service &ndash; which offers players the ability to pay for their purchases and make deposits through their mobile phones. You don&rsquo;t need a card or bank account. All purchases will be added to your monthly phone bill.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Through Siru\'s Mobile Payment Service it is possible to make mobile purchases for a value of 5 - 30 &euro; and 50 &ndash; 200 SEK/NOK. The service can be used with all Nordic operators with no inhibiting operator-specific service numbers. Players can use the <strong>SIRU </strong>Mobile Payment Services in the same way as any bank payment options.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Players have the possibility to make purchases for a value of 300 &euro;/ 30 days /subscription. You can also check your balance easily by visiting&nbsp;<a href="https://payment.sirumobile.com/mysiru/en/login" rel="nofollow" target="_blank">this page</a></span><span style="color: #888888;">.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Siru Mobile has Customer Support in three different languages, including English, Finnish, and Swedish.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Siru Mobile has a Customer Call Centre as well, these are the numbers to call:</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Suomi/Finland: 0600 17478 (1 &euro;/min + pvm/mpm)<br />Sverige/Sweden: +46 (0) 8 559 24 6830</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Read more about Siru Mobile&nbsp;<a href="http://sirumobile.com/" rel="nofollow" target="_blank">here</a>.&nbsp;</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/sirumobile-deposit.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="skrill"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">SKRILL</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">SKRILL is a secure and convenient method to transfer cash online. It provides real-time instant payments supported in over 20 currencies.<br /><br />After opening your free SKRILL account, it can be used to make direct deposits at Videoslots.com and many other online businesses.<br /><br />Setting up your account on SKRILL is easy. Only a valid email address is needed for registration. Simply submit the completed online registration form.</span></p>
                        <h3 style="text-align: justify;"><span style="text-decoration: underline;"><span style="color: #888888; text-decoration: underline;"><br /></span></span><span style="color: #888888;">Additional SKRILL account benefits are:</span></h3>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;">With SKRILL you will no longer need to expose your card information every time you shop online. SKRILL can process the payments for you securely.</span></li>
                            <li><span style="color: #888888;">All you need to send a payment is the recipient\'s email address. This eliminates the hassle of dealing with complicated bank wire forms and the inconvenience of sending and depositing cheques!</span></li>
                            <li><span style="color: #888888;">You can use your SKRILL account to withdraw funds via Bank Transfer, Card, E-Gold, and Check.</span></li>
                            <li><span style="color: #888888;">You can access your account anytime, anywhere, via the SKRILL website.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">Sign up for your free SKRILL account now. It\'s quick, easy and confidential!</span></p>
                        <h3 style="text-align: justify;"><span style="color: #888888;">Here\'s how to fund your main account at Videoslots using SKRILL:</span></h3>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;">Once you\'ve signed-up at the SKRILL website, all you\'ll need is your username (or email address) and password to make transactions.</span></li>
                            <li><span style="color: #888888;">Log in to the casino and go to the banking section. Select SKRILL, and enter your SKRILL username and password, then hit "Next".</span></li>
                            <li><span style="color: #888888;">Enter the amount you wish to deposit and that\'s it - your deposit will be instantly reflected in your casino account.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">Now click <a href="https://www.skrill.com/" rel="nofollow" target="_blank">here</a> to open your free SKRILL account.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/moneybookers_deposit_dc.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="smsvoucher"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">SMS Voucher</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Top up your account with a mobile coupon code with SMS Voucher and make your payments online fast and secure. By using SMS Voucher, the risk of having your identity or bank information disclosed is eliminated. This makes it more secure than other conventional online payment methods.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Click <a href="http://smsvoucher.se/" rel="nofollow" target="_blank">here</a> to read more information about this payment option.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/smsvoucher_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="Trustly"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Trustly</span></h2>
                        <h3 style="text-align: justify;"><span style="color: #888888;">Direct Bank e-Payments</span></h3>
                        <p style="text-align: justify;"><span style="color: #888888;">Trustly is a fast and secure payment method, free of charge. With Trustly you can deposit or withdraw money directly from your online bank account. It is easy, no sign-up or software installation is required. All you need is your regular online bank credentials.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">For more detailed information click&nbsp;</span><a href="https://trustly.net/" rel="nofollow" target="_blank">here</a><span style="color: #888888;">.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/trustly_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="venuspoint"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Vega Wallet</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Vega Wallet is payment method which allows you to send, receive and withdraw funds by exchanging points. In few minutes you can register your account and make your transactions immediately. Convert your points into Japanese Yen and send the money to your account with a bank transfer.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">For more information about this payment option click&nbsp;<a href="https://vega-wallet.com/index.html?lang=en" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/vega_Logo.png" alt="" /></p>
                        <p style="text-align: justify;"><span style="color: #888888;"><a name="zimpler"></a></span></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Zimpler</span></h2>
                        <h3 style="text-align: justify;"><span style="color: #888888;">Mobile payments made simple</span></h3>
                        <p style="text-align: justify;"><span style="color: #888888;">Simplify your gaming experience with Zimpler; a mobile payment service that allows you to make&nbsp;</span><span style="color: #888888;">quicker, more efficient and secure deposits from the palm of your hand. Anytime, anyplace!</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Just add your mobile number and follow the instructions.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you want to read more about Zimpler, click <a href="https://www.zimpler.com/" rel="nofollow" target="_blank">here</a>.</span></p>
                        <p style="text-align: justify;"><img src="{{cdn}}/file_uploads/Zimpler_Logo2.png" alt="" width="131" height="65" /></p>
                        <p style="text-align: justify;"><a name="general"></a></p>
                        <hr />
                        <h2 style="text-align: justify;"><span style="color: #888888;">Make secure deposits and withdrawals at Videoslots.com</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Its easy and secure to make a deposit at Videoslots.com.</span><br /><img style="float: right; margin: 10px;" src="{{cdn}}/file_uploads/img_tech-support.png" alt="" width="250" height="167" />&nbsp;</p>
                        <p style="text-align: justify;"><img style="float: left;" src="{{cdn}}/file_uploads/Secure-your-Business.png" alt="" width="167" height="167" /></p>
                        <ul style="text-align: justify;">
                            <li><span style="color: #888888;">We provide the same levels of security as your bank when transferring your funds.</span><br />&nbsp;</li>
                            <li><span style="color: #888888;">Encryption: As soon as you have logged in on Videoslots.com, all information sent from<br />our website is encrypted with 256-bit Secure Socket Layer (SSL). <br />VeriSign verifies the SSL certificate we use, meaning that a third party can read no information sent from our site.</span><br />&nbsp;</li>
                            <li><span style="color: #888888;">All deposits are instant, and your funds will be available in your Videoslots account <br />in real time.</span><br />&nbsp;</li>
                            <li><span style="color: #888888;">24-hour support every day, seven days a week, 24 hours a day.</span><br />&nbsp;</li>
                        </ul>
                        <h2 style="text-align: justify;"><span style="color: #888888;">General</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Card number and other classified information will be sent over a secured, encrypted connection. Details are fully encrypted by 256-bit SSL encryption, for total security. When data is transferred over the Internet, our e-commerce partner and we use 256-Bit Secure Socket Layer Encryption Technology to ensure the security of all data. This is the encryption technique of choice globally for leading financial institutions.&nbsp;<br /><br />Risk Sentinel, an advanced fraud-management software that protects Videoslots Players from online fraud, has been implemented to provide you with ongoing protection.<br /><br />Transaction processing is handled by Videoslots Ltd of The Space Level 2 &amp; 3, Alfred Craig Street, Pieta, PTA 1320 Malta (<a href="/" target="_self">www.videoslots.com</a>). Videoslots Ltd is a company incorporated under the laws of Malta and provides the services on the casino website <a href="/" target="_self">www.videoslots.com</a> in cooperation with its subsidiaries and partners. All references to the company and brand name Videoslots on this site are also to the benefit of subsidiaries and partners of Videoslots Ltd. <br /><br />The information provided will be treated as strictly confidential and will not be sold to others.<br /><br />If you are totally dissatisfied with the service you have received, we will consider your case and refund your purchase so long as your reason is valid. To apply for a refund e-mail us on&nbsp;<a href="mailto:support@videoslots.com" target="_self">support@videoslots.com</a><br /><br />It is recommended you only participate in any gaming events if it is legal for you to do so according to the laws that apply in the jurisdiction from where you are connecting or calling. You must understand and accept that we are unable to provide you with any legal advice or assurances.&nbsp;Furthermore, it is strongly recommended that you consult the Terms and Conditions in order to learn upon the availability and/or limitation of payment solutions available to you in accordance with rules and regulations enacted and applicable to your jurisdiction of residence.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">For more information, also see <a href="/responsible-gambling/">Responsible Gaming</a> and <a href="/terms-and-conditions/">Terms and Conditions</a>.</span></p>',
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
<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsForPrivacyPolicyPage extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'on',
            'alias' => 'simple.1261.html',
            'value' => '<h1 style="text-align: justify;">Kungaslottet Privacy Policy</h1>
                        <p style="text-align: left;"><span style="text-align: justify;">http://dbet.ca/ (hereinafter the &ldquo;Website&rdquo;), is operated by:</span></p>
                        <p style="text-align: left;"><span>Kungaslottet Limited (&ldquo;Kungaslottet Ltd&rdquo;), a private limited liability company constituted under the laws of Malta and has its registered office at The Space, Level 2 &amp; 3, Alfred Craig Street, Pieta PTA 1320, Malta; or in accordance with the relevant license(s) and jurisdictions where it is licensed to operate.</span></p>
                        <p style="text-align: left;"><span>Any reference to &ldquo;We&rdquo;, &ldquo;Our&rdquo;, &ldquo;Us&rdquo; or the &ldquo;Company&rdquo; in this Privacy Policy shall be construed as reference to Kungaslottet Ltd&rsquo;s operations of Kungaslottet and/or the Website (hereinafter the &ldquo;Services&rdquo;). Any reference to &ldquo;You&rdquo; or &ldquo;Your&rdquo; in this Privacy Policy shall be construed as reference to Our players and customers.&nbsp;</span></p>
                        <p style="text-align: left;"><span>This Privacy Policy (together with Our terms and conditions and Our cookie policy) sets the standard for how Kungaslottet Ltd collects, stores and uses Your personal data when You visit Our Website, as well as what Your rights are, how the law protects these rights, and how You can exercise them (hereinafter the &ldquo;Privacy Policy&rdquo;).</span></p>
                        <p style="text-align: left;"><span>The Company values Your integrity and privacy immensely and is committed to processing all of Your personal data transparently, fairly and lawfully.</span></p>
                        <p style="text-align: left;"><span>The Services are being offered by Kungaslottet as part of the open and regulated iGaming market conducted and managed by iGaming Ontario <a href="http://www.igamingontario.ca/en">(http://www.igamingontario.ca/en)</a>. In the course of providing the Services;&nbsp;</span></p>
                        <p style="padding-left: 30px; text-align: left;"><span>(i)Kungaslottet collects, uses and discloses personal information on behalf of, and as agent to, iGaming Ontario;&nbsp;</span></p>
                        <p style="padding-left: 30px; text-align: left;"><span>(ii)Kungaslottet collects personal information in accordance with the Freedom of Information and Protection of Privacy Act (FIPPA) and The Protection of Privacy and Electronic Documents Act (PIPEDA), and under the authority of Ontario Regulation 722/21 made under the Alcohol and Gaming Commission of Ontario (AGCO) Act, and the operating agreement entered into between Kungaslottet and iGaming Ontario;</span>&nbsp;</p>
                        <p style="padding-left: 30px; text-align: left;"><span>(iii)Kungaslottet also processes, handles and stores Your personal information in line with the AGCO&rsquo;s Registrar Standards for Internet Gaming.</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        <table border="1" cellspacing="0" cellpadding="0">
                        <tbody>
                        <tr>
                        <td style="text-align: center;" valign="top" width="200">
                        <p><strong><span>Valid from Date</span></strong></p>
                        </td>
                        <td style="text-align: center;" valign="top" width="200">
                        <p><strong><span>Description</span></strong></p>
                        </td>
                        <td valign="top" width="200">
                        <p style="text-align: center;"><span><strong>Version</strong></span></p>
                        </td>
                        </tr>
                        <tr>
                        <td style="text-align: center;" valign="top" width="200">
                        <p><span>16.12.2022</span></p>
                        </td>
                        <td style="text-align: center;" valign="top" width="200">
                        <p><span>Creation of the document</span></p>
                        </td>
                        <td valign="top" width="200">
                        <p style="text-align: center;"><span>1.0</span></p>
                        </td>
                        </tr>
                        </tbody>
                        </table>
                        <div style="text-align: justify;">&nbsp;</div>
                        <p style="text-align: justify;"><strong><span><a href="#about-us">1. About Us</a></span></strong></p>
                        <p style="text-align: justify;"><strong><span><a href="#your-data">2. Your Data</a></span></strong></p>
                        <p style="text-align: justify;"><strong><span><a href="#sharing-of-data">3. Sharing of Data</a></span></strong></p>
                        <p style="text-align: justify;"><strong><span><a href="#joint-controllers">4.&nbsp;</a></span></strong><strong><span><a href="#transfers-of-personal-data">Transfers of Personal Data</a></span></strong></p>
                        <p style="text-align: justify;"><strong><span><a href="#automated-decision-making">5. Automated Decision Making</a></span></strong></p>
                        <p style="text-align: justify;"><strong><span><a href="#technical-and-organisational-measures">6. Technical and Organisational Measures</a></span></strong></p>
                        <p style="text-align: justify;"><strong><span><a href="#data-retention">7. Data Retention</a></span></strong></p>
                        <p style="text-align: justify;"><strong><span><a href="#your-rights">8. Your Rights</a></span></strong></p>
                        <p style="text-align: justify;"><strong>&nbsp;</strong></p>
                        <hr />
                        <p style="text-align: justify;"><span>&nbsp;</span></p>
                        <p id="about-us" style="text-align: justify;"><strong><span>1. ABOUT US</span></strong></p>
                        <p style="text-align: justify;"><strong><span>(a)&nbsp;</span></strong><span><strong>Purpose of Policy</strong></span></p>
                        <p style="text-align: justify;"><span>This Privacy Policy aims to provide You with a thorough understanding of how We process Your personal data collected through Your use of this Website, and also includes all data You may have provided in connection with Your registration and through Your use of Our online casino.</span></p>
                        <p style="text-align: justify;"><span>This Website is intended solely for persons over 19 years of age and we do not knowingly collect data related to persons under this age. If it becomes clear to us that We have collected personal data related to persons under the age of 19, for reasons related to the misuse of Our Website, We will do Our utmost to ensure that such data is processed in accordance with applicable law and Our policies and procedures.&nbsp;</span></p>
                        <p style="text-align: justify;"><span>Unless otherwise stated in this Privacy Policy, the applicable terms herein shall have the same meaning as in the terms and conditions.</span></p>
                        <p style="text-align: justify;"><span>This Privacy Policy must be read in conjunction with any other confidentiality information We may have given You from time to time. This Privacy Policy is complementary to other such information and is not intended as a substitute for it.</span></p>
                        <p style="text-align: justify;"><span><strong>(b)&nbsp;</strong></span><span><strong>Information, Queries &amp; Complaints</strong></span></p>
                        <p style="text-align: justify;"><span>You are responsible for providing personal data that is correct and informing Us in writing of any changes that may occur, so that we can use all reasonable means to ensure Your personal information is correct and up to date. In addition, We will implement data accuracy checks in accordance with the applicable data protection laws and may ask You to verify Your data we hold on You from time to time.</span></p>
                        <p style="text-align: justify;"><span>As We take Your privacy seriously, We have appointed a Privacy officer (&ldquo;PO&rdquo;), whose responsibility is to oversee that the Company:</span></p>
                        <p style="text-align: justify; padding-left: 30px;"><span>(i)acts in accordance with its legal obligations and&nbsp;</span></p>
                        <p style="text-align: justify; padding-left: 30px;"><span>(ii)is processing Your personal data in compliance with applicable rules and regulations.&nbsp;</span></p>
                        <p style="text-align: justify;"><span>The PO is Your contact person regarding any questions or complaints you may have relating to this Privacy Policy or Your data. Should You have any queries, complaints or require further information regarding this policy, please contact Our PO using the information below.</span></p>
                        <p style="text-align: justify;"><span>If you are using the services provided by Kungaslottet Sports Ltd in accordance with the relevant license(s) as provided </span><a href="/terms-and-conditions/">here</a><span>, the following shall apply:</span></p>
                        <table border="1" cellspacing="0" cellpadding="0">
                        <tbody>
                        <tr>
                        <td>
                        <p style="text-align: justify;"><span><strong>Full Name of Legal Entity</strong></span></p>
                        </td>
                        <td>
                        <p style="text-align: justify;"><span>Kungaslottet Sports Limited (C93953)</span></p>
                        </td>
                        </tr>
                        <tr>
                        <td>
                        <p style="text-align: justify;"><span><strong>Mailing address</strong></span></p>
                        </td>
                        <td>
                        <p style="text-align: justify;"><span>The Space, Level 2 &amp; 3, Alfred Craig Street, Pieta, PTA 1320 Malta</span></p>
                        </td>
                        </tr>
                        <tr>
                        <td>
                        <p style="text-align: justify;"><span><strong>Email address</strong></span></p>
                        </td>
                        <td>
                        <p style="text-align: justify;"><span><a href="mailto:dpo@dbet.com">dpo.sportsbook@dbet.com</a></span></p>
                        </td>
                        </tr>
                        </tbody>
                        </table>
                        <p style="text-align: justify;"><span>You have the right, at any time, to lodge a complaint with the respective data protection authority in Ontario, Canada listed hereunder;&nbsp;</span></p>
                        <p style="text-align: justify;"><span>In the event you exhaust our complaints process without what you believe to be a satisfactory resolution, you may request a copy of your case file, which we shall provide, in order to escalate the complaint to iGO. We will aid iGO in the investigation and handling of your complaint. You may also escalate any complaints or enquiries to iGaming Ontario:&nbsp;</span></p>
                        <p style="text-align: justify;"><span><a href="https://www.igamingontario.ca/en/player/player-support" target="_blank">https://www.igamingontario.ca/en/player/player-support.</a></span></p>
                        <p style="text-align: justify;"><span>We would really appreciate the opportunity to resolve Your issues before contacting the respective authority, so please contact us first.</span></p>
                        <p id="your-data" style="text-align: justify;"><strong><span>2.&nbsp;</span></strong><span><strong>YOUR DATA</strong></span></p>
                        <p style="text-align: justify;"><span><strong>(a)&nbsp;&nbsp;Definition</strong></span></p>
                        <p style="text-align: justify;"><span>Applicable privacy laws define personal information as follows:</span></p>
                        <p style="text-align: justify;"><span>Our Services collect &ldquo;personal information,&rdquo; which means recorded information about an identifiable individual other than contact information i.e. information that identifies, relates to, describes, references, is capable of being associated with, or could reasonably be linked, directly or indirectly, with a particular individual, including any information that constitutes &ldquo;personal information&rdquo; under applicable privacy laws.&nbsp;</span></p>
                        <p style="text-align: justify;"><span>As a customer of the Company, You are an "individual" in relation to this Privacy Policy. In short, all personal data relating to You as a person or individual is protected under the applicable data protection laws and legislation. However, it does not include data where the identifiers relating to a "data item" have been removed in such a way that the data is rendered anonymous, meaning when You are not or no longer identifiable (i.e. anonymous data) or data which relates to an individual&rsquo;s business or place of work.</span></p>
                        <p style="text-align: justify;"><span>In carrying out Our Services, We might make use of certain &ldquo;Know Your Client&rdquo; (KYC) technology which allows the Company to be able to verify the identity of the players in its customer base, falling in line with its regulatory obligations as a licensed operator in Ontario.&nbsp;</span></p>
                        <div style="text-align: justify;"><strong>(b)&nbsp;&nbsp;The important things - what, how and why?</strong></div>
                        <p style="text-align: justify;"><span>We may collect, use, store and transfer various kinds of personal data. In this section We will explain the following:</span></p>
                        <p style="text-align: justify;"><span><strong>&bull;What kind of data do we collect?</strong></span></p>
                        <p style="text-align: justify;"><span><strong>&bull;How do we collect data - Do We get data directly from You? Do We get it from other sources?</strong></span></p>
                        <p style="text-align: justify;"><span><strong>&bull;For what purpose do We collect data? - How and why do We use data?&nbsp;</strong></span></p>
                        <p style="text-align: justify;">&nbsp;</p>
                        <p style="text-align: justify;"><span><strong>&bull;What is our legal basis for data processing?&nbsp; The possible legal bases that could justify it are:</strong></span></p>
                        <p style="text-align: justify;"><span>Legal obligations &ndash; Are We required by law or regulations to process this data? Do We need to process this data to fulfil a legal obligation?&nbsp;</span></p>
                        <p style="text-align: justify;"><span>Legitimate interest - This means that We process Your data because it relates directly to and is necessary in order for the Company to be able to offer its Services to You, which forms part of our legitimate interest as a Company. Before We process Your data in line with this basis, We ensure that We assess the potential impact such treatment may have on You and Your rights. Therefore, We do not use a method where Your rights and interests as an individual override Our interests in processing Your data.</span></p>
                        <p style="text-align: justify;"><span>In performance of a contractual obligation - Processing of personal data is necessary to fulfil the contractual obligations We enter into with You and of which You are a part (i.e. terms and conditions). Collection of Your personal information is required in order for Us to be able to render Our Services to you and in certain cases, such as when You as a player are either a Politically Exposed Person (PEP) or a Head of International Organisation (HIO), We might request further personal information from You in order to adhere to Our legal obligations.&nbsp;</span></p>
                        <p style="text-align: justify;"><span>Consent - Your consent is used as a legal basis for processing Your data, We only process Your data as long as We have Your consent to do so. If at any time You feel that You no longer wish to have Your data processed, We will no longer do so. However, this does not affect any processing of personal data that We have performed with Your consent before You have cancelled Your consent. Please see section 8 of this Privacy Policy for more information on how to cancel Your consent.</span></p>
                        <p style="text-align: justify;"><span>Certain data We collect and process about You is classified as aggregate information, meaning information which cannot lead to Your identification as a natural person, yet may be used by us as a Company to better our services and product offering to You as an end-user and consumer. These types of aggregate information, how they&rsquo;re collected and the reasons for which We use them are detailed in the table below.&nbsp;</span></p>
                        <p style="text-align: justify;"><span>Here is a more detailed breakdown of which personal information We process, when and why We process it and which legal basis We rely on to do so:</span></p>
                        <p style="text-align: justify;">&nbsp;</p>
                        <p style="text-align: justify;"><span>&nbsp;Automatically generated and collected when player plays a game on the Website</span></p>
                        <p style="text-align: justify;"><span>(1)Offer service to player&nbsp;</span></p>
                        <p style="text-align: justify;"><span>A.Execution of contract</span></p>
                        <p style="text-align: justify;"><span>B.Legitimate interest</span></p>
                        <table border="1" cellspacing="0" cellpadding="0">
                        <tbody>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span><strong>Data collected</strong></span></p>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span><strong>When do we collect Your data?&nbsp;</strong></span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span><strong>Purpose of dta collection</strong></span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span><strong>Legal basis for data processing</strong></span></p>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span><br /></span></p>
                        <p style="text-align: justify;"><span>Geolocation Data:</span></p>
                        <p style="text-align: justify;"><span>This includes data relating to Your location as a player when using the Website</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Upon registration and throughout the relationship between the player and the operator</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Identify location of player and ensure the player is situated in Ontario&nbsp;</span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Legal obligation&nbsp;</span></p>
                        <p style="text-align: justify;"><span>B.Legitimate Interest&nbsp;</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Identification data:&nbsp;</span></p>
                        <p style="text-align: justify;"><span>This includes name, selected username, date of birth, gender</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Requested upon registration</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Identify the customer before the contract is performed</span></p>
                        <p style="text-align: justify;"><span>(2)Identify the customer and create a unique customer profile&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(3)Verify the customer for Anti-Money Laundering (&ldquo;AML&rdquo;) reasons</span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Execution of contract</span></p>
                        <p style="text-align: justify;"><span>B.Legal obligation</span></p>
                        <p style="text-align: justify;"><span>C.Legitimate interest</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Contact details:&nbsp;</span></p>
                        <p style="text-align: justify;"><span>This includes email address, home address and mobile number</span></p>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Requested upon registration&nbsp;</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Identify the customer before the contract is performed&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(2)Contact the customer&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(3)Disseminate marketing material should the player have consented to receiving said material&nbsp;</span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Execution of contract</span></p>
                        <p style="text-align: justify;"><span>B.Legal obligation</span></p>
                        <p style="text-align: justify;"><span>C.Legitimate interest</span></p>
                        <p style="text-align: justify;"><span>D.Consent&nbsp;</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Identification of individual through documentation:&nbsp;</span></p>
                        <p style="text-align: justify;"><span>This includes ID documents, proof of address, proof of income and proof of wealth</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Uploaded by the player upon request. Request usually done through a pop-up message on the website or via email</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Verify player&rsquo;s identity&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(2)Compliance with AML obligations</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Legal obligation&nbsp;</span></p>
                        <p style="text-align: justify;"><span>B.Legitimate interest</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Financial data:&nbsp;</span></p>
                        <p style="text-align: justify;"><span>This includes data relating to Your chosen deposit method i.e. Your bank details, credit card details amongst others.&nbsp;</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Collected by player depositing or withdrawing funds to and from their account with the Company. May also be requested via email and/or through chat with Customer Support.&nbsp;</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Fulfil legal obligations&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(2)Fulfil security checks&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(3)Offer service to player&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(4)Fulfil internal policies such as &ldquo;closed-loop&rdquo; policy&nbsp;</span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Execution of contract</span></p>
                        <p style="text-align: justify;"><span>B.Legal obligation&nbsp;</span></p>
                        <p style="text-align: justify;"><span>C.Legitimate interest</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Transaction data:&nbsp;</span></p>
                        <p style="text-align: justify;"><span>This includes transaction details which relate to payments made to and from You</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Automatically generated and collected when player makes deposits or withdrawals on the Website&nbsp;</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Fulfil legal and regulatory obligations and gaming licence requirements&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(2)Offer service to player</span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span><strong>(1)</strong>&nbsp;Execution of contract.</span></p>
                        <p style="text-align: justify;"><span><strong>(2)</strong>&nbsp;Legal obligation.</span></p>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span><strong>Game data:&nbsp;</strong></span></p>
                        <p style="text-align: justify;"><span><strong>This includes game activity and data relating to games You play on our Website&nbsp;</strong></span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Automatically generated and collected when player plays a game on the Website.</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Fulfil legal and regulatory obligations and gaming licence requirements&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(2)Offer service to player&nbsp;</span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Execution of contract</span></p>
                        <p style="text-align: justify;"><span>B.Legal obligation</span></p>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Communication data:&nbsp;</span></p>
                        <p style="text-align: justify;"><span>This includes data regarding Your communications as a player with the Company&nbsp;</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Collected through email correspondence and communications You make through emails or chat with Customer Support. Telephone conversations or calls may also be recorded.&nbsp;</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Fulfil legal and regulatory obligations and gaming licence requirements&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(2)Offer service to player&nbsp;</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Execution of contract</span></p>
                        <p style="text-align: justify;"><span>B.Legal obligation</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Profile data:&nbsp;</span></p>
                        <p style="text-align: justify;"><span>This includes data relating to Your gaming activity, gaming habits and preferences&nbsp;</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Automatically generated and collected through gaming activity on Website, and through the use of cookies onsite.&nbsp;</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Offer service to player&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(2)Create a more personal and tailored user experience for the player</span></p>
                        <p style="text-align: justify;"><span>(3)Segment player based on gaming activity and preferences, in order to better understand player needs and satisfy said needs as efficiently as possible</span></p>
                        <p style="text-align: justify;"><span>(4)Target marketing communications to player&rsquo;s preferences, should the player have consented to receiving said marketing</span></p>
                        <p style="text-align: justify;"><span><br /></span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Legitimate interest&nbsp;</span></p>
                        <p style="text-align: justify;"><span>B.Consent&nbsp;</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Technical Data:&nbsp;</span></p>
                        <p style="text-align: justify;"><span>This includes Your internet protocol (IP) address, Your login information, browser type and version, time zone, location, system and platform&nbsp;</span></p>
                        <div style="text-align: justify;">&nbsp;</div>
                        </td>
                        <td width="19%">
                        <p style="text-align: justify;"><span>Collected through cookies.&nbsp;</span></p>
                        </td>
                        <td width="29%">
                        <p style="text-align: justify;"><span>(1)Fulfil legal and regulatory obligations and gaming licence requirements&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(2)Fulfil security checks&nbsp;</span></p>
                        <p style="text-align: justify;"><span>(3)Offer service to player&nbsp;</span></p>
                        </td>
                        <td width="24%">
                        <p style="text-align: justify;"><span>A.Execution of contract&nbsp;</span></p>
                        <p style="text-align: justify;"><span>B.Legal obligation</span></p>
                        <p style="text-align: justify;"><span>C.Legitimate interest&nbsp;</span></p>
                        </td>
                        </tr>
                        <tr>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Marketing data: </span></p>
                        <p style="text-align: justify;"><span>This includes data about your preferences regarding offers, promotions and marketing communications </span></p>
                        </td>
                        <td width="25%">
                        <p style="text-align: justify;"><span>Collected at registration stage, nature of data is dependent on whether You choose to receive marketing material or not</span></p>
                        </td>
                        <td width="25%">
                        <p style="text-align: justify;"><span>(1) Offer service to player</span></p>
                        </td>
                        <td width="25%">
                        <p style="text-align: justify;"><span>A. Consent </span></p>
                        <p style="text-align: justify;"><span>B. Legitimate Interest </span></p>
                        </td>
                        </tr>
                        <tr>
                        <td colspan="4" width="100%">
                        <p style="text-align: justify;"><span>Cookie Data: please see our Cookie Policy for further information about how and for which purpose we collect Your Cookies.&nbsp;</span></p>
                        </td>
                        </tr>
                        </tbody>
                        </table>
                        <p style="text-align: justify;"><span>You can find further information below about how We process Your data for the following reasons:</span></p>
                        <ul>
                        <li><span><strong>Our own marketing activities</strong>-&nbsp; We do Our best to ensure that You have the greatest control over what kind of marketing material You receive from Us or from third parties who act as data processors to Us, processing Your data on our behalf and following Our instructions. You can view and change Your marketing related choices in Our Privacy section of Your account. If You feel that You would like more control over which marketing material We send to You, then You are welcome to contact our PO using the contact information provided above. Should You wish to withdraw Your consent, it may take up to 48 hours before We can be assured that the changes have been implemented in our system and in the systems of our marketing partners (and for this reason, You may receive emails or information from us within those 48 hours). Section 8 explains exactly how You can withdraw Your consent.&nbsp;</span><span style="text-align: justify;">&nbsp;</span></li>
                        </ul>
                        <ul>
                        <li style="text-align: justify;"><span><strong>Marketing activities of Our business partners</strong> - We never share Your personal data with our business partners for their own marketing activities without Your consent.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span>Your personal data will not be processed for purposes other than those for which they were collected. Should further processing be required, we shall perform compatibility assessment (to confirm that any such further purpose is compatible with the initial purpose for which data was collected) and You will be informed of that purpose and provided with all necessary information.&nbsp;</span></p>
                        <p id="sharing-of-data" style="text-align: justify;"><strong><span>3.&nbsp;</span></strong><span><strong>SHARING OF DATA</strong></span></p>
                        <p style="text-align: justify;"><span>Due to the nature of Our service, in order to process Your data as explained in section 2 of this Privacy Policy, We may need to share Your personal and aggregate information with a number of trusted third parties. These 3rd parties include:</span></p>
                        <p style="text-align: justify;"><span><strong>(i)&nbsp;&nbsp;Game Providers</strong>&nbsp;-&nbsp;At times, Our game providers will need access to selected data (such as username and IP address) in order to provide us with the games You play on Our Website.</span></p>
                        <p style="text-align: justify;"><span><strong>(ii)&nbsp;&nbsp;Payment Providers and Related Service Providers</strong>&nbsp;-&nbsp;Similarly, We may share some of Your personal information with the payment provider You use to make deposits and withdrawals on Our Website.</span></p>
                        <p style="text-align: justify;"><span><strong>(iii)&nbsp;&nbsp;Marketing Partners</strong>&nbsp;&nbsp;-&nbsp;&nbsp;When You consent to us sending You marketing communications and promotions, We may share Your contact information (such as email address or postal address) with our marketing partners who are responsible for sending Our marketing material to You.</span></p>
                        <p style="text-align: justify;"><span><strong>(iv) Government or Regulatory Authorities</strong>&nbsp;- We may, if necessary or authorized by law, provide Your personal data to law enforcement agencies, government or regulatory organizations, courts or other public authorities.&nbsp;</span></p>
                        <p style="text-align: justify;"><span><strong>(v) Client communication software&nbsp;</strong>- We use third party software to help us communicate with You. This software allows us to send emails to You and talk to You on Our live&nbsp;<a href="/customer-service/contact-us/" rel="nofollow">chat</a> whenever you have any questions.</span></p>
                        <p style="text-align: justify;"><span><strong>(vi)&nbsp;&nbsp;AML and anti-fraud tools</strong>&nbsp;-&nbsp;We use third party software to perform certain AML and fraud verification checks, which in this case are necessary to comply with Our legal obligations.</span></p>
                        <p style="text-align: justify;"><span>We always ensure that a third party who has access to Your personal data is obliged to respect the security of Your personal data, and always process it in a lawful manner, as well as in accordance with Our Privacy Policy and strict contractual provisions. We do not allow any third party service provider to use Your personal data for their own purposes, except when Your personal data is shared with another data controller, such as for example, the governmental and regulatory authorities.</span></p>
                        <p id="joint-controllers" style="text-align: justify;"><span><strong>4. TRANSFER OF PERSONAL DATA</strong></span></p>
                        <p style="text-align: justify;"><span>Service providers referred to in section 3 above may be based in countries that are located outside of Ontario. This may mean that Your data may be shared outside of Canada. Whenever a transfer of Your personal data is made to a data processor or a data controller based outside Canada, We always ensure that Your data is protected in the same way as it is in Ontario. To ensure the protection of Your data, We will implement at least one of the following safeguards:</span></p>
                        <p style="text-align: justify;"><span>(I)Adequacy basis - We ensure that We transfer Your personal data to countries that provide an adequate level of data protection.</span></p>
                        <p style="text-align: justify;">&nbsp;</p>
                        <p style="text-align: justify;"><span>(II)Standard Contractual Clauses - When an organization is not based in Canada, We may use special contracts, known as standard contractual clauses. These contracts also ensure that personal data is afforded the same protection as it is in Canada.&nbsp;</span></p>
                        <div style="text-align: justify;"><span><strong>5.&nbsp;AUTOMATED DECISION MAKING</strong></span></div>
                        <p style="text-align: justify;"><span>When establishing and implementing our business relationships, We do not use automated decision making. If We use this method in individual cases, i.e. only when the processing activities based on automated decision-making are permissible under the exceptions within the applicable data protection laws, these operations shall be subject to suitable safeguards to protect Your rights and freedoms. Most importantly, You will be provided (i) the appropriate information, (ii) the right to obtain human intervention on the part of the controller, (iii) the right to express Your point of view, (iv) the right to obtain an explanation of the decision reached after such assessment and (v) the right to contest the decision, unless prohibited by applicable law.</span></p>
                        <p id="automated-decision-making" style="text-align: justify;"><span><strong>6.TECHNICAL AND ORGANISATIONAL MEASURES&nbsp;</strong></span></p>
                        <p style="text-align: justify;"><span>The Company always strives to ensure that Your personal data is secure, both in Our hands and in the hands of any third party to whom we may have passed on Your personal data. Internally, We have implemented a number of technical, contractual, as well as organizational measures, to ensure that Your personal data is not accidentally lost, used, accessed in an unauthorized manner, altered or disclosed. We also ensure that access to Your personal information is determined on a "need-to- know" basis, which means that only people with direct needs to access Your personal data will have access to them. In addition, anyone who has access to Your personal data is bound by confidentiality.</span></p>
                        <p style="text-align: justify;"><span>We also have procedures in place to deal with any suspected or actual personal data breaches. We will inform both You and iGO, the regulatory authority affected by such data security, and we will maintain a list of any such breaches. In this case, we will notify iGaming Ontario and other relevant authorities, and assist the same in reporting and resolving data breaches.&nbsp;</span></p>
                        <p id="technical-and-organisational-measures" style="text-align: justify;"><strong><span>7.&nbsp;</span></strong><strong>DATA RETENTION</strong></p>
                        <p style="text-align: justify;"><span>The Company will only retain Your personal data for as long as it is necessary to fulfil the purpose for which it was collected and in accordance with our data retention policies. Some objectives may include the satisfaction of any legal, accounting or reporting requirements. Once the purpose for which the personal data has been collected and processed is reached, and unless We have no other legal obligations to hold onto Your personal data, We will ensure that Your personal data is disposed of permanently, safely and securely.&nbsp;</span></p>
                        <p style="text-align: justify;"><span>When determining how long a retention period is appropriate for Your personal data, We take into account various factors, such as the nature and sensitivity of the personal data, the potential risk of unauthorized use or disclosure of such data, the purpose We collected and processed such data for and applicable laws and/or legal requirements imposed on Us.</span></p>
                        <p style="text-align: justify;"><span>You are welcome to contact Our PO using the contact information provided in this Privacy Policy for further information about Our storage and data retention periods.</span></p>
                        <p id="your-rights" style="text-align: justify;"><strong><span>9.&nbsp;</span></strong><span><strong>YOUR RIGHTS</strong></span></p>
                        <p style="text-align: justify;"><span>Applicable legislation gives You certain rights which You may exercise in relation to Your personal information. In accordance with the law, You have the right to:</span></p>
                        <p style="text-align: justify;"><span><span style="text-decoration: underline;"><span><strong>Request Access to Your Personal Data -</strong></span><span>&nbsp;This means that You have the right to request a copy of the personal data We hold about You against a minimal fee. To request such access, please exercise Your right by filing a Freedom of Information Request with iGaming Ontario here: <a href="https://www.igamingontario.ca/en/freedom-information-requests" target="_blank">https://www.igamingontario.ca/en/freedom-information-requests</a>.&nbsp;</span></p>
                        <p style="text-align: justify;"><span><strong><span style="text-decoration: underline;">Request for Correction of Your Personal Data</span> -</strong>&nbsp;This means that if any of the personal information We hold about You is incomplete or incorrect, You have the right to have it corrected. Keep in mind, however, that We may need You to provide proof and documentation (such as Your ID documentation or proof of address) in order to comply with Your request.</span></p>
                        <p style="text-align: justify;"><span><strong><span style="text-decoration: underline;">Request to have Your personal data deleted</span>&nbsp;</strong></span><strong>-</strong><span>&nbsp;</span><span>This means that You can request to have Your personal data deleted if We no longer have a legal reason to continue to process or store it. Please note that this right is not guaranteed - in the sense that We do not have the ability to comply with Your request if We are subject to a legal obligation to store Your data or if We have the reason that it that necessary to store Your personal data, in order to defend ourselves in a legal dispute.</span></p>
                        <p style="text-align: justify;"><span><strong><span style="text-decoration: underline;">Withdraw Your consent at any time when we rely on Your consent to process Your personal data</span> -</strong></span><strong>&nbsp;</strong></span><span>Termination or withdrawal of Your consent will not affect the legality of the processing We have performed until the time You withdrew Your consent. Withdrawal of Your consent means that in the future You no longer want to have Your data treated in the same way. This means that You can no longer give us permission to provide certain services (e.g. Marketing). You can withdraw Your consent at any time via the Privacy section located on "Your Account" on the Website. In addition, You can withdraw Your consent from marketing through the &lsquo;opt-out&rsquo; unsubscribe button provided in the email You receive from us.</span></p>
                        <p style="text-align: justify;"><span><strong><span style="text-decoration: underline;">File a complaint</span> -&nbsp;</strong>&nbsp;</span><span>As explained in section 1 (b) of this Privacy Policy. We will respond to all legitimate requests within a 30-day timeframe from the submission of a request. If Your request is particularly complex, or if You have made multiple requests in a certain period, it may take us a little longer. In such a case, we will notify You of this necessary extension.&nbsp;</span></p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'simple.1261.html',
            'value' => '<h1 style="text-align: justify;">Kungaslottets Integritetspolicy</h1>
                        <table border="1" cellspacing="0" cellpadding="5">
                        <tbody>
                        <tr>
                        <td width="200"><strong>Datum</strong></td>
                        <td width="200"><strong>Beskrivning</strong></td>
                        <td width="200"><strong>Version</strong></td>
                        </tr>
                        <tr>
                        <td width="200">15 september 2024</td>
                        <td width="200">Skapandet av dokumentet</td>
                        <td width="200">Version 1.0</td>
                        </tr>
                        </tbody>
                        </table>
                        <p style="text-align: justify;">DBET Online Casino (<strong>"DBET"</strong>) tillg&auml;ngligt p&aring; www.dbet.com eller www.dbet.com mobilapplikation (<strong>"Webbplatsen"</strong>), drivs av:</p>
                        <ul>
                        <li>Kungaslottet Limited (<strong>"Kungaslottet Ltd"</strong>), som lyder under Maltesisk lag och har sitt s&auml;te i 105, Gwardamangia Hill, Pieta, PTA 1313, Malta</li>
                        </ul>
                        <p style="text-align: justify;">I enlighet med de relevanta licenserna och jurisdiktionerna som kan finnas <a href="/terms-and-conditions/">h&auml;r</a>.</p>
                        <p style="text-align: justify;">Varje h&auml;nvisning till "Vi", "V&aring;r", &rdquo;V&aring;ra&rdquo;, "Oss" eller "F&ouml;retaget" i denna integritetspolicy ska tolkas som h&auml;nvisning till Kungaslottet Ltds verksamhet g&auml;llande Webbplatsen.</p>
                        <p style="text-align: justify;">F&ouml;retaget v&auml;rderar din integritet och sekretess h&ouml;gt och har f&ouml;r avsikt att hantera alla dina personuppgifter p&aring; ett transparent, r&auml;ttvist och lagligt s&auml;tt.</p>
                        <p style="text-align: justify;">Denna integritetspolicy (tillsammans med <a href="/terms-and-conditions/">Regler och Villkor</a> samt V&aring;r <a href="/cookie-policy/">Cookiepolicy</a>) anger grunden f&ouml;r hur Kungaslottet Ltd samlar, lagrar och anv&auml;nder dina personuppgifter n&auml;r du bes&ouml;ker V&aring;r webbplats. I likhet med vad dina r&auml;ttigheter &auml;r och hur lagen skyddar dessa r&auml;ttigheter och hur du kan ut&ouml;va dem (<strong>"Integritetspolicy"</strong>).</p>
                        <ol class="page-sections_link_box"><!--Automaticall adds numbering to bullet points, do not add in below list as will show duplicate-->
                        <li><strong><a href="#om-oss">Om Oss</a></strong></li>
                        <li><strong><a href="#dina-personuppgifter">Dina personuppgifter</a></strong></li>
                        <li><strong><a href="#delning-av-data">Delning av data</a></strong></li>
                        <li><strong><a href="#gemensamt-personuppgiftsansvariga">Gemensamt personuppgiftsansvariga</a></strong></li>
                        <li><strong><a href="#overforing-av-personuppgifter">&Ouml;verf&ouml;ring av personuppgifter utanf&ouml;r Europeriska ekonomiska samarbetsomr&aring;det (&rdquo;EES&rdquo;)</a></strong></li>
                        <li><strong><a href="#automatiserat-beslutsfattande">Profilering och Automatiserat beslutsfattande</a></strong></li>
                        <li><strong><a href="#datasakerhetsatgarder">Datas&auml;kerhets&aring;tg&auml;rder</a></strong></li>
                        <li><strong><a href="#data-retention">Data retention</a></strong></li>
                        <li><strong><a href="#dina-rattigheter">Dina r&auml;ttigheter</a></strong></li>
                        <li><strong><a href="#andringar">&Auml;ndringar till integritetspolicy</a></strong></li>
                        </ol><hr />
                        <p id="om-oss" style="text-align: justify;"><strong>1. OM OSS</strong></p>
                        <p style="text-align: justify;"><strong>(a) Syftet med Integritetspolicy</strong></p>
                        <p style="text-align: justify;">Den h&auml;r Integritetspolicyn syftar till att ge dig en grundlig f&ouml;rst&aring;else f&ouml;r hur Vi behandlar dina personuppgifter som samlas in genom din anv&auml;ndning av den h&auml;r Webbplatsen, vilket ocks&aring; inkluderar alla uppgifter du angett vid registrering s&aring;v&auml;l som via ditt brukande av V&aring;rt online casino.</p>
                        <p style="text-align: justify;">Den h&auml;r Webbplatsen &auml;r enbart avsedd f&ouml;r personer &ouml;ver 18 &aring;r, och Vi samlar d&auml;rf&ouml;r inte medvetet uppgifter om personer under en s&aring;dan &aring;lder. Om det kommer till V&aring;r k&auml;nnedom att Vi samlat in personuppgifter om personer under 18 &aring;r p&aring; grund av missbruk av V&aring;r hemsida, kommer Vi g&ouml;ra V&aring;rt yttersta f&ouml;r att s&auml;kerhetsst&auml;lla att s&aring;dan uppgifter behandlas i enlighet med g&auml;llande lag och V&aring;ra policyer och rutiner.</p>
                        <p style="text-align: justify;">Om inte annat preciseras i den h&auml;r integritetspolicyn ska f&ouml;rsedda termer som h&auml;rmed fastst&auml;llts, f&ouml;rfoga &ouml;ver samma inneb&ouml;rd som angetts i <a href="/terms-and-conditions">villkoren</a>.</p>
                        <p style="text-align: justify;">Den h&auml;r integritetspolicyn m&aring;ste l&auml;sas i samband med andra sekretessmeddelanden som Vi kan f&ouml;rse dig med i framtiden. Den h&auml;r integritetspolicyn &auml;r kompletterad med andra meddelanden av samma sort och &auml;r f&ouml;ljaktligen inte avsedd att &aring;sidos&auml;tta dem.</p>
                        <p style="text-align: justify;">Vi lovar att skydda dina personuppgifter och att alltid respektera din integritet i enlighet med b&auml;sta aff&auml;rspraxis och till&auml;mpliga lagar, s&auml;rskilt den allm&auml;nna dataskyddsf&ouml;rordningen (EU) 2016/679 (<strong>&ldquo;GDPR&rdquo;</strong>), s&aring;v&auml;l som eventuella lokala lagar i den l&auml;nder d&auml;r Vi har licens, under vilka Vi driver V&aring;rt onlinecasino.</p>
                        <p style="text-align: justify;">Du ansvarar f&ouml;r att tillhandah&aring;lla personuppgifter som &auml;r korrekta och skriftligen informera Oss om eventuella &auml;ndringar som kan intr&auml;ffa, s&aring; att Vi kan anv&auml;nda alla rimliga medel f&ouml;r att h&aring;lla V&aring;r information om dig korrekt och uppdaterad. Dessutom kan Vi genomf&ouml;ra kontroller av uppgifternas noggrannhet i enlighet med GDPR och be dig att verifiera dina uppgifter som Vi har om dig fr&aring;n tid till annan.</p>
                        <p><strong>(b) Personuppgiftsansvariga (Data Controllers)</strong></p>
                        <p style="text-align: justify;">Vi &auml;r personuppgiftsansvariga (data controllers) i enlighet till de relevanta licenserna som finns tillg&auml;ngliga h&auml;r och ansvarar d&auml;rmed f&ouml;r dina personuppgifter (personal data).</p>
                        <p style="text-align: justify;">Eftersom Vi tar din integritet p&aring; fullaste allvar har Vi utsett ett dataskyddsombud (<strong>&rdquo;DPO&rdquo;</strong>) vars ansvar &auml;r att &ouml;vervaka att f&ouml;retaget</p>
                        <ol type="i">
                        <li>uppfyller sina lagliga skyldigheter s&aring;som hantering av dina personuppgifter och;</li>
                        <li>behandlar dina personuppgifter i enlighet med g&auml;llande regler och f&ouml;rordningar.</li>
                        </ol>
                        <p style="text-align: justify;">V&aring;r DPO &auml;r din kontaktperson ang&aring;ende alla fr&aring;gor som r&ouml;r denna Integritetspolicy. Om du har n&aring;gra fr&aring;gor v&auml;nligen kontakta V&aring;r DPO med hj&auml;lp av uppgifterna nedan.</p>
                        <p style="text-align: justify;">Om du anv&auml;nder dig av de tj&auml;nster som tillhanhah&aring;lls av Kungaslottet Ltd i enllighet med relevant(a) licens(er), som tillhandah&aring;lls h&auml;r, ska f&ouml;ljande g&auml;lla:</p>
                        <table border="1" cellspacing="0" cellpadding="5">
                        <tbody>
                        <tr>
                        <td><strong> Fullst&auml;ndigt namn p&aring; juridisk person</strong></td>
                        <td>Kungaslottet Ltd</td>
                        </tr>
                        <tr>
                        <td><strong>Postadress</strong></td>
                        <td>105, Gwardamangia Hill, Pieta, PTA 1313, Malta</td>
                        </tr>
                        <tr>
                        <td><strong>E-postadress</strong></td>
                        <td>dpo@dbet.com</td>
                        </tr>
                        </tbody>
                        </table>
                        <p style="text-align: justify;">&nbsp;</p>
                        <p id="dina-personuppgifter" style="text-align: justify;"><strong>2. DINA PERSONUPPGIFTER</strong></p>
                        <p style="text-align: justify;"><strong>(a) Vad &auml;r personuppgifter?</strong></p>
                        <p style="text-align: justify;">Den allm&auml;nna dataskyddsf&ouml;rordningen (&ldquo;GDPR&rdquo;) definierar personuppgifter som f&ouml;ljande:</p>
                        <p style="text-align: justify;"><em>"Varje upplysning som avser en identifierad eller identifierbar fysisk person ("registrerad&rdquo;), varvid en identifierbar fysisk person &auml;r en person som direkt eller indirekt kan identifieras s&auml;rskilt med h&auml;nvisning till en identifierare som ett namn, ett identifikationsnummer, en lokaliseringsuppgift eller onlineidentifikatorer eller en eller flera faktorer som &auml;r specifika f&ouml;r den fysiska personens fysiska, fysiologiska, genetiska, psykiska, ekonomiska, kulturella eller sociala identitet.&rdquo;</em></p>
                        <p style="text-align: justify;">Du, som f&ouml;retagets kund, &auml;r &rdquo;registrerad&rdquo; i f&ouml;rh&aring;llande till den h&auml;r integritetspolicyn (data subject). Kort sagt inneb&auml;r det att personuppgifter som r&ouml;r dig som registrerad &auml;r skyddade i enlighet med dataskyddslagarna, g&auml;llande f&ouml;rordningar och lagstiftning. Det inkluderar dock inte data d&auml;r identifieringar relaterade till den registrerade g&ouml;rs anonyma, det vill s&auml;ga n&auml;r den registrerade inte &auml;r eller inte l&auml;ngre kan identifieras (vilket f&ouml;ljaktligen betyder anonym data).</p>
                        <p style="text-align: justify;"><strong>(b) Viktiga saker &ndash; vad, hur och varf&ouml;r?</strong></p>
                        <p style="text-align: justify;">Vi kan samla, anv&auml;nda, lagra samt &ouml;verf&ouml;ra olika typer av personuppgifter. I den h&auml;r paragrafen f&ouml;rklarar Vi f&ouml;ljande:</p>
                        <div id="bold-list"><ol type="i">
                        <li style="text-align: justify;"><strong>Vilken typ av information samlar Vi;</strong></li>
                        <li style="text-align: justify;"><strong>Hur Vi samlar in s&aring;dan information;</strong></li>
                        <li style="text-align: justify;"><strong>F&ouml;r vilka &auml;ndam&aring;l samlar information;</strong></li>
                        <li style="text-align: justify;"><strong>Vilken r&auml;ttsligt grund finns f&ouml;r bearbetning av s&aring;dan information</strong></li>
                        <ol type="a">
                        <li style="text-align: justify;"><strong>Juridiska skyldigheter</strong> &ndash; Det kan vara n&ouml;dv&auml;ndigt att behandla dessa uppgifter f&ouml;r att f&ouml;lja till&auml;mpliga lagar, f&ouml;rordningar och lagar.</li>
                        <li style="text-align: justify;"><strong>Legitimt intresse</strong> &ndash; I f&ouml;rh&aring;llande till den r&auml;ttsliga grunden behandlar Vi dina uppgifter f&ouml;r att driva och kontrollera V&aring;rt f&ouml;retag, med det yttersta m&aring;let att ge dig b&auml;sta m&ouml;jliga service och upplevelse. Innan Vi ut&ouml;var denna r&auml;ttighet utv&auml;rderar Vi noggrant den potentiella inverkan som s&aring;dan behandling kan ha p&aring; dig och dina r&auml;ttigheter. Som s&aring;dan anv&auml;nder Vi inte en metod d&auml;r dina r&auml;ttigheter och intressen som "registrerad" &aring;sidos&auml;tts av V&aring;ra intressen av att behandla data.</li>
                        <li style="text-align: justify;"><strong>Verkst&auml;llande av kontrakt</strong> &ndash; Behandling av personuppgifter &auml;r n&ouml;dv&auml;ndig f&ouml;r att uppfylla de avtalsf&ouml;rpliktelser som Vi ing&aring;r med dig och som du s&aring;ledes &auml;r del av (d.v.s <a href="/terms-and-conditions/">regler & villkor</a>).</li>
                        <li style="text-align: justify;"><strong>Samtycke</strong> &ndash; Den r&auml;ttsliga grunden f&ouml;r att behandla dina uppgifter &auml;r ditt samtycke. Vi kommer endast att behandla dina uppgifter s&aring; l&auml;nge Vi har ditt samtycke till det. Om du best&auml;mmer dig f&ouml;r att &aring;terkalla ditt samtycke kommer Vi att sluta behandla dina uppgifter. Observera att eventuell behandling av personuppgifter som Vi redan har utf&ouml;rt med ditt samtycke innan du &aring;terkallade den inte kommer att p&aring;verkas.</li>
                        </ol></ol></div>
                        <p style="text-align: justify;"><em>V&auml;nligen l&auml;s paragraf 9 i den h&auml;r integritetspolicyn f&ouml;r mer information om hur du &aring;terkallar ditt samtycke.</em></p>
                        <div style="overflow-x: scroll;">
                        <table width="100%" border="1" cellspacing="0" cellpadding="5">
                        <tbody>
                        <tr><!--headers-->
                        <td valign="top" width="25%"><strong>Datalagring</strong></td>
                        <td valign="top" width="19%"><strong>Hur lagrar Vi dina uppgifter?</strong></td>
                        <td valign="top" width="29%"><strong>Syftet med insamling</strong></td>
                        <td valign="top" width="24%"><strong>R&auml;ttslig grund g&auml;llande hantering av uppgifter</strong></td>
                        </tr>
                        <tr><!--row 1-->
                        <td valign="top" width="25%"><strong>Identifieringsdata:</strong> inkluderar fullst&auml;ndigt namn, valfritt anv&auml;ndarnamn, f&ouml;delsedatum, k&ouml;n</td>
                        <td valign="top" width="19%">Beg&auml;rs vid registrering</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Kundidentifiering och att skapa en unik kundprofil<br /> <strong>(2)</strong> Kundverifiering f&ouml;r att motverka penningtv&auml;tt ("AML")<br /> <strong>(3)</strong> Identifiering av kund vid kontakt</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Prestanda av kontrakt<br /> <strong>(2)</strong> R&auml;ttslig skyldighet<br /> <strong>(3)</strong> Prestanda av kontrakt/ Ber&auml;ttigat intresse</td>
                        </tr>
                        <tr><!--row 2-->
                        <td valign="top" width="25%"><strong>Kontaktuppgifter:</strong> inkluderar e-postadress, hemadress, mobilnummer</td>
                        <td valign="top" width="19%">Beg&auml;rs vid registrering</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Kundidentifiering och att skapa en unik kundprofil<br /> <strong>(2)</strong> Kontakta kunder f&ouml;r support &auml;ndam&aring;l<br /> <strong>(3)</strong> Distribution av marknadsf&ouml;ringsmaterial</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Prestanda av kontrakt<br /> <strong>(2)</strong> Prestanda av kontrakt<br /> <strong>(3)</strong> Samtycke</td>
                        </tr>
                        <tr><!--row 3-->
                        <td valign="top" width="25%"><strong>N&ouml;dv&auml;ndiga uppgifter f&ouml;r verifierings&auml;ndam&aring;l</strong> - t.ex. din ID-handling, adressbevis och eventuellt inkomstbevis, bevis p&aring; f&ouml;rm&ouml;genhet.</td>
                        <td valign="top" width="19%">Att laddas upp p&aring; Mina sidor p&aring; beg&auml;ran, kan beg&auml;ras antingen via ett popup-meddelande p&aring; hemsidan eller via e-post.</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Kundverifiering<br /> <strong>(2)</strong> Kr&auml;vs f&ouml;r att f&ouml;lja AML lag och spellicenskrav</td>
                        <td valign="top" width="24%"><strong>(1)</strong> R&auml;ttslig skyldighet<br /> <strong>(2)</strong> R&auml;ttslig skyldighet</td>
                        </tr>
                        <tr><!--row 4-->
                        <td valign="top" width="25%"><strong>Finansiella uppgifter:</strong> inneh&aring;ller finansiella detaljer om dina valfria ins&auml;ttnings- och uttagsmetoder, d&auml;rf&ouml;r kr&auml;vs bankinformation, kreditkortsuppgifter eller andra relevanta uppgifter om de valda betalningsmetoderna.</td>
                        <td valign="top" width="19%">Sparas vid ins&auml;ttning eller uttag av pengar till spelarkontot. Kan ocks&aring; lagras d&aring; Vi beg&auml;rt upplysning om s&aring;dan information via e-post/chatt/telefonsamtal</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Beg&auml;rs f&ouml;r att du ska tillhandh&aring;lla tj&auml;nsten (dvs. att s&auml;tta in pengar p&aring; spelarkontot)<br /> <strong>(2)</strong> Beg&auml;rs f&ouml;r Know-Your-Customer (&ldquo;KYC&rdquo;) granskning (inkomstk&auml;lla)<br /> <strong>(3)</strong> Beg&auml;rs f&ouml;r granskningar av internetbrott<br /> <strong>(4)</strong> F&ouml;r att s&auml;kerst&auml;lla en sluten policy</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Prestanda av kontrakt<br /> <strong>(2)</strong> R&auml;ttslig skyldighet<br /> <strong>(3)</strong> Legitimt intresse<br /> <strong>(4)</strong> R&auml;ttslig skyldighet</td>
                        </tr>
                        <tr><!--row 5-->
                        <td valign="top" width="25%"><strong>Transaktionsdata:</strong> inneh&aring;ller uppgifter om betalningar som gjorts till och av dig</td>
                        <td valign="top" width="19%">Automatiskt n&auml;r ins&auml;ttning och uttag g&ouml;rs</td>
                        <td valign="top" width="29%"><strong>(1)</strong> N&ouml;dv&auml;ndigt f&ouml;r att f&ouml;rse dig med service<br /> <strong>(2)</strong> Kr&auml;vs f&ouml;r att f&ouml;lja AML lag och spellicenskrav<br /> <strong>(3)</strong> Kr&auml;vs f&ouml;r att sp&aring;ra din verksamhet f&ouml;r sociala ansvars&aring;tg&auml;rder</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Prestanda av kontrakt<br /> <strong>(2)</strong> R&auml;ttslig skyldighet<br /> <strong>(3)</strong> R&auml;ttslig skyldighet/ Legitimt intresse</td>
                        </tr>
                        <tr><!--row 6-->
                        <td valign="top" width="25%"><strong>Speluppgifter:</strong> inkluderar detaljer om spel som du spelar p&aring; V&aring;r hemsida (dvs. din spelaktivitet)</td>
                        <td valign="top" width="19%">Automatiskt genererad med spelaktivitet</td>
                        <td valign="top" width="29%"><strong>(1)</strong> N&ouml;dv&auml;ndigt f&ouml;r att f&ouml;rse dig med service<br /> <strong>(2)</strong> Kr&auml;vs f&ouml;r att f&ouml;lja spel lagar</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Prestanda av kontrakt<br /> <strong>(2)</strong> R&auml;ttslig skyldighet</td>
                        </tr>
                        <tr><!--row 7-->
                        <td valign="top" width="25%"><strong> Uppgifter om din kommunikation med Oss (via e-post, live chatt, telefonsamtal)</strong></td>
                        <td valign="top" width="19%">E-postkorrespondens och kontakt genom live chatt, telefonsamtal kan spelas in f&ouml;r registreringskrav</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Kr&auml;vs f&ouml;r att f&ouml;rse dig med kundservice (f&ouml;r kundfr&aring;gor, kommunicera n&ouml;dv&auml;ndiga fr&aring;gor)</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Prestanda av kontrakt</td>
                        </tr>
                        <tr><!--row 8-->
                        <td valign="top" width="25%"><strong>Profildata:</strong> data som &auml;r relaterad till dina spelvanor och dina preferenser</td>
                        <td valign="top" width="19%">Genereras automatiskt med spell&auml;ge, eller anv&auml;nder cookies f&ouml;r att registrera preferenser</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Kan anv&auml;ndas i aggregerad och anonymiserad form f&ouml;r att f&ouml;rb&auml;ttra tj&auml;nsten<br /> <strong>(2)</strong> Kan anv&auml;ndas f&ouml;r en personligare anv&auml;ndarupplevelse<br /> <strong>(3)</strong> Segmenterings&auml;ndam&aring;l - segmentera dig i olika grupper vilka beror p&aring; flera faktorer s&aring;som ditt spell&auml;ge, etc. Avsikten, f&ouml;r Oss, &auml;r att f&ouml;rb&auml;ttra V&aring;r produkt och service genom att b&auml;ttre f&ouml;rst&aring; V&aring;ra kunder<br /> <strong>(4)</strong> Segmentering f&ouml;r AML och sociala ansvars&aring;tg&auml;rder<br /> <strong>(5)</strong> Specifik marknadsf&ouml;ring</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Legitimt intresse - data i anonymiserad form &auml;r inte personuppgifter<br /> <strong>(2)</strong> Samtycke<br /> <strong>(3)</strong> Legitimt intresse<br /> <strong>(4)</strong> Legitimt intresse<br /> <strong>(5)</strong> Samtycke</td>
                        </tr>
                        <tr><!--row 9-->
                        <td valign="top" width="25%"><a name="_Hlk513707457"></a><strong>Tekniska data:</strong> inneh&aring;ller din internetadress (IP), din inloggningsinformation, typ av webbl&auml;sare och version, tidszoninst&auml;llning och plats, operativsystem och plattform<br /> <strong>Anv&auml;ndande data:</strong> inneh&aring;ller uppgifter om hur du anv&auml;nder V&aring;r hemsida</td>
                        <td valign="top" width="19%">Cookie data</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Platsdata/IP som anv&auml;nds f&ouml;r att f&ouml;rs&auml;kra att kunden inte &auml;r fr&aring;n ett begr&auml;nsat- eller h&ouml;griskland<br /> <strong>(2)</strong> Platsdata/ IP-adress anv&auml;nds ocks&aring; f&ouml;r att s&auml;kerst&auml;lla att proxy eller VPN inte anv&auml;nds, samt f&ouml;r att s&auml;kerst&auml;lla att inget missbrukande av bonusar eller bedr&auml;geri sker<br /> <strong>(3)</strong> &Ouml;vrig data (inklusive plats och IP) anv&auml;nds f&ouml;r att f&ouml;rb&auml;ttra webbplatsens funktionalitet, fels&ouml;ka tekniska problem, skapa fler produkter f&ouml;r olika plattformar</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Prestanda av kontrakt<br /> <strong>(2)</strong> Legitimt intresse<br /> <strong>(3)</strong> Legitimt intresse</td>
                        </tr>
                        <tr><!--row 10-->
                        <td valign="top" width="25%"><strong>My RTP data:</strong> kan inkludera din tr&auml;ff frekvens, det totala antalet spins, den totala RTP:n p&aring; ditt konto, h&ouml;gsta vinst i specifikt spel samt vilken insats som lagts f&ouml;r att erh&aring;lla den vinsten</td>
                        <td valign="top" width="19%">Automatiskt genererad med gameplay</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Nu kan du se din egen faktiska RTP som baseras p&aring; din spelaktivitet; en funktion i V&aring;r tj&auml;nst f&ouml;r att ge dig V&aring;r speltj&auml;nst. Detta g&ouml;r Vi f&ouml;r att f&ouml;rb&auml;ttra transparens med dig som en akt&ouml;r under konsumentskyddslagstiftningen</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Juridisk skyldighet/ Legitimt intresse</td>
                        </tr>
                        <tr><!--row 11-->
                        <td valign="top" width="25%"><strong>Marknads- och kommunikationsuppgifter:</strong> inneh&aring;ller dina preferenser vad g&auml;ller mottagande av marknadsf&ouml;ringsmeddelanden fr&aring;n Oss och andra tredje partner (till exempel affiliates), s&aring;v&auml;l som dina kommunikationspreferenser</td>
                        <td valign="top" width="19%">&nbsp;</td>
                        <td valign="top" width="29%"><strong>(1)</strong> Egen marknadsf&ouml;ring via olika kommunikationskanaler<br /> <strong>(2)</strong> Marknadsf&ouml;ring genom affiliates</td>
                        <td valign="top" width="24%"><strong>(1)</strong> Samtycke (granul&auml;r - per kanal)<br /> <strong>(2)</strong> Samtycke</td>
                        </tr>
                        <tr><!--row 12-->
                        <td colspan="4" valign="top" width="100%"><strong>Cookie uppgifter</strong> &ndash; v&auml;nligen l&auml;s V&aring;r cookiepolicy f&ouml;r mer information ang&aring;ende vilka &auml;ndam&aring;len &auml;r f&ouml;r lagring av dina cookies s&aring;v&auml;l som hur detta sker.</td>
                        </tr>
                        </tbody>
                        </table>
                        </div>
                        <p style="text-align: justify;">Nedan finner du mer information g&auml;llande hantering av dina uppgifter av f&ouml;ljande sk&auml;l:</p>
                        <ul>
                        <li>
                        <p><strong>V&aring;ra Egna Marknadsf&ouml;ringsaktiviteter</strong> &ndash; I enlighet med g&auml;llande lagar och baserat p&aring; den r&auml;ttsliga grunden legitimt intresseeller ditt samtycke, kan Vi &auml;ven anv&auml;nda dina personuppgifter f&ouml;r att skicka marknadsf&ouml;ringsmaterial och meddelanden till dig via e-post eller textmeddelanden.</p>
                        <p>Vi str&auml;var efter att erbjuda dig den h&ouml;gsta niv&aring;n av kontroll &ouml;ver vilka typer av marknadsf&ouml;ringsmaterial du f&aring;r fr&aring;n Oss, eller fr&aring;n tredje parts databehandlare som behandlar dina uppgifter f&ouml;r V&aring;r r&auml;kning och f&ouml;ljer V&aring;ra instruktioner.</p>
                        <p>Du kan se och justera dina val relaterade till marknadsf&ouml;ring i V&aring;ra sekretessinst&auml;llningar p&aring; ditt anv&auml;ndarkonto. Om du vill ha mer kontroll &ouml;ver marknadsf&ouml;ringsmaterialet som Vi skickar dig ber Vi dig att kontakta V&aring;r DPO med hj&auml;lp av den angivna kontaktinformationen. I h&auml;ndelse av att du v&auml;ljer att &aring;terkalla ditt samtycke, var medveten om att det kan ta upp till 48 timmar f&ouml;r Oss att s&auml;kerst&auml;lla att &auml;ndringarna har implementerats i V&aring;rt system och i V&aring;ra marknadsf&ouml;ringspartners system. Under denna tid kan du fortfarande f&aring; mejl eller information fr&aring;n Oss.</p>
                        </li>
                        <li><strong>&Aring;terkallande av Samtycke</strong> &ndash; Du kan n&auml;r som helst &aring;terkalla ditt samtycke via sekretessinst&auml;llningarna som finns p&aring; &rsquo;Ditt konto&rsquo; p&aring; Webbplatsen. Dessutom kan du &aring;terkalla ditt samtycke fr&aring;n marknadsf&ouml;ring genom "opt-out"-knappen f&ouml;r att avsluta prenumerationen som finns i e-postmeddelandet du f&aring;r fr&aring;n Oss.</li>
                        <li><strong>V&aring;ra Aff&auml;rspartners Marknadsf&ouml;ringsaktiviteter</strong> &ndash; Vi delar aldrig dina personuppgifter med V&aring;ra aff&auml;rspartners f&ouml;r deras egna marknadsf&ouml;ringsaktiviteter utan ditt samtycke.</li>
                        <li><strong>AML och Socialt Ansvar</strong> &ndash; Kungaslottet Ltd har f&ouml;r avsikt att ge dig den absolut tryggaste spelmilj&ouml;n som Vi kan skapa. Av den anledningen kan Vi behandla vissa personuppgifter som g&auml;ller dig i en omfattning som &ouml;verstiger de lagstadgade kraven n&aring;got, men detta g&ouml;rs f&ouml;r din egen s&auml;kerhet och sinnesfrid. Vi har ett ber&auml;ttigat intresse att g&ouml;ra det, eftersom Vi k&auml;nner Oss ansvariga f&ouml;r din onlines&auml;kerhet. V&aring;ra kontroller &auml;r utformade f&ouml;r att vara minimalt invasiva och respektera dina r&auml;ttigheter och friheter som registrerad (data subject).</li>
                        </ul>
                        <p style="text-align: justify;">Dina personuppgifter kommer endast att behandlas f&ouml;r de &auml;ndam&aring;l f&ouml;r vilka de samlades in. I h&auml;ndelse av att Vi kommer beh&ouml;va bahandla det f&ouml;r n&aring;got annat &auml;ndam&aring;l kommer Vi att g&ouml;ra en kompatibilitetsbed&ouml;mning f&ouml;r att s&auml;kerhetst&auml;lla att det nya &auml;ndam&aring;let &auml;r f&ouml;renligt med det ursprungliga syftet f&ouml;r vilket uppgifterna samlades in. Vi kommer d&aring; att informera dig om det nya syftet och f&ouml;rse dig med all n&ouml;dv&auml;ndig information. Skulle det nya syftet inte vara f&ouml;renligt med det ursprungliga syftet, kommer Vi att be om ditt samtycke.</p>
                        <p id="delning-av-data" style="text-align: justify;"><strong>3. DELNING AV DATA</strong></p>
                        <p style="text-align: justify;">P&aring; grund av V&aring;r tj&auml;nsts natur kan Vi beh&ouml;va dela dina personuppgifter med ett antal p&aring;litliga tredje parter f&ouml;r att kunna behandla dina uppgifter enligt beskrivningen i avsnitt 2 i denna Integritetspolicy. Dessa tredje parter inkluderar:</p>
                        <div id="bold-list"><ol type="i">
                        <li><strong>Spelleverant&ouml;rer</strong> - Ibland kommer V&aring;ra spelleverant&ouml;rer att beh&ouml;va tillg&aring;ng till utvalda data (som anv&auml;ndarnamn och IP-adress) f&ouml;r att kunna f&ouml;rse Oss med de spel du spelar p&aring; V&aring;r Webbplats.</li>
                        <li><strong>Betalningsleverant&ouml;rer och n&auml;rst&aring;ende tj&auml;nsteleverant&ouml;rer</strong> - P&aring; samma s&auml;tt kan Vi komma att dela en del av din personliga information med den betalningsleverant&ouml;r du anv&auml;nder f&ouml;r att g&ouml;ra ins&auml;ttningar och uttag p&aring; V&aring;r Webbplats.</li>
                        <li><strong>Marknadsf&ouml;ringspartner</strong> - Genom att godk&auml;nna ("Opt in") att ta emot marknadsf&ouml;rings- och reklammaterial fr&aring;n Oss, godk&auml;nner du att din kontaktinformation, s&aring;som din e-post, telefonnummer eller postadress, kommer att delas med V&aring;ra marknadsf&ouml;ringspartners som kommer att ansvara f&ouml;r att skicka materialet till dig.</li>
                        <li><strong>Regerings- eller regleringsmyndigheter</strong> - Vi kan, om n&ouml;dv&auml;ndigt eller till&aring;tet enligt lag, l&auml;mna dina personuppgifter till brottsbek&auml;mpande institutioner, myndigheter eller tillsynsorganisationer, domstolar eller andra offentliga myndigheter. Vi str&auml;var efter att h&aring;lla V&aring;ra kunder informerade om juridiska krav g&auml;llande deras personuppgifter, s&aring;vida det inte f&ouml;rhindras av lagstiftning, domstolar eller n&ouml;dsituationer. &Auml;ven om Vi f&ouml;rbeh&aring;ller Oss r&auml;tten att bestrida f&ouml;rfr&aring;gningar som Vi anser &auml;r oproportionerliga, otydliga eller saknar l&auml;mplig auktoritet, kan Vi inte garantera att Vi kommer ifr&aring;gas&auml;tta varje beg&auml;ran.</li>
                        <li><strong>Klient kommunikationsprogramvara</strong> - Vi anv&auml;nder en programvara fr&aring;n tredje part f&ouml;r att underl&auml;tta kommunikationen med dig. Denna programvara g&ouml;r det m&ouml;jligt f&ouml;r Oss att skicka e-postmeddelanden och interagera med dig via livechatt om du har n&aring;gra fr&aring;gor.</li>
                        <li><strong>AML- och bedr&auml;geribek&auml;mpningsverktyg</strong> - Vi anv&auml;nder programvara fr&aring;n en tredje part f&ouml;r att utf&ouml;ra vissa bedr&auml;geribek&auml;mpningar och f&ouml;rfalskningar som kr&auml;vs f&ouml;r att uppfylla V&aring;ra r&auml;ttsliga skyldigheter i det h&auml;r avseendet.</li>
                        <li><strong>Grupp av f&ouml;retag eller f&ouml;retag</strong> &ndash; N&auml;r Vi &ouml;verf&ouml;r personuppgifter utanf&ouml;r EU/EES implementerar Vi adekvata tekniska och organisatoriska &aring;tg&auml;rder f&ouml;r att h&aring;lla dina personuppgifter s&auml;kra.</li>
                        </ol></div>
                        <p style="text-align: justify;">Vi har &aring;tagit Oss att s&auml;kerst&auml;lla att alla tredje parter som har tillg&aring;ng till din personliga information m&aring;ste respektera s&auml;kerheten f&ouml;r din personliga information och att behandla den lagligt och i enlighet med V&aring;r Integritetspolicy och strikta avtalsbest&auml;mmelser. Vi till&aring;ter inte tredje parts tj&auml;nsteleverant&ouml;rer att anv&auml;nda dina personuppgifter f&ouml;r sina egna syften. Vi kan dock komma att l&auml;mna ut dina personuppgifter till andra personuppgiftsansvariga, s&aring;som statliga och tillsynsmyndigheter, som kan beg&auml;ra dina personuppgifter f&ouml;r att fullg&ouml;ra sina offentliga uppgifter. Detta &auml;r ocks&aring; ett krav f&ouml;r att Vi ska uppfylla V&aring;ra juridiska skyldigheter och f&ouml;r rapportering, efterlevnad, revision och utrednings&auml;ndam&aring;l.</p>
                        <p style="text-align: justify;">Om personuppgifter delas med tredje parts databehandlare enligt GDPR kommer det endast att ske f&ouml;r specifika &auml;ndam&aring;l och i enlighet med V&aring;ra instruktioner som personuppgiftsansvariga. Tredje part har endast beh&ouml;righet att anv&auml;nda personuppgifter i den utstr&auml;ckning som Vi har r&auml;tt till. Det &auml;r alltid V&aring;rt m&aring;l att endast dela viktig information med tj&auml;nsteleverant&ouml;rer f&ouml;r att utf&ouml;ra bearbetningsaktiviteter enligt V&aring;ra instruktioner.</p>
                        <p id="gemensamt-personuppgiftsansvariga" style="text-align: justify;"><strong>4. GEMENSAMT PERSONUPPGIFTSANSVARIGA</strong></p>
                        <p style="text-align: justify;">F&ouml;retaget kan agera som en gemensam personuppgiftsansvarig n&auml;r det, tillsammans med en eller flera organisationer, gemensamt best&auml;mmer syftet och medlen f&ouml;r behandling av personuppgifter. I s&aring;dana fall ska de gemensamma kontrollanterna ing&aring; ett gemensamt kontrollorgan (joint-controllership) som anger varje parts skyldigheter och ansvar, och du kommer att informeras om detta avtal i god tid.</p>
                        <p id="overforing-av-personuppgifter" style="text-align: justify;"><strong>5. &Ouml;VERF&Ouml;RING AV PERSONUPPGIFTER UTANF&Ouml;R EUROPEISKA EKONOMISKA SAMARBETSOMR&Aring;DET (&rdquo;EES&rdquo;)</strong></p>
                        <p style="text-align: justify;">N&aring;gra av de tj&auml;nster som beskrivs i paragraf 3, h&auml;r ovan, kan vara baserade i l&auml;nder som inte ing&aring;r i europeiska ekonomiska samarbetsomr&aring;det (&rdquo;EES&rdquo;). Det kan inneb&auml;ra att dina uppgifter kan komma att behandlas p&aring; en plats utanf&ouml;r EES. N&auml;rhelst en &ouml;verf&ouml;ring av dina personuppgifter g&ouml;rs till en databehandlare eller en personuppgiftsansvarig baserad utanf&ouml;r EES, s&auml;kerst&auml;ller Vi alltid att dina uppgifter skyddas p&aring; samma s&auml;tt som de stannar inom EES. F&ouml;r att s&auml;kerst&auml;lla skyddet av dina uppgifter kommer Vi att implementera minst en av l&auml;mpliga skydds&aring;tg&auml;rder som fastst&auml;lls av EU-kommissionen:</p>
                        <div id="bold-list"><ol type="i">
                        <li><strong>Adekvat grund</strong> - Vi fastst&auml;ller att Vi &ouml;verf&ouml;r dina personuppgifter till l&auml;nder som tillhandah&aring;ller en adekvat skyddsniv&aring; i &ouml;verst&auml;mmelse med den europeiska kommissionen.</li>
                        <li><strong>Standardparagraf</strong> - Om dataprocessorn eller den som &auml;r personuppgiftsansvarig inte &auml;r baserad i ett land som omfattas av adekvat beslut om tillr&auml;cklighet. Kan Vi anv&auml;nda s&auml;rskilda kontrakt, k&auml;nda som standardavtalsparagrafer, vilka &auml;r modellavtal godk&auml;nda av europeiska kommissionen. S&aring;dana avtal s&auml;kerst&auml;ller ocks&aring; att personuppgifterna ges samma skydd som f&ouml;rfogas i EES. I enlighet med EU-domstolens Schrems II r&auml;ttspraxis kommer Vi att implementera kompletterane &aring;tg&auml;rder (tekniska, kontraktuella eller organisatoriska), ut&ouml;ver standardavtalsklausulerna, vid behov och/eller d&auml;r det &auml;r relevant.</li>
                        </ol></div>
                        <p style="text-align: justify;"><!--6--></p>
                        <p id="automatiserat-beslutsfattande" style="text-align: justify;"><strong>6. PROFILERING OCH AUTOMATISERAT BESLUTSFATTANDE</strong></p>
                        <p style="text-align: justify;">I vissa fall anv&auml;nds automatiserade system f&ouml;r att underl&auml;tta genereringen av beslut baserade p&aring; personlig information. Detta tillv&auml;gag&aring;ngss&auml;tt g&ouml;r det m&ouml;jligt f&ouml;r Oss att effektivisera beslutsprocessen och s&auml;kerst&auml;lla att V&aring;ra beslut &auml;r aktuella, opartiska, effektiva och korrekta. D&auml;rf&ouml;r kan implementeringen av automatiserat beslutsfattande p&aring;verka de produkter, tj&auml;nster eller funktioner som Vi kan erbjuda dig i nutid eller framtid, s&aring;v&auml;l som kapaciteten att anv&auml;nda V&aring;ra tj&auml;nster.</p>
                        <p style="text-align: justify;"><strong>Vi kan anv&auml;nda automatiskt beslutsfattande i f&ouml;ljande situationer:</strong></p>
                        <ul>
                        <li><strong>&Ouml;ppna konto:</strong> N&auml;r du &ouml;ppnar ett konto hos Oss kontrollerar Vi att produkten eller tj&auml;nsten &auml;r relevant f&ouml;r dig, baserat p&aring; V&aring;r kunskap. Dessutom verifierar Vi att du uppfyller de villkor som kr&auml;vs f&ouml;r att &ouml;ppna ett konto i enlighet med V&aring;r policy f&ouml;r kundacceptans. Detta kan innefatta att verifiera den s&ouml;kandes &aring;lder, bos&auml;ttning, nationalitet eller ekonomiska st&auml;llning.</li>
                        <li><strong>Bedr&auml;geriuppt&auml;ckt:</strong> Vi anv&auml;nder din personliga information f&ouml;r att hj&auml;lpa Oss att best&auml;mma och uppt&auml;cka om ditt konto kan komma att anv&auml;ndas f&ouml;r bedr&auml;geri eller penningtv&auml;tt. Om Vi tror att det finns risk f&ouml;r bedr&auml;geri kan Vi blockera eller st&auml;nga av kontot.</li>
                        <li><strong>F&ouml;r att bed&ouml;ma vadslagningsrisken:</strong> Ett automatiserat system kan anv&auml;ndas f&ouml;r att utv&auml;rdera den associerade risken med ditt spel i enlighet med V&aring;ra spelregler. Detta system anv&auml;nds av m&aring;nga speloperat&ouml;rer f&ouml;r att hj&auml;lpa dem att fatta r&auml;ttvisa och v&auml;lgrundade beslut om vadslagning. Spelpo&auml;ng tar h&auml;nsyn till information som tillhandah&aring;lls av kunden vid registreringen och under hela anv&auml;ndningen av V&aring;ra tj&auml;nster.</li>
                        <li><strong>Att bed&ouml;ma AML- och RG-risk:</strong> Automatisk profilriskbed&ouml;mning inkluderar po&auml;ngs&auml;ttning av flera riskfaktorer f&ouml;r varje kundkonto. De riskfaktorer som systemet h&aring;ller reda p&aring; po&auml;ngs&auml;tts och medelv&auml;rdesbildas f&ouml;r att skapa en profilriskpo&auml;ng och klassificering.</li>
                        </ul>
                        <p style="text-align: justify;">Baserat p&aring; denna information fattar Vi ett opartiskt och informerat beslut om huruvida kunden faller inom V&aring;r riskaptit och/eller kommer att forts&auml;tta med V&aring;rt aff&auml;rsavtal, i enlighet med V&aring;ra interna policyer och juridiska ansvar.</p>
                        <p style="text-align: justify;">Det &auml;r din r&auml;tt att inte bli f&ouml;rem&aring;l f&ouml;r ett beslut, inklusive profilering, n&auml;r det baseras p&aring; automatiserad behandling av dina personuppgifter, och det har en r&auml;ttslig verkan eller en liknande betydande effekt p&aring; dig.</p>
                        <p style="text-align: justify;">Observera att r&auml;tten inte g&auml;ller n&auml;r behandlingen &auml;r:</p>
                        <ul>
                        <li>n&ouml;dv&auml;ndig f&ouml;r att ing&aring; eller fullg&ouml;ra ett avtal med dig; eller</li>
                        <li>godk&auml;nd enligt lag; eller</li>
                        <li>baserat p&aring; ditt uttryckliga samtycke.</li>
                        </ul>
                        <p style="text-align: justify;">Om du har n&aring;gra fr&aring;gor eller funderingar ang&aring;ende detta avsnitt, v&auml;nligen skicka dem skriftligen till V&aring;rt dataskyddsombud.</p>
                        <p style="text-align: justify;"><!--7--></p>
                        <p id="datasakerhetsatgarder" style="text-align: justify;"><strong>7. DATAS&Auml;KERHETS&Aring;TG&Auml;RDER</strong></p>
                        <p style="text-align: justify;">F&ouml;retaget str&auml;var alltid efter att dina uppgifter &auml;r s&auml;kra, s&aring;v&auml;l i V&aring;ra h&auml;nder som i h&auml;nderna p&aring; n&aring;gon tredje part som Vi kan ha l&auml;mnat ut din personliga information till.</p>
                        <p style="text-align: justify;">Internt har Vi implementerat ett antal tekniska, avtalsenliga och organisatoriska &aring;tg&auml;rder f&ouml;r att s&auml;kerst&auml;lla att dina personuppgifter inte av misstag g&aring;r f&ouml;rlorade, anv&auml;nds, kommer &aring;t, &auml;ndras eller avsl&ouml;jas p&aring; ett otill&aring;tet s&auml;tt. Detta skydd ska f&ouml;lja en djupg&aring;ende f&ouml;rsvarsstrategi genom kontinuerliga investeringar i teknik, processer och andra resurser i linje med b&auml;sta branschpraxis. Vi s&auml;kerst&auml;ller ocks&aring; att tillg&aring;ngen till dina personuppgifter best&auml;ms utifr&aring;n en "need-to-know"-basis, vilket inneb&auml;r att endast individer som har ett direkt behov av att k&auml;nna till din personliga information har tillg&aring;ng till den. Dessutom &auml;r alla som har tillg&aring;ng till dina personuppgifter bundna av tystnadsplikt. Vi har ocks&aring; rutiner f&ouml;r att hantera eventuella misst&auml;nkta eller faktiska intr&aring;ng i personuppgifter. Vi kommer att informera b&aring;de dig som "registrerad" och den tillsynsmyndighet som ber&ouml;rs av s&aring;dana datas&auml;kerhetsintr&aring;ng n&auml;r det &auml;r juridiskt n&ouml;dv&auml;ndigt att g&ouml;ra det, och Vi kommer att f&ouml;ra en lista &ouml;ver s&aring;dana intr&aring;ng.</p>
                        <p style="text-align: justify;">Det &auml;r av yttersta vikt att V&aring;ra kunder f&ouml;rblir vaksamma och f&ouml;ljer de vanliga s&auml;kerhetsf&ouml;reskrifterna f&ouml;r informationss&auml;kerhet. D&auml;rf&ouml;r:</p>
                        <ul>
                        <li>Det &auml;r absolut n&ouml;dv&auml;ndigt att du aldrig avsl&ouml;jar dina kontouppgifter, personlig information och annan konfidentiell kontodata.</li>
                        <li>Det &auml;r avg&ouml;rande att du aldrig l&aring;ter n&aring;gon ta kontroll &ouml;ver din dator eller annan enhet.</li>
                        <li>Det &auml;r v&auml;sentligt att du aldrig utg&aring;r fr&aring;n att ett mobilsamtal, mejl eller sms &auml;r &auml;kta. Kontrollera alltid via en p&aring;litlig k&auml;lla innan du utf&ouml;r n&aring;gon &aring;tg&auml;rd.</li>
                        </ul>
                        <p style="text-align: justify;"><!--8--></p>
                        <p id="data-retention" style="text-align: justify;"><strong>8. DATA RETENTION</strong></p>
                        <p style="text-align: justify;">F&ouml;retaget skall endast beh&aring;lla dina personuppgifter s&aring; l&auml;nge som n&ouml;dv&auml;ndigt f&ouml;r att uppfylla syftet f&ouml;r vilket de samlades in. Vissa syften kan innefatta att uppfylla alla juridiska, redovisnings- eller rapporteringskrav.</p>
                        <p style="text-align: justify;">N&auml;r Vi best&auml;mmer hur l&aring;ng en lagringsperiod som &auml;r l&auml;mplig f&ouml;r dina personuppgifter, tar Vi h&auml;nsyn till olika faktorer, s&aring;som personuppgifternas art och k&auml;nslighet, den potentiella risken f&ouml;r obeh&ouml;rig anv&auml;ndning eller avsl&ouml;jande av s&aring;dana uppgifter, syftet som Vi samlade in och behandlade. s&aring;dan data f&ouml;r och till&auml;mpliga lagar och/eller juridiska krav som &aring;l&auml;ggs Oss.</p>
                        <p style="text-align: justify;">Till exempel, i Malta, &auml;r Vi skyldiga enligt lag mot penningtv&auml;tt att lagra dina personuppgifter under en period av minst fem (5) &aring;r efter att V&aring;r aff&auml;rsrelation har avslutats. I Storbritannien, i enlighet med den Ordinary code provision 3.5.4 av LCCP, ska din personliga bevaras f&ouml;r AML-&auml;ndam&aring;l i sju (7) &aring;r. Upps&auml;gningen av V&aring;r aff&auml;rsrelation tr&auml;der i kraft den dag ditt konto hos Oss st&auml;ngs officiellt.</p>
                        <p style="text-align: justify;">Ett annat exempel, redovisnings- och finansiella dokument b&ouml;r bevaras i tio (10) &aring;r i enlighet med aktiebolagslagen - artikel 163(5) (Malta).</p>
                        <p style="text-align: justify;">Var v&auml;nlig kontakta V&aring;r DPO via kontaktuppgifterna som angett ovan f&ouml;r mer information om V&aring;r retentionsperiod.</p>
                        <p style="text-align: justify;"><!--9--></p>
                        <p id="dina-rattigheter" style="text-align: justify;"><strong>9. DINA R&Auml;TTIGHETER</strong></p>
                        <div id="bold-list">
                        <p style="text-align: justify;">Dataskyddsf&ouml;rordningen ger dig som registrerad vissa r&auml;ttigheter under vissa omst&auml;ndigheter. I enlighet med lagen har du r&auml;tt att:</p>
                        <ol type="i">
                        <li><strong>Beg&auml;ra tillg&aring;ng till dina personuppgifter</strong> - Detta inneb&auml;r att du har r&auml;tt att kostnadsfritt beg&auml;ra en kopia av de personuppgifter Vi har om dig. Vi kommer att g&ouml;ra V&aring;rt yttersta f&ouml;r att svara p&aring; alla legitima f&ouml;rfr&aring;gningar inom en (1) m&aring;nads tidsram fr&aring;n inl&auml;mnandet av en beg&auml;ran. Om din f&ouml;rfr&aring;gan &auml;r s&auml;rskilt komplex, eller om du har gjort flera f&ouml;rfr&aring;gningar under en viss period, kan det ta lite l&auml;ngre tid f&ouml;r Oss. I ett s&aring;dant fall kommer Vi att meddela dig om denna f&ouml;rl&auml;ngning som, i enlighet med GDPR, kan g&auml;lla ytterligare tv&aring; (2) m&aring;nader efter den f&ouml;rsta enm&aring;nadsperioden.</li>
                        <li><strong>Beg&auml;ra korrigering av dina personuppgifter</strong> - Detta inneb&auml;r att om n&aring;gra av de personliga uppgifter Vi har g&auml;llande dig &auml;r ofullst&auml;ndiga eller felaktiga, s&aring; har du r&auml;tt att f&aring; detta korrigerat. T&auml;nk p&aring; att du kan beh&ouml;va tillhandah&aring;lla bevis och dokumentation (t.ex. ID-dokumentation eller adressbevis) f&ouml;r att styrka din beg&auml;ran.</li>
                        <li><strong>Beg&auml;ra radering av dina personuppgifter</strong> - Detta inneb&auml;r att du kan beg&auml;ra radering av dina personuppgifter n&auml;r Vi inte l&auml;ngre har en legitim anledning att forts&auml;tta bearbeta eller beh&aring;lla dem. V&auml;nligen var medveten om att denna r&auml;ttighet inte &auml;r absolut, vilket betyder att Vi inte kan tillgodose din f&ouml;rfr&aring;gan i de fall Vi enligt lag &auml;r skyldiga att beh&aring;lla uppgifterna, eller n&auml;r Vi har anledning att beh&aring;lla uppgifter som &auml;r n&ouml;dv&auml;ndiga d&aring; Vi ska f&ouml;rsvara Oss i en r&auml;ttslig tvist.</li>
                        <li><strong>Protestera mot behandlingen av dina personuppgifter</strong> d&auml;r Vi f&ouml;rlitar Oss p&aring; V&aring;rt legitima intresse (eller ett tredje parts legitima intresse) f&ouml;r att behandla dina personuppgifter och du k&auml;nner att Vi behandlar dina uppgifter p&aring; ett s&aring;dant s&auml;tt att det kr&auml;nker dina grundl&auml;ggande r&auml;ttigheter och friheter. I vissa fall kan Vi dock kunna visa att Vi har en &ouml;vertygande legitim grund att behandla dina uppgifter som kan &aring;sidos&auml;tta dina r&auml;ttigheter och friheter. Du kan l&auml;mna in dina inv&auml;ndningar mot behandlingen av dina personuppgifter p&aring; grund av de ovan n&auml;mnda legitima f&ouml;retagsintressena genom att kontakta V&aring;r DPO.</li>
                        <li><strong>Beg&auml;ra begr&auml;nsning av hanteringen av dina personuppgifter</strong>- Du kan be Oss att tillf&auml;lligt avbryta behandlingen av dina personuppgifter i n&aring;got av f&ouml;ljande fall:
                        <div id="normal-list"><ol type="a">
                        <li>n&auml;r du vill att Vi ska fastst&auml;lla riktigheten av dina uppgifter;</li>
                        <li>n&auml;r V&aring;r anv&auml;ndning av dina uppgifter inte &auml;r i enlighet med lag, men du vill inte att Vi ska radera den;</li>
                        <li>n&auml;r du beh&ouml;ver pss f&ouml;r att lagra dina personuppgifter, &auml;ven d&aring; Vi sj&auml;lva inte l&auml;ngre beh&ouml;ver uppgifterna, eller;</li>
                        <li>n&auml;r du har inv&auml;nt mot anv&auml;ndningen av dina uppgifter, men Vi m&aring;ste verifiera om Vi har &ouml;vertygande legitima sk&auml;l att anv&auml;nda dem.</li>
                        </ol></div>
                        </li>
                        <li><strong>Beg&auml;ra &ouml;verf&ouml;ring av dina personuppgifter (dvs. data&ouml;verf&ouml;rbarhet)</strong> &ndash; Detta inneb&auml;r att du kan beg&auml;ra att Vi &ouml;verf&ouml;r vissa uppgifter om dig som Vi har behandlat till en tredje part. Denna r&auml;ttighet g&auml;ller endast data som f&ouml;rv&auml;rvats via automatiserade k&auml;llor som du ursprungligen gav Oss samtycke att anv&auml;nda, eller d&auml;r Vi anv&auml;nde dina uppgifterna f&ouml;r att utf&ouml;ra V&aring;ra f&ouml;rpliktelser enligt ett avtal med dig.</li>
                        <li><strong>N&auml;r som helst &aring;terkalla ditt samtycke d&auml;r Vi &aring;beropat samtycke att behandla uppgifterna</strong> &ndash; Upps&auml;gning eller &aring;terkallande av ditt samtycke p&aring;verkar inte lagligheten av den behandling som Vi har utf&ouml;rt f&ouml;rr&auml;n den tidpunkt d&aring; du &aring;terkallade ditt samtycke. &Aring;terkallande av ditt samtycke inneb&auml;r att du i framtiden inte l&auml;ngre vill f&aring; dina uppgifter behandlade p&aring; samma s&auml;tt. Detta inneb&auml;r att du inte l&auml;ngre kan ge Oss till&aring;telse att tillhandah&aring;lla vissa tj&auml;nster (t.ex. marknadsf&ouml;ring). Om du n&auml;r som helst skulle vilja dra tillbaka ditt samtycke kan du g&ouml;ra det via sekretesssektionen som finns p&aring; "Ditt konto" p&aring; Webbplatsen. Dessutom kan du &aring;terkalla ditt samtycke fr&aring;n marknadsf&ouml;ring genom att klicka p&aring; knappen f&ouml;r att avbryta prenumeration som finns i e-postmeddelandet du f&aring;r fr&aring;n Oss.</li>
                        <li>
                        <p><strong>Skicka ett klagom&aring;l till en tillsynsmyndighet</strong> &ndash;</p>
                        <p>Du har ocks&aring; r&auml;tten att, n&auml;r som helst, l&auml;mna in klagom&aring;l till tillsynsmyndigheten i Malta, Informations- och dataskyddskommissionen (&rdquo;IDPC&rdquo;), eller med respektive dataskyddsmyndighet i det land d&auml;r du &auml;r bosatt som anges nedan:</p>
                        <ul style="list-style-type: disc;">
                        <li>Integritetsskyddsmyndigheten i Sverige;</li>
                        <li>Alla andra till&auml;mpliga dataskyddsmyndigheter i det land d&auml;r du &auml;r bosatt.</li>
                        </ul>
                        <p>Vi uppskattar dock m&ouml;jligheten att handl&auml;gga dina &auml;renden innan du kontaktar respektive myndighet, d&auml;rf&ouml;r ber Vi dig &ouml;dmjukast att i f&ouml;rsta hand kontakta Oss.</p>
                        <p>Vi kan beh&ouml;va beg&auml;ra specifik information fr&aring;n dig f&ouml;r att hj&auml;lpa Oss att bekr&auml;fta din identitet och s&auml;kerst&auml;lla din r&auml;tt att f&aring; tillg&aring;ng till dina personuppgifter (eller att ut&ouml;va n&aring;gon av dina andra r&auml;ttigheter). Detta &auml;r en s&auml;kerhets&aring;tg&auml;rd f&ouml;r att s&auml;kerst&auml;lla att personuppgifter inte l&auml;mnas ut till n&aring;gon person som inte har r&auml;tt att ta emot dem. Vi kan ocks&aring; kontakta dig f&ouml;r att be dig om ytterligare information i samband med din beg&auml;ran f&ouml;r att p&aring;skynda V&aring;rt svar. Du kan ut&ouml;va dina r&auml;ttigheter genom f&ouml;ljande kanaler:</p>
                        <ul style="list-style-type: disc;">
                        <li>V&aring;r kundsupport <span class="pointer" style="color: #112f7c;" onclick="openZendesk()"><span style="text-decoration: underline;">livechatt</span></span>.</li>
                        <li>Genom att skicka en beg&auml;ran via e-post: <a href="mailto:support@dbet.com">support@dbet.com</a>.</li>
                        <li>Genom att kontakta V&aring;r DPO: <a href="mailto:dpo@dbet.com">dpo@dbet.com</a>.</li>
                        </ul>
                        </li>
                        </ol></div>
                        <p style="text-align: justify;"><!--10--></p>
                        <p id="andringar" style="text-align: justify;"><strong>10. &Auml;NDRINGAR TILL INTEGRITETSPOLICY</strong></p>
                        <p style="text-align: justify;">Vi f&ouml;rbeh&aring;ller Oss r&auml;tten att, efter eget gottfinnande, &auml;ndra, modifiera, l&auml;gga till och/eller ta bort delar av denna integritetspolicy n&auml;r som helst. Om du &auml;r en befintlig kund som Vi har ett avtalsf&ouml;rh&aring;llande med kommer du att informeras om alla &auml;ndringar som g&ouml;rs i denna integritetspolicy.</p>'
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand === 'dbet') {
            foreach ($this->data as $row) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', $row['language'])
                    ->update(['value' => $row['value']]);
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'dbet') {
            foreach ($this->data as $row) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $row['alias'])
                    ->where('language', $row['language'])
                    ->update(['value' => '']);
            }
        }
    }
}

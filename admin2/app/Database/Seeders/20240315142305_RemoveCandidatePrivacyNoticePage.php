<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class RemoveCandidatePrivacyNoticePage extends Migration
{
    protected $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }


    /**
     * Do the migration
     */
    public function up()
    {
        $page = $this->connection
            ->table('pages')
            ->where('alias', 'candidate-privacy-notice')
            ->first();

        $this->connection
            ->table('boxes')
            ->where('page_id', $page->page_id)
            ->delete();

        $this->connection
            ->table('localized_strings')
            ->where('alias', 'simple.1274.html')
            ->delete();

        $this->connection
            ->table('pages')
            ->where('alias', 'candidate-privacy-notice')
            ->delete();
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table('pages')
            ->insert(
                [
                    'parent_id'   => 0,
                    'alias'       => 'candidate-privacy-notice',
                    'filename'    => 'diamondbet/generic.php',
                    'cached_path' => '/candidate-privacy-notice',
                ]
            );

        $page = $this->connection
            ->table('pages')
            ->where('alias', 'candidate-privacy-notice')
            ->first();

        $this->connection
            ->table('boxes')
            ->insert(
                [
                    'container' => 'full',
                    'box_class' => 'SimpleExpandableBox',
                    'priority'  => 0,
                    'page_id'   => $page->page_id,
                ]
            );

        $this->connection
            ->table('localized_strings')
            ->insert(
                [
                    'alias'    => 'simple.1274.html',
                    'language' => 'en',
                    'value'    => '<h1 style="text-align: justify;"><span style="color: #df9f24;">We value your privacy&nbsp;</span></h1>
                        <h2><span style="color: #888888;">Videoslots Candidate Privacy Notice&nbsp;</span></h2>
                        <h2 style="padding-left: 30px;"><span style="color: #df9f24;">A. Introduction: Who are we &amp; why are you reading this?</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Videoslots is the brainchild of Alexander Stevendahl, an expert poker player and a star affiliate. Our site was born online in 2011 where a handful of entrepreneurs who had a knack for entertaining others helped build our second home into what it is today. Our unique product offering and experience we offer our customers is what enables our brand to shine.&nbsp;&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Videoslots Ltd (C49090), with registered address at The Space, Level 2 &amp; 3, Alfred Craig Street, Pieta, PTA 1320 Malta, is the Data Controller. As Data Controller, we are responsible for deciding how and why we process your personal data. You are reading this privacy notice because you are applying for work with us. This document will explain how and why we use your personal data for the purposes of the recruitment process. This notice will be updated from time-to-time if any of our processes change.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Your privacy is your right. We take your privacy very seriously which is why we have created this notice.&nbsp;</span></p>
                        <p><span style="color: #888888;">Your personal data will be used exclusively for recruitment purposes in the manner as described in this notice, and at all times in accordance with the applicable laws.</span></p>
                        <h2 style="padding-left: 30px;"><span style="color: #df9f24;">B. Getting to know you &hellip;&nbsp;</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">It&rsquo;s quite simple really! You can either apply directly through our site, or a recruitment agency submits your application on your behalf.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">We also get the opportunity to get to know you better through our meetings with you, and through any references, you may give us.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Since we are an online casino, we are bound by quite a few laws. This means that, depending on the role you are applying for, we may be bound by law or by a licence condition to carry out background checks on you. If this is the case, we will be sure to let you know that the role requires such checks to be carried out!&nbsp;</span></p>
                        <h2 style="padding-left: 30px;"><span style="color: #df9f24;">C. What do we know about you?&nbsp;</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">In order to consider you for a vacancy we need an application. A job application typically consists of a Curriculum Vitae (C.V.) and covering letter. In addition, you may be applying for a role which would require you to share further achievements/skills with us, such as a portfolio work.&nbsp; Should we require anything in addition to your C.V. and covering letter, we will be sure to state this clearly in the job advert.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Once your application is received through the channels mentioned in Section B above, we will collect, process and store all information you provided us with in your CV and covering letter in the manner as set out within this notice. In most cases, for your application to be complete, we would need to know the following about you:&nbsp;</span></p>
                        <ul>
                        <li><span style="color: #888888;">We need to get in touch with you right? Please include your contact details such as your mobile number and personal email address;&nbsp;</span></li>
                        <li><span style="color: #888888;">Your educational and career history (that is relevant to the role);</span></li>
                        <li><span style="color: #888888;">Your motivation for applying. We would also love to hear why you are the best candidate for the role;</span></li>
                        <li><span style="color: #888888;">Any additional requirements that we specify clearly in our advert as outlined in the first paragraph to this Section C.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">We only require the above information to process your application to determine your suitability for the role you wish to apply for (Section D will explain just how we do this!). If you do not provide us with the information we need, we will not be able to consider you for the position &ndash; and that would make us very sad! ☹&nbsp;</span></p>
                        <p><span style="color: #888888;">In your application, you may choose to provide us with additional information which:</span></p>
                        <ul>
                        <li><span style="color: #888888;">Could be considered as biometric data (a fancy word for &lsquo;a photo of yourself&rsquo;);</span></li>
                        <li><span style="color: #888888;">Could give us an insight on your racial or ethnic origin;</span></li>
                        <li><span style="color: #888888;">Could reveal your religious and philosophical beliefs;</span></li>
                        <li><span style="color: #888888;">Would tell us whether you are a member of a trade union or not;</span></li>
                        <li><span style="color: #888888;">Could reveal any information related to your genetics or your health;</span></li>
                        <li><span style="color: #888888;">Could give us an indication of your sexual orientation;</span></li>
                        <li><span style="color: #888888;">Your marital status and whether you have children;</span></li>
                        <li><span style="color: #888888;">Links to your personal social media profiles.</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">We do not require any of the above information or any other sensitive data for the consideration of your application. However, since your CV or application might accidentally indicate any of the above, if you do provide us with such information we promise not to use it to make recruitment decisions &ndash; you have our word!</span></p>
                        <h2 style="padding-left: 30px;"><span style="color: #df9f24;">D. What do we need your personal data for?</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">We need to process the personal data you provide us with in your application, as well as what is gathered during the full recruitment process, to make an informed and fair decision. We will use your personal data to:</span></p>
                        <ul>
                        <li><span style="color: #888888;">Assess whether you meet the basic requirements outlined in the Job Description;&nbsp;</span></li>
                        <li><span style="color: #888888;">Assess your knowledge, skills and competence;</span></li>
                        <li><span style="color: #888888;">Get in touch with you through the contact details you provide us with to keep you updated on the process;</span></li>
                        <li><span style="color: #888888;">Contact any named references or previous employers;</span></li>
                        <li><span style="color: #888888;">Carry out any background checks (if we are required to do so by law or to comply with a licence requirement).</span></li>
                        </ul>
                        <p style="text-align: justify;"><span style="color: #888888;">We try to keep things exciting, so no two application procedures are the same. The recruitment procedure we use depends on the job you have applied for. After your personal information in your application form is reviewed by the Hiring Team (FYI &ndash; the hiring team consists of our Human Resources team and the hiring manager), a decision is made whether to proceed with an interview. This decision is based on whether your profile and the job requirements are a strong match. Most of the time, we start off with a short phone call to get to know you better.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">If you get selected for a face-to-face interview, we will collect additional information throughout the interview by asking you questions related to your eligibility for the role and your motivation for applying.&nbsp;&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">The information you give throughout your interview may be noted down and such information may be used to help us make a decision.&nbsp;</span></p>
                        <p><span style="color: #888888;">Depending on the role you have applied for, you may be asked to carry out a skills assessment.&nbsp; The assessment will be used as part of the hiring process. The results of the assessment will be shared with you if you wish to see these once a selection decision has been made.&nbsp;</span></p>
                        <h2 style="padding-left: 30px;"><span style="color: #df9f24;">E. So now we know your secrets &hellip; whom do we share them with?</span></h2>
                        <h3 style="padding-left: 30px;"><span style="color: #888888;">(a)With our family</span></h3>
                        <p><span style="color: #888888;">No, we don&rsquo;t mean our real family &ndash; we mean our Videoslots family. The personal information you provide Videoslots will be accessible by a select few (but super important people) on our recruitment software tool called BambooHR. Generally, the only persons having access to your personal data at recruitment stage are our Human Resources team, the hiring manager (or any persons within that team that may need to give their input), our management team (C-level), or any managers above the hiring manager.</span></p>
                        <h3 style="padding-left: 30px;"><span style="color: #888888;">(b)Externally</span></h3>
                        <p style="padding-left: 30px;"><strong><span style="color: #888888;">(i)</span></strong><span style="color: #888888;">We use a third-party software to carry out our background checks (when these are necessary) so we may need to share some of your information with them to do this;</span></p>
                        <p style="padding-left: 30px;"><strong><span style="color: #888888;">(ii)</span></strong><span style="color: #888888;">We also use a third-party human resources software known as BambooHR &ndash; this software is cloud-based, which inevitably means that the third-party might be able to access all the information stored on this software.</span></p>
                        <p><span style="color: #888888;">However, whenever we share your personal data externally, we always make sure that your personal data is safe. That is why we always make sure to only use trusted third-parties who take appropriate measure to safeguard your personal data, and that we have agreements in place with them to make sure that your personal data is always in safe hands.</span></p>
                        <h2 style="padding-left: 30px;"><span style="color: #df9f24;">F. Protecting your data</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">Security is very important to us. We want you to feel confident that your information is safe and sound with us. We take a number of precautions and measures to ensure the security of your data. We have put in place the appropriate security measures to ensure that your personal data is not accidentally lost, used or accessed in an unauthorised way, altered or disclosed. We also limit the access to your personal data on a need-to-know basis. Anyone having access to or processing your personal data is bound by a duty of confidentiality.</span></p>
                        <h2 style="padding-left: 30px;"><span style="color: #df9f24;">G. How long do we keep your data for?</span></h2>
                        <p style="text-align: justify;"><span style="color: #888888;">If the recruitment process doesn&rsquo;t turn out as we&rsquo;d hoped, we will only keep your information for four months after we communicate to you our decision on whether to employ you &ndash; we are legally justified in doing so.&nbsp;</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">We do offer you the opportunity to retain your application, on the basis that a further opportunity may arise in future.&nbsp; With your specific consent, we may keep your application for a further two months (meaning 6 months in total, in case you don&rsquo;t feel like doing the math!). Afterwards, we will irretrievably delete your application.&nbsp;</span></p>
                        <h2 style="padding-left: 30px;"><span style="color: #df9f24;">H. Do you know your rights? You should!</span></h2>
                        <p><span style="color: #888888;">Under certain circumstances, by law, you may have the right to:</span></p>
                        <ul>
                        <li style="text-align: justify;"><span style="color: #888888;">Request a copy of the personal data that we hold about you;</span></li>
                        <li style="text-align: justify;"><span style="color: #888888;">Make amends to incomplete or inaccurate information we hold about you;</span></li>
                        <li style="text-align: justify;"><span style="color: #888888;">Delete or partially remove some of your information if we no longer have a good reason to continue processing it;</span></li>
                        <li style="text-align: justify;"><span style="color: #888888;">Object to your data being processed - this is in the case that we are relying on a legitimate interest to process your data, and there is something about your particular situation which makes you want to object to processing on this ground;</span></li>
                        <li style="text-align: justify;"><span style="color: #888888;">Request the restriction of processing of your personal information &ndash; this means you may ask us to suspend the processing of your personal information, if, for instance, you want us to establish its accuracy or the reason for processing it;</span></li>
                        <li style="text-align: justify;"><span style="color: #888888;">Ask us to share your personal information with third parties (such as other potential employers);</span></li>
                        <li style="text-align: justify;"><span style="color: #888888;">Where we process your data on the basis of your consent, such as where you consent to us keeping your details for a couple of months in case a new position comes up, you may withdraw your consent at any time.</span></li>
                        </ul>
                        <p><span style="color: #888888;">If you want to exercise any of the above rights, or if you have any questions about this privacy notice, please get in touch with our Data Protection Officer on the following details:&nbsp;&nbsp;</span></p>
                        <div>&nbsp;</div>
                        <table border="1" cellspacing="0" cellpadding="0">
                        <tbody>
                        <tr>
                        <td valign="top">
                        <p><strong><span style="color: #888888;">&nbsp;Full name of legal&nbsp; entity</span></strong></p>
                        </td>
                        <td valign="top">
                        <p><strong><span style="color: #888888;">&nbsp;Postal Address</span></strong></p>
                        </td>
                        <td valign="top">
                        <p><strong><span style="color: #888888;">&nbsp;Email Address</span></strong></p>
                        </td>
                        </tr>
                        <tr>
                        <td valign="top">
                        <p><span style="color: #888888;">&nbsp;Videoslots Ltd&nbsp; C49090)</span></p>
                        </td>
                        <td valign="top">
                        <p><span style="color: #888888;">&nbsp;The Space, Level 2 &amp; 3, Alfred Craig Street, Pieta, PTA 1320 Malta</span></p>
                        </td>
                        <td valign="top">
                        <p><span style="color: #888888;">&nbsp;dpo@videoslots.com</span></p>
                        </td>
                        </tr>
                        </tbody>
                        </table>
                        <p style="text-align: justify;"><span style="color: #888888;">You also have the right to file a complaint with the Information and Data Protection Commission </span><strong><span style="color: #888888;">(&ldquo;IDPC&rdquo;)</span></strong><span style="color: #888888;"> at any time, being the main Supervisory Authority for data protection matters in Malta. However, we would truly appreciate the opportunity to address your issues before you contact the IDPC, so kindly contact us in the first instance.</span></p>
                        <p style="text-align: justify;"><span style="color: #888888;">Thanks for your interest and consideration to work with us, we value your time spent visiting our site and wish you the best of luck in your career.&nbsp;</span></p>
                        <p><strong><span style="color: #888888;">The </span><span style="color: #888888;">Videoslots&nbsp;</span><span style="color: #888888;">Team</span>&nbsp;</strong></p>
                        <div>&nbsp;</div>
                        <table border="1" cellspacing="0" cellpadding="0">
                        <tbody>
                        <tr>
                        <td valign="top">
                        <p><strong><span style="color: #888888;">Policy Version No.&nbsp;</span></strong></p>
                        </td>
                        <td valign="top">
                        <p><strong><span style="color: #888888;">Date of last update</span></strong></p>
                        </td>
                        </tr>
                        <tr>
                        <td valign="top">
                        <p><span style="color: #888888;">1.0</span></p>
                        </td>
                        <td valign="top">
                        <p><span style="color: #888888;">5. June 2018</span></p>
                        </td>
                        </tr>
                        </tbody>
                        </table>',
                ]
            );
    }
}

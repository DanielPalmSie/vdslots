<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForRG60 extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'mail.request-soi-to-increase-ndl.content' => '<p><b>Dear __FIRSTNAME__,</b></p>
                <p>We hope you are enjoying playing on our website.</p>
                <p>We have been notified that you wish to increase your Net Deposit limit you just triggered.</p>
                <p>It is strongly recommended that you set your limits to a comfortable level to safeguard your gambling and make sure you don’t spend more time or money than you initially planned.</p>
                <p>To increase your Net Deposit limit, we will need to know the new amount you wish to increase it to.
                Additionally, we are required by law and under the terms of our gambling license to verify your source of your wealth before this can be done.
                Such checks are required so that we can justify your activities and transactions at Videoslots ltd. This is not only to protect Videoslots ltd, but also you as an individual.</p>
                <p>For us to complete this process, and to avoid any further interruptions to your gaming experience, please provide one or more of the relevant documents listed below:</p>
                <ul>
                    <li>Employment income - payslip with a bank statement, documents confirming salary and/or tax returns;</li>
                    <li>Inheritance Income - a copy of the will, signed letter from the solicitor;</li>
                    <li>Investments – a copy of the portfolio statement or bank statement showing receipt of funds and investment company name;</li>
                    <li>Dispositions – legal agreements bank statements clearly showing receipt of funds;</li>
                    <li>Property - evidence of title, copies of trust deeds or signed contract of sale;</li>
                    <li>Ownership of business - audited accounts containing information on dividends;</li>
                    <li>Confirmation of your source of wealth from regulated professionals such as accountants, auditors, notaries and lawyers;</li>
                    <li>or any other type of documents which would assist us in understanding your total net worth.</li>
                </ul>
                <p>If you have difficulty obtaining any of the relevant documentation listed above, please don’t hesitate to contact us for further assistance.</p>
                <p>If sending a copy of a bank statement, this can either be an original PDF document or a colour scan / picture of the equivalent printed document.</p>
                <p>Please note that your uploaded files must be in any of the following formats:</p>
                <ul>
                    <li>File type JPG, JPEG, BMP, PNG, TIFF, or PDF</li>
                    <li>Not larger than 3MB (three megabytes) in size</li>
                    <li>Proof of address cannot be older than 3 months</li>
                    <li>All documents need to have all 4 corners visible</li>
                </ul>
                <p>What will my documents be used for?</p>
                <p>We use any customer documentation solely for the purpose of meeting our regulatory requirements. Documents provided will be held on a secure server and never disclosed to any third parties without a legal justification.</p>
                <p>How the limit works?<br />
                The NET Deposit limit triggers after you deposited over a certain amount. If you have £500 as NET Deposit Limit on your account, and you decide to first deposit £400 and then an additional £200, you will not be allowed to make further deposits that month. All deposits you have made so far will however be accessible, and you will be able to deposit again once the limit has been reset on the 1st of every month. If you would win money and withdraw, your net deposits will be reduced and if it goes down under £500 you will be able to deposit again on the same month.</p>
                <p>Best regards,<br />Support</p>',
            'mail.request-soi-to-increase-ndl.subject' => 'RG60 Document Request __USERID__',
        ]
    ];
}

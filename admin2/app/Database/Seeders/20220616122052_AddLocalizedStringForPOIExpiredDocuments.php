<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForPOIExpiredDocuments extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'restrict.msg.processing.documents.title' => 'Processing documents',
            'expired' => 'ExpiredExpired',
            'documents.expired.info' => 'Documents that have expired',
            'restrict.msg.processing.documents' => '<p>You are currently temporally restricted from requesting a withdraw,  due to documention pending approved by one of our agents. <br />Please go to the <a style="text-decoration: underline;" href="{{phive|UserHandler|getUserAccountUrl|documents}}" target="_top">Documents</a> page to review your current status.<br />Please contact our Customer Service via live chat or e-mail (<strong>support@videoslots.com</strong>) if you have any further questions.</p>'
        ]
    ];
}
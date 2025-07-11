<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringsForCookiePopup extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $data = [
        'en' => [
            'confirm' => 'Confirm',
            'necessary' => 'Necessary',
            'functional' => 'Functional',
            'analytics' => 'Analytics',
            'marketing' => 'Marketing',
            'cookie.manage' => 'Manage Cookies',
            'cookie.popup.title' => 'Cookies',
            'cookie.use' => 'We use Cookies',
            'cookie.allow.all' => 'Allow all Cookies',
            'reject.all.cookies' => 'Reject all Cookies',
            'cookie.banner.info.text' => 'Our website uses cookies to deliver content, maintain security, enable user choice, improve our site and for marketing purposes. This helps us to provide you with the best user experience when using our site.',
            'cookie.banner.necessary.text' => 'Necessary cookies also known as essential cookies, are cookies needed to enable core functionality of the website such as security, network management and accessiblity.',
            'cookie.banner.functional.text' => 'Functional cookies allow us to provide a better user experience by enhancing features and website performance, such as by remembering your log-in deatils.',
            'cookie.banner.analytics.text' => 'Analytics cookies help to provide quantative measures of visitors to our website.  These KPIs allow us to improve the usability and layout of this site for better performance and delivery of our site.',
            'cookie.banner.marketing.text' => 'Marketing cookies help us and other third parties to provide content which may be of interest to you based on your browsing history.',
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
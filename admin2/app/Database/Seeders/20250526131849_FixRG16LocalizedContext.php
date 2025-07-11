<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class FixRG16LocalizedContext extends Seeder
{
    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $new_data = [
        'en' => [
            'mail.RG16.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have resumed activity on an account that was previously self-locked. Are you sure you\'re ready to come back?</p> 
                <p>If you feel the need for any additional support, we encourage you to review our responsible gambling tools or consider taking a break. These tools are here to help you maintain a safe and enjoyable gaming experience.</p>
                <p>If you have any questions or need assistance, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
        ]
    ];

    protected array $old_data = [
        'en' => [
            'mail.RG16.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have resumed activity on an account that was previously self-locked. Are you sure you\'re ready to come back?</p> 
                <p>If you feel the need for any additional support, we encourage you to review our responsible gambling tools or consider taking a break. These tools are here to help yu maintain a safe and enjoyable gaming experience.</p>
                <p>If you have any questions or need assistance, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->new_data as $lang => $translation) {
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $lang)
                    ->update(['value' => $value]);
            }
        }
    }

    public function down()
    {
        foreach ($this->old_data as $lang => $translation) {
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $lang)
                    ->update(['value' => $value]);
            }
        }
    }
}
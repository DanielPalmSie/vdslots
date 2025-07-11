<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringForBlockedCountryPopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;
    protected array $data;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
        $this->data = [
            [
                'language' => 'en',
                'alias' => 'blocked.access.restricted',
                'value' => 'Access Restricted'
            ]
        ];
    }

    public function up()
    {
        if ($this->brand === 'kungaslottet') {
            $this->data[] =
                [
                    'language' => 'en',
                    'alias' => 'blocked.user.country.restriction',
                    'value' => '<p>We’re sorry, but users from your country are currently not allowed to log in or
                                </br> register on our site. We apologize for any inconvenience this may cause.</p>
                                <p>For further information or assistance, please contact our
                                <a class="support-link" href="/customer-service/"><strong>support team.</strong></a></p>
                                <p>Thank you for your understanding.</p>'
                ];

        } elseif ($this->brand === 'mrvegas') {
            $this->data[] =
                [
                    'language' => 'en',
                    'alias' => 'blocked.user.country.restriction',
                    'value' => '<p>We’re sorry, but users from your country are currently not allowed to log in or
                                </br> register on our site. We apologize for any inconvenience this may cause.</p>
                                <p>For further information or assistance, please contact our support team.
                                </br><a class="support-link" href="/customer-service/"><strong>support@mrvegas.com.</strong></a></p>
                                <p>Thank you for your understanding.</p>'
                ];
        } elseif ($this->brand === 'videoslots') {
            $this->data[] =
                [
                    'language' => 'en',
                    'alias' => 'blocked.user.country.restriction',
                    'value' => '<p>We’re sorry, but users from your country are currently not allowed to log in or
                                </br> register on our site. We apologize for any inconvenience this may cause.</p>
                                <p>For further information or assistance, please contact our support team.
                                </br><a class="support-link" href="/customer-service/"><strong>support@videoslots.com.</strong></a></p>
                                <p>Thank you for your understanding.</p>'
                ];
        }

        $this->connection
            ->table($this->table)
            ->insert($this->data);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('alias',
                [
                    'blocked.access.restricted',
                    'blocked.user.country.restriction'
                ]
            )
            ->where('language', 'en')
            ->delete();
    }
}

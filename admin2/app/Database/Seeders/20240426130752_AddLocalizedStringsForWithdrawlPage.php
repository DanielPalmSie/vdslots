<?php 
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringsForWithdrawlPage extends Seeder
{
    private Connection $connection;
    private string $table;
    protected array $data;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
        $this->data = [];
    }

    public function up()
    {
        $brandDetails = $this->getBrandDetails($this->brand);

        if (!empty($brandDetails)) {
            // Prepare data for withdrawal HTML
            $this->addOrUpdateRecord('withdraw.desktop.start.html', $this->generateHtmlContent($brandDetails['email'], $brandDetails['brandName']));

            // Prepare data for withdrawal image
            $this->addOrUpdateRecord('withdraw.desktop.start.image', '<img src="{{cdn}}/file_uploads/img_tech-support.png" alt="tech-support-image"/>');

            // Prepare data for withdrawal headline
            $this->addOrUpdateRecord('withdraw.desktop.start.headline', "Make a secure withdrawal from {$brandDetails['brandName']}");
        }

        $this->connection
            ->table($this->table)
            ->insert($this->data);
    }

    private function addOrUpdateRecord($alias, $value)
    {
        // Fetch the existing record for the current alias and language
        $existingRecord = $this->connection->table($this->table)
            ->where('alias', $alias)
            ->where('language', 'en')
            ->first();

        // If the record exists, delete it
        if ($existingRecord) {
            $this->connection->table($this->table)
                ->where('alias', $alias)
                ->where('language', 'en')
                ->delete();
        }

        // Insert a new record
        $this->data[] = [
            'language' => 'en',
            'alias' => $alias,
            'value' => $value,
        ];
    }

    private function getBrandDetails($brand)
    {
        // Add details for each brand
        $brandDetails = [
            'kungaslottet' => [
                'email' => 'support@kungaslottet.com',
                'brandName' => 'Kungaslottet',
            ],
            'videoslots' => [
                'email' => 'support@videoslots.com',
                'brandName' => 'Videoslots.com',
            ],
            'mrvegas' => [
                'email' => 'support@mrvegas.com',
                'brandName' => 'Mrvegas.com',
            ],
            'megariches' => [
                'email' => 'support@megariches.com',
                'brandName' => 'Megariches.com',
            ]
        ];

        return $brandDetails[$brand] ?? [];
    }

    private function generateHtmlContent($email, $brandName)
    {
        return "
            <p>It's easy and secure to make a withdrawal from $brandName.<br /></p>
            <p><img style='float: left;' src='{{cdn}}/file_uploads/Secure-your-Business.png' alt='' width='167' height='167' /></p>
            <p>When you withdraw funds from $brandName, you need to use the same <br /> payment method as when you made your deposit.</p>
            <p>You have one fee-free withdrawal per day, but you can, of course, withdraw <br /> more frequently if you'd like. Those withdrawals come with a {{csym}} {{modm:2.50}} fee though.</p>
            <p>Note that you need to verify your account to be able to withdraw. <br /> This is done in the 'Document' section in your profile. If you have any questions, <br /> please email <a href='mailto:$email'>$email</a>. They will be happy to assist you.</p>
            <p>All withdrawals are manually checked, and they are processed daily.</p>
        ";
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('alias', ['withdraw.desktop.start.html', 'withdraw.desktop.start.image', 'withdraw.desktop.start.headline'])
            ->where('language', 'en')
            ->delete();
    }
}
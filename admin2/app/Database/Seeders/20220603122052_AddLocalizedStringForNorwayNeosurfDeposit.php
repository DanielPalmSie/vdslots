<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForNorwayNeosurfDeposit extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'deposit.start.neosurf.no.html' => '<p>Deposit with Neosurf, your funds are immediately available. Withdrawals are processed within 5 minutes around the clock. To obtain a voucher spendable on our site please follow this <a target="_blank" href="{{buy_voucher_url}}">secure URL</a>. If you already have a voucher, please input the code.</p>'
        ]
    ];
}
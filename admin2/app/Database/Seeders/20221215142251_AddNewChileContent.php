<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddNewChileContent extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'deposit.start.khipu.html' => '<p>Deposit with Khipu.</p>',
            'deposit.start.lider_express.html' => '<p>Deposit with Lider Express.</p>',
            'deposit.start.lider.html' => '<p>Deposit with Lider.</p>',
            'deposit.start.mach.html' => '<p>Deposit with Mach.</p>',
            'deposit.start.bci.html' => '<p>Deposit with Bci.</p>',            
            'deposit.start.acuenta.html' => '<p>Deposit with Acuenta.</p>',
            'khipu.nid' => 'Personal / National ID:',
            'bci.nid' => 'Personal / National ID:',
            'acuenta.nid' => 'Personal / National ID:',
            'lider.nid' => 'Personal / National ID:',
            'lider_express.nid' => 'Personal / National ID:',
            'mach.nid' => 'Personal / National ID:'
        ]
    ];
}

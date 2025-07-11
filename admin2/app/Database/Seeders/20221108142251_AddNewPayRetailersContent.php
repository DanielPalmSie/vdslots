<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddNewPayRetailersContent extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'hosted_ccard' => 'Card',
            'deposit.start.hosted_visa.html' => '<p>Deposit with your card, your funds are immediately available. Withdrawals are processed within 5 minutes around the clock.</p>',
            'deposit.start.hosted_ccard.html' => '<p>Deposit with your card, your funds are immediately available. Withdrawals are processed within 5 minutes around the clock.</p>',
            'deposit.start.hosted_mc.html' => '<p>Deposit with your card, your funds are immediately available. Withdrawals are processed within 5 minutes around the clock.</p>',
            'hosted_visa.nid' => 'National ID Number',            
            'hosted_mc.nid' => 'National ID Number',
            'hosted_ccard.nid' => 'National ID Number',
            'payretailers.account.type' => 'Account Type',            
            'boleto_rapido.nid' => 'National ID Number',
            'bradesco.nid' => 'National ID Number',
            'itau.nid' => 'National ID Number',
            'santander.nid' => 'National ID Number',
            'banco_do_brazil.nid' => 'National ID Number',
            'pix.nid' => 'National ID Number',
            'ted.nid' => 'National ID Number',
            'deposit.start.pix.html' => '<p>Pix instant payments.</p>',
            'withdraw.start.pix.html' => '<p>Withdraw via Pix.</p>',
            'pix.pix.id' => 'Pix Key / ID',
            'pix.account.type' => 'Account Type',
            'pix.document.type' => 'Document Type',
            'payretailers.account.type' => 'Account Type',
            'payretailers.document.type' => 'Document Type',            
            'payretailers.bank.code' => 'Select Bank',
            'deposit.start.boleto.html' => '<p>Deposit with Boleto.</p>',
            'deposit.start.ted.html' => '<p>Deposit with TED.</p>',
            'deposit.start.santander.html' => '<p>Deposit with Santander.</p>',
            'deposit.start.itau.html' => '<p>Deposit with Itau.</p>',
            'deposit.start.bradesco.html' => '<p>Deposit with Bradesco.</p>',
            'deposit.start.banco_do_brazil.html' => '<p>Deposit with Banco do Brazil.</p>',
            'deposit.start.boleto_rapido.html' => '<p>Deposit with Boleto Rapido.</p>'
        ]
    ];
}

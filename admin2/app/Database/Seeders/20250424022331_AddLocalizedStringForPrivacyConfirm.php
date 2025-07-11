<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForPrivacyConfirm extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'privacy.confirmation.title' => ' Privacy Confirmation',
            'privacy.confirmation.description' => 'Stay connected with {{1}}! Opt in to receive the latest promotions across all product categories (Casino, Sportsbook, Pool Betting & Bingo), delivered through email, SMS, phone, post, and notifications.',
            'privacy.confirmation.description.opt' => "Don't miss out on exclusive offers! Opt out at any time.",
            'privacy.confirmation.accept' => 'Accept',
            'privacy.confirmation.edit.preference' => 'Edit Preference',
            'privacy.confirmation.maybe.later' => 'Maybe Later',
            'privacy.confirmation.casino' => 'Casino',
            'privacy.confirmation.sports' => 'Sports book',
            'privacy.confirmation.bingo' => 'Bingo',
            'privacy.confirmation.poker' => 'Poker'


        ]
    ];
}

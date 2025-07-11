<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForPayNPlay extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [

        'en' => [
            'paynplay.deposit' => 'Deposit',
            'paynplay.choose.amount' => 'Choose Amount',
            'paynplay.deposit.description' => 'By clicking Deposit & Play you accept our <b>Privacy Notice</b> and <b>Terms & Conditions.</b>',
            'paynplay.deposit.btn' => 'Deposit',
            'paynplay.login.without.deposit' => 'Login without Deposit',

            'paynplay.deposit.success.message' => 'Deposit Completed!',
            'paynplay.deposit.success.description' => 'Go to your account start page to see the transaction or to your bonus page to activate applicable first time deposit bonuses.',
            'paynplay.deposit.success.btn'   => 'OK',

            'paynplay.deposit.failure.message' => 'Deposit Failed!',
            'paynplay.deposit.failure.description' => 'Go to your account start page to see the transaction or to your bonus page to activate applicable first time deposit bonuses.',
            'paynplay.deposit.failure.btn'   => 'Continue',

            'paynplay.withdraw' => 'Withdraw',
            'paynplay.withdraw.description' => 'Enter the amount you wish to withdraw to your bank account.',
            'paynplay.withdraw.now' => 'Withdraw Now',

            'paynplay.withdraw.success.message' => 'Withdrawal Successful!',
            'paynplay.withdraw.success.description' => 'Your withdrawal is waiting for confirmation, withdrawals are processed daily.',
            'paynplay.withdraw.success.btn'   => 'OK',

            'paynplay.withdraw.failure.message' => 'Withdrawal Failed',
            'paynplay.withdraw.failure.description' => 'The amount is too small.',
            'paynplay.withdraw.failure.low.balance.description' => 'Balance is too low.',
            'paynplay.withdraw.failure.btn'   => 'Ok',

            'paynplay.user-details.popup-title' => 'Welcome to Kungaslottet!',
            'paynplay.user-details.more-details' => 'Before you can proceed to gameplay we require you to provide some more details.',
            'paynplay.user-details.wish-to-receive-offers' => 'I wish to receive all kind of free spins and promotional offers via all channels (email, SMS, phone and post).',
            'paynplay.user-details.email-placeholder' => 'Email',
            'paynplay.user-details.phone-placeholder' => 'Mobile Number',
            'paynplay.user-details.continue' => 'Continue',
            'paynplay.user-details.invalid-email' => 'Please enter a valid email address',
            'paynplay.user-details.invalid-phone' => 'Please enter a valid mobile number',

            'paynplay.account-verification.popup-title' => 'Account Verification',
            'paynplay.account-verification.enter-code' => 'Enter the 4 digit code the was sent to:',
            'paynplay.account-verification.email' => 'Email:',
            'paynplay.account-verification.mobile' => 'Mobile:',
            'paynplay.account-verification.change-email-mobile' => 'Change email/mobile',
            'paynplay.account-verification.code-placeholder' => 'Validation Code',
            'paynplay.account-verification.resend-code' => 'Resend Code',
            'paynplay.account-verification.validate' => 'Validate',
            'paynplay.account-verification.invalid-code' => 'Invalid validation code',
        ]

    ];
}

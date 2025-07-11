<?php

class TestBluemIBAN extends TestPhive
{
    private $known_personal_accounts = [
        [
            'iban' => 'NL58ABNA9999142181',
            'firstname' => 'Doortje',
            'lastname' => 'Doorzon'
        ],
        [
            'iban' => 'NL12ABNA9999876523',
            'firstname' => 'Daan Jeroen Maarten',
            'lastname' => 'Quackernaat'
        ],
        [
            'iban' => 'NL29ABNA1599147955',
            'firstname' => 'Gunther',
            'lastname' => 'Glückspilz'
        ],
    ];

    private $known_organisational_account = [
        [
            'iban' => 'NL63RABO3665292913',
            'firstname' => 'Timerfruit B.V.',
            'lastname' => ''
        ],
        [
            'iban' => 'NL05ABNA9558721522',
            'firstname' => 'CARU Containers B.V.',
            'lastname' => ''
        ],
    ];

    private $mistyped_accounts = [
        [
            'iban' => 'NL77BNPA6423931531',
            'firstname' => 'Henrik',
            'lastname' => 'de Jong'
        ],
    ];

    private $unknown_accounts = [
        [
            'iban' => 'NL04RABO8731326943',
            'firstname' => 'Univent',
            'lastname' => 'B.V.'
        ],
    ];

    private $not_found_accounts = [
        [
            'iban' => 'NL20HAND0787418706',
            'firstname' => 'Test',
            'lastname' => 'User1'
        ],
        [
            'iban' => 'NL56DEUT0265186420',
            'firstname' => 'Test',
            'lastname' => 'User2'
        ],
    ];

    /**
        Only function that can be called outside this class. Will in turn execute all test cases for this class.

        To execute the tests.
        Create a test php file inside videoslots/diamondbet/test folder [eg. test_iban_check.php]
        Add following lines to the file
        <?php
        require_once __DIR__ . '/../../phive/phive.php';
        require_once __DIR__ . '/../../phive/modules/Test/TestPhive.php';
        require_once __DIR__ . '/../../phive/modules/Test/TestBluemIBAN.php';
        require_once __DIR__ . '/../../phive/vendor/autoload.php';

        TestBluemIBAN::execute();

        And execute the php file as php test_iban_check.php
     */
    public static function execute()
    {
        $self = new self('Licensed');

        phive()->setSetting('dump_log', true);

        $self->testResponseForCheckIBAN();
        $self->testCheckIBANWithIncompleteData();
        $self->testCheckIBANWithPersonalKnownAccount();
        $self->testCheckIBANWithOrganisationalKnownAccount();
        $self->testCheckIBANWithUnKnownAccount();
        $self->testCheckIBANWithNonFoundAccount();
        $self->testCheckIBANWithMisTypedAccount();
    }

    private function testResponseForCheckIBAN()
    {
        $user = $this->getTestPlayer('NL');

        $res = lic('checkIBAN', [$user], $user);
        $result = is_array($res['result']) ? false : $res['result'];

        $this->msg(isset($result), 'Response is missing status', 'Response has status');
        $this->msg(isset($res['message']), 'Response is missing message','Response has message');
        $this->assertFalse($res['status'], 'Asserting that result is false for incomplete data.');

        $this->removeTestUser($user);
    }

    private function testCheckIBANWithIncompleteData()
    {
        $user = $this->getTestPlayer('NL');

        $res = lic('checkIBAN', [$user], $user);
        $this->assertFalse($res['success'], 'Asserting that result is false for incomplete data.');
        $this->assertTrue(is_array($res['result']), 'Asserting that result is false for incomplete data.');

        //there should be a log in trans data
        $log = $this->getLog($user, 'bluem-iban-error-response');
        $this->assertTrue(!empty($log), 'Asserting that correct tag is logged for user.');
    }

    private function testCheckIBANWithPersonalKnownAccount()
    {
        $user = $this->getTestPlayer('NL');
        $data = $this->known_personal_accounts[array_rand($this->known_personal_accounts)];
        $user = $this->updateUser($user, $data, false);
        $res =  lic('checkIBAN', [$user, ['iban' => $data['iban']]], $user); // Mocking
        $result = is_array($res['result']) ? false : $res['result'];

        $this->assertTrue($result, 'testCheckIBANWithKnownAccount: Asserting that result is true for known user');
        $this->assertTrue($user->isIBANCheckPassed(), 'testCheckIBANWithKnownAccount: Asserting that flag IBAN Check passed is set');

        $this->removeTestUser($user);
    }

    private function testCheckIBANWithOrganisationalKnownAccount()
    {
        $user = $this->getTestPlayer('NL');

        $allow_organisation = licSetting('bluem', $user)['iban_check']['allow_organisations'];

        $data = $this->known_organisational_account[array_rand($this->known_organisational_account)];
        $user = $this->updateUser($user, $data);
        $res =  lic('checkIBAN', [$user], $user);
        $result = is_array($res['result']) ? false : $res['result'];

        $message = $allow_organisation ? 'true' : 'false';
        $this->assertTrue($allow_organisation ? $result : !$result, 'testCheckIBANWithKnownAccount [Allowed organisation]: Asserting that result is '. $message .' for known organisational account');

        $this->removeTestUser($user);
    }

    private function testCheckIBANWithUnKnownAccount()
    {
        $user = $this->getTestPlayer('NL');
        $user = $this->updateUser($user, $this->unknown_accounts[array_rand($this->unknown_accounts)]);
        $res =  lic('checkIBAN', [$user], $user);
        $result = is_array($res['result']) ? false : $res['result'];

        $this->assertFalse($result, 'testCheckIBANWithUnKnownAccount: Asserting that result is false for unknown user');
        $this->assertFalse($user->isIBANCheckPassed(), 'testCheckIBANWithUnKnownAccount: Asserting that flag IBAN Check passed is not set');

        $this->removeTestUser($user);
    }

    private function testCheckIBANWithNonFoundAccount()
    {
        $user = $this->getTestPlayer('NL');
        $user = $this->updateUser($user, $this->not_found_accounts[array_rand($this->not_found_accounts)]);
        $res =  lic('checkIBAN', [$user], $user);
        $result = is_array($res['result']) ? false : $res['result'];

        $this->assertFalse($result, 'testCheckIBANWithNonFoundAccount: Asserting that result is false for not found user');
        $this->assertFalse($user->isIBANCheckPassed(), 'testCheckIBANWithNonFoundAccount: Asserting that flag IBAN Check passed is not set');

        $this->removeTestUser($user);
    }

    private function testCheckIBANWithMisTypedAccount()
    {
        $user = $this->getTestPlayer('NL');
        $user = $this->updateUser($user, $this->mistyped_accounts[array_rand($this->mistyped_accounts)]);
        $res =  lic('checkIBAN', [$user], $user);
        $result = is_array($res['result']) ? false : $res['result'];

        $this->assertTrue($result, 'testCheckIBANWithMisTypedAccount: Asserting that result is true for mistyped user');
        $this->assertTrue($user->isIBANCheckPassed(), 'testCheckIBANWithMisTypedAccount: Asserting that flag IBAN Check passed is set');
        $this->assertTrue($user->isFlaggedMisTypedFromIBAN(), 'testCheckIBANWithMisTypedAccount: Asserting that mistyped flag is set');
        $this->assertTrue(!empty($user->getIBANSuggestedName()), 'testCheckIBANWithMisTypedAccount: Asserting that suggested name is saved in settings.');

        $this->removeTestUser($user);
    }

    private function updateUser(DBUser $user, array $data, $update_setting = true) : DBUser
    {
        $user->updateData([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname']
        ]);

        if($update_setting) {
            $user->setSetting('iban', $data['iban']);
        }

        return cu($user->getId());
    }

    private function removeTestUser(DBUser $user)
    {
        $this->cleanupTestPlayer($user->getId(), ['users_settings', 'trans_log', 'user_settings']);
    }

    private function getLog(DBUser $user, string $tag = '')
    {
        $sql = "SELECT * FROM trans_log WHERE user_id = {$user->getId()}";
        $sql .= $tag ? " AND tag = '{$tag}'" : '';

        return Phive('SQL')->loadAssoc($sql);
    }

    protected function assertTrue($value, $message)
    {
        $this->msg($value, $message, $message);
    }

    protected function assertFalse($value, $message)
    {
        $this->msg(!$value, $message, $message);
    }
}

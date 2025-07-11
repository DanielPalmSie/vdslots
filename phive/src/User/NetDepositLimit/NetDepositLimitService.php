<?php

declare(strict_types=1);

namespace Videoslots\User\NetDepositLimit;

final class NetDepositLimitService extends BaseNetDepositLimitService
{
    /**
     * @return bool
     */
    public function increaseLimit(): bool
    {
        $user = cuPl();

        if (empty($user)) {
            return false;
        }

        $userHandler = phive('DBUserHandler');
        $rg60Mail = phive('MailHandler2')->getSetting('RG60_MAIL');

        $message = 'Customer requested a net deposit threshold increase.';
        $userHandler->logTrigger($user, 'RG60', $message);
        $userHandler->logTrigger($user, 'AML54', $message);

        if (limitAttempts('request-net-deposit-limit-increase', $user->getId(), 2)) {
            return false;
        }

        phive('MailHandler2')->sendMail('request-soi-to-increase-ndl', $user, null, null, $rg60Mail, $rg60Mail);

        $userHandler->logAction($user, $message, 'RG60');
       
        if (!$this->hasRG60LeftCommentLastMonth((int)$user->getId())) {
            $comment = "RG60 has been triggered on this account. " .
                "The customer has requested an increase to their Net Deposit Threshold. " .
                "An automated email has been sent, asking the customer to provide documents for review";
            $user->addComment(
                $comment,
                0,
                'rg-evaluation'
            );
        }

        $subject = "Notification: {$user->getId()} requested a casino net deposit limit increase";
        $content = "<p>Customer <a href='" . phive('UserHandler')->getBOAccountUrl($user) .
            "'>{$user->getId()}</a> requested a casino net deposit limit increase.";
        phive('MailHandler2')->mailLocalFromConfig(
            $subject,
            $content,
            'net-deposit-limit-request-emails',
            'affordability'
        );

        return true;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        return rgLimits()->reachedType(cu(), 'net_deposit', 0, true);
    }
}

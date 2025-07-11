<?php

namespace Videoslots\FraudDetection\FraudFlags\Licensed\CA\Traits;

use DBUser;

trait FraudFlagTrait
{
    private function manipulateFraudFlagCron(
        DBUser $user,
        string $dep_type,
        string $set_for_psp,
        string $dep_col,
        string $dep_type_scheme
    ): bool
    {
        $wd_dep_col = ($dep_col === 'dep_type') ? 'payment_method' : $dep_col;

        $sql = "SELECT COUNT(*) FROM pending_withdrawals
                    WHERE pending_withdrawals.user_id ={$user->getId()}
                      and pending_withdrawals.status='pending'
                      and pending_withdrawals.{$wd_dep_col}<>'{$set_for_psp}' ";

        $pending_withdrawls = phive('SQL')->sh($user->getId(), '', 'pending_withdrawals')->getValue($sql);

        if ($pending_withdrawls) {
            return $this->hasMaxDepositFraud($user->getId(), $dep_type, 'wd-flag', $set_for_psp, $dep_col, $dep_type_scheme);
        }

        return false;
    }

    /**
     * Returns true if a player's deposit value with PSP(X) is greater
     * than deposit value with PSP X (any other PSP, skrill etc) and the player tries to withdraw with PSP X.
     * This function simply flags transactions with aformentioned criteria for CA users who tries to withdraw
     * with any psp other than specific psp (instadebit, interac).
     */
    private function hasMaxDepositFraud(
        int    $user_id,
        string $dep_type,
        string $action,
        string $set_for_psp,
        string $dep_col,
        string $dep_type_scheme
    ): bool
    {
        try {
            if (
                $action == 'wd-flag' ||
                ($set_for_psp === 'interac' && $dep_type === 'paymentiq' && $dep_type_scheme !== 'interac')
                || $dep_type !== $set_for_psp
            ) {
                $sql = phive('SQL')->sh($user_id);
                $depositFraudQuery = "SELECT (SELECT count(DISTINCT scheme) FROM deposits
                    WHERE deposits.user_id = {$user_id}) > 1
                    AND
                    (SELECT IFNULL(sum(amount),0) FROM deposits
                    WHERE deposits.user_id ={$user_id}
                       and deposits.{$dep_col}='{$set_for_psp}'
                    )
                    >
                    (SELECT IFNULL(sum(amount),0) FROM deposits
                        WHERE deposits.user_id ={$user_id}
                            and deposits.status='approved'
                            and deposits.{$dep_col}<>'{$set_for_psp}'
                    )";

                $sql->query($depositFraudQuery);

                return $sql->fetch("NUM")[0] === "1";
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}

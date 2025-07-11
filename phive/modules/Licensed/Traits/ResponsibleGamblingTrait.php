<?php

trait ResponsibleGamblingTrait
{
    /**
     * Sets default Net Deposit Limit for users over 24 years of age
     *
     * @return void
     */
    public function resetModifiedNetDepositLimitCron(): void
    {
        $age = $this->getLicSetting('reset_net_deposit_limit_by_age') ?? [];
        foreach ($age as $year) {
            $max_interval = DateInterval::createFromDateString("{$year} years");
            $dob_max = (new DateTime('now'))->sub($max_interval)->format('Y-m-d');
            $type = 'net_deposit';
            $time_span = 'month';
            $country = $this->countryIso;

            $sql = "SELECT rl.user_id, rl.cur_lim, us.value as original_net_deposit_limit FROM rg_limits rl
                JOIN users u ON u.id = rl.user_id
                LEFT JOIN users_settings us ON us.user_id = rl.user_id AND us.setting = 'original_net_deposit_limit'
                WHERE u.country = '{$country}' AND rl.time_span = '{$time_span}' AND rl.type = '{$type}'
                AND u.dob = '{$dob_max}'";

            $data = phive('SQL')->shs()->loadArray($sql);
            foreach ($data as $item) {
                $user = cu($item['user_id']);
                $new_limit = $item['original_net_deposit_limit'] ?? lic('getNetDepositMonthLimit', [$user], $user);

                if ($item['cur_lim'] >= $new_limit) {
                    continue;
                }

                rgLimits()->changeLimit($user, $type, $new_limit, $time_span);
                phive('UserHandler')->logAction(
                    $user,
                    "Automatically updated net deposit limit for users over {$year} years of age",
                    "reset-{$type}-{$time_span}"
                );
            }
        }
    }
}

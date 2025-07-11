<?php

trait IntendedGamblingTrait
{

    public function doIntendedGambling(DBUser $user = null): bool
    {
        $user = $user ?? cu();

        if (empty($user)) {
            return false;
        }
        return
            $user->hasPrivacySettings() &&
            $this->getLicSetting('intended_gambling') &&
            $user->getSetting('should_do_intended_gambling') &&
            empty($user->getSetting('intended_gambling'));
    }

    function intendedGambling(): void
    {
        if (!phive('DBUserHandler/PrivacyHandler')->hasPrivacySettings()) return;

        loadBaseJsCss();
        if (!empty($_GET['showtc']) || !empty($_GET['showpp'])) {
            include_once __DIR__ . '../../../../diamondbet/html/top_base_js.php';
            return;
        }
        ?>
        <script>
            $(document).ready(function () {
                var extraOptions = isMobile() ? {} : {width: 800, height: 450};
                var params = {
                    module: 'Licensed',
                    file: 'intended_gambling'
                };
                extBoxAjax('get_raw_html', 'intended_gambling', params, extraOptions);
            });
        </script>
        <?
    }

    public function getIntendedGamblingRanges(DBUser $user): array
    {
        $currency = $this->getIntendedGamblingCurrency($user);
        $lic_gambling_range = $this->currencyGamblingLimitRanges($currency);
        $keys = ["value", "label"];
        $ranges = [];

        foreach ($lic_gambling_range as $label) {
            $value = str_replace(' ', '', $label);

            if (str_ends_with($value, '+')) {
                $new_value = preg_replace("/[^0-9]/", "", $value);
                $value = str_replace('+', '-' . $new_value, $value);
            }

            $label = $label . " $currency";

            $ranges[] = array_combine($keys, [$value, $label]);
        }

        return $ranges;
    }

    public function getIntendedGamblingCurrency(DBUser $user): string
    {
        return $user->getCurrency();
    }

    public function currencyGamblingLimitRanges(string $currency): array
    {
        $cur_ranges = phive('Licensed')->getSetting('intended_gambling_cur_ranges');
        $default_cur = getCur()["code"];
        return $cur_ranges[$currency] ?? $cur_ranges[$default_cur];
    }

    public function setIntendedGamblingEligibility(DBUser $user): void
    {
        if ($this->getLicSetting('intended_gambling')) {
            $user->setSetting('should_do_intended_gambling', 1);
        }
    }

}

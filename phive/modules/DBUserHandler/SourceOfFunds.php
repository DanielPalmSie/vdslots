<?php

class SourceOfFunds
{

    /**
     * We get the key on savings for example your_savings_1
     *
     * @param string $currency
     * @param string $low
     * @param string $top
     * @return false|int|string
     */
    public function getIncomeLevelKey($currency, $low, $top)
    {
        return $this->getRangeKeyByLevels($currency, 'income', $low, $top);
    }

    /**
     * We get the key on savings for example your_savings_1
     *
     * @param string $currency
     * @param string $low
     * @param string $top
     * @return false|int|string
     */
    public function getSavingsLevelKey($currency, $low, $top)
    {
        return $this->getRangeKeyByLevels($currency, 'savings', $low, $top);
    }

    /**
     * We get for example low 0 and top 20000 from dmapi and we need to get the key which will be annual_income_1
     *
     * @param string $currency
     * @param string $type
     * @param string $low
     * @param string $top
     * @return false|int|string
     */
    public function getRangeKeyByLevels($currency, $type, $low, $top)
    {
        if ($type == 'savings') {
            $options = $this->getSavingOptions($currency);
        } else {
            $options = $this->getIncomeOptions($currency);
        }

        $res = false;
        foreach ($options as $op_key => $op_value) {
            $option_low = $this->getLowIncomeFromString($op_value);
            $option_top = $this->getTopIncomeFromString($op_value);
            if ($option_low == $low && $option_top == $top) {
                $res = $op_key;
                break;
            }
        }

        if (empty($res)) {
            error_log("Fatal Error on function getRangeKeyByLevels at SourceOfFunds class, income option not found.");
        }

        return $res;
    }

    /**
     * Gets an array with all saving ranges.
     *
     * Code extracted from SourceOfFundsBoxBase without changing the logic
     *
     * @param $currency
     * @return string[]
     */
    public function getIncomeOptions($currency)
    {
        $symbol = phive('Currencer')->getCurSym($currency);
        $start_income_value = mc('20000', $currency, 'multi', false);

        $annual_income_options_all = [
            'default' => [
                'annual_income_1' => "{$symbol}0-20,000",
                'annual_income_2' => "{$symbol}20,000-40,000",
                'annual_income_3' => "{$symbol}40,000-60,000",
                'annual_income_4' => "{$symbol}60,000-80,000",
                'annual_income_5' => "{$symbol}80,000-100,000",
                'annual_income_6' => "{$symbol}100,000+"
            ],
            'formatted_currencies' => [
                'annual_income_1' => $symbol . '0-' . number_format($start_income_value),
                'annual_income_2' => $symbol . number_format($start_income_value) . '-' . number_format($start_income_value * 2),
                'annual_income_3' => $symbol . number_format($start_income_value * 2) . '-' . number_format($start_income_value * 3),
                'annual_income_4' => $symbol . number_format($start_income_value * 3) . '-' . number_format($start_income_value * 4),
                'annual_income_5' => $symbol . number_format($start_income_value * 4) . '-' . number_format($start_income_value * 5),
                'annual_income_6' => $symbol . number_format($start_income_value * 5) . '+',
            ],
            'other' => [
                'annual_income_1' => '0-200,000kr',
                'annual_income_2' => '200,000-400,000kr',
                'annual_income_3' => '400,000-600,000kr',
                'annual_income_4' => '600,000-800,000kr',
                'annual_income_5' => '800,000-1,000,000kr',
                'annual_income_6' => '1,000,000kr +'
            ],
        ];

        // Show income options based on the users currency, not the country
        // TODO: this now assumes only SEK, NOK, and DKK have a mod10 currency
        $annual_income_options = $annual_income_options_all['default'];
        if(in_array($currency, ['SEK', 'NOK', 'DKK'])) {
            $annual_income_options = $annual_income_options_all['other'];
        } elseif (in_array($currency, ['BRL', 'CLP'])) {
            $annual_income_options = $annual_income_options_all['formatted_currencies'];
        }

        return $annual_income_options;
    }

    /**
     * Code extracted from SourceOfFundsBoxBase without changing the logic
     *
     * @param $currency
     * @return string[]
     */
    public function getSavingOptions($currency)
    {
        $symbol = phive('Currencer')->getCurSym($currency);
        $start_saving_value = mc('25000', $currency, 'multi', false);

        $your_savings_options_all = [
            'default' => [
                'your_savings_1' => "{$symbol}0-25,000",
                'your_savings_2' => "{$symbol}25,000-50,000",
                'your_savings_3' => "{$symbol}50,000-100,000",
                'your_savings_4' => "{$symbol}100,000-250,000",
                'your_savings_5' => "{$symbol}250,000-500,000",
                'your_savings_6' => "{$symbol}500,000+"
            ],
            'formatted_currencies' => [
                'your_savings_1' => $symbol . '0-' . number_format($start_saving_value),
                'your_savings_2' => $symbol . number_format($start_saving_value) . '-' . number_format($start_saving_value * 2),
                'your_savings_3' => $symbol . number_format($start_saving_value * 2) . '-' . number_format($start_saving_value * 4),
                'your_savings_4' => $symbol . number_format($start_saving_value * 4) . '-' . number_format($start_saving_value * 10),
                'your_savings_5' => $symbol . number_format($start_saving_value * 10) . '-' . number_format($start_saving_value * 20),
                'your_savings_6' => $symbol . number_format($start_saving_value * 20) . '+',
            ],
            'other' => [
                'your_savings_1' => '0-250,000kr',
                'your_savings_2' => '250,000-500,000kr',
                'your_savings_3' => '500,000-1,000,000kr',
                'your_savings_4' => '1,000,000-2,500,000kr',
                'your_savings_5' => '2,500,000-5,000,000kr',
                'your_savings_6' => '5,000,000kr +'
            ],
        ];

        $your_savings_options = $your_savings_options_all['default'];
        // TODO: this now assumes only SEK, NOK, and DKK have a mod10 currency
        if(in_array($currency, ['SEK', 'NOK', 'DKK'])) {
            $your_savings_options = $your_savings_options_all['other'];
        } elseif (in_array($currency, ['BRL', 'CLP'])) {
            $your_savings_options = $your_savings_options_all['formatted_currencies'];
        }

        return $your_savings_options;
    }

    /**
     * Helper function copied from DMAPI code base to convert a range string into a low income band number
     *
     * @param string $income_string
     * @return string
     */
    private function getLowIncomeFromString(string $income_string): string
    {
        $income = preg_replace('/[^0-9-]/', '', $income_string);
        $income_array = explode('-', $income);

        return $income_array[0];
    }

    /**
     * Helper function copied from DMAPI code base to convert a range string into a top income band number
     *
     * @param string $income_string
     * @return  string
     */
    private function getTopIncomeFromString(string $income_string): string
    {
        $income = preg_replace('/[^0-9-]/', '', $income_string);
        $income_array = explode('-', $income);

        // if $income_array[1] is empty, that means the user submitted something like 100.000+,
        // in which case we use the low income as the top income too
        if(empty($income_array[1])) {
            return $income_array[0];
        }

        return $income_array[1];
    }

    /**
     * Return CNDL from income range in source of wealth declaration
     * CNDL returned in user's currency
     *
     * @param string $sow_income_range
     * @param DBUser $user
     * @return int
     */
    public function getCNDLfromSOWIncomeRange(string $sow_income_range, DBUser $user): int
    {
        $jurisdiction = $user->getJurisdiction();
        $users_currency = $user->getCurrency();
        $sow_ndl_map = phive('Config')->getValue('net-deposit-limit', "affordability-check-{$jurisdiction}");

        if (empty($sow_ndl_map)) {
            return -1;
        }

        $sow_low_range = $this->getLowIncomeFromString($sow_income_range);
        $sow_top_range = $this->getTopIncomeFromString($sow_income_range);

        $sow_ndl_map = explode(PHP_EOL, $sow_ndl_map);

        foreach ($sow_ndl_map as $sow_ndl) {
            $split_values = explode('::', $sow_ndl);
            $income_range = $split_values[0];
            $cndl = $split_values[1];

            if (empty($cndl)) {
                continue;
            }
            $low_range = (string) mc($this->getLowIncomeFromString($income_range), $users_currency);
            $top_range = (string) mc($this->getTopIncomeFromString($income_range), $users_currency);

            if (
                $sow_low_range === $low_range
                &&
                $sow_top_range === $top_range
            ) {
                return mc($cndl, $users_currency);
            }
        }

        return -1;
    }

}

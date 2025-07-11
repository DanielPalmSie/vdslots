@include('admin.filters.select2-filter', [
    'label' => $label ?? 'Currency',
    'name' => $name ?? 'currency',
    'placeholder' => $placeholder ?? 'Shows all currencies if not selected',
    'options' => function() {
        $currencyOptions = \App\Helpers\DataFormatHelper::getCurrencyList();
        $options = [];
        foreach ($currencyOptions as $currency) {
            $options[] = [
                'value' => $currency->code,
                'label' => "{$currency->symbol} {$currency->code}"
            ];
        }
        return $options;
    }
])

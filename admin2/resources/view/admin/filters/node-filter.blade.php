@include('admin.filters.select2-filter', [
    'label' => $label ?? 'Database node',
    'name' => $name ?? 'node',
    'placeholder' => $placeholder ?? 'Shows all if not selected',
    'options' => function() {
        $nodeOptions = dfh()->getNodesList();
        $options = [];
        foreach ($nodeOptions as $key => $value) {
            $options[] = [
                'value' => $value,
                'label' => $key
            ];
        }
        return $options;
    }
])

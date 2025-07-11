@include('admin.filters.select2-filter', [
    'label' => 'AML Flags',
    'name' => 'aml-flags',
    'placeholder' => '   Select AML Flags',
    'multiple' => true,
    'additionalScriptCallback' => 'setupAMLFlagsSelect2',
    'options' => function() {
        $amlOptions = \App\Helpers\DataFormatHelper::getAMLFlags();
        $options = [];
        foreach ($amlOptions as $flagValue => $flagTitle) {
            $options[] = [
                'value' => $flagValue,
                'label' => $flagTitle
            ];
        }
        return $options;
    }
])

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        function setupAMLFlagsSelect2() {
            const select_aml_flags = $("#select-aml-flags");
            const current_flags = <?php echo json_encode($app['request_stack']->getCurrentRequest()->get('aml-flags')) ?>;
            current_flags ? select_aml_flags.val(current_flags) : select_aml_flags.val('');

            initializeSelect2(select_aml_flags, {
                placeholder: "Select AML Flags",
                allowClear: true
            }, function(option) {
                if (!option.id) {
                    return option.text;
                }

                return `<span class="${option.id}" style="width:5px; cursor:default;" title="Status: ${option.id}">&nbsp;&nbsp;&nbsp;</span> ${option.text}`;
            });
        }
    </script>
@endsection

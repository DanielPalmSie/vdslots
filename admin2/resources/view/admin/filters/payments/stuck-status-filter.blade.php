@include('admin.filters.select2-filter', [
    'label' => 'Stuck Status',
    'name' => 'stuck',
    'placeholder' => 'Select a Stuck Status',
    'additionalScriptCallback' => 'setupStuckStatusSelect2',
    'options' => function() {
        $stuckOptions = \App\Helpers\DataFormatHelper::getstuckStatuses();
        $options = [];
        foreach ($stuckOptions as $statusValue => $statusTitle) {
            $options[] = [
                'value' => $statusValue,
                'label' => $statusTitle
            ];
        }
        return $options;
    }
])

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        function setupStuckStatusSelect2() {
            var select_stuck_status = $("#select-stuck");
            initializeSelect2(select_stuck_status, {
                placeholder: "Select Stuck Status",
                allowClear: true
            }, function(option) {
                if (!option.id) {
                    return option.text;
                }

                return `<span class="${option.text.toLowerCase()}-tr-line stuck-select2-option">&nbsp;&nbsp;&nbsp;</span> ${option.text}`;
            });
        }
    </script>
@endsection

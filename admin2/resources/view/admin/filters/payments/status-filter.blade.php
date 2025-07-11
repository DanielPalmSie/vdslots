@include('admin.filters.select2-filter', [
    'label' => 'Status',
    'name' => 'status',
    'allowClear' => false,
    'additionalScriptCallback' => 'setupTransactionStatusFilter',
    'additionalSelectOptions' => $additionalSelectOptions ?: [
        'Approved' => 'approved',
        'Disapproved' => 'disapproved',
        'Pending' => 'pending',
        'Preprocessing' => 'preprocessing',
        'Processing' => 'processing',
    ]
])

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        function setupTransactionStatusFilter() {
            var no_search = { minimumResultsForSearch: -1 };
            $("#select-status").select2(no_search).val("{{ $app['request_stack']->getCurrentRequest()->get('status', $default ?: 'approved') }}").change();
        }
    </script>
@endsection

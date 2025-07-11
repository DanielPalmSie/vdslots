@php
    $transactionsType = $type ?? '';
@endphp

@include('admin.filters.select2-filter', [
    'label' => 'Method',
    'name' => 'method',
    'placeholder' => 'Loading values...',
    'additionalScriptCallback' => 'setupPaymentMethodSelect2'
])

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        function setupPaymentMethodSelect2() {
            const selectShow = $("#select-show");
            if (selectShow.length) {
                selectShow.change(function () {
                    const selectedType = $(this).val();
                    const transactionType = categorizeType(selectedType);

                    const selectMethod = $("#select-method");
                    selectMethod.empty().select2('val', '');
                    selectMethod.trigger("change");

                    initMethodSelect2(transactionType);
                });
            } else {
                initMethodSelect2();
            }
        }

        function initMethodSelect2(transactionType = '') {
            const payment_method = "{{ $app['request_stack']->getCurrentRequest()->get('method', '') }}";
            const select_method = $('#select-method').select2();
            const url = "{{ $app['url_generator']->generate('transactions.get-methods') }}";

            const finalTransactionType = @json($transactionsType) || transactionType;
            $.get(url, { type: finalTransactionType }, function (data) {
                select_method.empty().select2('val', '');
                select_method.append("<option></option>");

                $.each(data, function (_, element) {
                    const isSelected = (element === payment_method) ? "selected" : "";
                    select_method.append(`<option ${isSelected} value='${element}'>${element}</option>`);
                });

                select_method.select2('val', '');
                select_method.trigger("change").data('placeholder', 'Select a payment method').select2();
            });
        }

        function categorizeType(type) {
             if (type.includes('deposit')) {
                return 'deposits';
            } else if (
                type.includes('withdrawal') ||
                type.includes('refunds') ||
                type.includes('undone')
            ) {
                return 'withdrawals';
            } else {
                return '';
            }
        }
    </script>
@endsection

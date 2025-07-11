@include('admin.filters.payments.payment-method-filter')

@include('admin.filters.select2-filter', [
    'label' => 'Sub-Methods',
    'name' => 'submethod',
    'placeholder' => '    Select SubMethod',
    'multiple' => false,
    'additionalScriptCallback' => 'setupMethodAndSubMethodSelect2',
    'additionalSelectAttributes' => 'disabled'
])

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        function setupMethodAndSubMethodSelect2() {
            $("#select-method").change(function () {
                const selectedMethod = $(this).val();

                const selectedType = $("#select-show").val();
                const transactionType = categorizeType(selectedType);

                getSubMethodsByMethod(transactionType, selectedMethod);
            });
        }

        function getSubMethodsByMethod(transactionType = '', method) {
            const url = "{{ $app['url_generator']->generate('transactions.get-sub_methods') }}";
            const selectSubMethod = $("#select-submethod");

            if (!method) {
                resetSubMethodSelect(selectSubMethod);
                return;
            }

            const finalTransactionType = @json($transactionsType) || transactionType;
            $.get(url, { method: method, type: finalTransactionType }, function (data) {
                populateSubMethods(selectSubMethod, data);
            });
        }

        function resetSubMethodSelect(selectSubMethod) {
            selectSubMethod.empty().select2('val', '');
            selectSubMethod.prop('disabled', true);
        }

        function populateSubMethods(selectSubMethod, data) {
            const currentMethod = <?php echo json_encode($app['request_stack']->getCurrentRequest()->get('submethod')) ?>;

            selectSubMethod.empty().select2('val', '');
            selectSubMethod.append("<option></option>");

            $.each(data, function (_, element) {
                const isSelected = currentMethod && currentMethod.includes(element) ? "selected" : "";
                selectSubMethod.append(`<option ${isSelected} value='${element}'>${element}</option>`);
            });

            selectSubMethod.prop('disabled', Object.keys(data).length === 0);
        }
    </script>
@endsection

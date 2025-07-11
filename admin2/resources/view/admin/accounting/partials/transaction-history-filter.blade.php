<form id="filter-form-transaction-history" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @php
                    $containerClass = "form-group col-6 col-lg-4 col-xl-3";
                @endphp

                @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                @include('admin.filters.generic-input-filter', ['name' => 'user-id', 'label' => 'User', 'placeholder' => 'User ID'])
                @include('admin.filters.payments.status-filter')

                @include('admin.filters.select2-filter', [
                    'label' => 'Show',
                    'name' => 'show',
                    'placeholder' => 'Deposits and withdrawals if nothing selected',
                    'additionalSelectOptions' => [
                        'Deposits' => 'deposits',
                        'Withdrawals' => 'withdrawals',
                        'Withdrawal Refunds' => 'refunds',
                        'Undone Withdrawals' => 'undone',
                        'Manual Deposits' => 'manualdeposits',
                        'Manual Withdrawals' => 'manualwithdrawals'
                    ]
                ])

                @include('admin.filters.payments.payment-method-submethod-filter')

                @include('admin.filters.generic-input-filter', ['name' => 'ext-id', 'label' => 'Ext/Loc ID', 'placeholder' => 'Part of Ext/Loc ID'])
                @include('admin.filters.generic-input-filter', ['name' => 'int-id', 'label' => 'Internal ID', 'placeholder' => 'Internal ID'])
                @include('admin.filters.generic-input-filter', ['name' => 'account', 'label' => 'Card/Account number/Details', 'placeholder' => 'Part of the transaction details'])
                @include('admin.filters.generic-input-filter', ['name' => 'min', 'label' => 'Min Amount', 'placeholder' => 'In cents'])
                @include('admin.filters.generic-input-filter', ['name' => 'max', 'label' => 'Max Amount', 'placeholder' => 'In cents'])

                @include('admin.filters.payments.currencies-filter')

                @include('admin.filters.select2-filter', [
                    'label' => 'Show all currencies converted to EUR',
                    'name' => 'converted',
                    'placeholder' => 'Show all currencies converted to EUR',
                    'additionalSelectOptions' => [
                        'Yes' => 'yes',
                        'No' => 'no'
                    ]
                ])

                @include('admin.filters.generic-input-filter', ['name' => 'actor', 'label' => 'Approved by', 'placeholder' => 'Part of username'])
                @include('admin.filters.country-province-filter')
                @include('admin.filters.node-filter')
            </div>
        </div>
        <div class="card-footer">
            <button id="transaction-history-filter-btn" type="submit" class="btn btn-info">Search</button>
        </div>
    </div>
</form>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#transaction-history-filter-btn').on('click', function (e) {
                e.preventDefault();
                $("input[name='export']").remove();
                $("input[name='sendtobrowse']").remove();
                handleProvinceSelection();
                $('#filter-form-transaction-history').submit();
            });
        });
    </script>
@endsection

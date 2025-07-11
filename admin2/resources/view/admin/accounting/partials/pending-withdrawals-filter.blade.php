<form id="filter-form-pending-withdrawals" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
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
                @include('admin.filters.generic-input-filter', ['name' => 'ext-id', 'label' => 'External ID', 'placeholder' => 'External ID'])
                @include('admin.filters.generic-input-filter', ['name' => 'int-id', 'label' => 'Internal ID', 'placeholder' => 'Internal ID'])
                @include('admin.filters.generic-input-filter', ['name' => 'account', 'label' => 'Card/Account number/Details', 'placeholder' => 'Part of the transaction details'])
                @include('admin.filters.generic-input-filter', ['name' => 'min', 'label' => 'Min Amount', 'placeholder' => 'In cents'])
                @include('admin.filters.generic-input-filter', ['name' => 'max', 'label' => 'Max Amount', 'placeholder' => 'In cents'])

                @include('admin.filters.payments.payment-method-filter', ['type' => 'withdrawals'])
                @include('admin.filters.payments.currencies-filter')

                @include('admin.filters.node-filter')

                @if(p('accounting.section.pending-withdrawals.stuck-statuses'))
                    @include('admin.filters.payments.stuck-status-filter')
                @endif

                @include('admin.filters.country-province-filter')

                @if(p('accounting.section.pending-withdrawals.aml-flags'))
                    @include('admin.filters.payments.aml-flags-filter')
                @endif

                @include('admin.filters.payments.status-filter', [
                    'additionalSelectOptions' => [
                        'Pending'    => 'pending',
                        'Processing' => 'processing',
                        'Both'       => 'pending|processing',
                    ],
                    'default' => 'pending',
                ])

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
        function setupGenericFilters() {
            $('#transaction-history-filter-btn').on('click', function (e) {
                e.preventDefault();
                $("input[name='export']").remove();
                handleProvinceSelection();
                $('#filter-form-pending-withdrawals').submit();
            });
        }

        $(document).ready(function () {
            setupGenericFilters();
        });
    </script>
@endsection

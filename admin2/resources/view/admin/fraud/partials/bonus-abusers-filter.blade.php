<form id="filter-form-transaction-history" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                <div class="form-group col-6 col-lg-4 col-xl-2">
                    <label for="wager-threshold">Total wager during the period</label>
                    <div class="input-group">
                        <input type="text" name="wager-threshold" class="form-control" placeholder=""
                               value="{{ $app['request_stack']->getCurrentRequest()->get('wager-threshold', 1000) }}">
                        <div class="input-group-prepend">
                            <span class="input-group-text">EUR</span>
                        </div>
                    </div>
                </div>
                <div class="form-group col-6 col-lg-4 col-xl-2">
                    <label for="bonus-threshold">Bonus percentage threshold</label>
                    <div class="input-group">
                        <input type="text" name="bonus-threshold" class="form-control" placeholder=""
                               value="{{ $app['request_stack']->getCurrentRequest()->get('bonus-threshold', 4) }}">
                        <div class="input-group-prepend">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                <div class="form-group col-6 col-lg-4 col-xl-2">
                    <label for="username">Username</label>
                    <input name="username" type="text" class="form-control" placeholder="Comma-separated for multiple values"
                           value="{{ $app['request_stack']->getCurrentRequest()->get('username') }}">
                </div>
                <div class="form-group col-6 col-lg-4 col-xl-3">
                    <label for="select-country">Country</label>
                    <select name="country[]" id="select-country" class="form-control select2-class" multiple="multiple"
                            style="width: 100%;" data-placeholder="Shows all countries if not selected" data-allow-clear="true">
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}">[{{ $country['iso'] }}] {{ $country['printable_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-6 col-lg-4 col-xl-2">
                    <label for="select-transactiontype">Transaction Type</label>
                    <select name="transactiontype" id="select-transactiontype" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="All if nothing is selected" data-allow-clear="true">
                        <option></option>
                        @foreach($data['bonus_trans_types'] as $type)
                            <option value="{{ $type }}">[{{ $type }}] {{ \App\Helpers\DataFormatHelper::getCashTransactionsTypeName($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Search</button>
        </div>
    </div>
</form>
{{--todo after testing move this to the layout--}}
@section('header-css')
    @parent
    <style>
        body { opacity: 0}
    </style>
@endsection
@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(window).on('load', function () {
            if (localStorage.getItem('new-bo-scroll') > 0) {
                $(document).scrollTop(localStorage.getItem('new-bo-scroll'));
            }
            $('body').animate({'opacity':'1'},100);
        });
        $(document).ready(function() {
            $("#select-transactiontype").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('transactiontype') }}").change();
            $("#select-country").select2().val(<?php echo json_encode($app['request_stack']->getCurrentRequest()->get('country')) ?>).change();

            //todo after testing move this to the layout
            $(document).on( 'scroll', function(){
                localStorage.setItem('new-bo-scroll', $(window).scrollTop());
            });
        });

    </script>
@endsection

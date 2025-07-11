<form id="filter-form-player-balance" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="form-group col-4 col-lg-2 col-fhd-2">
                    <label>Date</label>
                    <input autocomplete="off" type="text" name="date"
                           class="form-control daterange-picker" placeholder="Select a day" value="{{ $app['request_stack']->getCurrentRequest()->get('date') }}">
                    <span class="invalid-feedback"></span>
                </div>
                <div class="form-group col-4 col-lg-2 col-fhd-2">
                    <label for="select-currency">Currency</label>
                    <select name="currency" id="select-currency" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Shows all currencies if not selected" data-allow-clear="true">
                        <option></option>
                        @foreach(\App\Helpers\DataFormatHelper::getCurrencyList() as $currency)
                            <option value="{{ $currency->code }}">{{ $currency->symbol }} {{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 col-lg-2 col-fhd-2">
                    <label for="select-country">Country</label>
                    <select name="country" id="select-country" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Shows all countries if not selected" data-allow-clear="true">
                        <option></option>
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div><!-- /.card-body -->
        <div class="card-footer">
            <button class="btn btn-info">Search</button>
        </div><!-- /.card-footer-->
    </div>
</form>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(window).on('load', function () {
            $('.daterange-picker').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
        });

        $(document).ready(function () {
            var date_picker = $('#filter-form-player-balance').find('input[name=date]');
            var date_limit = '2016-08-06';

            date_picker.on('apply.daterangepicker', function (ev, picker) {
                var self = $(this);
                var selectedDate = moment(picker.startDate.format('YYYY-MM-DD'));
                var low_limit = moment(date_limit);

                if (selectedDate < low_limit) {
                    self.val(date_limit);
                    self.addClass('is-invalid');
                    self.parent().find('.invalid-feedback').html('Data only available since ' + date_limit);
                } else {
                    self.val(picker.startDate.format('YYYY-MM-DD'));
                    self.removeClass('is-invalid');
                    self.parent().find('.invalid-feedback').html('');
                }
            });

            $("#select-currency").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('currency') }}").change();
            $("#select-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('country') }}").change();
        });
    </script>
@endsection

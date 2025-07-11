<form action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="form-group col-4 col-xl-2">
                    <label for="select-country">Country</label>
                    <select name="country" id="select-country" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Shows all countries if not selected" data-allow-clear="true">
                        <option value="all">All</option>
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 col-xl-2">
                    <label for="select-province">Province</label>
                    <select name="province[]" id="select-province" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select a province" data-allow-clear="true" multiple="multiple" disabled>
                        @foreach(\App\Helpers\DataFormatHelper::getProvinceListByCountry('CA', true) as $p)
                            <option value="{{ $p['iso'] }}"
                                {{ in_array($p['iso'], $app['request_stack']->getCurrentRequest()->get('province')) ? 'selected' : ''}}>
                                {{ $p['printable_name'] }} ({{ $p['iso'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 col-xl-2">
                    <label for="select-currency">Currency</label>
                    <select name="currency" id="select-currency" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select a currency" data-allow-clear="true">
                        <option></option>
                        @foreach(\App\Helpers\DataFormatHelper::getCurrencyList() as $currency)
                            <option value="{{ $currency->code }}">{{ $currency->symbol }} {{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-4 col-xl-2">
                    <label for="select-year">Year</label>
                    <select id="select-year" name="year" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select a year" data-allow-clear="true">
                        <option></option>
                        @for($y = \Carbon\Carbon::now()->year; $y >= 2011; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group col-4 col-xl-2">
                    <label for="select-month">Month</label>
                    <select id="select-month" name="month" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select a month" data-allow-clear="true">
                        <option></option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group col-4 col-xl-2">
                    <label for="select-source">Source</label>
                    <select id="select-source" name="source" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select a source">
                        <option value="all">All</option>
                        <option value="vs">{{ ucfirst(phive('BrandedConfig')->getBrand()) }}</option>
                        <option value="pr">Affiliates</option>
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
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            var provincesList = $("#select-province").find('option').map(function () {
                return $(this).val();
            });
            provincesList = $.makeArray(provincesList).filter((province) => province.toLowerCase() !== 'all');

            $("#select-country").change(function () {
                var countryHasProvinces = $(this).val() === 'CA';
                $("#select-province").prop('disabled', !countryHasProvinces);

                var selectedProvinces = $("#select-province").val();
                if (!selectedProvinces)
                    selectedProvinces = provincesList;

                $("#select-province").attr('required', countryHasProvinces);
                $("#select-province").val(!countryHasProvinces ? [] : selectedProvinces).change();
            });

            $("#select-currency").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('currency') }}").change();
            $("#select-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('country', 'all') }}").change();
            $("#select-province").select2();
            $("#select-year").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('year') }}").change();
            $("#select-month").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('month') }}").change();
            $("#select-source").select2({
                minimumResultsForSearch: -1
            }).val("{{ $app['request_stack']->getCurrentRequest()->get('source', 'all') }}").change();

            $("#select-province").on('select2:select', function () {
                if ($(this).val().includes('all')) {
                    $(this).val(provincesList).change();
                }
            });
        });
    </script>
@endsection

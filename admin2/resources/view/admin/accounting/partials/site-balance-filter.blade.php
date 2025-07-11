<form action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
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
                    @include('admin.filters.country-filter')
                </div>
                <div id="province-filter" class="form-group col-4 col-xl-2">
                    @include('admin.filters.province-filter',["country"])
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
                        <option value="vs">Videoslots</option>
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
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $("#select-currency").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('currency') }}").change();
            $("#select-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('country', 'all') }}").change();
            $("#select-year").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('year') }}").change();
            $("#select-month").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('month') }}").change();
            $("#select-source").select2({
                minimumResultsForSearch: -1
            }).val("{{ $app['request_stack']->getCurrentRequest()->get('source', 'all') }}").change();
            $('#select-country').on('change', function () {
                var selectedCountry = $(this).val();
                refreshProvinceComponent(selectedCountry);
            });
        });

        function refreshProvinceComponent(selectedCountry) {
            let container = document.getElementById('province-filter');
            let currentSelectedProvince;
            $("#select-main_province").select2("destroy");
            if (selectedCountry == "CA") {
                container.innerHTML = `@include("admin.filters.province-filter", ['country' => "CA"])`;
                currentSelectedProvince = "{{ implode(',',$app['request_stack']->getCurrentRequest()->get('provinces', null)) }}".split(',');
                if(currentSelectedProvince[0] == ''){
                    currentSelectedProvince = "{{ implode(',', array_keys(\App\Helpers\DataFormatHelper::getProvinces('CA'))) }}".split(',');
                }
            }else {
                container.innerHTML = `@include("admin.filters.province-filter", ['country' => "all"])`;
            }


            $('#select-main_province').select2().val(currentSelectedProvince).change();
            $('#select-main_province').select2().on("change", function (e) {
                let currentSelectedProvince =[];
                var changedValue = $(this).val();
                if (Array.isArray(changedValue) && changedValue.length>0 && changedValue.includes('all')) {
                     currentSelectedProvince = "{{ implode(',', array_keys(\App\Helpers\DataFormatHelper::getProvinces('CA'))) }}".split(',');
                    $('#select-main_province').select2().val(currentSelectedProvince).change();
                }
            });

        }
    </script>
@endsection

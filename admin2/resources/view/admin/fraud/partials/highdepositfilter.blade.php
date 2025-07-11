<form id="fraud-high-deposits-filter" action="{{ $app['url_generator']->generate('fraud-high-deposits') }}" method="get">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                <div class="form-group col-6 col-md-4 col-xl-2">
                    <label for="select-country">Country</label>
                    <select name="country" id="select-country" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Shows all countries if not selected" data-allow-clear="true">
                        <option value="all">All</option>
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-6 col-lg-4 col-xl-2">
                    <label for="username">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Part of username"
                           value="{{ $app['request_stack']->getCurrentRequest()->get('username') }}">
                </div>
                <div class="form-group col-6 col-lg-4 col-xl-2">
                    <label>Threshold (EUR)</label>
                    <input type="text" name="amount" class="form-control" placeholder="Amount"
                           value="{{ $app['request_stack']->getCurrentRequest()->get('amount', 2000) }}">
                </div>
            </div>
        </div><!-- /.box-body -->
        <div class="card-footer">
            <button class="btn btn-info">Search</button>
        </div><!-- /.box-footer-->
    </div>
</form>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#select-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('country', 'all') }}").change();
        });
    </script>
@endsection

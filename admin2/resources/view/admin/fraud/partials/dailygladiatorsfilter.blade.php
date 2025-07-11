<form action="{{ $app['url_generator']->generate('fraud-daily-gladiators') }}" method="get">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6 col-xl-4">
                    <label>Date range</label>
                    <div class="input-group input-daterange">
                        <input autocomplete="off" type="text" name="start_date"
                               class="form-control daterange-picker" placeholder="Start Date"
                               value="{{ $query_data['start_date'] }}">
                        <div class="input-group-prepend">
                            <span class="input-group-text">to</span>
                        </div>
                        <input autocomplete="off" type="text" name="end_date"
                               class="form-control daterange-picker" placeholder="End Date"
                               value="{{ $query_data['end_date'] }}">
                    </div>
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
    </script>
@endsection

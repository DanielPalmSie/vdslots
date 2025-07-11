<form action="{{ $app['url_generator']->generate('fraud-non-turned-over-withdrawals') }}" method="get">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6 col-xl-2">
                    <label>From date</label>
                    <input autocomplete="off" type="text" name="date"
                           class="form-control daterange-picker" placeholder="Date" value="{{ $query_data['date'] }}">
                </div>
                <div class="col-6 col-xl-2">
                    <label>Threshold in %</label>
                    <input type="text" name="percent" class="form-control" placeholder="Threshold"
                           value="{{ $query_data['percent'] }}">
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

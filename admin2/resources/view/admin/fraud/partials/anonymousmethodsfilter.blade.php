<form action="{{ $app['url_generator']->generate('fraud-anonymous-methods') }}" method="get">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Search by date</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6 col-xl-2">
                    <label>Date</label>
                    <input autocomplete="off" type="text" name="date"
                           class="form-control daterange-picker" placeholder="Date" value="{{ $query_data['date'] }}">
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

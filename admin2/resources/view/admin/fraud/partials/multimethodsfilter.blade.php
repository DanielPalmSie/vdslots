<form action="{{ $app['url_generator']->generate('fraud-multi-method-transactions') }}" method="get">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="form-group col-6 col-xl-2">
                    <label>Date</label>
                    <input autocomplete="off" type="text" name="date"
                           class="form-control daterange-picker" placeholder="Date" value="{{ $query_data['date'] }}">
                </div>
                <div class="form-group col-6 col-xl-2">
                    <label>Multi-methods count</label>
                    <input type="text" name="count" class="form-control" placeholder="Count"
                           value="{{ $query_data['count'] }}">
                </div>
                <div class="form-group col-6 col-xl-3">
                    <label for="select-collapse">Only one transaction per method</label>
                    <select name="collapse" id="select-collapse" class="form-control">
                        <option <?= $query_data['collapse'] == 1 ? 'selected' : '' ?> value="1">Yes</option>
                        <option <?= $query_data['collapse'] != 1 ? 'selected' : '' ?> value="0">No</option>
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

<form action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card border-top border-top-3">
        <div class="card-body">
            <div class="row">
                <div class="col-6 col-lg-2">
                    <label>From</label>
                    <input autocomplete="off" data-date-format="yyyy-mm-dd" type="text" name="start_date"
                           class="form-control daterange-picker" placeholder="Date"
                           value="{{ \Carbon\Carbon::parse($sort['start_date'])->format('Y-m-d') }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label>To</label>
                    <input autocomplete="off" data-date-format="yyyy-mm-dd" type="text" name="end_date"
                           class="form-control daterange-picker" placeholder="Now"
                           value="{{ isset($sort['end_date']) ? \Carbon\Carbon::parse($sort['end_date'])->format('Y-m-d') : null}}">
                </div>

            </div>
        </div><!-- /.box-body -->
        <div class="card-footer">
            <button class="btn btn-info">Search</button>
        </div><!-- /.box-footer-->
    </div>
</form>

<script>

    $(function () {
        $('.daterange-picker').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: true,
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
    });
</script>

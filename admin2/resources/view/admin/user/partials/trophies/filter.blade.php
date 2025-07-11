<form action="{{ $app['url_generator']->generate('admin.user-add-trophy', ['user' => $user->id]) }}"
      method="get">
    <div class="card border-top border-top-3">
        <div class="card-body d-lg-flex">
            <div class="col-12 col-lg-6">
                <div class="form-group">
                    <label>From</label>
                    <input autocomplete="off" data-date-format="yyyy-mm-dd" type="text" name="start_date"
                           class="form-control daterange-picker" placeholder="Start date"
                           value="{{ !empty($sort['start_date']) ? \Carbon\Carbon::parse($sort['start_date'])->format('Y-m-d') : null }}">

                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="form-group">
                    <label>To</label>
                    <input autocomplete="off" data-date-format="yyyy-mm-dd" type="text" name="end_date"
                           class="form-control daterange-picker" placeholder="Now"
                           value="{{ !empty($sort['end_date']) ? \Carbon\Carbon::parse($sort['end_date'])->format('Y-m-d') : null }}">
                </div>
            </div>
        </div>
        <!-- /.box-body -->
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
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
    });
</script>

{{--
data-date-week-start="1" --}}

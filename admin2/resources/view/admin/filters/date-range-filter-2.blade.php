<label autocomplete="off" for="date-range">Date range:</label>
<input id="date-range" name="date-range" type="text" class="form-control pull-right"
       placeholder="Select a range" value="">
<input id="date-range2" name="date-range2" type="text" class="form-control pull-right"
       placeholder="Select a range" value="">
<input type="hidden" name="start_date" value="{{ $params['start_date'] }}">
<input type="hidden" name="end_date" value="{{ $params['end_date'] }}">
<input type="hidden" name="start_date2" value="{{ $params['start_date2'] }}">
<input type="hidden" name="end_date2" value="{{ $params['end_date2'] }}">


@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {

            var date_range  = $('#date-range');
            var date_range2 = $('#date-range2');
            var start_date  = $('input[name="start_date"]').val();
            var start_date2 = $('input[name="start_date2"]').val();
            var end_date    = $('input[name="end_date"]').val();
            var end_date2   = $('input[name="end_date2"]').val();
            date_range.daterangepicker(
                    {
                        linkedCalendars: false,
                        locale: {
                            format: 'YYYY-MM-DD'
                        },
                        startDate: start_date,
                        endDate: end_date

                    }
            );
            date_range2.daterangepicker(
                    {
                        linkedCalendars: false,
                        locale: {
                            format: 'YYYY-MM-DD'
                        },
                        startDate: start_date2,
                        endDate: end_date2
                    }
            );
        });
    </script>
@endsection

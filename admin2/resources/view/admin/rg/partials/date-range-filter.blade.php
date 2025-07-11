<label autocomplete="off" for="date-range">Date range:</label>
<input id="date-range" name="date-range" type="text" class="form-control pull-right"
       placeholder="Select a range" value="">
<input type="hidden" name="start_date" value="{{ $params['start_date'] }}">
<input type="hidden" name="end_date" value="{{ $params['end_date'] }}">


@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {

            var date_range = $('#date-range');
            var start_date = $('input[name="start_date"]').val();
            var end_date = $('input[name="end_date"]').val();
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
        });
    </script>
@endsection

<label autocomplete="off" for="date-range">Month picker:</label>
<input id="month-picker" name="month-picker" type="text" class="form-control pull-right"
       placeholder="Select a month" value="{{ $params['month-picker'] }}">
@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            var month_picker = $('#month-picker');
            month_picker.datepicker({
                format: "yyyy-mm",
                startView: 1,
                minViewMode: 1

            });
            month_picker.datepicker('setDate', new Date('{{ $params["month-picker"] }}'));
        });
    </script>
@endsection
@section('header-css')
    @parent
    <style>
        .datepicker {
            z-index: 4000;
        }
    </style>
@endsection
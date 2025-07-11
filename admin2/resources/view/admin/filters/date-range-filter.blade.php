<?php
/** @var \App\Classes\DateRange $date_range */
?>
@if($full)
    <form id="fraud-failed-form" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
        <div class="card border-top border-top-3">
            <div class="card-header">
                <h3 class="card-title">Filters</h3>
            </div>
            <div class="card-body">
                <div class="row">
@endif
                    <div class="form-group col-6 col-md-4 col-lg-3">
                        <label for="date-range">@if($input_name) {{ $input_name }} @else Date range @endif</label>
                        <input autocomplete="off" id="date-range{{ $input_id }}" name="date-range{{ $input_id }}" type="text" class="form-control float-right date-range-filter"
                               placeholder="No date range selected" value="{{ is_object($date_range) ? $date_range->getRange($date_format) : '' }}">
                    </div>
@if($full)
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-info">Search</button>
            </div>
        </div>
    </form>
@endif

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            var picker_elem = $('.date-range-filter');
            var picker_format = 'YYYY-MM-DD';
            picker_elem.daterangepicker(
                    {
                        linkedCalendars: false,
                        autoUpdateInput: false,
                        opens: "right",
                        alwaysShowCalendars: true,
                        locale: {
                            format: picker_format,
                            cancelLabel: 'Clear',
                            firstDay: 1
                        },
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Previous Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                            'Last 6 Months': [moment().subtract(6, 'month'), moment()]
                        }
                    }
            );

            picker_elem.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format(picker_format) + ' - ' + picker.endDate.format(picker_format));
            });

            picker_elem.on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

        });
    </script>
@endsection

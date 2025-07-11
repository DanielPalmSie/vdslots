<form action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="row">
        <div class="col-12 col-lg-6">
            <div class="card border-top border-top-3">
                <div class="card-body d-flex">
                    <div class="form-group col-4">
                        <label for="select-year">Year</label>
                        <select id="select-year" name="year" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select a year" data-allow-clear="true">
                            <option></option>
                            @for($y = \Carbon\Carbon::now()->year; $y >= 2011; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label for="select-month">Month</label>
                        <select id="select-month" name="month" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select a month" data-allow-clear="true">
                            <option></option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label for="select-week">Week</label>
                        <select id="select-week" name="week" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select a week" data-allow-clear="true">
                            <option></option>
                            @for($week = 1; $week <= 53; $week++)
                                <option value="{{ $week }}">{{ $week }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info">Search</button>
                </div>
            </div>
        </div>
    </div>
</form>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#select-year").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('year') }}").change();
            $("#select-month").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('month') }}").change();
            $("#select-week").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('week') }}").change();
        });
    </script>
@endsection

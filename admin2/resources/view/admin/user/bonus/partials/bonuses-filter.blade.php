<form id="fraud-failed-form" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card border-top border-top-3">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6 col-lg-2">
                    <div class="form-group">
                        <label for="select-results">Show last # entries</label>
                        <select name="results" id="select-results" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select one from the list">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                </div>
                @include('admin.filters.date-range-filter', ['date_format' => 'date'])
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Search</button>
        </div>
    </div>
</form>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#select-results").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('results', "50") }}").change();
        });
    </script>

@endsection

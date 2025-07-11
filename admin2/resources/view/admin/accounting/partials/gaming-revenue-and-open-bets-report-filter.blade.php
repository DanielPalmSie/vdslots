<form action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="form-group col-4 col-xl-2">
                    <label for="select-country">Month Range</label>
                    @include('admin.filters.month-range-filter')
                </div>
                <div class="form-group col-4 col-xl-2">
                    <label for="select-currency">Jurisdiction</label>
                    <select name="jurisdiction" id="select-jurisdiction" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select a jurisdiction" data-allow-clear="true">
                        <option></option>
                        @foreach($data_array['jurisdiction_query_map'] as $key => $value)
                            <option value="{{ $key }}">{{ strtoupper($key) }}</option>
                        @endforeach
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
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $("#select-jurisdiction").select2().val("{{ $jurisdiction }}").change();
        });
    </script>
@endsection

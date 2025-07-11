<form id="filter-form-consolidation" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                <div class="form-group col-6 col-lg-4 col-xl-2">
                    <label for="select-provider">Payment provider</label>
                    <select name="provider" id="select-provider" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="You need to select one provider from the list" data-allow-clear="true">
                        <option></option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->method }}">{{ ucwords($provider->method) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="checkbox">
                        <label class="font-weight-normal">
                            <input name="mismatched" type="checkbox" value="1" @if($app['request_stack']->getCurrentRequest()->get('provider', 0) == 1) checked="checked" @endif> Show only those mismatched with Transaction Report
                        </label>
                    </div>
                </div>
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
            $("#select-provider").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('provider') }}").change();
        });
    </script>
@endsection

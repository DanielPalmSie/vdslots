<form id="obj-datatable_filter" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>

        <div class="card-body">
            <div class="row">

                <div class="form-group col-md-4 col-xl-2">
                    @include('admin.filters.generic-date-range-filter')
                </div>
                <div class="form-group col-md-4 col-xl-2">
                    @include('admin.filters.username-filter')
                </div>

                <div class="form-group col-md-4 col-xl-2">
                    @include('admin.filters.country-filter')
                </div>

            </div>
        </div><!-- /.card-body -->
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Search</button>
        </div><!-- /.card-footer-->
    </div>
</form>

<form id="obj-datatable_filter" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">

    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Filters</h3>
        </div>

        <div class="card-body">
            <div class="row align-items-end">

                <div class="form-group col-4 col-lg-2">
                    @include('admin.filters.username-filter')
                </div>
                <div class="form-group col-4 col-lg-2">
                    @include('admin.filters.country-filter')
                </div>
                <div class="form-group col-4 col-lg-2">
                    @include('admin.filters.generic-date-range-filter')
                </div>
                <div class="form-group col-4 col-lg-2">
                    @include('admin.filters.tot-filter')
                </div>
            </div>
        </div><!-- /.card-body -->
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Search</button>
        </div><!-- /.card-footer -->
    </div>
</form>
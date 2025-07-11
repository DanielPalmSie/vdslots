<form id="obj-datatable_filter" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Filters</h3>
        </div>

        <div class="box-body">
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
            </div>
        </div><!-- /.box-body -->
        <div class="box-footer">
            <button type="submit" class="btn btn-info">Search</button>
        </div><!-- /.box-footer-->
    </div>
</form>

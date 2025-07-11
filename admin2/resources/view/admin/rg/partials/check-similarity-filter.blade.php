<form id="obj-datatable_filter" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">

    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>

        <div class="card-body">
            <div class="row align-items-end">

                <div class="form-group col-4 col-lg-2">
                    @include('admin.filters.username-filter')
                </div>

                <div class="form-group col-4 col-lg-2">
                    @include('admin.filters.similarity-filter')
                </div>

            </div>
        </div><!-- /.box-body -->
        <div class="card-footer bg-white">
            <button type="submit" class="btn btn-info">Search</button>
        </div><!-- /.box-footer-->
    </div>
</form>

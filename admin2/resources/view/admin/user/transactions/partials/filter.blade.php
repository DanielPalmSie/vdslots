<form id="search-form-transactions" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card border-top border-top-3">
        <div class="card-body">
            <div class="row">
                @include('admin.filters.date-range-filter', ['date_format' => 'date'])
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-info">Search</button>
        </div>
    </div>
</form>

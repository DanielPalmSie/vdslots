<form action="{{ $app['url_generator']->generate('sportsbook.clean-events') }}" method="get">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-4">
                    <label>Event ID</label>
                    <input name="event_id" class="form-control" placeholder="Event ID" type="text">
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-info px-4">Search</button>
        </div>
    </div>
</form>

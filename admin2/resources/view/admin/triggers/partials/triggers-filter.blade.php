<form action="{{ $app['url_generator']->generate('fraud-aml-monitoring') }}" method="get">
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Filters</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-xs-6 col-lg-2">
                    <label>Name</label>
                    <div class="input-group">
                        
                    </div>
                </div>
            </div>
        </div><!-- /.box-body -->
        <div class="box-footer">
            <button class="btn btn-info">Search</button>
        </div><!-- /.box-footer-->
    </div>
</form>
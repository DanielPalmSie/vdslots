<form action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title">Filters</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="form-group col-4 col-lg-2">
                    <label for="bin">BIN</label>
                    <input type="text" name="bin" class="form-control"
                           value="<?= $params['bin'] ?>"
                           pattern="[0-9]*" minlength="6" maxlength="6">
                </div>
                <div class="form-group col-4 col-lg-2">
                    <label for="bin">Block Status</label>
                    <select name="status" class="form-control select2-class">
                        <option></option>
                        <option value="1" {{ $params['status'] === '1' ? 'selected="selected"' : '' }}>Blocked</option>
                        <option value="0" {{ $params['status'] === '0' ? 'selected="selected"' : '' }}>Allowed</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <button class="btn btn-info">Search</button>
        </div>
    </div>
</form>

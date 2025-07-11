@extends('admin.layout')

@section('content')

<div class="container-fluid">

    @include('admin.payments.partials.topmenu')

    <form
        action="{{ isset($blacklistedBin['bin']) ?
                    $app['url_generator']->generate('bin-blacklist.update') :
                    $app['url_generator']->generate('bin-blacklist.store')}}"
        target="_self" method="post">
        <div class="alert alert-danger <?= $errorMessage ? '' : 'd-none' ?>">
            <?= $errorMessage ?>
        </div>

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ isset($blacklistedBin) ? 'Update' : 'Create' }} Blocked BIN</h3>
            </div>

            <div class="card-body">
                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                <input type="hidden" name="id" value="<?= $blacklistedBin['id'] ?? '' ?>">
                <div class="row">

                    <div class="form-group col-md-6">
                        <label for="bin">BIN</label>
                        <input type="text" name="bin" class="form-control"
                            value="<?= $blacklistedBin['bin'] ?? '' ?>"
                            pattern="[0-9]{6}" required minlength="6" maxlength="6">
                        <span>BIN number - 6 digits</span>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="bin">Block Status</label>
                        <select name="status" class="form-control select2-class">
                            <option value="1" selected>Blocked</option>
                            <option value="0"
                                <?= isset($blacklistedBin['status']) && !$blacklistedBin['status'] ? 'selected="selected"' : '' ?>>
                                Allowed
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="bin">Comment</label>
                        <input type="text" name="comment" class="form-control"
                            value="<?= $blacklistedBin['comment'] ?? '' ?>" required maxlength="255">
                        <span>Reason for this action</span>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" name="submit" value="" id="submit" class="btn btn-primary">
                    {{ isset($blacklistedBin) ? 'Update' : 'Create' }}
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

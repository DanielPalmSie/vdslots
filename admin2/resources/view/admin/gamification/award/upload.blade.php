@extends('admin.layout')

@section('content')
    <div class="box box-solid box-primary">
        <div class="box-header">
            <h3 class="box-title">Upload CSV to Give Awards</h3>

            </div>
        </div>

        <div class="box-body">
            <form action="{{ $uploadUrl }}"
                  method="POST"
                  enctype="multipart/form-data">

                <input type="hidden" name="token" value="{{ $_SESSION['token'] ?? '' }}"/>

                <div class="alert alert-info">
                    CSV must contain header: <code>user_id, award_id, amount</code>
                </div>

                <div class="form-group">
                    <label for="award_csv">CSV&nbsp;file</label>
                    <input type="file"
                           name="award_csv"
                           id="award_csv"
                           accept=".csv"
                           class="form-control"
                           required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-upload"></i> Import
                </button>
            </form>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
@endsection

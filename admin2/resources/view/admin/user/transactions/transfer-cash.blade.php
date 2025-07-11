@extends('admin.layout')
<?php $u = cu($user->username);?>

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @if(!empty($message))
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="alert alert-danger" role="alert">
                    <p>{{ $message }}</p>
                </div>
            </div>
        </div>
    @endif
    <form id="transfer-cash-form"
          method="post"
          action="{{ $app['url_generator']->generate('admin.user-transfer-cash', ['user' => $user->id]) }}">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h5 class="card-title">Transfer cash to {{ $user->username }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="amount">Amount to transfer <b>in cents/öre:</b></label>
                            <input type="text" name="amount" class="form-control" id="transfer-amount"
                                   placeholder="Amount in cents">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" name="description" class="form-control" value="Admin transferred money"
                                   placeholder="Write a description" id="transfer-description" maxlength="254">
                            <span class="help-block"><span id="chars-left">2</span> characters left </span>
                        </div>
                        <div class="form-group">
                            <label for="transactiontype">Transaction Type</label>
                            <select name="transactiontype" id="transactiontype" class="form-control"
                                    style="width: 100%;" data-placeholder="Select a type"
                                    data-allow-clear="true">
                                <option></option>
                                @foreach($type_list as $key => $value)
                                    <option value="{{ $key }}">{{ $key }}.) {{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-info" type="submit" id="transfer-cash-button">Transfer</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div id="transfer-cash-verify" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title">Alert</h4>
                </div>
                <div class="modal-body">
                    <div id="transfer-cash-verify-message"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-flat btn-ok" data-action="show">Yes</button>
                    <button type="button" class="btn btn-danger btn-flat btn-close" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        $(function () {
            $('#transactiontype').select2();
            function setupChars() {
                $("#chars-left").text(254 - $(this).val().length);
            }
            $("#transfer-description").on('keyup', setupChars).trigger('keyup');

            $("#transfer-cash-form").submit(function (event) {
                event.preventDefault();

                var modal = $('#transfer-cash-verify'),
                    amountField = $("#transfer-amount"),
                    messageField = $('#transfer-cash-verify-message');

                $.ajax({
                    url: "{{ $app['url_generator']->generate('admin.user-transfer-cash-verify', ['user' => $user->id]) }}",
                    type: "POST",
                    data: {
                        'amount': amountField.val()
                    },
                    success: function () {
                        event.currentTarget.submit();
                    },
                    error: function (error) {
                        messageField.html('<p><b>' + error.responseJSON.message + '</b></p>');

                        modal.modal({
                            show: 'true'
                        }).one('click', '.btn-ok', function () {
                            modal.modal('hide');
                            event.currentTarget.submit()
                        });
                    }
                });
            });
        });
    </script>
@endsection


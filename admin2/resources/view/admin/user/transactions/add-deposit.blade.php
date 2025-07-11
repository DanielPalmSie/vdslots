@extends('admin.layout')
<?php $u = cu($user->username);?>

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <form id="add-deposit-form"
          method="post"
          action="{{ $app['url_generator']->generate('admin.user-add-deposit', ['user' => $user->id]) }}">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h5 class="card-title">Generate deposit for: {{ $user->id }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="amount">Amount <b>in cents:</b></label>
                            <input type="text" name="amount" class="form-control" id="deposit_amount_in_cents"
                                   placeholder="Amount in cents" value="{{ $app['request_stack']->getCurrentRequest()->get('amount') }}">
                        </div>
                        <div class="form-group">
                            <label for="dep_type">Main Type</label>
                            <select name="dep_type" id="dep_type" class="form-control"
                                    style="width: 100%;" data-placeholder="Select a type"
                                    data-allow-clear="true">
                                <option></option>
                                @foreach($methods_list as $dep_method)
                                    <option value="{{ $dep_method }}">{{ $dep_method }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="scheme">Scheme / Bank name / Subtype</label>
                            <input type="text" name="scheme" class="form-control"
                                   placeholder="Subtype" value="{{ $app['request_stack']->getCurrentRequest()->get('scheme') }}">
                        </div>
                        <div class="form-group">
                            <label for="card_hash">Card Hash / Bank account number</label>
                            <input type="text" name="card_hash" class="form-control"
                                   placeholder="Card hash" value="{{ $app['request_stack']->getCurrentRequest()->get('card_hash') }}">
                        </div>
                        <div class="form-group">
                            <label for="ext_id">Ext ID</label>
                            <input type="text" name="ext_id" class="form-control"
                                   placeholder="Ext ID" value="{{ $app['request_stack']->getCurrentRequest()->get('ext_id') }}">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button name="transfer_money" class="btn btn-info" type="submit" id="make_manual_deposit">Deposit</button>
                    </div>
                </div>
            </div>

            @if ($has_deposit_limits)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-info">
                        <div class="card-header">
                            <h5 class="card-title">Deposit limit info</h5>
                        </div>
                        @foreach($deposit_limits as $deposit_limit)
                            <div class="card-body">
                                <dl class="row ml-5">
                                    <dt class="col-sm-4">Type</dt>
                                    <dd class="col-sm-8">
                                        {{ \App\Helpers\DataFormatHelper::getLimitsNames()[$deposit_limit->type] }} - {{ $deposit_limit->time_span }}
                                    </dd>

                                    <dt class="col-sm-4">Forced until</dt>
                                    <dd class="col-sm-8">{{ $deposit_limit->forced_until ?? 'Not forced' }}</dd>

                                    <dt class="col-sm-4">Date of activation</dt>
                                    <dd class="col-sm-8">{{ $deposit_limit->created_at }}</dd>

                                    <dt class="col-sm-4">Current limit</dt>
                                    <dd class="col-sm-8">{{ $user->currency }} {{ \App\Helpers\DataFormatHelper::nf($deposit_limit->cur_lim) }}</dd>

                                    <dt class="col-sm-4">Remaining</dt>
                                    <dd class="col-sm-8">{{ $user->currency }} {{ \App\Helpers\DataFormatHelper::nf($deposit_limit->remaining) }}</dd>

                                    <dt class="col-sm-4">Remaining until</dt>
                                    <dd class="col-sm-8">{{ $deposit_limit->resets_at }}</dd>

                                    <dt class="col-sm-4">Will change on</dt>
                                    <dd class="col-sm-8">{{ $deposit_limit->changes_at }}</dd>

                                    <dt class="col-sm-4">Will change to</dt>
                                    <dd class="col-sm-8">
                                        @if (!empty($deposit_limit->new_lim))
                                            {{ $user->currency }} {{ \App\Helpers\DataFormatHelper::nf($deposit_limit->new_lim) }}
                                        @else
                                            None
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        @endforeach

                    </div>

                </div>
            @endif

        </div>
    </form>

    <div id="add-deposit-verify" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title">Alert</h4>
                </div>
                <div class="modal-body">
                    <div id="add-deposit-verify-message"></div>
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
            $('#dep_type').select2().val("{{ $app['request_stack']->getCurrentRequest()->get('dep_type') }}").change();

            $("#make_manual_deposit").click(function($e) {
                $e.preventDefault();

                $.ajax({
                    url: "{{ $app['url_generator']->generate('admin.user-add-deposit-verify', ['user' => $user->id]) }}",
                    type: "POST",
                    data: {
                        'amount': $("#deposit_amount_in_cents").val()
                    },
                    success: function () {
                        $("#add-deposit-form").submit();
                    },
                    error: function (jqXHR) {
                        var modal_alert = $('#add-deposit-verify-message');
                        modal_alert.html('<p><b>'+jqXHR.responseJSON.message+'</b></p>');

                        $('#add-deposit-verify').modal({
                            show: 'true'
                        }).one('click','.btn-ok', function(){
                            $('#add-deposit-verify').modal('hide');
                            $("#add-deposit-form").submit();
                        }).one('click','.btn-close',function() {

                        }).on('hide.bs.modal', function(e){

                        });
                    }
                });
            })


        });
    </script>
@endsection


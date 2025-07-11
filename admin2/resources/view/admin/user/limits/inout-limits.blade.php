
@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <div class="card">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-gaming-limits', ['user' => $user->id]) }}">Gaming limits</a></li>
                <li class="nav-item border-primary border-top"><a class="nav-link active">Deposit/Withdrawal limits</a></li>
                @if(p('view.account.limits.block'))
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-block-management', ['user' => $user->id]) }}">Account Blocking management</a></li>
                @endif
            </ul>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="row">
                            <div class="col-6 col-lg-4">
                                @if(count($user->settings_repo->getInOutLimits()) > 0)
                                    <div style="min-height: 320px" class="card"><div class="card-outline card-secondary card-body">
                                            <table class="table table-hover table-bordered">
                                                    <?php $delete_permission = p('remove.account.limits.in.out'); ?>
                                                <tbody>
                                                <tr>
                                                    <th class="bg-gray-light">Name</th>
                                                    <th class="bg-gray-light">Type</th>
                                                    <th class="bg-gray-light">Value ({{ $user->currency }})</th>
                                                    @if($delete_permission)
                                                        <th class="bg-gray-light">Action</th>
                                                    @endif
                                                </tr>
                                                @foreach($user->settings_repo->getInOutLimits() as $key => $val)
                                                    <tr id="{{ $key }}">
                                                        <td id="td-key">{{ ucwords(explode('-', $key)[0]) }}</td>
                                                        <td>{{ strtoupper(explode('-', $key)[1]) }}</td>
                                                        <td>{{ \App\Helpers\DataFormatHelper::nf($val) }}</td>
                                                        @if($delete_permission)
                                                            <td><button data-key="{{ $key }}" class="remove-limit-btn btn btn-xs btn-danger"
                                                                        type="submit">Remove</button>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div></div>
                                @else
                                    <div class="card"><div class="card-outline card-secondary card-body"><p>No deposits or withdrawals limits set.</p></div> </div>
                                @endif
                            </div>
                            @if(p('edit.account.limits.in.out'))
                                <div class="col-6 col-lg-2">
                                    <div style="min-height: 320px" class="card"><div class="card-outline card-secondary card-body">
                                            <div class="form-group"><span id="helpBlockRemove" class="help-block"></span></div>
                                            <form id="add-limit-form" method="post">
                                                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                                                <div class="form-group">
                                                    <label for="inout-type form-label">Limit type</label>
                                                    <select data-placeholder="Select a limit type" id="inout-type" name="type"
                                                            class="form-control ">
                                                        <option></option>
                                                        <option value="in">Deposit</option>
                                                        <option value="out">Withdrawal</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="method-select">Payment method</label>
                                                    <select name="method" id="method-select" class="form-control"
                                                            data-placeholder="Select a method"
                                                            data-allow-clear="true">
                                                        <option></option>
                                                        @foreach(\App\Repositories\TransactionsRepository::getMethods($app, $request) as $method)
                                                            <option value="{{ $method }}">{{ ucwords($method) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="limit">Limit in cents</label>
                                                    <input type="text" class="form-control" name="limit">
                                                </div>
                                                <div class="form-group">
                                                    <span id="helpBlockError" class="help-block"></span>
                                                </div>
                                                <div class="form-group">
                                                    <button id="add-limit-btn" class="btn btn-primary" type="submit">Submit</button>
                                                </div>
                                            </form>
                                        </div>
                                        @endif
                                    </div></div>
                        </div>
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
            $('#inout-type').select2();
            $('#method-select').select2();

            $('#add-limit-btn').click(function (e) {
                e.preventDefault();
                var self = $(this);
                $.ajax({
                    url: "{{ $app['url_generator']->generate('admin.user-set-inout-limits', ['user' => $user->id]) }}",
                    type: "POST",
                    data: $('#add-limit-form').serialize(),
                    success: function (data, textStatus, jqXHR) {
                        response = data;
                        $("#helpBlockError").text(response['message']);
                        if (response['success'] == true) {
                            $(".form-control").addClass('has-success').val('');
                            setTimeout(function(){
                               location.reload();
                            }, 2500);
                        } else {
                            $(".form-control").addClass('has-error');
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            $('.remove-limit-btn').click(function (e) {
                e.preventDefault();
                var self = $(this);
                var key = self.data('key');
                $.ajax({
                    url: "{{ $app['url_generator']->generate('admin.user-remove-inout-limits', ['user' => $user->id]) }}",
                    type: "POST",
                    data: { key: key},
                    success: function (data, textStatus, jqXHR) {
                        response = data;
                        $("#helpBlockRemove").text(response['message']);
                        if (response['success'] == true) {
                            $('#'+key).remove();
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });
        });
    </script>
@endsection

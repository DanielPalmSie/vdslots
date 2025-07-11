@extends('admin.layout')
<?php $u = cu($user->username);?>

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <div class="card">
        @include('admin.user.bonus.partials.nav-bonuses')
        <div class="card-body">
                <form id="add-bonus-form" method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
                    <div class="row">
                        <!-- Bonus Type Selection -->
                        <div class="col-md-5 col-lg-2">
                            <div class="card card-info">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="bonus_type">Bonus Type</label>
                                        <select data-placeholder="Select a type" name="bonus-type" class="form-control"
                                                id="bonus_type">
                                            <option></option>
                                            @foreach($bonus_types as $bonus_type)
                                                <option value="{{ $bonus_type }}">{{ $bonus_type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Normal Bonus Sub Form -->
                        <div id="normal-bonus-sub-form" class="col-12 col-lg-5">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h5 class="card-title">Add Freespins to User: {{ $user->id }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="normal-bonus-select">Choose Bonus</label>
                                        <select name="normal-select" id="normal-bonus-select" class="form-control"
                                                style="width: 100%;" data-placeholder="You need to select a bonus type first"
                                                data-allow-clear="true">
                                            <option></option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="normal-bonus-comment">Comment</label>
                                        <input type="text" name="normal-bonus-comment" class="form-control"
                                               placeholder="Comment">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button formaction="{{ $app['url_generator']->generate('admin.user-bonuses-add-bonus-post', ['user' => $user->id, 'f-id' => 'normal']) }}"
                                            data-return="{{ $app['url_generator']->generate('admin.user-bonuses-rewards', ['user' => $user->id]) }}"
                                            id="add-normal-bonus-btn" class="btn btn-info" type="submit">Add Bonus
                                    </button>
                                    <h4 id="ajax-message-normal"></h4>
                                </div>
                            </div>
                        </div>

                        <!-- Deposit Bonus Sub Form -->
                        <div id="deposit-bonus-sub-form" class="col-12 col-lg-5">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h5 class="card-title">Add Deposit/Reload Bonus to User: {{ $user->id }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="deposit-bonus-select">Choose Bonus</label>
                                        <select name="deposit-select" id="deposit-bonus-select" class="form-control"
                                                style="width: 100%;" data-placeholder="You need to select a bonus type first"
                                                data-allow-clear="true">
                                            <option></option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="deposit-bonus-amount">Deposit Amount <b>in cents/öre:</b></label>
                                        <input type="text" name="deposit-bonus-amount" class="form-control"
                                               placeholder="Amount in cents">
                                    </div>
                                    <div class="form-group">
                                        <label for="deposit-bonus-comment">Comment</label>
                                        <input type="text" name="deposit-bonus-comment" class="form-control"
                                               placeholder="Comment">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button formaction="{{ $app['url_generator']->generate('admin.user-bonuses-add-bonus-post', ['user' => $user->id, 'f-id' => 'deposit']) }}"
                                            data-return="{{ $app['url_generator']->generate('admin.user-bonuses-rewards', ['user' => $user->id]) }}"
                                            id="add-deposit-bonus-btn" class="btn btn-info" type="submit">Add Bonus
                                    </button>
                                    <h4 id="ajax-message-deposit"></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        $(function () {
            $('#normal-bonus-select').select2();
            $('#deposit-bonus-select').select2();
            $('#bonus_type').select2().on('change', function (e) {
                var normal_bonus_select = $('#normal-bonus-select');
                var deposit_bonus_select = $('#deposit-bonus-select');
                normal_bonus_select.data('placeholder', 'Loading values...');
                normal_bonus_select.select2();
                deposit_bonus_select.data('placeholder', 'Loading values...');
                deposit_bonus_select.select2();
                var type = this.value ? this.value : '';
                var url = "{{ $app['url_generator']->generate('admin.user-bonuses-add-bonus', ['user' => $user->id, 'list' => 1]) }}";

                if (type) {
                    url = url + '&type=' + type;
                }
                var url_limit = url + '&limit=1';
                url = url + '&limit=0';

                $.get(url, function (data) {
                    retValues = jQuery.parseJSON(data);
                    normal_bonus_select.empty();
                    normal_bonus_select.select2('val', '');
                    normal_bonus_select.append("<option></option>");
                    $.each(retValues, function (item, element) {
                        normal_bonus_select.append("<option value='" + element['b_id'] + "'>" + element['bonus_name'] + "</option>");
                    });
                    normal_bonus_select.trigger("change");
                    normal_bonus_select.data('placeholder', 'List fully loaded. Select a bonus');
                    normal_bonus_select.select2();
                });

                $.get(url_limit, function (data) {
                    retValues = jQuery.parseJSON(data);
                    deposit_bonus_select.empty();
                    deposit_bonus_select.select2('val', '');
                    deposit_bonus_select.append("<option></option>");
                    $.each(retValues, function (item, element) {
                        deposit_bonus_select.append("<option value='" + element['b_id'] + "'>" + element['bonus_name'] + " - " + element['rake_percent'] / 100 + "x" + "</option>");
                    });
                    deposit_bonus_select.trigger("change");
                    deposit_bonus_select.data('placeholder', 'List fully loaded. Select a bonus');
                    deposit_bonus_select.select2();
                });
            });

            $('#add-normal-bonus-btn').click(function (e) {
                e.preventDefault();
                var self = $(this);
                $.ajax({
                    url: self.attr('formaction'),
                    type: "POST",
                    data: $('#add-bonus-form').serialize(),
                    success: function (data, textStatus, jqXHR) {
                        response = data;
                        $("#ajax-message-normal").text(response['message']);
                        if (response['success'] == true) {
                            window.setTimeout(window.location = self.data('return'), 10000);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            $('#add-deposit-bonus-btn').click(function (e) {
                e.preventDefault();
                var self = $(this);
                $.ajax({
                    url: self.attr('formaction'),
                    type: "POST",
                    data: $('#add-bonus-form').serialize(),
                    success: function (data, textStatus, jqXHR) {
                        response = data;
                        $("#ajax-message-deposit").text(response['message']);
                        if (response['success'] == true) {
                            window.setTimeout(window.location = self.data('return'), 10000);
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

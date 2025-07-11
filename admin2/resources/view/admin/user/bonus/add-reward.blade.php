@extends('admin.layout')
<?php $u = cu($user->username);?>

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <div class="card">
        @include('admin.user.bonus.partials.nav-bonuses')
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active">
                    <form id="add-reward-form" method="post">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
                        <div class="row">
                            <!-- Reward Type Selection -->
                            <div class="col-12 col-lg-2">
                                <div class="card card-info">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="reward_type">Choose Reward Type</label>
                                            <select data-placeholder="Select a type" name="reward-type" class="form-control"
                                                    id="reward_type">
                                                <option></option>
                                                @foreach($rewards_types as $reward_type)
                                                    <option value="{{ $reward_type }}">{{ $reward_type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Reward Section -->
                            <div class="col-12 col-lg-5">
                                <div class="card card-info">
                                    <div class="card-header">
                                        <h5 class="card-title">Add Reward to Player: {{ $user->id }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="award_id">Choose Reward</label>
                                            <select name="award-id" id="award_id" class="form-control"
                                                    style="width: 100%;">
                                                <option></option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="comment">Comment</label>
                                            <input type="text" name="comment" class="form-control" placeholder="Comment">
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button formaction="{{ $app['url_generator']->generate('admin.user-bonuses-add-reward', ['user' => $user->id]) }}"
                                                data-return="{{ $app['url_generator']->generate('admin.user-bonuses', ['user' => $user->id]) }}"
                                                id="add-reward-btn" class="btn btn-info" type="submit">Add Reward</button>
                                        <h4 id="ajax-message-reward"></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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
            $('#award_id').select2();
            $('#reward_type').select2().on('change', function (e) {
                var reward_list_select = $('#award_id');
                var type = this.value ? this.value : '';
                var url = "{{ $app['url_generator']->generate('admin.user-bonuses-add-reward', ['user' => $user->id, 'list' => 1]) }}";
                if (type) {
                    url = url + '&type=' + type;
                }
                reward_list_select.select2({
                    ajax: {
                        url: url,
                        delay: 250,
                        quietMillis: 100,
                        data: function (params) {
                            return {
                                search: params.term,
                                page: params.page
                            };
                        },
                        processResults: function (response, params) {
                            params.page = params.page || 1;
                            var results = [];
                            $.each(response.data, function (key, value) {
                                results.push({
                                    id: value.id,
                                    text: value.description,
                                });
                            });

                            return {
                                results: results,
                                pagination: {
                                    more: (params.page * 10) < response.total
                                }
                            };
                        }
                    },
                    placeholder: "Select a game",
                    allowClear: true,
                    cache: true,
                });
            });

            $('#add-reward-btn').click(function (e) {
                e.preventDefault();
                var self = $(this);
                $.ajax({
                    url: self.attr('formaction'),
                    type: "POST",
                    data: $('#add-reward-form').serialize(),
                    success: function (data, textStatus, jqXHR) {
                        response = data;
                        $("#ajax-message-reward").text(response['message']);
                        if (response['success'] == true) {
                            window.setTimeout(window.location = self.data('return'), 2000);
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

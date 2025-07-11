@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

<div class="card">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-gaming-limits', ['user' => $user->id]) }}">Gaming limits</a></li>
            @if(p('view.account.limits.in.out'))
                <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-inout-limits', ['user' => $user->id]) }}">Deposit/Withdrawal limits</a></li>
            @endif
            <li class="nav-item border-top border-primary"><a class="nav-link active">Account Blocking management</a></li>
        </ul>
        <?php $edit_permission = p('edit.account.limits.block'); ?>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active">
                    <table class="table table-striped table-bordered">
                        <tbody>
                        <tr>
                            <th>Type</th>
                            <th>Date of activation</th>
                            <th>Current duration</th>
                            <th>Will change on</th>
                            <th>Will change to</th>
                            @if(p('edit.account.limits.block'))
                                <th>Actions</th>
                            @endif
                        </tr>
                        <tr>
                            <td>Lock account</td>
                            @if (empty($user->settings_repo->settings->{"unlock-date"}) && empty($user->settings_repo->settings->{"lock-hours"}))
                                <td colspan="4">Not locked</td>
                                <td>
                                    @if($edit_permission)
                                        <button data-key="lock-account" data-title="Lock account" data-type="set-lock"
                                                data-toggle="modal" data-target="#single-modal"
                                                data-label="Amount of days to lock this account"
                                                data-newplacehoder="Days"
                                                class="btn btn-xs btn-primary single-limit-btn">Set a lock
                                        </button>
                                    @endif
                                </td>
                            @else
                                <td>{{ $user->settings_repo->settings->{"lock-date"} }}</td>
                                <td>{{ $user->settings_repo->settings->{"lock-hours"} }} hours</td>
                                <td>{{ $user->settings_repo->settings->{"unlock-date"} }}</td>
                                <td></td>
                                <td>
                                    @if($user->country != 'GB' && $edit_permission)
                                        <button data-title="Revoke lock" data-label="If you early revoke this account lock a 7 days cooling off period applies."
                                                data-url="{{ $app['url_generator']->generate('admin.user-edit-gaming-limits', ['user' => $user->id, 'type' => 'revoke-lock']) }}"
                                                class="btn btn-xs btn-primary" id="revoke-button">Early revoke
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td>Self-exclusion</td>
                            @if (!$user->block_repo->isSelfExcluded() && !$user->block_repo->isExternalSelfExcluded())
                                <td colspan="4">Not self-excluded</td>
                                <td>
                                    @if($edit_permission)
                                        <button data-key="self-exclude" data-title="Set self-exclusion" data-type="set-self"
                                                data-toggle="modal" data-target="#self-exclusion-modal"
                                                data-label="Exclude duration"
                                                data-newplaceholder="Months"
                                                class="btn btn-xs btn-primary self-exclusion-limit-btn">Set self-exclusion
                                        </button>
                                    @endif
                                </td>
                            @elseif($user->block_repo->isSelfExcluded() && !$user->block_repo->isExternalSelfExcluded())
                                <td>{{ $user->settings_repo->settings->{"excluded-date"} }}</td>
                                <td>{{ \Carbon\Carbon::now()->diffInMonths(\Carbon\Carbon::parse($user->settings_repo->settings->{"unexclude-date"})->addDay()) }} months</td>
                                <td>{{ $user->settings_repo->settings->{"unexclude-date"} }}</td>
                                <td></td>
                                <td>
                                    <button data-key="self-exclude" data-title="Extend self-exclusion" data-type="extend-self"
                                            data-toggle="modal" data-target="#self-exclusion-modal"
                                            data-label="More exclude duration"
                                            data-newplaceholder="Months"
                                            class="btn btn-xs btn-primary self-exclusion-limit-btn">Extend self-exclusion
                                    </button>
                                </td>
                            @elseif($user->block_repo->isExternalSelfExcluded())
                                <td colspan="5">{{ $user->block_repo->getBlockReasonDescription() }}</td>
                            @endif
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

    @include('admin.user.limits.partials.single-modal')
    @include('admin.user.limits.partials.self-exclusion-modal', compact('self_exclusion_options'))

@endsection

@section('footer-javascript')
    @parent
    <script>

        $('#revoke-button').on('click', function(e) {
            e.preventDefault();
            var self = $(this);
            showConfirmBtn(self.data('title'), self.data('label'), self.data('url'));
        });

        $('.self-exclusion-limit-btn').on('click', function(e) {
            e.preventDefault();
            var self = $(this);
            $('#self-exclusion-key').val(self.data('key'));
            $('#self-exclusion-type').val(self.data('type'));
            $('#self-exclusion-subtype').val(self.data('subtype'));
            $('#self-exclusion-modal-title').text(self.data('title'));
            $('#self-exclusion-modal-input-label').text(self.data('label'));
            $('#self-exclusion-modal-input-field').attr("placeholder", self.data('newplaceholder'));
        });

        $('.self-exclusion-modal-save-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ $app['url_generator']->generate('admin.user-edit-gaming-limits', ['user' => $user->id]) }}";
            var self = $(this);
            $.ajax({
                url: url,
                type: "POST",
                data: $('#self-exclusion-modal-form').serialize(),
                success: function (data, textStatus, jqXHR) {
                    console.log(data);
                    response = jQuery.parseJSON(data);
                    $('#help-block-self-exclusion').text(response['message']);
                    if (response['success'] == true) {
                        $('#self-exclusion-modal-form').remove();
                        $('.self-exclusion-modal-save-btn').remove();
                        $('.self-exclusion-modal-close-btn').data('action', 'reload');
                    } else {
                        $('#self-exclusion-modal-group').addClass('has-error');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('AJAX ERROR');
                }
            });
        });

        $('.self-exclusion-modal-close-btn').on('click', function(e) {
            e.preventDefault();
            if ($(this).data('action') == 'reload') {
                location.reload();
            }
        });

        $('.single-limit-btn').on('click', function(e) {
            e.preventDefault();
            var self = $(this);
            $('#single-key').val(self.data('key'));
            $('#single-type').val(self.data('type'));
            $('#single-subtype').val(self.data('subtype'));
            $('#single-modal-title').text(self.data('title'));
            $('#single-modal-input-label').text(self.data('label'));
            $('#single-modal-input-field').attr("placeholder", self.data('newplaceholder'));
        });

        $('.single-modal-save-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ $app['url_generator']->generate('admin.user-edit-gaming-limits', ['user' => $user->id]) }}";
            var self = $(this);
            $.ajax({
                url: url,
                type: "POST",
                data: $('#single-modal-form').serialize(),
                success: function (data, textStatus, jqXHR) {
                    response = jQuery.parseJSON(data);
                    if (response['success'] == true) {
                        $('#single-modal-form').remove();
                        $('.single-modal-save-btn').remove();
                        $('.single-modal-close-btn').data('action', 'reload');
                        $('#help-block-single-limits').text(response['message']);
                    } else {
                        $('#single-modal-group').addClass('has-error');
                        $('#help-block-single-limits').text(response['message']);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('AJAX ERROR');
                }
            });
        });

        $('.single-modal-close-btn').on('click', function(e) {
            e.preventDefault();
            if ($(this).data('action') == 'reload') {
                location.reload();
            }
        });
    </script>
@endsection

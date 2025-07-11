@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <style>
        .error {
            color: red;
        }
        tbody td:first-child {
            vertical-align: middle !important;
        }
    </style>
    <div class="card">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="nav-item border-top border-primary"><a class="nav-link active">Gaming limits</a></li>
                @if(p('view.account.limits.in.out'))
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-inout-limits', ['user' => $user->id]) }}">Deposit/Withdrawal limits</a></li>
                @endif
                @if(p('view.account.limits.block'))
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('admin.user-block-management', ['user' => $user->id]) }}">Account Blocking management</a></li>
                @endif
            </ul>
            <div class="card-body">
                <div class="tab-content" style="overflow-x: hidden">
                    <div class="tab-pane active">
                        @foreach($common_limits as $limit => $title)
                            @if ($limit == 'betmax')
                                @include('admin.user.limits.partials.betmax')
                            @elseif ($limit == 'timeout')
                                @include('admin.user.limits.partials.timeout')
                            @elseif ($limit == 'login')
                                @if (lic('showLoginLimit', [], $user->id))
                                    @include('admin.user.limits.partials.login')
                                @endif
                            @elseif ($limit == 'balance' && lic('hasBalanceTypeLimit', [], $user->id))
                                @include('admin.user.limits.partials.balance')
                            @else
                                @include('admin.user.limits.partials.normal-limit')
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="confirmation-modal" tabindex="-1" role="dialog" aria-labelledby="confirmation-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmation-modal-label">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <p class="question">One fine body…</p>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default dismiss" data-dismiss="modal">No</button>
                    <button type="button" class="btn btn-primary confirm">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="force-limit-modal" tabindex="-1" role="dialog" aria-labelledby="force-limit-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="force-limit-modal-label">Force limit title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="form-group">
                        <label for="days">Days</label>
                        <input type="number" class="form-control" id="days" placeholder="Enter number of days">
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default dismiss" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary set-forced-limit">Save</button>
                </div>
            </div>
        </div>
    </div>

    @include('admin.user.limits.partials.single-modal')
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
@endsection


@section('footer-javascript')
    @parent
    <script>
        $(".actions button").click(function($e) {
            $e.preventDefault();
            var $self = $(this);
            var action = $self.val();
            var $form = $("#" + $self.parent().data('form'));
            var limit_removed = $self.parent().data('has_removed_limits');
            var $confirmation_modal = $("#confirmation-modal").data('show', true);

            switch (action) {
                case 'update-limits':
                    $confirmation_modal.find('.question').text('Are you sure you want to update the limits?');
                    break;
                case 'remove':
                    $confirmation_modal.find('.question').text('Are you sure you want to remove the limits?');
                    break;
                case 'remove-no-cooling':
                    $confirmation_modal.find('.question').text('Are you sure you want to remove the limits with no cooling period?');
                    break;
                case 'history':
                    $confirmation_modal.find('.question').text('Are you sure you want to leave this page and go to limits history?');
                    break;
                case 'force-limit':
                    $confirmation_modal.data('show', false);
                    var $force_limit_modal = $("#force-limit-modal");
                    $force_limit_modal.find("#days").val(0);
                    $force_limit_modal.find(".modal-title").text($form.find(".hidden-title").val());
                    $force_limit_modal.modal("show");
                    $force_limit_modal.find(".set-forced-limit").off("click");
                    $force_limit_modal.find(".set-forced-limit").click(function() {
                        var $days = $("#days");

                        if ($days.val() < 1) {
                            $days.parent().find(".error").remove();
                            $days.parent().append("<span class='error'>The number of days must be greater than 0</span>");
                            $days.parent().find("input").off("click");
                            $days.parent().find("input").click(function() {
                               $(this).parent().find(".error").remove();
                            });
                            return;
                        }

                        $force_limit_modal.modal("hide");
                        $form
                            .append($("<input type='hidden' name='number_days'>").val($days.val()))
                            .append($("<input type='hidden' name='action'>").val('force-limit'))
                            .submit();
                    });
                    $force_limit_modal.find(".dismiss").off("click");
                    $force_limit_modal.find(".dismiss").on("click", function(){
                        $force_limit_modal.modal('hide');
                    });
                    break;
                case 'remove-force-limit':
                    $confirmation_modal.find('.question').text('Are you sure you want to remove forced limit?');
                    break;

                default:
            }

            if($confirmation_modal.data('show') === true) {
                $confirmation_modal.modal('show');
                $confirmation_modal.find(".confirm").off('click');
                $confirmation_modal.find(".confirm").on("click", function(){
                    var errors = false;
                    if (action === 'update-limits') {
                        var $empty_when = 3;
                        if ($form.attr("id") === 'timeout-limit' || $form.attr("id") === 'betmax-limit') {
                            $empty_when = 1;
                        }
                        if ($form.attr("id") === 'net_deposit-limit') {
                            $empty_when = 0;
                        }

                        var current_limits_empty = $form.find('.cur-lim').filter(function() {
                            return $(this).val().toString().length === 0;
                        }).toArray().length === $empty_when;

                        var empty_fields = [];

                        if($form.attr("id") === 'net_deposit-limit') {
                            var has_at_least_one_new_limit = $form.find(".new-limit").filter(function() {
                                return $(this).val().toString().length > 0;
                            }).toArray().length > 0;
                            errors = !has_at_least_one_new_limit;
                        }
                        else if (current_limits_empty || limit_removed) {
                            empty_fields = $form.find(".new-limit").filter(function() {
                                $(this).parent().find('.error').remove();

                                if ($(this).val().toString().length === 0) {
                                    $(this).parent().append("<span class='error'>This field is required</span>");
                                    errors = true;
                                    return false
                                }

                                if ($(this).val() < 1) {
                                    $(this).parent().append("<span class='error'>Value must be greater than 1</span>");
                                    errors = true;
                                    return false;
                                }

                                @if ($limits = (licSetting('deposit_limit', $user->id)['highest_allowed_limit'] ?? false))
                                    if ($(this).closest("form").attr('id') === 'deposit-limit') {
                                        var limits = {!! json_encode($limits) !!};
                                        var index = $(this).attr('name').match(/\[(\w+)\]/)[1];
                                        var deposit_limit = limits[index] || 0;

                                        if ($(this).val() > deposit_limit && deposit_limit > 0) {
                                            $(this).parent().append("<span class='error'>Max allowed deposit limit is " + (deposit_limit / 100) + " EUR</span>");
                                            errors = true;
                                            return false;
                                        }
                                    }
                                @endif

                                return true;
                            }).toArray();

                        } else {
                            empty_fields = $form.find(".new-limit").filter(function() {
                                $(this).parent().find('.error').remove();

                                if ($(this).val().toString().length !== 0 && $(this).val() < 1) {
                                    $(this).parent().append("<span class='error'>Value must be greater than 1</span>");
                                    errors = true;
                                    return false;
                                }

                                @if ($limits = (licSetting('deposit_limit', $user->id)['highest_allowed_limit'] ?? false))
                                    if ($(this).closest("form").attr('id') === 'deposit-limit') {
                                        var limits = {!! json_encode($limits) !!};
                                        var index = $(this).attr('name').match(/\[(\w+)\]/)[1];
                                        var deposit_limit = limits[index] || 0;

                                        if ($(this).val() > deposit_limit && deposit_limit > 0) {
                                            $(this).parent().append("<span class='error'>Max allowed deposit limit is " + (deposit_limit / 100) + " EUR</span>");
                                            errors = true;
                                            return false;
                                        }
                                    }
                                @endif

                                return true;
                            }).toArray();
                        }

                        if (empty_fields.length > 0) {
                            $confirmation_modal.modal('hide');
                        }
                    }

                    $form.find('.new-limit').parent().click(function() {
                        $(this).find('.error').remove();
                    });

                    if (!errors) {
                        $form.append("<input type='hidden' name='action' value='"+action+"'>");
                        $form.submit();
                    }

                    $confirmation_modal.modal('hide');
                });

                $confirmation_modal.find(".dismiss").on("click", function(){
                    $confirmation_modal.modal('hide');
                });
            }

        });
    </script>
@endsection

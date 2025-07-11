<?php
/**
 * @var \App\Models\User $user
 * @var array $follow
 */
$follow = $user->settings_repo->getFollowUpData();
?>
<div class="modal fade" id="risk-group-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form role="form">
                    <input type="hidden" name="key">
                    <div class="form-group">
                        <label for="lower-limit-field">Add a comment</label>
                        <input type="text" name="comment_input" class="form-control" placeholder="Comment..."/>
                    </div>
                    <div class="form-group">
                        <p class="error" style="color: red;"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success lower-modal-save-btn save">Save</button>
            </div>
        </div>
    </div>
</div>
<div class="card card-outline card-warning @if($follow_up_collapse == 1) collapsed-box @endif" id="follow-up-box">
    <div class="card-header">
        <h3 class="card-title text-lg">Follow up</h3>
        <div class="card-tools">
            <button class="btn btn-tool" id="follow-up-box-btn" data-boxname="follow-up-box" data-widget="collapse"
                    data-toggle="tooltip" title="Collapse">
                <i class="fa fa-{{ $follow_up_collapse == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
    <ul class="nav nav-pills" role="tablist">
        @if(p('show.follow.up.box.rg'))
            <li class="nav-item">
                <a class="nav-link" href="#rgfu" aria-controls="rgfu" role="tab" data-toggle="tab" data-category="rg">Responsible Gambling</a>
            </li>
        @endif
        @if(p('show.follow.up.box.aml'))
            <li class="nav-item">
                <a class="nav-link" href="#amlfu" aria-controls="amlfu" role="tab" data-toggle="tab" data-category="aml">AML</a>
            </li>
        @endif
        @if(p('show.follow.up.box.fr'))
            <li class="nav-item">
                <a class="nav-link" href="#frfu" aria-controls="frfu" role="tab" data-toggle="tab" data-category="fr">Fraud</a>
            </li>
        @endif
    </ul>
    <div class="tab-content">
        @if(p('show.follow.up.box.rg'))
            <div role="tabpanel" class="tab-pane fade" id="rgfu">
                @include('admin.user.partials.boxes.follow-up-partial', ['category' => "rg"])
            </div>
        @endif
        @if(p('show.follow.up.box.aml'))
            <div role="tabpanel" class="tab-pane fade" id="amlfu">
                @include('admin.user.partials.boxes.follow-up-partial', ['category' => "aml"])
            </div>
        @endif
        @if(p('show.follow.up.box.fr'))
            <div role="tabpanel" class="tab-pane fade" id="frfu">
                @include('admin.user.partials.boxes.follow-up-partial', ['category' => "fr"])
            </div>
        @endif
    </div>
    </div>
</div>
@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script>
        var target = '#rgfu';

        $.fn.showModal = function() { $(this).modal('show'); };
        $.fn.hideModal = function() {
            var source = $(this).find("[name='comment_input']");
            var error = $(this).find('.error');
            if (source.val() !== '') {
                $(this).modal('hide');
                source.val('');
            } else {
                error.text('Comment is required!');
                source.keydown(function() {
                    error.text('');
                })
            }
        };
        $.fn.getComment = function() {
            return $(this).find("[name='comment_input']").val();
        };

        $(function () {
            $('.follow-up-form-submit-btn').on('click', function () {
                $.ajax({
                    url: "{{ $app['url_generator']->generate('admin.user-update-follow-up', ['user' => $user->id]) }}",
                    type: "POST",
                    data: $("#" + $(this).data('form')).serialize(),
                    success: function (data, textStatus, jqXHR) {

                        if (data['success'] == true) {
                            displayNotifyMessage('success', data['message']);
                        } else {
                            displayNotifyMessage('error', data['message']);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        displayNotifyMessage('error', errorThrown);
                    }
                });
            });

            $("#follow-up-box .nav-pills li").click(function (e, i) {
                target = "#" + $(e.target).data('category') + "fu";
            });
        });

        $(document).ready(function() {
            $("#follow-up-box .nav-pills li:nth-child(1) a").click();

            $modal = $('#risk-group-modal').modal({
                backdrop: 'static',
                keyboard: false,
                show: false
            });

            $('#risk-group-modal .save').click(function() {
                $(target).find("[name='comment']").val($modal.getComment());
                $modal.hideModal();
                $('.tab-pane.active .follow-up-form-submit-btn').click();
            });

            $(".follow-up-form select")
                .change(function() {
                    $modal.showModal();
                });
        });
    </script>
@endsection

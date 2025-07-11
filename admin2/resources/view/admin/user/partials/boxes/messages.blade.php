<?php
/**
 * @var App\Models\User $user
 * @var $comments
 */
    $max_result_limit = 5000;
    $comments = \App\Repositories\UserCommentRepository::getComments($user, $max_result_limit, $_GET['comments_page'] ?? null);
    $can_unstick_comments = p('unstick.account.comments');
?>

<div class="card card-outline card-warning @if($comments_collapse == 1) collapsed-box @endif" id="comments-box">
    <div class="card-header border-bottom-0">
        <h3 class="card-title text-lg">Comments</h3>
        <div class="card-tools">
            <button class="btn btn-tool" id="comments-box-btn" data-boxname="comments-box" data-widget="collapse"
                    data-toggle="tooltip" title="Collapse">
                <i class="fa fa-{{ $comments_collapse == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body pt-0">
        <ul class="nav nav-pills text-white" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" href="#all" aria-controls="all" role="tab" data-toggle="pill" aria-selected="true">Show all</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#complaint" aria-controls="complaint" role="tab" data-toggle="pill" aria-selected="false">Complaint</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#rg" aria-controls="rg" role="tab" data-toggle="pill" aria-selected="false">Discussion about RG limits</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#phone" aria-controls="phone" role="tab" data-toggle="pill" aria-selected="false">Phone</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#vip" aria-controls="phone" role="tab" data-toggle="pill" aria-selected="false">VIP</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#communication" aria-controls="phone" role="tab" data-toggle="pill" aria-selected="false">Communication</a>
            </li>
            @if(p('view.account.comments.sar'))
                <li class="nav-item">
                    <a class="nav-link" href="#sar" aria-controls="sar" role="tab" data-toggle="pill" aria-selected="false">SAR</a>
                </li>
            @endif
            <li class="nav-item">
                <a class="nav-link" href="#amlfraud" aria-controls="amlfraud" role="tab" data-toggle="pill" aria-selected="false">Fraud & AML</a>
            </li>
            @if(p('view.account.comments.mlro'))
                <li class="nav-item">
                    <a class="nav-link" href="#mlro" aria-controls="mlro" role="tab" data-toggle="pill" aria-selected="false">MLRO</a>
                </li>
            @endif
            <li class="nav-item">
                <a class="nav-link" href="#rg-risk-score" aria-controls="rg-risk-score" role="tab" data-toggle="pill" aria-selected="false">RG Risk Score</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#aml-risk-score" aria-controls="aml-risk-score" role="tab" data-toggle="pill" aria-selected="false">AML Risk Score</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#manual-flags" aria-controls="manual-flags" role="tab" data-toggle="pill" aria-selected="false">Manual Flags</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#sportsbook" aria-controls="sportsbook" role="tab" data-toggle="pill" aria-selected="false">Sportsbook</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#automatic-flags" aria-controls="automatic-flags" role="tab" data-toggle="pill" aria-selected="false">Automatic flags</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#rg-evaluation" aria-controls="rg-evaluation" role="tab" data-toggle="pill" aria-selected="false">RG Evaluation</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#rg-action" aria-controls="rg-action" role="tab" data-toggle="pill" aria-selected="false">RG Action</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#account-closure" aria-controls="account-closure" role="tab" data-toggle="pill" aria-selected="false">Account Closure</a></li>
            </li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade show active" id="all">
                <div class="direct-chat-messages h-auto" style="max-height: 400px;">
                    @foreach($comments as $comment)
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endforeach
                    @if ($comments->lastPage() > 1)
                        <ul class="pagination">
                            <li class="page-item {{ ($comments->currentPage() == 1) ? ' disabled' : '' }}">
                                <a class="page-link" href="?comments_page=1">First</a>
                            </li>
                            <li class="page-item{{ ($comments->currentPage() == 1) ? ' disabled' : '' }}">
                                <a class="page-link" href="?comments_page={{ $comments->currentPage() - 1 }}">Previous</a>
                            </li>
                            <li class="page-item{{ ($comments->currentPage() == $comments->lastPage()) ? ' disabled' : '' }}">
                                <a class="page-link" href="?comments_page={{ $comments->currentPage() + 1 }}" >Next</a>
                            </li>
                            <li class="page-item{{ ($comments->currentPage() == $comments->lastPage()) ? ' disabled' : '' }}">
                                <a class="page-link" href="?comments_page={{ $comments->lastPage() }}">Last</a>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="complaint">
                @foreach($comments as $comment)
                    @if($comment->tag == 'complaint')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            <div role="tabpanel" class="tab-pane fade" id="rg">
                @foreach($comments as $comment)
                    @if($comment->tag == 'limits' || $comment->tag == 'discussion')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            <div role="tabpanel" class="tab-pane fade" id="phone">
                @foreach($comments as $comment)
                    @if($comment->tag == 'phone_contact')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            <div role="tabpanel" class="tab-pane fade" id="vip">
                @foreach($comments as $comment)
                    @if($comment->tag == 'vip')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            <div role="tabpanel" class="tab-pane fade" id="communication">
                @foreach($comments as $comment)
                    @if($comment->tag == 'communication')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            @if(p('view.account.comments.sar'))
            <div role="tabpanel" class="tab-pane fade" id="sar">
                @foreach($comments as $comment)
                    @if($comment->tag == 'sar')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            @endif
            <div role="tabpanel" class="tab-pane fade" id="amlfraud">
                @foreach($comments as $comment)
                    @if($comment->tag == 'amlfraud')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            @if(p('view.account.comments.mlro'))
                <div role="tabpanel" class="tab-pane fade" id="mlro">
                    @foreach($comments as $comment)
                        @if($comment->tag == 'mlro')
                            @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                        @endif
                    @endforeach
                </div>
            @endif
            <div role="tabpanel" class="tab-pane fade in" id="rg-risk-score">
                <div class="direct-chat-messages h-auto" style="max-height: 400px;">
                    @foreach($comments as $comment)
                        @if($comment->tag == 'rg-risk-group')
                            @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                        @endif
                    @endforeach
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade in" id="aml-risk-score">
                <div class="direct-chat-messages h-auto" style="max-height: 400px;">
                    @foreach($comments as $comment)
                        @if($comment->tag == 'aml-risk-group')
                            @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                        @endif
                    @endforeach
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade in" id="manual-flags">
                <div class="direct-chat-messages h-auto" style="max-height: 400px;">
                    @foreach($comments as $comment)
                        @if($comment->tag == 'manual-flags')
                            @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                        @endif
                    @endforeach
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade in" id="sportsbook">
                <div class="direct-chat-messages h-auto" style="max-height: 400px;">
                    @foreach($comments as $comment)
                        @if($comment->tag == 'sportsbook')
                            @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                        @endif
                    @endforeach
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="automatic-flags">
                @foreach($comments as $comment)
                    @if($comment->tag == 'automatic-flags')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            <div role="tabpanel" class="tab-pane fade" id="rg-evaluation">
                @foreach($comments as $comment)
                    @if($comment->tag == 'rg-evaluation')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            <div role="tabpanel" class="tab-pane fade" id="rg-action">
                @foreach($comments as $comment)
                    @if($comment->tag == 'rg-action')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
            <div role="tabpanel" class="tab-pane fade" id="account-closure">
                @foreach($comments as $comment)
                    @if($comment->tag == 'account-closure')
                        @include('admin.user.partials.boxes.messages-message', compact('user', 'comment', 'can_unstick_comments'))
                    @endif
                @endforeach
            </div>
        </div>
        <form method="post" id="comments_form">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="row border-top pt-3">
                <div class="form-group col-12 col-lg-7">
                    <select name="tag" id="select-comment-type" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select an option">
                        <option></option>
                        <option value="all">Uncategorized</option>
                        <option value="limits">Discussion about RG limits</option>
                        <option value="complaint">Complaint</option>
                        <option value="phone_contact">Phone contact</option>
                        <option value="vip">VIP</option>
                        <option value="communication">Communication</option>
                        @if(p('edit.account.comments.sar'))
                            <option value="sar">SAR</option>
                        @endif
                        <option value="amlfraud">Fraud & AML</option>
                        @if(p('edit.account.comments.mlro'))
                            <option value="mlro">MLRO</option>
                        @endif
                        <option value="rg-risk-group">RG Risk Score</option>
                        <option value="aml-risk-group">AML Risk Score</option>
                        <option value="sportsbook">Sportsbook</option>
                        <option value="rg-action">RG Action</option>
                    </select>
                </div>
                <div class="form-group col-12 col-lg-5">
                    <div class="d-flex flex-column ml-4">
                        <div class="form-check form-check-inline mr-3">
                            <input class="form-check-input" name="sticky" type="checkbox" value="1" id="sticky-checkbox">
                            <label class="form-check-label text-sm" for="sticky-checkbox">Set as sticky</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" name="secret" type="checkbox" value="1" id="secret-checkbox">
                            <label class="form-check-label text-sm" for="secret-checkbox">Add permission</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="input-group">
                        <input type="text" name="comment" placeholder="Type a comment" class="form-control">
                        <span class="input-group-append">
                            <button type="button" id="comments-form-submit" class="btn btn-danger btn-sm btn-flat">Send</button>
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@section('footer-javascript')
    @parent
    <script>
        $(function () {

            $('#comments-box').find('.tab-pane').each(function (index) {
                if($.trim($(this).text()).length == 0) {
                    $(this).html("<div style='padding: 10px; color: grey'>No messages found.</div>");
                }
            });

            $("#select-comment-type").select2().val("all").change();

            $('#comments-form-submit').on('click', function () {
                $.ajax({
                    url: "{{ $app['url_generator']->generate('admin.comment-new') }}",
                    type: "POST",
                    data: $('#comments_form').serialize(),
                    success: function (data, textStatus, jqXHR) {
                        response = jQuery.parseJSON(data);
                        if (response['success'] == true) {
                            location.reload();
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });
            $(window).keydown(function (event) {
                if (event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });
            // deleting a comment
            $('.direct-chat-msg .tools i.fa-trash').on('click', function (event, ui) {
                var delete_url = $(this).attr('url');
                showConfirm('Delete comment', 'Are you sure you want to delete this comment? ', delete_url);
            });
            // unstick a comment
            $('.direct-chat-msg .tools i.fa-unlock').on('click', function (event, ui) {
                var unstick_url = $(this).attr('url');
                showConfirm('Unstick comment', 'Are you sure you want to unstick this comment? ', unstick_url);
            });
        });
    </script>
@endsection

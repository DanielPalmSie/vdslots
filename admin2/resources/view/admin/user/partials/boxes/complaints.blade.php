<?php
/**
 * @var \App\Models\User $user
 * @var \App\Models\UserComplaint|null $complaint
 * @var \App\Models\UserComplaintResponse|null $complaint_response
 */
$complaint = $user->repo->getLastComplaint();
$complaint_response = !empty($complaint) ? $complaint->getLastResponse() : null;
$complaint_comments = !empty($complaint) ? $complaint->getComments() : null;

$complaint_url = empty($complaint)
    ? $app['url_generator']->generate('admin.users.complaints.store', ['user' => $user->id])
    : $app['url_generator']->generate('admin.users.complaints.update', ['user' => $user->id, 'complaint' => $complaint->id]);
$complaint_response_url = empty($complaint)
    ? ''
    : $app['url_generator']->generate('admin.users.complaints.responses.store', ['user' => $user->id, 'complaint' => $complaint->id ?? 0]);
?>

<div class="card card-outline card-warning @if($issues_collapse == 1) collapsed-box @endif" id="issues-box">
    <div class="card-header">
        <h3 class="card-title text-lg">
            <a href="#" style="color: inherit" data-toggle="modal" data-target="#complain-modal">
                <i class="far fa-{{ empty($complaint) ? 'check-square' : $complaint->getTypeIcon() }}" aria-hidden="true"></i> {{ 'Complaint ' }}
            </a>
        </h3>
        @if(!empty($complaint))
            <div class="card-tools">
                <button class="btn btn-tool" id="issues-box-btn" data-boxname="issues-box" data-widget="collapse"
                        data-toggle="tooltip" title="Collapse">
                    <i class="fa fa-{{ $issues_collapse == 1 ? 'plus' : 'minus' }}"></i>
                </button>
            </div>
        @endif
    </div>
    @if(!empty($complaint))
        <div class="card-body">
            <ul style="margin-bottom: -5px" class="list-group list-group-unbordered p-0">
                <li class="list-group-item d-flex justify-content-between">
                    <div>
                        <b>Ticket ID</b> <span> {{ $complaint->ticket_id }}</span>
                    </div>
                    <p><a target="_blank" href="{{ $complaint->ticket_url }}">Open the ticket</a></p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Type</b>
                    <p>{{ $complaint->getTypeName() }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Status</b>
                    <p>{{ $complaint->getStatus() }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Tagged at</b>
                    <p>{{ $complaint->created_at }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Tagged by</b>
                    <p>
                        <a target="_blank" href="{{ $app['url_generator']->generate('admin.userprofile', ['user' => $complaint->actor_id]) }}">{{ ($complaint->actor ? $complaint->actor->getFullName() : '') . ' #' . $complaint->actor_id }}</a>
                    </p>
                </li>
                @if(!empty($complaint_comments))
                <li class="list-group-item d-flex justify-content-between">
                    <b>Comment</b>
                    <p>
                        <a href="#" data-toggle="modal" data-target="#complaint-comment-show-modal">Show comment</a>
                    </p>
                </li>
                @endif
                @if(!empty($complaint_response))
                <li class="list-group-item d-flex justify-content-between">
                    <b>Response</b>
                    <p>
                        <a href="#" data-toggle="modal" data-target="#complaint-response-modal">Show response</a>
                    </p>
                </li>
                @endif
            </ul>
        </div>
        <div class="card-footer bg-white">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#deactivate-complaint-modal">Deactivate</button>
            @if(empty($complaint_response))
                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#complaint-response-modal">Add response</button>
            @endif
            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#complaint-comment-modal">Add comment</button>
        </div>
    @endif
</div>

<div class="modal fade" id="complain-modal" tabindex="-1" role="dialog"
     aria-labelledby="complain-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">User complaint</h4>
                <button type="button" class="close" aria-label="Close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="complain-modal-form" role="form">
                    @if(!empty($complaint))
                        <input name="id" type="hidden" value="{{ $complaint->id }}">
                        <div class="form-group">
                            <b>Ticket ID {{ $complaint->ticket_id }}</b>
                            <p class="float-right">
                                <a target="_blank" href="{{ $complaint->ticket_url }}">Open the ticket</a>
                            </p>
                        </div>
                        <div class="form-group">
                            <b>Type</b>
                            <p class="float-right">{{ $complaint->getTypeName() }}</p>
                        </div>
                        <div class="form-group">
                            <b>Status</b>
                            <p class="float-right">{{ $complaint->getStatus() }}</p>
                        </div>
                        <div class="form-group">
                            <b>Tagged at</b>
                            <p class="float-right">{{ $complaint->created_at }}</p>
                        </div>
                        <div class="form-group">
                            <b>Tagged by</b>
                            <p class="float-right"><a target="_blank" href="{{ $app['url_generator']->generate('admin.userprofile', ['user' => $complaint->actor_id]) }}">{{ ($complaint->actor ? $complaint->actor->getFullName() : '') . ' #' . $complaint->actor_id }}</a></p>

                        </div>
                        @if($complaint_comments->isNotEmpty())
                        <div class="form-group">
                            <b>Comment</b>
                            <p class="float-right">
                                <a href="#" data-toggle="modal" data-target="#complaint-comment-show-modal">Show comments</a>
                            </p>
                        </div>
                        @endif
                        @if(!empty($complaint_response))
                        <div class="form-group">
                            <b>Response</b>
                            <p class="float-right">
                                <a href="#" data-toggle="modal" data-target="#complaint-response-modal">Show response</a>
                            </p>
                        </div>
                        @endif
                    @else
                    <label for="complaint-type">Type</label>
                        <div id="complaint-type" class="form-group">
                            <div class="row">
                                @foreach(\App\Models\UserComplaint::TYPES as $key => $val)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" id="optionsRadios{{ $key }}" value="{{ $key }}">
                                            <label class="form-check-label" for="optionsRadios{{ $key }}">
                                                <i class="fa fa-{{ $val['icon'] }} mr-1" aria-hidden="true"></i> {{ $val['name'] }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted"></small>
                        </div>
                        <div id="complaint-ticket_id" class="form-group">
                            <label for="ticket_id">Ticket ID</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">ID</span>
                                </div>
                                <input type="text" class="form-control" name="ticket_id" id="ticket_id" placeholder="" value="">
                            </div>
                            <span class="form-text text-muted"></span>
                        </div>
                        <div id="complaint-ticket_url" class="form-group">
                            <label for="ticket_url">Ticket URL</label>
                            <input type="url" name="ticket_url" id="complaint-ticket-url" class="form-control" placeholder="Enter Ticket URL" value="">
                            <small class="form-text text-muted"></small>
                        </div>
                        <div id="complaint-complaint_comment" class="form-group">
                            <label for="complaint_comment">Comment</label>
                            <textarea class="form-control" rows="3" name="complaint_comment" id="complaint_comment" placeholder="Add a comment"></textarea>
                            <small class="form-text text-muted"></small>
                        </div>
                    @endif

                    <small id="single-modal-input-field-helper" class="form-text text-muted"></small>
                </form>
                <span id="help-block-single" class="help-block strong"></span>
            </div>
            <div class="modal-footer d-flex justify-content-start">
                <div>
                    @if(empty($complaint))
                        <button type="button" class="btn btn-primary complain-modal-btn" data-status="1">Activate</button>
                    @else
                        <button  type="button" class="btn btn-primary" data-toggle="modal" data-target="#deactivate-complaint-modal">Deactivate</button>
                    @endif
                    @if(!empty($complaint) && empty($complaint_response))
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#complaint-response-modal">Add response</button>
                    @endif
                    @if(!empty($complaint))
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#complaint-comment-modal">Add comment</button>
                    @endif
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="complaint-response-modal" tabindex="-1" role="dialog"
     aria-labelledby="complaint-response-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title">User complaint's response</h4>
            </div>
            <div class="modal-body">
                <form id="complaint-response-modal-form" role="form">
                    @if(!empty($complaint_response))
                        <div class="form-group">
                            <b>Response Type</b>
                            <p class="float-right">{{ $complaint_response->getTypeName() }}</p>
                        </div>
                        <div class="form-group">
                            <b>Response Description</b>
                            <p class="float-right">{{ $complaint_response->description }}</p>
                        </div>
                    @else
                        <div id="complaint-response-type" class="form-group">
                            <label for="payment_method">Response Type</label>
                            <select name="type" id="complaint_response_type" class="form-control"
                                    style="width: 100%;" data-placeholder="Select one from the list"
                                    data-allow-clear="true" data-target="#partial_withdrawal_form">
                                @foreach(\App\Models\UserComplaintResponse::TYPES as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="complaint-response-description" class="form-group">
                            <label for="complaint_comment">Response Description</label>
                            <textarea class="form-control complaint-text-area"  name="description"
                                      placeholder="Response description is the description of the final result of the incident follow-up"
                                      rows="3"></textarea>
                            <span class="help-block"></span>
                        </div>
                    @endif
                </form>
            </div>
            <div class="modal-footer">
                @if(empty($complaint_response))
                    <button type="button" class="btn btn-primary complaint-response-modal-btn" data-status="1">Create</button>
                @endif
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="complaint-comment-show-modal" tabindex="-1" role="dialog"
     aria-labelledby="complaint-comment-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title">User complaint's comment</h4>
            </div>
            <div class="modal-body">
                @if(!empty($complaint_comments))
                    @foreach($complaint_comments as $comment)
                        <div class="direct-chat-info clearfix">
                            <span class="direct-chat-name pull-left">Comment:</span>
                            <span class="direct-chat-timestamp float-right">{{ $comment->created_at }}</span>
                        </div>
                        <div class="direct-chat-text ">
                            <p>{{ $comment->comment }}</p>
                        </div>
                    @endforeach
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="complaint-comment-modal" tabindex="-1" role="dialog"
     aria-labelledby="complaint-comment-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title">User complaint's comment</h4>
            </div>
            <div class="modal-body">
                <form id="complaint-comment-modal-form" role="form">
                    <div id="complaint-comment-comment" class="form-group">
                        <label for="complaint_comment">Complaint comment</label>
                        <textarea class="form-control complaint-text-area"  name="comment"
                                  placeholder="Add a comment"
                                  rows="3"></textarea>
                        <span class="help-block"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary complaint-comment-modal-btn" data-status="1">Create</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade bootstrap-dialog type-primary" id="deactivate-complaint-modal" tabindex="-1" role="dialog"
     aria-labelledby="complaint-comment-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title bootstrap-dialog-title">Deactivate complaint</h4>
            </div>
            <div class="modal-body">
                <span>Are you sure you want to deactivate this complaint? It can't be re-open</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-primary complain-modal-btn" data-status="0">
                    <span class="bootstrap-dialog-button-icon glyphicon glyphicon-ok"></span> Yes
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <span class="bootstrap-dialog-button-icon glyphicon glyphicon-remove"></span> No
                </button>
            </div>
        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        function validateURL(textval) {
            var urlregex = new RegExp("^(http:\/\/|https:\/\/|ftp:\/\/www.|www.){1}([0-9A-Za-z]+\.)");
            return urlregex.test(textval);
        }

        $(function () {
            var url_sel = $('#complaint-ticket_url');
            url_sel.find('input').on('change', function(e) {
                var self = $(this);
                if (self.val() != '' && !validateURL(self.val())) {
                    url_sel.addClass('has-error');
                    url_sel.find('span').html('Not a valid url.');
                } else {
                    url_sel.removeClass('has-error');
                    url_sel.find('span').html('');
                }
            });

            $('.complain-modal-btn').on('click', function(e) {
                e.preventDefault();
                var url = "{{ $complaint_url }}";
                var self = $(this);
                var form = $('#complain-modal-form').serializeArray();
                form.push({ name: "status", value: self.data('status')});
                $.ajax({
                    url: url,
                    type: "{{ $complaint ? 'PUT' : 'POST' }}",
                    data: form,
                    success: function (data, textStatus, jqXHR) {
                        if (data.success == true) {
                            $('#complain-modal').modal('hide');
                            location.reload();
                        } else {
                            console.log(data.errors);

                            $.each(data.errors, function (index, value) {
                                $('#example-'+index).html(value);
                                var form_el = $('#complaint-' + index);

                                if (form_el) {
                                  form_el.addClass('has-error');
                                  form_el.find('span.help-block').html(value);
                                }

                            });
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            $('.complaint-response-modal-btn').on('click', function(e) {
                e.preventDefault();
                var url = "{{ $complaint_response_url }}";
                var form = $('#complaint-response-modal-form').serializeArray();

                $.ajax({
                    url: url,
                    type: "POST",
                    data: form,
                    success: function (data, textStatus, jqXHR) {
                        if (data.success == true) {
                            $('#complaint-response-modal').modal('hide');
                            location.reload();
                        } else {
                            console.log(data.errors);

                            $.each(data.errors, function (index, value) {
                                $('#example-'+index).html(value);
                                var form_el = $('#complaint-response-' + index);

                                if (form_el) {
                                    form_el.addClass('has-error');
                                    form_el.find('span.help-block').html(value);
                                }

                            });
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            $('.complaint-comment-modal-btn').on('click', function(e) {
                e.preventDefault();
                var form = $('#complaint-comment-modal-form').serializeArray();
                form.push({ name: "tag", value: "{{ \App\Models\UserComment::TYPE_COMPLAINT }}"});
                form.push({ name: "user_id", value: "{{ $user->id }}"});
                form.push({ name: "foreign_id", value: "{{ $complaint->id }}"});

                $.ajax({
                    url: "{{ $app['url_generator']->generate('admin.comment-new') }}",
                    type: "POST",
                    data: form,
                    success: function (data, textStatus, jqXHR) {
                        var response = jQuery.parseJSON(data);

                        if (response['success'] == true) {
                            location.reload();
                        } else {
                            console.log(response['errors']);
                            $.each(response['errors'], function (index, value) {
                                $('#example-'+index).html(value);
                                var form_el = $('#complaint-response-' + index);

                                if (form_el) {
                                    form_el.addClass('has-error');
                                    form_el.find('span.help-block').html(value);
                                }

                            });
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

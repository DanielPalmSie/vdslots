@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "User Risk Score Rating"))
@endsection

@section('content')
    <div class="container-fluid">
        @if ($forwarded == 'AML')
            @include('admin.fraud.partials.topmenu')
        @elseif($forwarded == 'RG')
            @include('admin.rg.partials.topmenu')
        @endif
        <script>
            function printReviewedCell(actor_id, actor_name, time_reviewed) {
                return 'Reviewed by <a target="_blank" href="{{ accLinkAdmin($app) }}'+actor_id+'">'+
                    actor_name + '</a><br>'+ time_reviewed;
            }
        </script>
        <form id="risk-score-report-filter" method="get">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filters</h3>
                </div>
                <div class="card-body">
                    <div class="row align-items-start">
                        <div class="form-group col-6 col-lg-4 col-xl-3">
                            @include('admin.filters.user-id-filter')
                        </div>
                        <div class="form-group col-6 col-lg-4 col-xl-3">
                            <label for="select-country">Country</label>
                            <select name="country" id="select-country" class="form-control select2-class"
                                    style="width: 100%;" data-placeholder="Shows all countries if not selected" data-allow-clear="true">
                                <option value="all" selected>All</option>
                                @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                                    @if(!in_array($country['iso'], $exclude_countries))
                                        <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        @include('admin.filters.date-range-filter', ['date_range' => $period_date['score'], 'date_format' => 'date', 'input_name' => "Date range", 'input_id' => '-start'])
                        <div class="form-group col-6 col-lg-4 col-xl-3">
                            @include('admin.filters.number-range-filter', ['name' => 'user_score', 'label' => 'Score range' ])
                        </div>
                        <div class="form-group col-6 col-lg-4 col-xl-3">
                            @include('admin.filters.slider-range-filter', [
                                'start' => $app['request_stack']->getCurrentRequest()->get('section_profile_rating_start', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG),
                                'end' => $app['request_stack']->getCurrentRequest()->get('section_profile_rating_end', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG),
                                'label' => 'Profile rating range',
                                'type' => 'section',
                            ])
                        </div>
                        <div class="form-group col-6 col-lg-4 col-xl-3">
                            <label for="select-country">Reviewer</label>
                            <select name="reviewer" id="select-reviewer" class="form-control select2-class"
                                    style="width: 100%;" data-placeholder="Shows all reviewers if not selected" data-allow-clear="true">
                                <option value="all" selected>All</option>
                                @foreach($reviewers as $id => $reviewer)
                                    <option value="{{ $id }}">{{ $reviewer['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <input name="forwarded" type="hidden" value="{{ $forwarded }}">
                    <button class="btn btn-info">Search</button>
                </div>
            </div>
        </form>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $forwarded }} User Risk Score</h3>
            </div><!-- /.card-header -->
            <div class="card-body">
                <table class="table table-striped table-bordered dt-responsive border-left w-100 border-collapse"
                    id="risk-score-report-datatable">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th></th>
                            <th>Country</th>
                            <th>Score</th>
                            <th>{{ $forwarded }} Profile Score</th>
                            <th>Last Reviewer</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($page['data'] as $element)
                            <tr>
                                <td class="align-middle">{{ $element->user_id }}</td>
                                <td>{{ $element->declaration_proof }}</td>
                                <td>{{ $element->country }}</td>
                                <td>{{ $element->score }}</td>
                                <td>
                                    {{ $element->profile_rating_tag ?? $element->profile_rating }}
                                </td>
                                <td>
                                    @if(!empty($element->actor_id))
                                        <script>
                                            document.write(printReviewedCell("{{ $element->actor_id }}", "{{ $element->actor_name }}", "{{ $element->created_at }}"));
                                        </script>
                                    @endif
                                </td>
                                <td>
                                    {{ $element->last_comment_datetime }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

            <div class="modal fade" id="comments-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Comments</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success lower-modal-save-btn save" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .form-group {
                min-height: 59px;
            }
            .color-card {
                width: 20px;
                height: 20px;
                display: inline-block;
                float: left;
                margin-right: 5px;
            }
            .color-card.red {
                background: red;
            }
            .color-card.blue {
                background: blue;
            }
            .color-card.yellow {
                background: yellow;
            }
            .color-card.green {
                background: green;
            }
            .color-card.purple {
                background: purple;
            }
            .color-card.black {
                background: black;
            }
            .profile-rating {
                color: white;
                font-weight: bold;
                text-align: center;
            }
        </style>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $modal = $('#comments-modal').modal({
                backdrop: 'static',
                keyboard: false,
                show: false
            });
            $(".open-modal").click(function () {
                $modal.showModal();
            });
            $("#select-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('country', 'all') }}").change();
            $("#select-trigger-type").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('trigger-type', 'all') }}").change();
            $("#select-reviewer").select2().val("{{ $params['reviewer'] }}").change();
        });
    </script>
    <script>
        $(function () {
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('admin.admin.user-risk-score-report-special') }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#risk-score-report-filter').serializeArray();
                }
            };
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            var user_profile_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            table_init['columns'] = [
                {
                    "data": "user_id",
                    "defaultContent": "",
                    "render": function (data) {
                        return '<a target="_blank" href="' + user_profile_url + data + '/">' + data + '</a>';
                    }
                },
                {
                    "data": "declaration_proof",
                    "defaultContent": "",
                    "render": function (data) {
                        if (data === null || !data.length) {
                            return "";
                        }

                        return (data[0] === '1' ? '<span class="color-card blue prevent-parent-background" title="Declaration of Source of Wealth"></span>' : '')
                             + (data[1] === '1' ? '<span class="color-card red prevent-parent-background" title="Proof of Source of Wealth"></span>' : '')
                             + (data[2] === '1' ? '<span class="color-card yellow prevent-parent-background" title="Forced Loss Limit"></span>' : '')
                             + (data[3] === '1' ? '<span class="color-card green prevent-parent-background" title="Forced Bet Limit"></span>' : '')
                             + (data[4] === '1' ? '<span class="color-card purple prevent-parent-background" title="Forced Deposit Limit"></span>' : '')
                             + (data[5] === '0' ? '<span class="color-card black prevent-parent-background" title="Blocked"></span>' : '');
                    },
                    orderable: false
                },
                { "data": "country", "defaultContent": "", },
                { "data": "score", "defaultContent": "", },
                {
                    "data": "profile_rating_tag",
                    "defaultContent": "",
                    orderable: true,
                    "render": function (data, type, row) {
                        return row.profile_rating_tag ?? row.profile_rating;
                    }
                },
                {
                    "data": "actor_id",
                    orderable: true,
                    "defaultContent": "",
                    "render": function (data, type, row) {
                        if (row.actor_id && row.actor_name && row.created_at) {
                            return printReviewedCell(row.actor_id, row.actor_name, row.created_at);
                        }

                        if (typeof  data === "string" && data) {
                            try {
                                data = JSON.parse(data);
                            } catch (e) {
                            }
                        }
                        return data;
                    }
                },
                {
                    "data": "last_comment_datetime",
                    "defaultContent": "",
                    orderable: false,
                    "render": function (data, type, row) {
                        if (row.last_comment_datetime) {
                            return '<a style="cursor:pointer" class="open-modal"' +
                                ' data-user="' + row.user_id + '"' +
                                ' data-modal="#comments-modal">' +
                                ' Show comments' +
                                ' </a> <br/>' +
                                ' | Last comment at: ' + row.last_comment_datetime;
                        }
                    }
                }
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 3, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $page['recordsTotal'] }}");
            table_init['pageLength'] = 25;
            table_init['createdRow'] = function ( row, data ) {
                let tones = @json(\App\Helpers\GrsHelper::getGlobalScoreTones($app));
                let tone = tones[data.profile_rating_tag] == undefined ? tones.default : tones[data.profile_rating_tag];
                $('td', row).eq(4).css('background-color', tone).addClass('profile-rating');
            };
            table_init['drawCallback'] = function( settings ) {
                $("#risk-score-report-datatable").wrap( "<div class='table-responsive'></div>" );
                $('.open-modal').on('live click', function () {
                    var $anchor = $(this);
                    var comment_tags = JSON.parse('{!! json_encode($comment_tags) !!}');
                    $.getJSON({
                        "url" : "{{ $app['url_generator']->generate('admin.comment-get') }}",
                        "type" : "POST",
                        "data": {
                            user_id: $anchor.data('user'),
                            tags: comment_tags
                        }
                    }).done(function(data) {
                        $($anchor.data('modal')+' .modal-body').html('');
                        if (data.comments.length > 0) {
                            $.each(data.comments.reverse(), function (k, v) {
                                var modal_html = '<div class="direct-chat-text " style="margin: 5px 0 0 5px">' +
                                    v.comment +
                                    '<br><span class="direct-chat-timestamp">' + v.created_at + '</span></div>';
                                $($anchor.data('modal') + ' .modal-body').append(modal_html);
                            });

                        } else {
                            $($anchor.data('modal')+' .modal-body').html('No comments to show.')
                        }

                        $($anchor.data('modal')).modal('show');
                    });
                });
            };
            var table = $("#risk-score-report-datatable").DataTable(table_init);
        });
    </script>
@endsection

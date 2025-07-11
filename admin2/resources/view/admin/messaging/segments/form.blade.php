@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.messaging.partials.topmenu')
        <style>
            .card-tree ul {
                list-style-type: none;
                padding: 0;
            }
            .card-tree .field_item {
                padding: 10px;
                margin: 10px;
                margin-right: 2px;
                padding-right: 2px;
                background-color: #00a7d0;
                border: 1px solid darkslategrey;
            }
            .fields_list li {
                padding-left: 20px;
            }
            .filter-card {
                height: 600px;
                overflow: hidden;
            }
            .tab-content {
                padding: 16px ;
            }
            .card-header > p {
                font-size: 2em;
                font-weight:bold;
            }
            .title-text {
                font-size: 2em;
                font-weight:bold;
            }
            .card .card{
                background-color: #f4f4f4;
            }
            .list-unstyled .card > .card-header {
                background-color: #d2d6de;
            }
            .filter-section > .card > .card-body {
                height: 475px;
                overflow-y: auto;
                overflow-x: hidden;
                max-width: 100%;
            }
            .custom-list-item {
                font-size: 17px;
                margin-top: 6px;
                padding-left: 10px !important;
                cursor: move;
            }
            .c-row,
            .groups {
                margin-left: -10px;
                margin-right: -10px;
            }
            .group {
                background: #d2d6de;
                padding: 10px;
                margin-top: 10px;
            }
            .group .card-footer {
                background-color: transparent;
                border: none;
            }
            .group .card-footer span {
                color: #000;
                font-weight: 500;
                letter-spacing: .7px;
            }
            .group .btn-box-tool {
                padding: 10px;
            }
            .group.collapsed-card .delete-group {
                display: none;
            }
            .group.collapsed-card .card-header input {
                pointer-events:none;
                color: #000;
            }
            .group-header.card-header {
                padding: 0;
                z-index: 2;
            }
            .group-header input {
                width: 50%;
                border-right: 0;
                border-top: 0;
                border-left: 0;
                background-color: transparent;
                padding-left: 0;
                margin-left: 10px;
                font-size: 16px;
                border-color: white;
            }
            .group-header button {
                margin-left: 10px;
            }

            .group-body {
                position: relative;
                min-height: 100px;
            }
            .group-body .operator h5 {
                margin-top: 5px;
                padding-bottom: 5px;
                border-bottom: 1px solid black;
            }
            .group-body .operator .drop-here {
                margin-bottom: -10px;
                min-height: 40px;
            }
            .filter-section.sticky {
                position: fixed;
                top: 10px;
            }

            .input-parent > div {
                margin-bottom: 10px;
            }

            .input-parent > div > input {
                text-align: left;
            }
            .closeIcon {
                position: absolute;
                left: -5px;
                margin-top: 9px;
                cursor: pointer;
            }
            .filter-card {
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
            }

            .progress {
                margin-top: 5px;
                overflow: hidden;
            }

            .loading {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                top: 0;
                margin: auto;
                z-index: 1;
                background: #0000009e;
                color: #fff;
                text-align: center;
                padding-top: 6%;
                width: 100%;
                height: 100%;
                display: none;
                font-size: 30px;
            }
            .loading.active {
                display: block;
            }

            .closeIcon-operator {
                cursor: pointer;
            }
            .card-tree > .field_item > h5 i {
                display: none;
            }
            .group_name_error {
                border-color: red;
            }

            .disabled.overlay {
                display: none;
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                top: 0;
                z-index: 1;
                background: repeating-linear-gradient( 45deg, #b7b2b260, #b7b2b260 10px, #eeeeee60 10px, #eeeeee60 20px );
            }
            .group-disabled .disabled.overlay {
                display: block;
            }
            .err {
                font-size: 16px !important;
                padding: 5px 10px;
                color: #dd4a39;
            }
        </style>

        <div class="card">
            <div class="nav-tabs-custom">

                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.list-contacts') }}">All Contacts</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.list-filters') }}">Contact Filter Lists</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.new-filter-form') }}"><i class="fa fa-plus-square"></i> Create Filter List</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.segments.list') }}">List Segments</a></li>
                    <li class="nav item"><a class="nav-link active"><i class="fa fa-plus-square"></i> Create Segment</a></li>
                </ul>

                <div class="tab-content p-3">

                    <section class="row">

                        <div class="col-lg-3 col-md-3 col-sm-12 col-12 filter-section">

                            <div class="card filter-box">

                                <div class="card-header">

                                    <p class="title-text">Filters</p>

                                    <form class="form-horizontal">
                                        <div class="form-group row">
                                            <label for="filters-search" class="col-sm-3 control-label">Search</label>

                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="filters-search" placeholder="Search">
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div id="segment-accordion">
                                    <div class="card-body fields_list list-unstyled ">

                                        <div class="card">
                                            <div class="card-header">
                                                <button class="btn p-0 d-block w-100" type="button" data-toggle="collapse" data-target="#collapse-default" aria-expanded="true" aria-controls="collapse-default">
                                                    <h2 class="card-title">Default</h2>
                                                    <i class="fa fa-plus float-right"></i>
                                                </button>
                                            </div>

                                            <div id="collapse-default" class="collapse" aria-labelledby="heading-default" data-parent="#segment-accordion">
                                                <li class="draggable_item custom-list-item" data-type="and"><span>AND</span></li>
                                                <li class="draggable_item custom-list-item" data-type="or"><span>OR </span></li>
                                            </div>
                                        </div>

                                        @foreach($filter_fields as $group)
                                            <div class="card" id="{{$group->name}}">

                                                <div class="card-header">
                                                    <button class="btn p-0 d-block w-100" type="button" data-toggle="collapse" data-target="#collapse-{{ $loop->index }}" aria-expanded="true" aria-controls="collapse-{{ $loop->index }}">
                                                        <h2 class="card-title">{{$group['group_name']}}</h2>
                                                        <i class="fa fa-plus float-right"></i>
                                                    </button>
                                                </div>

                                                <div id="collapse-{{ $loop->index }}" class="collapse" aria-labelledby="heading-{{ $loop->index }}" data-parent="#segment-accordion">
                                                    @foreach($group['fields'] as $field=>$data)
                                                        <li class           = "field draggable_item custom-list-item"
                                                            data-type       = "condition"
                                                            data-searchable = "filters"
                                                            data-searchby   = "{{$data['title']}}"
                                                            data-value      = "{{$field}}"
                                                            data-addons     = {{json_encode($data['addons'])}}
                                                        >
                                                            <i class="far fa-hand-paper"></i>
                                                            <span>{{$data['title']}}</span>
                                                        </li>
                                                    @endforeach
                                                </div>

                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-9 col-md-9 col-sm-12 col-12 float-right">

                            <div class="overlay loading-overlay d-none">
                                <i class="fas fa-sync fa-spin"></i>
                            </div>

                            <div class="card">

                                <div class="card-body">

                                    <form id="bonus-type-form" method="post" class="form-horizontal fields-form "  action="{{$app['url_generator']->generate('messaging.segments.new')}}" >
                                        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                                        <div class="row">
                                            <div class="col-9">
                                                <div class="form-group row">
                                                    <label for="name" class="col-sm-2 control-label">Name</label>

                                                    <div class="col-sm-10">
                                                        <input type="text" name="name" id="name" class="form-control" value="{{$segment->name}}" >
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label for="description" class="col-sm-2 control-label">Description</label>

                                                    <div class="col-sm-10">
                                                        <input type="text" name="description" id="description" class="form-control" value="{{$segment->description}}" >
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-2 control-label">Users coverage:</label>

                                                    <div class="col-sm-8">
                                                        <div class="progress">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{($users_covered*100) / $users_count }}%;">{{sprintf('%0.2f', ($users_covered*100) / $users_count)}}%</div>
                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{100 - (($users_covered*100) / $users_count) }}%;"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <label id="ajax_progress">
                                                            {{$users_covered}} / {{$users_count}}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <button class="btn btn-success btn-block" id="save-all">SAVE</button>
                                                </div>
                                                <div class="form-group">
                                                    <button type="button" class="btn btn-warning btn-block" id="refresh-all">REFRESH ALL</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 place-errors">
                                            </div>
                                        </div>

                                        <div class="clearfix"></div>
                                        <input type="hidden" name="segment_id" value="{{$segment !== null ? $segment->id : null}}">
                                        <section class="groups">
                                            @if($segment !== null)

                                                @foreach($segment->groups as $key=>$group)
                                                    <div class="group card {{$group->disabled ? 'group-disabled' : ''}}">
                                                        <input type="hidden" class="query_data_input" value="{{$group->form_params}}">
                                                        <input type="hidden" class="group-disabled" value="{{$group->disabled}}">
                                                        <input type="hidden" class="group-id" value="{{$group->id}}">
                                                        <div class="disabled overlay"></div>

                                                        <div class="group-header card-header">
                                                            <input type="text" name="groups[{{$key}}][group_name]" class="form-control float-left group-name" value="{{$group->name}}" placeholder="Group name" >
                                                            <button type="button" class="btn btn-box-tool btn-default float-right" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                                            <button type="button" class="btn btn-danger float-right delete-group" data-group="{{$group->id}}">DELETE</button>
                                                            @if(empty($group->disabled))
                                                                <button type="button" class="btn btn-warning float-right disable-group">DISABLE</button>
                                                            @else
                                                                <button type="button" class="btn btn-warning float-right enable-group">ENABLE</button>
                                                            @endif
                                                            <div class="clearfix"></div>
                                                        </div>

                                                        <div class="group-body card-body">
                                                            <ul class="card-tree list-unstyled"></ul>
                                                        </div>

                                                        <div class="group-footer card-footer">
                                                            <span>We found {{$group->users_covered}} users in this group.</span>
                                                            <span class="float-right">Covers: <strong>{{sprintf('%0.2f', ($group->users_covered*100) / $users_count)}}%</strong></span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </section>

                                        <button id="add-group" type="button" class="btn btn-block btn-primary">ADD GROUP</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="clearfix"></div>
                    </section>
                </div>
            </div>
        </div>

        <div class="placeholders d-none">
            <div class="group card">
                <div class="group-header card-header">
                    <input type="text" name="group_name" class="form-control float-left group-name" value="" placeholder="Group name" >
                    <button type="button" class="btn btn-box-tool btn-default float-right" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    <button type="button" class="btn btn-danger float-right delete-group">DELETE</button>
                    <div class="clearfix"></div>
                </div>

                <div class="group-body card-body">
                    <ul class="card-tree list-unstyled"></ul>
                </div>

                <div class="group-footer card-footer">
                    {{--<span>We found 100 users in this group.</span>--}}
                </div>
            </div>
        </div>

        <div class="modal fade" id="confirmation-modal d-none" data-show="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Confirmation</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="question">One fine body…</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default float-left dismiss">No</button>
                        <button type="button" class="btn btn-primary confirm">Yes</button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
    </div>

    @include("admin.partials.href-confirm")
@endsection

@section('footer-javascript')
    @parent
    @include('admin.partials.jquery-ui-cdn')
    {{-- Contains missing functions related to filter to be able to make changes in one single place --}}
    @include('admin.messaging.partials.filter-component');

    <script>
        /**
         * action: open/close
         * @param className
         * @param action
         */
        // function toggleCollapse(className, action)
        // {
        //     $('.'+className+'[data-widget="collapse"]').each(function(i, el)
        //     {
        //         if ($(el).hasClass('ignore'))
        //         {
        //             return;
        //         }

        //         var $closed = $(this).parent().parent().parent().hasClass('collapsed-box');

        //         return ($closed && action === 'open') || (!$closed && action === 'close')
        //             ?   $(el).click()
        //             :   false;
        //     });
        // }

        $('.collapse')
            .on("show.bs.collapse", function () {
                $(this)
                    .prev(".card-header")
                    .find(".fa")
                    .removeClass("fa-plus")
                    .addClass("fa-minus");
            })
            .on("hide.bs.collapse", function () {
                $(this)
                    .prev(".card-header")
                    .find(".fa")
                    .removeClass("fa-minus")
                    .addClass("fa-plus");
                });

        function prepareUX($parent) {
            if (!$parent) {
                $parent = $("body");
            }

            $parent.find(".sortable_list").sortable(sortableAttrs);
            $parent.find(".field_item").droppable(droppableAttrs);

            $parent.find(".draggable_item").draggable({
                connectWith: ".container",
                helper: "clone",
                revert: "invalid"
            });

            $disable = $(".card.group").find(".enable-group, .disable-group");
            $disable.off('click');
            $disable.click(function () {
                $(this).parent().parent().find('.group-disabled').val($(this).hasClass('disable-group') ? 1 : 0);

                if ($(this).hasClass('disable-group')) {
                    $(this).text("ENABLE");
                    $(this).removeClass('disable-group').addClass('enable-group');
                    $(this).parent().parent().addClass('group-disabled');
                } else {
                    $(this).text("DISABLE");
                    $(this).removeClass('enable-group').addClass('disable-group');
                    $(this).parent().parent().removeClass('group-disabled');
                }
            });
        }

        function updateStats(covered, total, groups) {
            $("#ajax_progress").text(covered + " / " + total);
            $(".progress .progress-bar-success").width((covered*100) / total + "%").text(((covered*100) / total).toFixed(2) + "%");
            $(".progress .progress-bar-danger").width(100 - ((covered*100) / total) + "%");

            groups.forEach(function(group, i) {
                $group = $(".groups .group:nth-child("+(i+1)+")");
                $group.find(".card-footer").children().remove();
                $group.find(".card-footer").append("<span>We found " + group.covered + " users in this group.</span>");
                $group.find('.card-footer').append('<span class="float-right">Covers: <strong>'+((group.covered*100) / total).toFixed(2) + '%</strong></span>');
            });
        }

        function showError(error, $target) {
            $err = $('<div class="alert alert-danger alert-dismissable fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a></div>');

            if (typeof error === 'string') {
                $err.append(error);

            } else if (error.hasOwnProperty('overlaps')) {
                $message = '<p>Groups <strong>g1</strong> and <strong>g2</strong> have <strong>number</strong> users in common.</p>';

                error.overlaps.forEach(function(el) {
                    $err.append(
                        $message.replace('g1', el[0])
                                .replace('g2', el[1])
                                .replace('number', el[2])
                    )
                });
            } else if (error.hasOwnProperty('covered') ) {
                $message = '<p>This segment is covering <strong>n1</strong> out of <strong>n2</strong> users. <br>Please make sure the segment covers all users.</p>';
                $err.append(
                    $message.replace('n1', error.covered)
                            .replace('n2', error.total)
                );

                updateStats(error.covered, error.total, error.groups);
            }

            $target.append($err);
        }

        function submitForm($form, skip_save) {
            $groups = $('.groups .group');
            $target = $(".place-errors");
            $name = $("#name");
            $description = $("#description");

            $target.children().remove();

            $group_errors = false;
            $(".groups .group .group-header > input").each(function() {
                if ($(this).val() === '') {
                    $(this)
                        .addClass('group_name_error')
                        .blur(function() {
                            $(this).removeClass("group_name_error");
                            $(this).parent().find('.err').remove();
                        });
                    $(this).parent().append("<p class='err'>Name is required</p>");
                    $group_errors = true;
                }
            });
            if ($group_errors) {
                return showError("Please double check the groups.", $target);
            }

            if ($name.val() === '') {
                return showError("Segment name is required.", $target);
            }

            if ($description.val() === '') {
                return showError("Segment description is required.", $target);
            }

            if ($groups.length === 0) {
                return showError("You can't create a segment with no groups.", $target);
            }


            $(".loading").addClass('active');

            var data = $groups.toArray().reduce(function(carry, el) {
                console.log($(el), $(el).find('.group-name'), $(el).find('.group-name').val());
                carry.push({
                    group_name: $(el).find('.group-name').val(),
                    disabled: $(el).find('.group-disabled').val(),
                    id: $(el).find('.group-id').val(),
                    query_data: setFieldNames($(el).find('.card-body').children('ul').first().children('li').first(), 'query_data')
                });
                return carry;
            }, []);

            console.log('data', data);

            $.ajax({
                url     : $form.attr('action'),
                type    : $form.attr('method'),
                data    : {
                    segment_id: $('[name="segment_id"]').val(),
                    name: $name.val(),
                    description: $description.val(),
                    groups: data,
                    skip_save: skip_save
                },
                dataType: 'json',
                success : function(data) {
                    if (!data.success) {
                        return showError(data.errors, $target);
                    }

                    if (skip_save) {
                        return updateStats(data.covered, data.total, data.groups);
                    }

                    window.location.href = "{{$app['url_generator']->generate('messaging.segments.list')}}";
                },
                complete: function() {
                    setTimeout(function() {
                        $(".loading").removeClass('active');
                    }, 500);
                }
            });
        }

    </script>

    <script>
        var deleteGroup = function() {
            var group_id = $(this).data('group');
            var $group = $(this).parent().parent();

            var $confirmation_modal = $("#confirmation-modal").modal('show');

            $confirmation_modal.find('.question').text('Are you sure you want to delete this group?');
            $confirmation_modal.find(".confirm").off('click');
            $confirmation_modal.find(".confirm").on("click", function(){
                if (empty(group_id)) {
                    $group.remove();
                    $confirmation_modal.modal('hide');
                    return;
                }

                $.ajax({
                    type: "GET",
                    url: "{{$app['url_generator']->generate('messaging.segments.groups.delete')}}",
                    data: {'id': group_id},
                    success: function(res){
                        $group.remove();
                        $confirmation_modal.modal('hide');
                    }
                });
            });
            $confirmation_modal.find(".dismiss").off("click");
            $confirmation_modal.find(".dismiss").on("click", function(){
                $confirmation_modal.modal('hide');
            });
        };
        $query_data_input = $(".query_data_input");
        /**
         * Configure the events.
         */
        // $(window).on("scroll", function() {
        //     var fromTop = $("body").scrollTop();
        //     $(".filter-section").toggleClass("sticky", (fromTop > 200));
        // });

        $("#filters-search").keyup(function ()
        {
            var value = $(this).val();

            // toggleCollapse('filters', value !== "" ? 'open' : 'close');

            $('[data-searchable="filters"]').each(function()
            {
                return ($(this).data('searchby').toLowerCase().indexOf(value.toLowerCase()) < 0)
                    ?   $(this).addClass('d-none')
                    :   $(this).removeClass('d-none');
            });
        });

        $("#add-group").click(function() {
            var index = $(".groups .group").length;

            $placeholder = $(".placeholders .group").clone();
            $placeholder.find('input').each(function() {
                $(this).attr('name', 'groups[' + index + '][' + $(this).attr('name') + ']')
            });
            $(".groups").append($placeholder);

            $(".delete-group").click(deleteGroup);

            appendData($($placeholder).find(".card-body"), {expr_type: 'and'});
            prepareUX($placeholder);
        });

        $("#refresh-all").click(function(e) {
            e.preventDefault();
            submitForm($("#bonus-type-form"), true);
        });

        $("#save-all").click(function(e) {
            e.preventDefault();
            submitForm($("#bonus-type-form"));
        });

        if ($query_data_input.length > 0)
        {
            $query_data_input.each(function(i, el){
                appendData($(el).parent().find(".card-body"), JSON.parse($(el).val()))
            });
            $(".delete-group").click(deleteGroup);
        }
    </script>

    <script>
        $(document).ready(function () {
            prepareUX();
        });
    </script>
@endsection

@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.messaging.partials.topmenu')

        <style>
            .input-parent > div {
                margin-bottom: 10px;
            }

            .input-parent > div > input {
                text-align: left;
            }
            .closeIcon,
            .closeIcon-operator {
                cursor: pointer;
            }
            .closeIcon {
                position: absolute;
                left: -5px;
                margin-top: 9px;
            }

            .cursor {
                cursor: move;
            }

            .tab-content .treeview-menu {
                list-style: none !important;
                padding-left: 0;
            }

            .list-unstyled li {
                padding-left: 20px;
            }

            .full-width {
                width: 100%;
            }

            .custom-hide {
                display: none;
            }
            .searching {
                display: block;
            }
            .error input {
                border: 1px solid red;
            }
            .error::after {
                content: 'This field is required';
                color: red;
                font-weight: bold;
                float: left;
            }
            .filter-card {
                height: 600px;
                overflow: hidden;
            }
            .nav-tabs-custom > .tab-content {
                padding: 16px ;
            }
            .title-text {
                font-size: 2em;
                font-weight:bold;
            }
            .card .card {
                background-color: #f4f4f4;
            }
            ul .card > .card-header.with-border {
                background-color: #d2d6de;
            }
            .select2 {
                width: 100% !important;
                max-width: 100%;
            }
            #root_field {
                height: 470px;
                /*height:515px;*//*this will be used when english select will no longer be on the page*/
                overflow-y: auto;
            }
            .right {
                border-left:1px solid #d2d6de;
                margin-left: -10px;
                padding-left: 20px;
                margin-top: -5px;
            }
            .right input {
                margin-bottom: 10px;
                margin-top: 5px;
            }
            .or .form-control[readonly],
            .and .form-control[readonly]{
                background-color: #f9f9f9;
            }
            .and input,
            .and select {
                background-color: #f9f9f9;
            }
            .or input,
            .or select {
                background-color: #f9f9f9;
            }
            .custom-list-item {
                font-size: 17px;
                margin-top: 6px;
                padding-left: 10px !important;
            }
            .parent-style {
                font-weight: bold;
                padding-left: 16px !important;
            }
            #contact-list-table_info {
                float: right;
            }
            .top-scroll {
                height: 20px;
            }
            .date-interval-container > span,
            .date-interval-container > input {
                display: inline-block;
            }
            .date-interval-container > input {
                margin-left: 5px;
                margin-right: 5px;
                width: auto;
            }

        </style>

        <div class="card">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.list-contacts') }}">All Contacts</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.contact.list-filters') }}">Contact Filter
                            Lists</a></li>
                    @if(p('messaging.contacts.new'))
                        <li class="nav-item"><a class="nav-link active"><i class="fa fa-plus-square"></i> Create Filter List</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.segments.list') }}">List Segments</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.segments.form') }}"><i class="fa fa-plus-square"></i> Create Segment</a></li>
                </ul>

                <div class="tab-content p-3">
                    <div class="tab-pane active">
                        <section class="row">
                            <div class="col-lg-3 col-md-3 col-sm-12 col-12">
                                <div class="card filter-box">
                                    <div class="card-header with-border">
                                        <p class="title-text">Filters</p>
                                        <form class="form-horizontal disabled-form">
                                            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                                            <div class="form-group row">
                                                <label for="filters-search" class="col-sm-3 control-label">Search</label>

                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" id="filters-search" data-target="filters" placeholder="Search">
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="card-body">
                                        <ul id="fields_list" class="expandable tree list-unstyled side-filter-menu">

                                            <div class="card card-default card-solid">
                                                <div class="card-header with-border">
                                                    <button class="btn p-0 d-block w-100" type="button" data-toggle="collapse" data-target="#collapse-default" aria-expanded="true" aria-controls="collapse-default">
                                                        <h2 class="card-title">Default</h2>
                                                        <i class="fa fa-plus float-right"></i>
                                                    </button>
                                                </div>

                                                <div id="collapse-default" class="collapse" aria-labelledby="heading-default" data-parent="#fields_list">
                                                    <div class="card-body card-content">
                                                        <li id="and" class="operator draggable_item treeview cursor custom-list-item" data-type="and"><span>AND</span></li>
                                                        <li id="or" class="operator draggable_item treeview cursor custom-list-item" data-type="or"><span>OR </span></li>
                                                    </div>
                                                </div>
                                            </div>

                                            @foreach($filter_fields as $group)
                                                <div class="card card-default card-solid">
                                                    <div class="card-header with-border">
                                                        <button class="btn p-0 d-block w-100" type="button" data-toggle="collapse" data-target="#collapse-{{ $loop->index }}" aria-expanded="true" aria-controls="collapse-{{ $loop->index }}">
                                                            <h2 class="card-title">{{$group['group_name']}}</h2>
                                                            <i class="fa fa-plus float-right"></i>
                                                        </button>
                                                    </div>

                                                    <div id="collapse-{{ $loop->index }}" class="collapse" aria-labelledby="heading-{{ $loop->index }}" data-parent="#fields_list">
                                                        <div class="card-body card-content">
                                                            @foreach($group['fields'] as $field=>$data)
                                                                <li class="field draggable_item custom-list-item" data-type="condition"
                                                                    data-searchable="filters"
                                                                    data-searchby="{{$data['title']}}"
                                                                    data-value="{{$field}}" data-addons={{json_encode($data['addons'])}}>
                                                                    <i class="far fa-hand-paper"></i>
                                                                    <span class="cursor">{{$data['title']}}</span>
                                                                </li>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9 col-md-9 col-sm-12 col-12">
                                <div class="card filter-box">
                                    <div class="card-body">
                                        <form id="bonus-type-form" method="post" class="form-horizontal fields-form">
                                            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                                            <div class="row">
                                                <div class="col-lg-10 col-md-9 col-sm-9 col-9">
                                                    <input type="hidden" name="query_data" id="form_query_data">
                                                    <input type="hidden" name="selected_fields" id="form_selected_fields">
                                                    <div class="form-group row">
                                                        <label for="name" class="col-sm-2 control-label">Name</label>

                                                        <div class="col-sm-10">
                                                            <input type="text" name="name" id="name" class="form-control validation-required" value="{{ $namedSearch->name }}" >
                                                        </div>
                                                    </div>
                                                    <div class="form-group row">
                                                        <label for="language" class="col-sm-2 control-label">Languages</label>
                                                        <div class="col-sm-10">
                                                            <select name="language" id="select-language" class="form-control full-width validation-required" data-placeholder="Select language">
                                                                @foreach(\App\Repositories\UserRepository::getLanguages() as $key=>$language)
                                                                    <option value="{{ $key }}">{{ strtoupper($language) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-3 col-sm-3 col-3">
                                                    <div class="right">
                                                        <input class="btn btn-success full-width" type="submit" value="Save">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group"></div>
                                            <div class="form-group" id="root_field">
                                                <ul class="card-tree list-unstyled"></ul>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </section>

                        <section class="row">
                            <div class="col-lg-3 col-md-3 col-sm-12 col-12">
                                <div class="card">
                                    <div class="card-header with-border">
                                        <p class="title-text">Output</p>
                                        <form class="form-horizontal disabled-form">
                                            <div class="form-group row">
                                                <label for="output-search" class="col-sm-3 control-label">Search</label>

                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" id="output-search" data-target="output" placeholder="Search">
                                                </div>
                                            </div>
                                            <input class="btn btn-warning full-width" id="refresh" type="Button" value="Refresh table">
                                        </form>
                                    </div>
                                    <div class="card-body">
                                        <ul id="output_fields_list" class="expandable list-unstyled">
                                            @foreach($filter_fields as $group)
                                                <div class="card card-default card-solid">
                                                    <div class="card-header with-border">
                                                        <button class="btn p-0 d-block w-100" type="button" data-toggle="collapse" data-target="#output-collapse-{{ $loop->index }}" aria-expanded="true" aria-controls="output-collapse-{{ $loop->index }}">
                                                            <h2 class="card-title">{{$group['group_name']}}</h2>
                                                            <i class="fa fa-plus float-right"></i>
                                                        </button>
                                                    </div>

                                                    <div id="output-collapse-{{ $loop->index }}" class="collapse" aria-labelledby="heading-{{ $loop->index }}" data-parent="#output_fields_list">
                                                        <div class="card-body card-content target-parent">
                                                            <ul class="list-unstyled">
                                                                @foreach($group['fields'] as $field=>$data)
                                                                    @if (is_null($data['can_select']))
                                                                        <li class="targeted custom-list-item" data-searchable="output" data-searchby="{{$data['title']}}">
                                                                            <input type="checkbox" class="parent-style-checkbox" data-field="{{$field}}" data-title="{{$data['title']}}" {{in_array($field, $default_fields) ? 'checked' : ''}} >
                                                                            {{$data['title']}}
                                                                        </li>
                                                                    @else
                                                                        @if ($data['can_select'] === true)
                                                                            <li class="targeted custom-list-item" data-searchable="output" data-searchby="{{$data['title']}}" >
                                                                                <input type="checkbox" class="parent-style-checkbox" data-restriction="true" data-field="{{$field}}" data-title="{{$data['title']}}" {{in_array($field, $default_fields) ? 'checked' : ''}} >
                                                                                {{$data['title']}}
                                                                            </li>
                                                                        @endif
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9 col-md-9 col-sm-12 col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="top-scroll"></div>
                                        <table id="contact-list-table" class="table table-striped table-bordered" cellspacing="0" width="100%"></table>
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </section>
                    </div>
                </div>
            </div>
        </div>

        <div id="errorModal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Error</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    @include('admin.partials.jquery-ui-cdn')
    {{-- Contains missing functions related to filter to be able to make changes in one single place --}}
    @include('admin.messaging.partials.filter-component');

    <script type="text/javascript">
        /* key: title */
        var fieldsTitle = {};

        // /**
        //  * action: open/close
        //  * @param className
        //  * @param action
        //  */
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

        function styleCheckboxParent()
        {
            $(".parent-style-checkbox").parent().removeClass('parent-style');

            $(".parent-style-checkbox:checked").each(function()
            {
                $(this).parent().addClass('parent-style');
            });
        }

        function getSelectedFields(fieldsTitle)
        {
            return $('#output_fields_list .target-parent').toArray().reduce(function (li_carry, li_el)
            {
                return li_carry.concat(
                    $(li_el).find('ul:first li').toArray().filter(function (el)
                    {
                        return $(el).find('input:first').is(':checked');
                    }).reduce(function (carry, el)
                    {
                        fieldsTitle[$(el).find('input').data('field')] = $(el).find('input').data('title');
                        return carry.concat($(el).find('input').data('field'));
                    }, [])
                );
            }, []);
        }

        function clearErrors()
        {
            $(".error input, .error select").click(function()
            {
                $(this).parent().removeClass("error");
            });
            return true;
        }

        function invalidForm()
        {
            var validation_error = false;
            $(".validation-required").each(function(index, el)
            {
                if ($(el).val() !== "") return;

                $(el).parent().addClass("error");
                validation_error = true;
            });
            return validation_error;
        }

        function createTable(dataTable, query_data)
        {
            var table = dataTable.DataTable({
                processing  : true,
                serverSide  : true,
                ajax        : {
                    url : "{{ $app['url_generator']->generate('messaging.contact.list-contacts') }}",
                    type: "POST",
                    data: function (d)
                    {
                        d.query_data    = query_data
                            ? query_data
                            : setFieldNames($('#root_field').children('ul').first().children('li').first(), 'query_data');

                        d.selected_fields = getSelectedFields(fieldsTitle);
                        d.language = $("#select-language").val();

                        $("#form_query_data").val(d.query_data);

                        $("#form_selected_fields").val(JSON.stringify(d.selected_fields));
                    }
                },
                columns     : getSelectedFields(fieldsTitle).map(function (entry)
                {
                    return {
                        data: entry,
                        title: fieldsTitle[entry]
                    }
                }),
                searching   : false,
                order       : [[0, 'desc']],
                deferLoading: parseInt("{{ 50 }}"),
                pageLength  : 25,
                sDom        : '<"H"ilr><"clear">t<"F"p>'
            });

            table.ajax.reload();
        }

        $(document).ready(function ()
        {
            var contactsTable = $('#contact-list-table');
            var query_data = {expr_type: 'and'};
            var action = "{{ $app['url_generator']->generate('messaging.contact.new-filter') }}";

            @if(isset($namedSearch) && isset($namedSearch->form_params))
                query_data = {!! $namedSearch->form_params !!};
            @endif

            @if(isset($namedSearch) && isset($namedSearch->id))
                action = "{{ $app['url_generator']->generate('messaging.contact.update-filter', ['namedSearch' => $namedSearch['id']]) }}";
            @endif

            $("#bonus-type-form").attr('action', action);

            $("[data-restriction='true']").click(function(e)
            {
                var value = $(this).data('field');
                if (selectedFields[value] !== true)
                {
                    alert("You have to add " + value + " in the form to be able to select this field.");
                    e.preventDefault();
                    return false;
                }
            });

            $(".parent-style-checkbox").click(function(e)
            {
                return  ($(this).data('restriction') === true && selectedFields[$(this).data('field')] !== true)
                    ?   e.preventDefault()
                    :   styleCheckboxParent();
            });

            $('.expandable > li a').click(function ()
            {
                $(this).parent().find('ul').toggle();
            });

            $("#output-search, #filters-search").keyup(function ()
            {
                var target= $(this).data('target');
                var value = $(this).val();

                // toggleCollapse(target, value !== "" ? 'open' : 'close');

                $('[data-searchable="'+target+'"]').each(function()
                {
                    return ($(this).data('searchby').toLowerCase().indexOf(value.toLowerCase()) < 0)
                        ?   $(this).addClass('hidden')
                        :   $(this).removeClass('hidden');
                });
            });

            $("#bonus-type-form").submit(function ()
            {
                if (invalidForm() && clearErrors()) {
                    return false;
                }

                $.ajax({
                    url: $(this).attr('action'),
                    type: $(this).attr('method'),
                    data: {
                        query_data: setFieldNames($('#root_field').children('ul').first().children('li').first(), 'query_data'),
                        name: $(this).find('[name="name"]').val(),
                        language: $("#select-language").val(),
                        selected_fields: $(this).find('#form_selected_fields').val()
                    },
                    success: function (response)
                    {
                        if (response.success === true)
                        {
                            window.location.href = "{{ $app['url_generator']->generate('messaging.contact.list-filters') }}";
                        } else {
                            alert(response.message);
                        }
                    }
                });

                return false;
            });

            $("#output_fields_list input[type='checkbox']").click(function(e)
            {
                return ($(this).data('restriction') === true && selectedFields[$(this).data('field')] !== true)
                    ?   e.preventDefault()
                    :   $('#refresh').click();
            });

            $('body').keydown(function (e)
            {
                return (e.ctrlKey && e.keyCode === 13)
                    ?   $("#refresh").click()
                    :   true;
            });

            // prevent form submit on enter key press
            $('.disabled-form').on('keyup keypress', function(e)
            {
                return (e.keyCode || e.which) === 13
                    ?   e.preventDefault()
                    :   true;
            });

            $('#refresh').click(function (e)
            {
                return (invalidForm() && clearErrors())
                    ?   e.preventDefault()
                    :   contactsTable.DataTable().destroy() &&
                    contactsTable.empty() &&
                    createTable(contactsTable, null);
            });

            $("ul, li").disableSelection();

            $("#select-language").select2({
                allowClear: false
            }).val("{{ $namedSearch->language ?? 'en' }}").change();

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

            styleCheckboxParent();

            appendData($('#root_field'), query_data);

            createTable(contactsTable, query_data);
        });

    </script>

@endsection

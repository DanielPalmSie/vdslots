@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.messaging.partials.topmenu')
        <style>
            /* this class exists on select2() elements */
            .select2 {
                max-width: 100%;
            }
            .error-message {
                color: red;
            }
        </style>

        <div class="card">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.offline-campaigns') }}">All offline campaigns</a></li>
                    <li class="nav-item">
                        <a class="nav-link active">
                            @if ($campaign['id'])
                                Edit campaign [{{$campaign['name']}}]
                            @else
                                <i class="fa fa-plus-square"></i> Add offline campaign
                            @endif
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <form id="bonus-type-form" method="post" action="{{ $app['url_generator']->generate('messaging.offline-campaigns.save') }}" class="form-horizontal fields-form">
                            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                            @if ($campaign['id'])
                                <input type="hidden" name="campaign" value="{{$campaign['id']}}">
                            @endif
                            <div class="card-body">
                                <div class="col-lg-10 col-md-9 col-sm-9 col-9">
                                    <div class="form-group">
                                        <label for="name" class="col-sm-2 control-label">Name</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="name" id="name" class="form-control validation-required" value="{{$campaign['name']}}">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="select-named-search" class="col-sm-2 control-label">Contact list</label>
                                        <div class="col-sm-10">
                                            <select name="named_search_id"
                                                    id="select-named-search"
                                                    class="form-control select2-class validation-required"
                                                    data-placeholder="Select a contact filter"
                                                    data-allow-clear="true">
                                                <option></option>
                                                @foreach($named_searches as $ns)
                                                    <option value="{{ $ns->id  }}">
                                                        {{ $ns->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="select-type" class="col-sm-2 control-label">Promotion type</label>
                                        <div class="col-sm-10">
                                            <select id="select-type" class="form-control select2-class col-sm-10 validation-required"
                                                    data-placeholder="Select type" name="type">
                                                <option value="no_promotion">No promotion</option>
                                                <option value="bonus">Bonus</option>
                                                <option value="voucher">Voucher</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group" id="voucher-group">
                                        <label for="select-voucher-template" class="col-sm-2 control-label">Voucher
                                            template</label>
                                        <div class="col-sm-10">
                                            <select name="voucher_template_id"
                                                    id="select-voucher-template"
                                                    class="form-control select2-class col-sm-10 validation-required" data-parent="type" data-require="voucher"
                                                    data-placeholder="Select a voucher template">
                                                @foreach($voucher_templates as $template)
                                                    <option value="{{ $template->id  }}">
                                                        {{ empty($template->template_name) ? "Template #{$template->id}" : $template->template_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group" id="bonus-group">
                                        <label for="select-bonus-template" class="col-sm-2 control-label">Bonus template</label>
                                        <div class="col-sm-10">
                                            <select name="bonus_template_id"
                                                    id="select-bonus-template"
                                                    class="form-control select2-class validation-required" data-parent="type" data-require="bonus"
                                                    data-placeholder="Select a bonus template">
                                                @foreach($bonus_templates as $template)
                                                    <option value="{{ $template->id  }}">
                                                        {{ empty($template->template_name) ? "Template #{$template->id}" : $template->template_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="submit" class="col-sm-2 control-label"></label>
                                        <div class="col-sm-10">
                                            <input class="btn btn-success full-width" id="submit" type="submit" value="Save">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        var errors = {
            SELECT: function () {
                return '<p class="error-message">Please select one option.</p>';
            },
            INPUT: function (name) {
                return '<p class="error-message">Please insert the ' + name + '.</p>';
            }
        };
        function isValidForm($form) {
            var is_valid = true;

            $(".error-message").remove();

            $form.find('.validation-required').each(function () {
                var $el = $(this);
                var error = false;
                var depends_on_other_element = $el.data('parent') && $el.data('require');

                if(depends_on_other_element) {
                    var other_element_value = $("[name='"+$el.data('parent')+"']").val();
                    var expected_value = $el.data('require');
                    if (other_element_value === expected_value) {
                        error = $el.val() === '' || $el.val() === null;
                    }
                } else {
                    error = $el.val() === ''|| $el.val() === null;
                }

                if (error) {
                    $(this).parent().append(errors[$el.prop("tagName")]($el.attr('name')));
                    is_valid = false;
                }
            });
            return is_valid;
        }

    </script>
    <script>
        var default_type = '{{$campaign['type'] ?? 'no_promotion'}}';
        var default_named_search = '{{$campaign['named_search'] ?? ''}}';
        var default_template_id = '{{$campaign['template_id'] ?? ''}}';

        $('#select-named-search').select2().val(default_named_search).change();
        $('#select-bonus-template').select2();
        $('#select-voucher-template').select2();

        $('#select-'+default_type+'-template').val(default_template_id).change();

        $('#select-type').select2({
            minimumResultsForSearch: -1
        }).val(default_type).change().on('change', function (e) {
            $('#voucher-group, #bonus-group').hide();

            if (this.value == 'bonus') {
                $('#bonus-group').show();
                $('#select-voucher-template').change();
            }
            if (this.value == 'voucher') {
                $('#voucher-group').show();
                $('#select-bonus-template').change();
            }
        });
        $('#select-type').change();

        $("form").submit(function(e) {
            if (!isValidForm($(this))) {
                e.preventDefault();
                return false;
            }
        });
    </script>
@endsection

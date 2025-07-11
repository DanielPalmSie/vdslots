<div class="card-body">

    @if ($app)

    @if ($buttons['delete'])
    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalDeleteLabel">Confirm Delete</h4>
                </div>

                <div class="modal-body">
                    <p>You are about to delete the Config. This procedure is irreversible.</p>
                    <p>Do you want to proceed?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button id="delete-modalbtn" class="btn btn-danger" data-dismiss="modal">Delete</button>
                </div>
            </div>
        </div>
    </div>
    @endif
        <form id="config-form" class="" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            @if($config)
                <input name="id" class="form-control" type="hidden" value="{{ $config->id }}">
            @endif

            @if($config_type_json['type'])
                <input name="config_value_type" class="form-control" type="hidden" value="{{ $config_type_json['type'] }}">
            @endif

            <div class="row">
                <div class="col-12 col-sm-4 col-lg-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Name:</span>
                        </div>
                        <!--
                        <select name="config_name" id="select-config_name" class="form-control select2-class select-config_name" style="width: 100%;" data-placeholder="No Type specified" data-allow-clear="true">
                            @foreach ($all_distinct['config_name'] as $t)
                                @if ($config->config_name == $t)
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    -->
                        <input id="input-config_name"
                            name="config_name" class="form-control" type="text"
                            value="{{ $config->config_name }}"
                        >
                    </div>
                </div>
                <div class="col-12 col-sm-4 col-lg-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Tag:</span>
                        </div>
                        <input id="input-config_tag"
                            name="config_tag" class="form-control" type="text"
                            value="{{ $config->config_tag }}"
                        >
                    </div>
                </div>
                @if ($breadcrumb == 'New')
                <div class="col-12 col-sm-4 col-lg-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Type:</span>
                        </div>
                        <select name="config_type" class="form-control select2-class select-config_type" data-placeholder="No Config Type specified" data-allow-clear="false">
                            @foreach ($all_distinct['config_type'] as $t)
                                @if ($config->config_type == $t)
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <input name="config_value" class="form-control" type="hidden" value="">
                </div>
                @else
                <input name="config_type" type="hidden" value="{{ $config->config_type }}">
                @endif
                @if (in_array($config_type_json['type'], ['template', 'ISO2-template', 'ISO2', 'iso2']))
            </div>
            <div class="row">
                <!--
                <div class="col-12 col-sm-4 col-lg-4">
                </div>
                -->
                <div class="col-12 col-sm-6 col-lg-6 col-sm-offset-3 col-md-offset-3">
                @else
                <div class="col-12 col-sm-4 col-lg-4">
                @endif
                    @if ($config_type_json['type'] == 'number')
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Value:</span>
                            </div>
                            <input id="input-config_value"
                                name="config_value" class="form-control" type="number"
                                value="{{ $config->config_value }}"
                                @foreach ($config_type_json['extras'] as $key => $value)
                                    {{ $key }}="{{ $value  }}"
                                @endforeach
                            >
                        </div>
                    @elseif ($config_type_json['type'] == 'text')
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Value:</span>
                            </div>
                            <input id="input-config_value"
                                name="config_value" class="form-control" type="text"
                                value="{{ $config->config_value }}"
                            >
                        </div>
                    @elseif ($config_type_json['type'] == 'choice')
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Value:</span>
                            </div>
                            <select name="config_value" class="form-control select2-class select-config_value select2" style="width: 100%;" data-placeholder="No Type specified" data-allow-clear="false" data-minimum-results-for-search="Infinity">
                                @foreach ($config_type_json['values'] as $t)
                                    @if ($config->config_value == $t)
                                        <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                    @else
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @elseif ($config_type_json['type'] == 'datetime')
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Value:</span>
                            </div>
                            <input id="input-config_value"
                                name="config_value" class="form-control form_datetime" type="text"
                                value="{{ $config->config_value }}"
                            >
                        </div>
                    @elseif ($config_type_json['type'] == 'ISO2' || $config_type_json['type'] == 'iso2')
                        <div class="input-group">
                            <select multiple="multiple" size="10" name="config_value[]" class="form-control select2-class select-config_value select2" style="width: 100%;" data-placeholder="No Type specified" data-allow-clear="false">
                                @foreach ($countries['all'] as $b)
                                    @if ($countries['selected'][$b['iso']])
                                        <option value="{{ $b['iso'] }}" selected="true">{{ $b['printable_name'] }}</option>
                                    @else
                                        <option value="{{ $b['iso'] }}">{{ $b['printable_name'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @elseif ($config_type_json['type'] == 'template')
                        <div class="">
                            <div>
                                <table class="table compact-table text-center">
                                    <thead>
                                        <tr>
                                            @foreach (array_keys($config_type_json_data[0]) as $key)
                                            <th>{{ $key }}</th>
                                            @endforeach
                                            <th>Interaction</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($config_type_json_data as $index => $row_data)
                                        <tr>
                                            @foreach ($row_data as $key => $data)
                                            <td>
                                                <div class="form-group">
                                                    <input
                                                        name="{{ $key }}[{{ $index }}]"
                                                        value="{{ trim($row_data[$key]['value']) }}"
                                                        class="form-control"
                                                        type="{{ $row_data[$key]['type'] }}"
                                                    @foreach ($row_data[$key]['extras'] as $key => $value)
                                                        {{ $key }}="{{ $value  }}"
                                                    @endforeach
                                                    />
                                                    <a href="#" type="button" class="btn btn-mini float-right toggle-type"><i class="fa fa-file-text-o" aria-hidden="true"></i></a>
                                                    <br/>
                                                </div>
                                            </td>
                                            @endforeach
                                            <td style="width: 120px;">
                                                <button class="btn btn-sm btn-primary clone-config-template-btn" title="Clone"><i class="fa fa-copy"></i></button>
                                                <button class="btn btn-sm btn-default clear-config-template-btn" title="Clear"><i class="fa fa-file-o"></i></button>
                                                <button class="btn btn-sm btn-default undo-config-template-btn" title="Undo"><i class="fa fa-undo"></i></button>
                                                <button class="btn btn-sm btn-danger remove-config-template-btn" title="Delete"><i class="fa fa-remove"></i></button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif ($config_type_json['type'] == 'ISO2-template')
                        <div class="">
                            <div>
                                <table class="table compact-table text-center">
                                    <thead>
                                    <tr>
                                        @foreach (array_keys($config_type_json_data[0]) as $key)
                                            <th>{{ $key }}</th>
                                        @endforeach
                                        <th>Interaction</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($config_type_json_data as $index => $row_data)
                                        <tr>
                                            @foreach ($row_data as $key => $data)
                                                <td>
                                                    <div class="form-group">
                                                        @if($key == 'ISO2')
                                                            <select multiple="multiple" size="10" name="{{ $key }}[{{ $index }}][]" class="form-control select2-class select-config_value select2" style="width: 100%;" data-placeholder="No Type specified" data-allow-clear="false">
                                                                @foreach ($row_data[$key]['all'] as $b)
                                                                    @if ($row_data[$key]['selected'][$b['iso']])
                                                                        <option value="{{ $b['iso'] }}" selected="true">{{ $b['printable_name'] }}</option>
                                                                    @else
                                                                        <option value="{{ $b['iso'] }}">{{ $b['printable_name'] }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <input
                                                                    name="{{ $key }}[{{ $index }}]"
                                                                    value="{{ trim($row_data[$key]['value']) }}"
                                                                    class="form-control"
                                                                    type="{{ $row_data[$key]['type'] }}"
                                                            />
                                                        @endif
                                                        <a href="#" type="button" class="btn btn-mini float-right toggle-type"><i class="fa fa-file-text-o" aria-hidden="true"></i></a>
                                                        <br/>
                                                    </div>
                                                </td>
                                            @endforeach
                                            <td style="width: 120px;">
                                                <button class="btn btn-sm btn-primary clone-config-iso2-template-btn" title="Clone"><i class="fa fa-copy"></i></button>
                                                <button class="btn btn-sm btn-default clear-config-template-btn" title="Clear"><i class="fa fa-file-o"></i></button>
                                                <button class="btn btn-sm btn-default undo-config-template-btn" title="Undo"><i class="fa fa-undo"></i></button>
                                                <button class="btn btn-sm btn-danger remove-config-template-btn" title="Delete"><i class="fa fa-remove"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
    </form>

    <div id="button-footer">
        <div class="form-group row">
            <!--<div class="col-sm-6 col-sm-offset-4">-->
            <div class="col-sm-12 text-center">
                @if ($buttons['save'])
                    <button id="save-config-btn" class="btn btn-primary">
                        {{ $buttons['save'] }}
                    </button>
                @endif
                @if ($buttons['save-as-new'])
                    &nbsp; | &nbsp;
                    <button id="save-as-new-config-btn" class="btn btn-info">
                        {{ $buttons['save-as-new'] }}
                    </button>
                @endif
                @if ($buttons['delete'])
                    &nbsp; | &nbsp;
                    <button id="delete-config-btn" class="btn btn-danger" data-toggle="modal" data-target="#confirm-delete">
                        {{ $buttons['delete'] }}
                    </button>
                @endif
            </div>
        </div>
    </div>
    @push('extrajavascript')
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">

        var amount_inputs = 0;

        function updateInputNumbering() {
            var input_names = {!! json_encode(array_keys($config_type_json_data[0])) !!};
            amount_inputs = 0;
            for (var input_name_count = 0; input_name_count < input_names.length; input_name_count++) {
                $("input[name^='"+input_names[input_name_count]+"']").each(function(index, attr) {
                    $(this).attr("name", input_names[input_name_count] + "[" + index + "]");
                    if (amount_inputs < index) {
                        amount_inputs = index+1;
                    }
                });

            }
            if (amount_inputs <= 1) {
                $('.remove-config-template-btn').each(function() {
                    $(this).attr('disabled', true);
                });
            } else if(amount_inputs == 2) {
                $('.remove-config-template-btn').each(function() {
                    $(this).attr('disabled', false);
                });
            }
        }

        function updateIso2TemplateInputNumbering() {
            var input_names = {!! json_encode(array_keys($config_type_json_data[0])) !!};
            amount_inputs = 0;
            console.log(input_names);
            for (var input_name_count = 0; input_name_count < input_names.length; input_name_count++) {
                $("input[name^='"+input_names[input_name_count]+"']").each(function(index, attr) {
                    $(this).attr("name", input_names[input_name_count] + "[" + index + "]");
                    if (amount_inputs < index) {
                        amount_inputs = index+1;
                    }
                });

                $("select[name^='"+input_names[input_name_count]+"']").each(function(index, attr) {
                    $(this).attr("name", input_names[input_name_count] + "[" + index + "][]");
                    if (amount_inputs < index) {
                        amount_inputs = index+1;
                    }
                });

            }
            if (amount_inputs <= 1) {
                $('.remove-config-template-btn').each(function() {
                    $(this).attr('disabled', true);
                });
            } else if(amount_inputs == 2) {
                $('.remove-config-template-btn').each(function() {
                    $(this).attr('disabled', false);
                });
            }
        }

        $(document).ready(function() {

            $('.toggle-type').click(function(event) {
                event.preventDefault();

                var inputObj = $(this).parent().find('input');
                var attr = inputObj.attr('type');
                if (attr == 'text') {
                    attr = 'number';
                } else {
                    attr = 'text';
                }
                inputObj.attr({type:attr});
            });

            @if ($config_type_json['type'] == 'datetime')
            //$(".form_datetime").datetimepicker({format: '{{ $config_type_json['format'] }}'});
            $(".form_datetime").datetimepicker();
            @endif

            updateInputNumbering();

            $('.undo-config-template-btn').each(function() {
                $(this).hide();
            });

            $('.help-btn').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                var $collapse = $this.closest('.form-group').find('.help-block');
                $collapse.collapse('toggle');
            });

            $('#delete-config-btn').on('click', function(e) {
                e.preventDefault();
            });

            $('.clone-config-template-btn').on('click', function(e) {
                e.preventDefault();
                var $tr    = $(this).closest('tr');
                var $clone = $tr.clone(true);
                $tr.after($clone);
                updateInputNumbering();
            });

            $('.clone-config-iso2-template-btn').on('click', function(e) {
                e.preventDefault();
                $(".select2").each(function(index)
                {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });
                var $tr    = $(this).closest('tr');
                var $clone = $tr.clone(true);
                $tr.after($clone);
                updateIso2TemplateInputNumbering();
                $('.select2').select2();
            });

            $('.clear-config-template-btn').on('click', function(e) {
                e.preventDefault();
                var $tr = $(this).closest('tr');
                $tr.find('input').each(function() {
                    var val = $(this).val();
                    $(this).data("old-value", val);
                    $(this).val("");
                });
                $(this).hide();
                $tr.find('.undo-config-template-btn').each(function() {
                    $(this).show();
                });
            });

            $('.undo-config-template-btn').on('click', function(e) {
                e.preventDefault();
                var $tr = $(this).closest('tr');
                $tr.find('input').each(function() {
                    var val = $(this).data("old-value");
                    $(this).val(val);
                });
                $(this).hide();
                $tr.find('.clear-config-template-btn').each(function() {
                    $(this).show();
                });
            });

            $('.remove-config-template-btn').on('click', function(e) {
                e.preventDefault();
                if (amount_inputs > 1) {
                    var $tr = $(this).closest('tr');
                    $tr.remove();
                    updateInputNumbering();
                }
            });

        });
    </script>
    @endpush

    @endif

</div>

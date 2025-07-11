<style>
    .sortable_list {
        padding-top: 5px;
        padding-bottom: 5px;
    }

    .relative {
        position: relative;
    }

    .and, .or {
        margin-right: 10px;
        border-radius: 10px;
        overflow: hidden;
    }

    .and {
        border: 2px solid #d2d6de !important;
        background-color: #f4f4f4 !important;
    }

    .or {
        border: 2px solid #d2d6de !important;
        background-color: #ffffff !important;
    }

    .and > h5,
    .or > h5 {
        font-size: 17px;
        margin-left: 0;
        margin-top: 0;
        font-weight: bold;
    }
    .width-auto {
        width: auto;
    }
    .width-25 {
        width: 25%;
    }
    .width-75 {
        width: 75%;
    }
</style>

<script>
    jQuery.fn.outerHTML = function()
    {
        return $(this)[0].outerHTML;
    };
    /* key: true */
    selectedFields = {};
    droppableAttrs = {greedy: true, drop: onDrop};
    sortableAttrs = {revert: true, connectWith: ".sortable_list"};
    draggableAttrs = {
        connectWith: ".container",
        helper: "clone",
        revert: "invalid"
    };

    dateTypeInput = {
        date: function(value)
        {
            return '<div class="col-12">' +
                '<input class="form-control validation-required" type="date" value="'+value+'">' +
                '</div>';
        },
        today_plus: function(value)
        {
            return  '<div class="col-12">' +
                '<input class="form-control validation-required width-75 pull-left" type="number" data-today="plus" placeholder="amount" value="'+value+'">' +
                '<span class="width-25 float-right text-center">days</span>' +
                '</div>';
        },
        today_minus: function(value)
        {
            return '<div class="col-12">' +
                '<input class="form-control validation-required width-75 pull-left" type="number" data-today="minus"  placeholder="amount" value="'+value+'">' +
                '<span class="width-25 float-right text-center">days</span>' +
                '</div>';
        },
        none: function(value)
        {
            if (!value) value = 'none';
            return '<div class="col-12 single_value">' +
                '<input class="form-control validation-required width-75 pull-left" type="hidden" placeholder="none" value="'+value+'">' +
                '</div>';
        }
    };

    fieldDataUrl = "{{ $app['url_generator']->generate('messaging.contact.get-field-data') }}";

    function onDrop(e, ui)
    {
        if (!ui.draggable.hasClass('draggable_item'))
        {
            return;
        }
        var query_data = {expr_type: ui.draggable.data('type')};

        if (query_data.expr_type === 'condition')
        {
            query_data.fields = [ui.draggable.data('value'), null, null];
        } else {
            query_data.operands = [];
        }

        selectedFields[ui.draggable.data('value')] = true;

        appendData($(this), query_data);
    }

    function listenOnSelectPlaceholderChange()
    {
        $(".date-select-placeholder").change(function()
        {
            var item = $(this).val();
            var value = item === $(this).data('selected') ? $(this).data('input') : '';

            $(this).parent().parent().find('.input').html(
                dateTypeInput[item](value)
            );
        });
    }

    function appendData(self, query_data)
    {
        var list = self.children('ul').first();
        var container = '<div class="col-12 col-sm-12 col-md-3 col-lg-3 col-xl-3 col-fhd-3">';

        if (query_data.expr_type === 'condition')
        {
            var conditionInput = $('<select class="form-control full-width" data-placeholder="Select one from the list" data-allow-clear="true" data-target="#bonus-type-form">');
            var row = $('<div class="row input-parent relative">');

            $.ajax({
                url: fieldDataUrl,
                type: "POST",
                data: {field_id: query_data.fields[0]},
                success: function (response)
                {
                    list.append($('<li>').append(
                        row.append(
                            $(container).append('<i class="fa fa-fw fa-times closeIcon "></i>')
                                .append($('<input class="form-control" readonly>').val(query_data.fields[0].trim()))
                        ).append(
                            $(container).addClass('custom-hide').append(conditionInput)
                        )
                    ).append('<input class="type" hidden="true" value="condition">'));

                    container = '<div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-fhd-6">';

                    row.append($(container)
                        .append(getInputFields(query_data, response)()))
                        .append(getAddons(query_data, container, response));

                    registerDynamicSelect(query_data.fields[5]);
                    preventNegativeNumbers();

                    if (response.comparators)
                    {
                        conditionInput.val(query_data.fields[1])
                            .append(response.comparators.reduce(function (carry, element)
                            {
                                $option = $('<option></option>').val(element).text(idToName(element));

                                if (element === query_data.fields[1])
                                {
                                    $option.attr('selected', 'true');
                                }

                                return carry + $option[0].outerHTML;
                            }, ''))
                            .parent().removeClass('custom-hide')
                    }

                    $("[data-expand_date=true]").each(function() {
                        response.type = 'DATE';
                        query_data.fields = [
                            query_data.fields[0],
                            query_data.fields[1],
                            query_data.fields[5]
                        ];
                        $(this).parent().html(getInputFields(query_data, response)());
                    });

                    listenOnSelectPlaceholderChange(query_data.fields[2]);

                    row.find(".date-select-placeholder").change();
                    row.find(".dynamic-select-options").parent().change();

                    $(".closeIcon").click(function ()
                    {
                        $parent = $(this).parent();
                        $parent.find("input").each(function(index, el)
                        {
                            $field = $("input[data-field='"+$(el).val()+"']");
                            $field.removeAttr('checked');
                            $field.parent().removeClass('parent-style');

                            delete selectedFields[$(el).val()];
                        }).promise().done(function() {
                            $parent.parent().parent().remove();
                        });
                    })
                }
            });
        } else {
            var add_remove_button = $(".field_item").length > 0;
            var listItem = $('<li class="field_item ui-sortable-handle ui-droppable relative">')
                .addClass(query_data.expr_type)
                .droppable(droppableAttrs)
                .append($('<h5></h5>').text(query_data.expr_type.toUpperCase()).append(add_remove_button ? $('<i class="fa fa-fw fa-times pull-left closeIcon-operator"></i>') : ''))
                .append($('<ul class="sortable_list ui_sortable">').sortable(sortableAttrs))
                .append($('<input class="type" hidden="true">').val(query_data.expr_type));

            if ($(".operator-check").length === 0)
            {
                listItem.addClass('operator-check');
            }

            list.append(listItem);

            if (query_data.operands)
                query_data.operands.forEach(function (operand)
                {
                    appendData(listItem, operand);
                });

            $(".closeIcon-operator").click(function()
            {
                $(this).parent().parent().remove();
            });
        }
    }

    function registerDynamicSelect(selected)
    {
        $(".dynamic-select-options").parent().change(function()
        {
            $(this).parent().parent().find('.dynamic-select').html(
                $(this).find(":selected").data('options') ?
                $(this).find(":selected").data('options').reduce(function(carry, option)
                {
                    return carry + $("<option>").val(Object.keys(option)[0]).text(Object.values(option)[0]).attr('selected', selected ? selected.toLowerCase() == Object.keys(option)[0].toLowerCase() : false).outerHTML();
                }, "") :
                null
            );
        });
    }
    function getInputFields(query_data, response)
    {
        var select = '<select class="form-control full-width validation-required" data-placeholder="Select one from the list" data-allow-clear="false" data-target="#bonus-type-form">';
        var option = '<option value="" ></option>';
        var input = '<input class="form-control validation-required">';

        return {
            TEXT: function ()
            {
                return $(input).val(query_data.fields[2]);
            },
            NUMERICAL: function ()
            {
                return $(input).val(query_data.fields[2]).attr('type', 'number').attr('min', 0);
            },
            DATE: function ()
            {
                var options = [
                    ['date', 'Date'],
                    ['today_plus', 'Today +'],
                    ['today_minus', 'Today -'],
                    ['none', 'None']
                ];

                var selected = query_data.fields[2] ? query_data.fields[2][0] : null;

                return $('<div class="row">' +
                    '<select class="form-control width-25 pull-left validation-required date-select-placeholder" data-input="'+ (selected ? query_data.fields[2][1] : '') +'" data-selected="'+ (selected ? query_data.fields[2][0] : '') +'">' +
                    options.map(function(option)
                    {
                        return option[0] === selected
                            ? '<option value="'+option[0]+'" selected >'+option[1]+'</option>'
                            : '<option value="'+option[0]+'">'+option[1]+'</option>'
                    }) +
                    '</select>' +
                    '<div class="input width-75 float-right"></div>' +
                    '</div>');
            },
            DATETIME: function ()
            {
                return $(input).attr('type', 'datetime-local');
            },
            DROPDOWN: function ()
            {
                var selected = false;

                return $(select).val(query_data.fields[2])
                    .append(Object.keys(response.data).reduce(function (carry, key)
                    {
                        var option_data = response.data[key];
                        var requires_select_addon = typeof option_data === 'object';
                        var title = requires_select_addon ?  option_data.title : option_data;

                        $option     = $(option).val(key).text(idToName(title));

                        if (requires_select_addon)
                        {
                            $option.addClass('dynamic-select-options').attr('data-options', JSON.stringify(option_data.options));
                        }

                        $select     = query_data.fields[2] !== null && (
                            query_data.fields[2].toLowerCase() === title.toLowerCase()
                            ||  query_data.fields[2].toLowerCase() === key.toLowerCase() );

                        if ($select)
                        {
                            $option.attr('selected', 'true');
                            selected = true;
                        }

                        return carry + $option[0].outerHTML
                    }, []))
                    .append(! selected ? '<option selected="true"></option>' : '');
            }
        }[response.type];
    }

    function getAddons(query_data, container, element)
    {
        var left_container = '<div class="col-12 col-sm-12 col-md-3 col-lg-3 col-xl-3 col-fhd-3">';
        var input       = '<input type="date" class="form-control validation-required">';
        var placeholder = '<input class="form-control" readonly="">';
        var values      = {};

        for (i = 3; i < query_data.fields.length; i += 3)
        {
            values[query_data.fields[i]] = {
                comparator: query_data.fields[i+1],
                value: query_data.fields[i+2]
            };
        }

        return  !element.addons
            ?   null
            :   element.addons.reduce(function(carry, el)
            {
                return carry
                    +   $(left_container).append($(placeholder).attr('value', el.key)).outerHTML()

                    +   $(left_container).append(
                        el.comparators
                            ?   el.comparators.reduce(function(carry, comparator)
                                {
                                    return  carry.append(
                                        $('<option></option>')
                                            .val(comparator)
                                            .text(idToName(comparator))
                                            .attr('selected', values[el.key] && values[el.key].comparator === comparator)
                                    );
                                }, $('<select class="form-control full-width" >'))
                            :   $(placeholder).attr('value', '=')
                    ).outerHTML()

                    +   $(container).append(
                        el.type === 'select'
                            ? $('<select class="form-control full-width dynamic-select" >').attr('data-options_source', el.options_source)
                            : $(input)
                                .attr('data-expand_date', el.expand_date)
                                .attr('name', el.key)
                                .attr('type', el.type)
                                .attr('value', values[el.key] ? values[el.key].value : '')
                    ).outerHTML();
            }, '');
    }

    function preventNegativeNumbers()
    {
        $("input[type='number']").keyup(function(){
            if ($(this).val() < 0)
            {
                alert("You should only have positive numbers!");
                $(this).val(-1 * $(this).val());
            }
        });
        return true;
    }

    function idToName(string)
    {
        return (string.charAt(0).toUpperCase() + string.slice(1)).replace(/_/g, " ");
    }

    function setFieldNames(node, prefix)
    {
        node.children('.type').first().attr('name', prefix + "[expr_type]");

        return {
            expr_type:  node.children('.type').first().val(),
            operands:   node.children('ul').children('li').toArray().reduce(function(carry, el, i)
            {
                carry[i] = setFieldNames($(el), prefix + "[operands][" + i + "]");
                return carry;
            }, {}),
            fields:     node.children('.row').first().children().toArray().reduce(function (carry, el, i)
            {
                if ($(el).children('input, select').first().length === 0 && $(el).find('.input input').length > 0)
                {
                    carry[i] = [
                        $(el).find('select').first().attr('name', prefix + "[fields][" + i + "]").val(),
                        $(el).find('.input input').first().attr('name', prefix + "[fields][" + i + "]").val()
                    ];
                } else {
                    carry[i] = $(el).children('input, select').first().attr('name', prefix + "[fields][" + i + "]").val();
                }
                return carry;
            }, {})
        };
    }

    $(".draggable_item").draggable(draggableAttrs);
    $(".sortable_list").sortable(sortableAttrs);
    $(".field_item").droppable(droppableAttrs);
</script>
@include('admin.gamification.trophies.partials.sharedjs')
@include('admin.partials.js')

<script type="text/javascript">

    var amount_prizes = 0;
    var amount_levels = 0;
    var levels = {!! json_encode($racetemplate["levels"]) !!}
    var race_type = {!! json_encode($racetemplate["race_type"]) !!}
    var input_names = null;
    var tropy_awards = null;
    var prize_type = {!! json_encode($racetemplate["prize_type"]) !!}
    var $tr_prizes_template = null;
    var $tr_levels_template = null;

    function extractTemplates() {
        $tr_prizes_template = $('#edit-prizes-table tbody tr');
        $tr_levels_template = $('#edit-levels-table tbody tr');
    }

    function initializeEditPrizes() {
        input_names = {!! json_encode($racetemplate["counted_prizes"], JSON_FORCE_OBJECT) !!};
        tropy_awards = {!! json_encode($trophy_awards) !!};

        if (input_names == null) {
            return;
        }

        var input_index = 0;
        var amount_offset = 1;

        $tr_prizes_template.show();

        Object.keys(input_names).forEach(function(key) {
            var amount = input_names[key];
            var $tr_clone = $tr_prizes_template.clone(true);

            if (prize_type == "award") {
                $option_prize = $tr_clone.find("select[name^='prize\['] > option");
                $span_prize = $option_prize.closest('td').find(".span-racetemplate-alias");
                if (tropy_awards[input_index] != null) {
                    $option_prize.val(tropy_awards[input_index].award.id);
                    $option_prize.text(tropy_awards[input_index].award.description);

                    $span_prize.text(tropy_awards[input_index].award.alias);

                    if (tropy_awards[input_index].award_alt) {
                        $option_prize_alt = $tr_clone.find("select[name^='prize_alt\['] > option");
                        $option_prize_alt.val(tropy_awards[input_index].award_alt.id);
                        $option_prize_alt.text(tropy_awards[input_index].award_alt.description);

                        $span_prize = $option_prize_alt.closest('td').find(".span-racetemplate-alias");
                        $span_prize.text(tropy_awards[input_index].award_alt.alias);
                    }
                } else {
                    $span_prize.text("Award not found.");
                }
            } else {
                $input_prize = $tr_clone.find("input[name^='prize\[']");
                $input_prize.val(parseInt(key));
            }

            $input_start_position = $tr_clone.find("input[name^='start_position']");
            $input_start_position.val(amount_offset);

            $input_end_position = $tr_clone.find("input[name^='end_position']");
            $input_end_position.val(amount_offset+amount-1);

            $tr_prizes_template.before($tr_clone);

            amount_offset += amount;
            input_index++;
        });

        $tr_prizes_template.hide();
        //$tr_prizes_template.remove();
        updateEditPrizes();
    }

    function initializeEditLevels() {

        if (levels == null) {
            return;
        }

        var levels_inputs = levels.split("|");

        $tr_levels_template.show();

        levels_inputs.forEach(function(value) {
            var $tr_clone = $tr_levels_template.clone(true);
            var [threshold_cents, points] = value.split(":"); // Destructuring assignment seems supported enough.

            if (race_type == "bigwin") {
                var $input_threshold = $tr_clone.find("select[name^='threshold']");
                $input_threshold.val(threshold_cents).trigger('change');
            } else {
                var $input_cents = $tr_clone.find("input[name^='cents']");
                $input_cents.val(parseInt(threshold_cents));
            }

            $input_points = $tr_clone.find("input[name^='points']");
            $input_points.val(points);

            $tr_levels_template.before($tr_clone);
        });

        $tr_levels_template.hide();
        //$tr_levels_template.remove();
        updateEditLevels();
    }

    function updateEditPrizes() {

        amount_prizes = $('#edit-prizes-table tbody tr').length-1; // -1 to exclude the template.

        if (amount_prizes <= 1) {
            $('.remove-edit-prize-btn').each(function() {
                $(this).attr('disabled', true);
            });
        } else {
            $('.remove-edit-prize-btn').each(function() {
                $(this).attr('disabled', false);
            });
        }
    }

    function updateEditLevels() {

        amount_levels = $('#edit-levels-table tbody tr').length-1; // -1 to exclude the template.

        if (amount_levels <= 1) {
            $('.remove-edit-levels-btn').each(function() {
                $(this).attr('disabled', true);
            });
        } else {
            $('.remove-edit-levels-btn').each(function() {
                $(this).attr('disabled', false);
            });
        }
    }


    function setupAwardSelect2(classname) {
        $('.'+classname).each(function() {
            bindSelect2($(this));
        });
    }

    function bindSelect2(select_object) {
        select_object.select2({
            ajax: {
                url: "{{ $app['url_generator']->generate('trophyawards.ajaxfilter') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: $.map(data, function(data) {
                            return { id: data.id, text: data.description, alias: data.alias };
                        }),
                        pagination: {
                            more: (params.page * 30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            dropdownParent: $("#edit-prizes"),
            minimumInputLength: 3,
            multiple: false
        });
        select_object.on("select2:select", function (e) {
            $(this).closest('td').find('.span-racetemplate-alias').text(e.params.data.alias);

        });
        select_object.on("select2:unselect", function (e) {
            $(this).closest('td').find('.span-racetemplate-alias').text("");
        });
    }

    function updateForm() {
        if (prize_type == "award") {
            $('.racetemplate-prize-select2').show();
            $('.racetemplate-prize_alt-td').show();
            $('.racetemplate-prize-input').hide();
            $('#prizes-table-column-header-award_alt').show();
            $('#prizes-table-column-header').text("Award");
        } else {
            $('.racetemplate-prize-input').show();
            $('.racetemplate-prize-select2').hide();
            $('.racetemplate-prize_alt-td').hide();
            $('#prizes-table-column-header-award_alt').hide();
            $('#prizes-table-column-header').text("Amount");
        }

        if (race_type == "bigwin") {
            $('#levels-table-column-header').text("Threshold");

            //$(".td-template-edit-levels").find("select[name^='threshold']").each(function() {
            $(".td-template-edit-levels").find(".select-span-wrapper").each(function() {
                $(this).show();
            });
            $(".td-template-edit-levels").find("input[name^='cents\[']").each(function() {
                $(this).hide();
            });

        } else {
            $('#levels-table-column-header').text("Cents");

            $(".td-template-edit-levels").find("input[name^='cents\[']").each(function() {
                $(this).show();
            });
            //$(".td-template-edit-levels").find("select[name^='threshold']").each(function() {
            $(".td-template-edit-levels").find(".select-span-wrapper").each(function() {
                $(this).hide();
            });
        }

    }

    function setupRaceTemplateSelect2() {
        $('#select-racetemplates-prize_type').on("select2:select", function (e) {
            prize_type = $(this).select2('val');
            updateForm();
        });

        $('#select-racetemplates-race_type').on("select2:select", function (e) {
            race_type = $(this).select2('val');
            updateForm();
        });
    }

     function enableSelect2Controllers() {

        setupRaceTemplateSelect2();

        var s2_attributes = ["racetemplates-race_type", "racetemplates-display_as", "racetemplates-prize_type", "racetemplates-game_categories", "racetemplates-recur_type"];
        for (let t of s2_attributes) {
            $(".select-"+t).select2({
                tags: true,
                selectOnBlur: true,
            });
        }

        $(".select-levels_threshold").select2({
            //tags: true,
            selectOnBlur: true,
        });
    }

    function createRaceTemplate(button, callback_on_failure = null, callback_on_success = null, disable_redirect_on_success = false) {
        var racetemplate_form = $('#racetemplate-form').serializeArray();

        if (button) {
            button.prop( "disabled", true);
        }

        $.ajax({
            url: "{{ $app['url_generator']->generate('racetemplates.new') }}",
            type: "POST",
            data: racetemplate_form,
            success: function (data, text_status, jqXHR) {
                if (data.success == true) {

                    var message = 'Race Template created successfully.';

                    if (!disable_redirect_on_success) {
                        message += ' Loading new Race Template...';
                    }

                    displayNotifyMessage('success', message);

                    if (callback_on_success && typeof(callback_on_success) === "function") {
                        callback_on_success(data);
                    }

                    if (!disable_redirect_on_success) {
                        setTimeout(function() {
                            var redirect_to = "{{ $app['url_generator']->generate('racetemplates.edit', ['racetemplate' => -1]) }}";
                            redirect_to = redirect_to.replace("-1", data.racetemplate.id);
                            window.location.replace(redirect_to);
                        }, 3000);
                    }
                } else {
                    console.log(data);

                    if (button) {
                        button.prop( "disabled", false);
                    }

                    if (callback_on_failure && typeof(callback_on_failure) === "function") {
                        callback_on_failure();
                    }

                    var attribute_errors = getAttributeErrors(data);
                    displayNotifyMessage('error', 'Race Template creation failed. '+attribute_errors+(data.error?data.error:''));
                }
            },
            error: function (jqXHR, text_status, error_thrown) {
                console.log(error_thrown);

                if (button) {
                    button.prop( "disabled", false);
                }

                if (callback_on_failure && typeof(callback_on_failure) === "function") {
                    callback_on_failure();
                }

                displayNotifyMessage('error', 'Race Template creation failed. Error: '+error_thrown);
            }
        });
    }

    $(document).ready(function() {

        $('#start_time_picker').datetimepicker({
            format: 'HH:mm',
            icons: {
                time: 'fa fa-clock',
                date: 'fa fa-calendar',
                up: 'fa fa-chevron-up',
                down: 'fa fa-chevron-down',

            }
        });

        $('#input-racetemplates-recurring_end_date').daterangepicker({
            singleDatePicker: true,
            timePicker: true,
            timePicker24Hour: true,
            timePickerSeconds: true,
            autoUpdateInput: true,
            locale: {
                format: 'YYYY-MM-DD HH:mm:ss'
            }
        });

        extractTemplates();
        initializeEditPrizes();
        setupAwardSelect2('select-racetemplate-award_id');
        setupAwardSelect2('select-racetemplate-award_id_alt');

        initializeEditLevels();

        updateForm();

        $('#delete-racetemplate-btn').on('click', function(e) {
            e.preventDefault();
        });

        $('.undo-edit-prize-btn').each(function() {
            $(this).hide();
        });
        $('.undo-edit-levels-btn').each(function() {
            $(this).hide();
        });

        if (race_type == "bigwin") {
            $(".td-template-edit-levels").find("input[name^='cents\[']").each(function() {
                $(this).hide();
            });
        } else {
            //$(".td-template-edit-levels").find("select[name^='threshold']").each(function() {
            $(".td-template-edit-levels").find(".select-span-wrapper").each(function() {
                $(this).hide();
            });
        }

        $('#delete-config-btn').on('click', function(e) {
            e.preventDefault();
        });

        $('.clone-edit-prize-btn').on('click', function(e) {
            e.preventDefault();

            var $tr       = $(this).closest('tr');
            var $tr_select = $tr.find('select');
            $tr_select.select2("destroy"); // Need to remove select2 before cloning...

            var $tr_clone = $tr.clone(true);

            $select = $tr_clone.find('select');
            // Remove empty option as it's otherwise selected instead of the previously selected.
            $select.find('option')
                .filter(function() {
                    return !this.value || $.trim(this.value).length == 0 || $.trim(this.text).length == 0;
                })
            .remove();

            bindSelect2($tr_select);
            bindSelect2($select);

            $tr.after($tr_clone);

            updateEditPrizes();
        });

        $('.clone-edit-levels-btn').on('click', function(e) {
            e.preventDefault();
            var $tr    = $(this).closest('tr');
            var $tr_select = $tr.find('select');
            $tr_select.select2("destroy"); // Need to remove select2 before cloning...

            var selected_val = $tr_select.val();

            var $tr_clone = $tr.clone(true);

            $select = $tr_clone.find('select');
            // Remove empty option as it's otherwise selected instead of the previously selected.
            $select.find('option')
                .filter(function() {
                    return !this.value || $.trim(this.value).length == 0 || $.trim(this.text).length == 0;
                })
            .remove();

            $select.val(selected_val);

            $tr_select.select2({
                //tags: true,
                selectOnBlur: true,
            });
            $select.select2({
                //tags: true,
                selectOnBlur: true,
            });

            $tr.after($tr_clone);

            updateEditLevels();
        });


        $('.clear-edit-prize-btn').on('click', function(e) {
            e.preventDefault();
            var $tr = $(this).closest('tr');
            $tr.find('input').each(function() {
                var val = $(this).val();
                $(this).data("old-value", val);
                $(this).val("");
            });
            $(this).hide();
            $tr.find('.undo-edit-prize-btn').each(function() {
                $(this).show();
            });
        });
        $('.clear-edit-levels-btn').on('click', function(e) {
            e.preventDefault();
            var $tr = $(this).closest('tr');
            $tr.find('input').each(function() {
                var val = $(this).val();
                $(this).data("old-value", val);
                $(this).val("");
            });
            $(this).hide();
            $tr.find('.undo-edit-levels-btn').each(function() {
                $(this).show();
            });
        });

        $('.undo-edit-prize-btn').on('click', function(e) {
            e.preventDefault();
            var $tr = $(this).closest('tr');
            $tr.find('input').each(function() {
                var val = $(this).data("old-value");
                $(this).val(val);
            });
            $(this).hide();
            $tr.find('.clear-edit-prize-btn').each(function() {
                $(this).show();
            });
        });
        $('.undo-edit-levels-btn').on('click', function(e) {
            e.preventDefault();
            var $tr = $(this).closest('tr');
            $tr.find('input').each(function() {
                var val = $(this).data("old-value");
                $(this).val(val);
            });
            $(this).hide();
            $tr.find('.clear-edit-levels-btn').each(function() {
                $(this).show();
            });
        });

        $('.remove-edit-prize-btn').on('click', function(e) {
            e.preventDefault();
            if (amount_prizes > 1) {
                var $tr = $(this).closest('tr');
                $tr.remove();
                updateEditPrizes();
            }
        });
        $('.remove-edit-levels-btn').on('click', function(e) {
            e.preventDefault();
            if (amount_levels > 1) {
                var $tr = $(this).closest('tr');
                $tr.remove();
                updateEditLevels();
            }
        });

        $('#apply-edit-prizes-modalbtn').on('click', function(e) {
            e.preventDefault();
            var prizes = "";
            var success = true;
            var last_end_position_value = 0;

            $('#edit-prizes-table > tbody > tr:visible').each(function() {
                var prize_value = null;
                var prize_alt_value = null;

                if (prize_type == "award") {
                    $select_prize = $(this).find("select[name^='prize\[']");
                    prize_value = $select_prize.select2('val');
                } else {
                    $input_prize = $(this).find("input[name^='prize\[']");
                    prize_value = $input_prize.val();
                }

                if (prize_value == null || prize_value.length <= 0) {
                    return false;
                }

                if (prize_type == "award") {
                    $select_prize_alt = $(this).find("select[name^='prize_alt\[']");
                    prize_alt_value = $select_prize_alt.select2('val');
                }

                $input_start_position = $(this).find("input[name^='start_position']");
                start_position_value = parseInt($input_start_position.val());

                $input_end_position = $(this).find("input[name^='end_position']");
                end_position_value = parseInt($input_end_position.val());

                if (start_position_value > end_position_value) {
                    $('#edit-prizes-error-info').text('Error: Start Position (' + start_position_value + ') is higher than End position (' + end_position_value + ').');
                    success = false;
                    return false;
                }

                if (start_position_value <= last_end_position_value) {
                    $('#edit-prizes-error-info').text('Error: Start Position (' + start_position_value + ') equals or is lower than previous End Position (' + last_end_position_value + ').');
                    success = false;
                    return false;
                }

                if (start_position_value != last_end_position_value+1) {
                    $('#edit-prizes-error-info').text('Error: Start Position (' + start_position_value + ') is not one more than previous End Position (' + last_end_position_value + ').');
                    success = false;
                    return false;
                }

                for (i = start_position_value; i <= end_position_value; i++) {
                    if (prize_alt_value != null && prize_alt_value.length > 0) {
                        prizes += prize_value + "," + prize_alt_value + ":";
                    } else {
                        prizes += prize_value + ":";
                    }
                }

                last_end_position_value = end_position_value;
            });

            if (success) {
                $('#edit-prizes-error-info').text("");

                prizes = prizes.slice(0, -1); // Remove last ":".
                $('#input-racetemplates-prizes').val(prizes);
                saveRaceTemplate($(this), function() {
                    $('[aria-labelledby="myModalEditPrizesLabel"]').modal('hide');
                });
            }

            return success;
        });


        $('#apply-edit-levels-modalbtn').on('click', function(e) {
            e.preventDefault();
            var levels = "";
            var success = true;

            $('#edit-levels-table > tbody > tr:visible').each(function() {
                var prize_value = null;
                var prize_alt_value = null;
                var threshold_cents_value = 0;

                if (race_type == "bigwin") {
                    var $input_threshold = $(this).find("select[name^='threshold']");
                    threshold_cents_value = $input_threshold.select2('val');
                } else {
                    var $input_cents = $(this).find("input[name^='cents']");
                    threshold_cents_value = parseInt($input_cents.val());
                }

                var $input_points = $(this).find("input[name^='points']");
                var points_value = parseInt($input_points.val());

                levels += threshold_cents_value + ":" + points_value + "|";
            });

            if (success) {
                $('#edit-levels-error-info').text("");

                levels = levels.slice(0, -1); // Remove last "|".
                $('#input-racetemplates-levels').val(levels);
            }

            return success;
        });
    });

</script>

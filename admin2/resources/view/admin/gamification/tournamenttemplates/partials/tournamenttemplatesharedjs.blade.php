
@include('admin.gamification.trophies.partials.sharedjs')
@include('admin.partials.js')

<script type="text/javascript">

    Dropzone.autoDiscover = false;

    function setupGameRefSelect2(classname, classname_id) {
        $('.'+classname).select2({
            ajax: {
                url: "{{ $app['url_generator']->generate('game.ajaxfilter') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: $.map(data, function(data) {
                            return { id: data.ext_game_name, text: data.ext_game_name, game_name: data.game_name };
                        }),
                        pagination: {
                            more: (params.page * 30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
            multiple: false
        });
        $('.'+classname).on("select2:select", function (e) {
            var index = $(this).data('index') !== undefined ? $(this).data('index') : "";
            $('#'+classname_id+index).text(e.params.data.game_name);
        });
        $('.'+classname).on("select2:unselect", function (e) {
            var index = $(this).data('index') !== undefined ? $(this).data('index') : "";
            $('#'+classname_id+index).text("");
        });
    }

    function setupAwardLadderTagSelect2(classname) {
        $('.'+classname).select2({
            ajax: {
                url: "{{ $app['url_generator']->generate('tournamenttemplates.filterAwardLadderTag') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: $.map(data, function(data) {
                            return { id: data.tag, text: data.tag };
                        }),
                        pagination: {
                            more: (params.page * 30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            multiple: false
        });

        $('.'+classname).on("change", function(e) {
            //console.log("change");
            //console.log(e);
        });
        $('.'+classname).on("select2:select", function(e) {
            //console.log("select");
            //console.log($(this).val());
            $.ajax({
                url: "{{ $app['url_generator']->generate('tournamenttemplates.ajaxAwardLadder') }}",
                type: "GET",
                data: {'tag': $(this).val()},
                success: function (data, textStatus, jqXHR) {
                    $('#tab_award_ladder').html(data);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(textStatus);
                    console.log(errorThrown);

                    displayNotifyMessage('error', textStatus);
                }
            });
        });
    }


    function enableSelect2Controllers() {

        setupGameRefSelect2('select-game_ref', 'span-game_ref_game_name');
        setupAwardLadderTagSelect2('select-award_ladder_tag');

        var s2_attributes = [ "category", "play_format", "prize_type", "start_format", "win_format", 'mtt_recur_type', 'ladder_tag'];
        for (let t of s2_attributes) {
            $(".select-"+t).select2({
                tags: true,
                selectOnBlur: true,
            });
        }
    }

    function updateTournamentTemplateImages(new_game_ref) {
        $('.img-thumbnail').each(function(i) {
            var name = $(this).attr('src');
            var split_names = name.split('/');

            split_names[split_names.length-1] = new_game_ref+".jpg";

            var new_name = split_names.join('/');
            $(this).attr('src', new_name+'?'+new Date().getTime()); // Add timestamp to force browser to update the resource and not use cached resource.
            $(this).attr('title', new_name.split('/').reverse()[0]);
        });
    }

    function enableDropZone() {
        var dropzone = new Dropzone('#tournament-template-image-upload', {
            init: function() {
                this.on("success", function(file) {
                    $('.img-thumbnail').each(function(i) {
                        var name = $(this).attr('src');
                        if(name.includes(file.name)) {
                            if (name.indexOf("?") > 0) {
                                name = name.substring(0, name.indexOf("?")); // Remove the appended timestamp added to trigger update by browser.
                            }
                            $(this).attr('src', name+'?'+new Date().getTime()); // Add timestamp to force browser to update the resource and not use cached resource.
                            $(this).attr('title', name.split('/').reverse()[0]);
                        }
                    });
                });
            },
            parallelUploads: 2,
            maxFilesize: 3,
            filesizeBase: 1000,
            headers : {
                "X-CSRF-TOKEN" : document.querySelector('meta[name="csrf_token"]').content
            }
        });
    }

    function createTournamentTemplate(button, callback_on_failure) {
        var trophy_form = $('#tournament-template-form').serializeArray();

        if (button) {
            button.prop( "disabled", true);
        }

        $.ajax({
            url: "{{ $app['url_generator']->generate('tournamenttemplates.new') }}",
            type: "POST",
            data: trophy_form,
            success: function (data, text_status, jqXHR) {
                if (data.success == true) {

                    displayNotifyMessage('success', 'Tournament Template created successfully. Loading new TournamentTemplate...');

                    setTimeout(function() {
                        var redirect_to = "{{ $app['url_generator']->generate('tournamenttemplates.edit', ['tournament_template' => -1]) }}";
                        redirect_to = redirect_to.replace("-1", data.tournament_template.id);
                        window.location.replace(redirect_to);
                    }, 3000);

                } else {
                    console.log(data);

                    if (button) {
                        button.prop( "disabled", false);
                    }

                    if (callback_on_failure && typeof(callback_on_failure) === "function") {
                        callback_on_failure();
                    }

                    var attribute_errors = getAttributeErrors(data);

                    displayNotifyMessage('error', 'Tournament Template creation failed. '+attribute_errors+(typeof(data.error) == 'string' ? '<br /><br />Error: '+data.error : ''));
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

                displayNotifyMessage('error', 'Tournament Template creation failed. Error: '+error_thrown);
            }
        });
    }

    // TODO: Possibly add this somewhere else so the same functionality can be used together with the one in TournamentTemplate Set creation.
    function enableFollowAlongScrolling() {
        var element = $('.follow-scroll'),
        originalY = element.offset().top;

        // Space between element and top of screen (when scrolling)
        var topMargin = 10;

        // Should probably be set in CSS; but here just for emphasis
        element.css('position', 'relative');

        $(window).on('scroll', function(event) {
            var scrollTop = $(window).scrollTop();

            element.stop(false, false).animate({
                top: scrollTop < originalY
                        ? 0
                        : scrollTop - originalY + topMargin
            }, 400);
        });
    }

    function doUpdatedGameRefUpdates() {
        var input_alias  = $("#input-game_ref");
        var new_game_ref = input_alias.val();

        updateTournamentTemplateImages(new_game_ref);

        updateDesktopOrMobile(new_game_ref);
    }

    function updateDesktopOrMobile(game_ref) {
        $.ajax({
            url: "{{ $app['url_generator']->generate('game.desktop_or_mobile') }}",
            type: "GET",
            data: {'game_ref': game_ref},
            success: function (data, textStatus, jqXHR) {
                $('#input-desktop_or_mobile').attr('value', data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus);
                console.log(errorThrown);

                displayNotifyMessage('error', textStatus);
            }
        });
    }

    function updateTournamentTemplateExample(button, callback_on_success) {
        var tournamenttemplate_form = $('#tournament-template-form').serializeArray();

        if (button) {
            button.prop("disabled", true);
        }

        $.ajax({
            url: "{{ $app['url_generator']->generate('tournamenttemplates.getexample') }}",
            type: "POST",
            data: tournamenttemplate_form,
            complete: function() {
                if (button) {
                    button.prop("disabled", false);
                }
            },
            success: function (data, text_status, jqXHR) {
                if (data.success == true) {

                    $.each(data.tt_example, function (index, value) {
                        $('#example-'+index).html(value);
                    });

                    if (callback_on_success && typeof(callback_on_success) === "function") {
                        callback_on_success();
                    }

                    displayNotifyMessage('success', 'Tournament Template Example updated successfully.');

                } else {
                    console.log(data);
                    displayNotifyMessage('error', 'Tournament Template Example updated failed.');
                }
            },
            error: function (jqXHR, text_status, error_thrown) {
                console.log(error_thrown);
                displayNotifyMessage('error', 'Tournament Template Example update failed. Error: '+error_thrown);
            }
        });
    }

    $(document).ready(function() {
        enableFollowAlongScrolling();

        $("#input-game_ref").keyup(function(e) {
            //var uniqueid = input.data('uniqueid');
            //var new_game_ref = e.target.value;
            doUpdatedGameRefUpdates();
        });

        $("#input-game_ref").change(function() {
            doUpdatedGameRefUpdates();
        });

        $('#update-example-btn').on('click', function(e) {
            e.preventDefault();
            updateTournamentTemplateExample(getAllNonModalButtons());
        });
    });

</script>

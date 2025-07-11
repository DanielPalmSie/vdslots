
@include('admin.gamification.trophies.partials.sharedjs')
@include('admin.partials.js')

<script type="text/javascript">

    Dropzone.autoDiscover = false;

    function setupAwardSelect2(classname, classname_id) {
        $('.'+classname).select2({
            ajax: {
                url: "{{ $app['url_generator']->generate('trophyawards.ajaxfilter') }}",
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
                            return { id: data.id, text: data.description, alias: data.alias };
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
            $('#'+classname_id+index).text(e.params.data.alias);
        });
        $('.'+classname).on("select2:unselect", function (e) {
            var index = $(this).data('index') !== undefined ? $(this).data('index') : "";
            $('#'+classname_id+index).text("");
        });
    }

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

    function updateTrophyImages(uniqueid, new_alias) {
        $('.alias_thumbnail_'+uniqueid).each(function(i) {
            var name = $(this).attr('src');
            var split_names = name.split('/');

            split_names[split_names.length-1] = new_alias+"_event.png";

            var new_name = split_names.join('/');
            $(this).attr('src', new_name+'?'+new Date().getTime()); // Add timestamp to force browser to update the resource and not use cached resource.
            $(this).attr('title', new_name.split('/').reverse()[0]);
        });
    }

    function enableSelect2Controllers() {

        setupAwardSelect2('select-trophyawards-award_id', 'span-trophyawards-award_id_alias');
        setupAwardSelect2('select-trophyawards-award_id_alt', 'span-trophyawards-award_id_alt_alias');
        setupGameRefSelect2('select-game_ref', 'span-game_ref_game_name');

        var s2_attributes = ["type", "subtype", "category", "sub_category", "time_span"];
        for (let t of s2_attributes) {
            $(".select-"+t).select2({
                tags: true,
                selectOnBlur: true,
            });
        }
    }

    function enableDropZone() {
        if ($('#trophies-csv-upload').length) {
            var dropzone_csv = new Dropzone('#trophies-csv-upload', {
                init: function() {
                    this.on("sending", function(file, xhr, formData){
                        //formData.append("id", "{{ $trophy->id }}");
                    });
                    this.on("success", function(file, response) {
                        console.log(response);
                        if (response.csv) {
                            var counter = 0;
                            response.csv.forEach(function(element_row) {
                                if (element_row[3].length < 1 || element_row[4].length < 1) { // TODO: Make this smarter.
                                    return;
                                }
                                var alias_element = $("[name='trophy["+counter+"][alias]']");
                                alias_element.val(element_row[0]);
                                updateOnAliasChange(alias_element);
                                counter++;
                            });
                        }
                    });
                },
                parallelUploads: 1,
                maxFilesize: 3,
                filesizeBase: 1000,
                headers : {
                    "X-CSRF-TOKEN" : document.querySelector('meta[name="csrf_token"]').content
                }
            });
        }

        if ($('#trophies-images-upload').length) {
            var dropzone_images = new Dropzone('#trophies-images-upload', {
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
                /*
                thumbnail: function(file, dataUrl) {
                if (file.previewElement) {
                    file.previewElement.classList.remove("dz-file-preview");
                    -ar images = file.previewElement.querySelectorAll("[data-dz-thumbnail]");
                    for (var i = 0; i < images.length; i++) {
                    var thumbnailElement = images[i];
                    thumbnailElement.alt = file.name;
                    thumbnailElement.src = dataUrl;
                    }
                    setTimeout(function() { file.previewElement.classList.add("dz-image-preview"); }, 1);
                }
                }
                */
            });
        }
    }

    function synchronizeLocalizedStringsWithAlias(alias) {
        var first      = $('#trophy-language-form input').first();
        var first_name = first.attr('name');
        var old_alias  = first_name.replace(/(.*)\[.+\]/g, "$1");

        $('#trophy-language-form input').each(function(i){
            var name      = $(this).attr('name');
            var new_alias = alias || $('#input-alias').val();
            var new_name  = name.replace(/.*(\[.+\])/g, new_alias+"$1");
            $(this).attr('name', new_name);

        });

        return old_alias;
    }

    function createTrophy(button, callback_on_failure) {
        var trophy_form = $('#trophy-form,#trophy-language-form').serializeArray();

        if (button) {
            button.prop("disabled", true);
        }

        $.ajax({
            url: "{{ $app['url_generator']->generate('trophies.new') }}",
            type: "POST",
            data: trophy_form,
            success: function (data, text_status, jqXHR) {
                if (data.success == true) {

                    displayNotifyMessage('success', 'Trophy created successfully. Loading new Trophy...');

                    setTimeout(function() {
                        var redirect_to = "{{ $app['url_generator']->generate('trophies.edit', ['trophy' => -1]) }}";
                        redirect_to = redirect_to.replace("-1", data.trophy.id);
                        window.location.replace(redirect_to);
                    }, 3000);

                } else {
                    console.log(data);

                    if (button) {
                        button.prop("disabled", false);
                    }

                    if (callback_on_failure && typeof(callback_on_failure) === "function") {
                        callback_on_failure();
                    }

                    var attribute_errors = getAttributeErrors(data);
                    displayNotifyMessage('error', 'Trophy creation failed. '+attribute_errors+(data.error?data.error:''));
                }
            },
            error: function (jqXHR, text_status, error_thrown) {
                console.log(error_thrown);

                if (button) {
                    button.prop("disabled", false);
                }

                if (callback_on_failure && typeof(callback_on_failure) === "function") {
                    callback_on_failure();
                }

                displayNotifyMessage('error', 'Trophy creation failed. Error: '+error_thrown);
            }
        });
    }

    $(document).ready(function() {
    });

</script>


@include('admin.gamification.trophies.partials.sharedjs')
@include('admin.partials.js')

<script type="text/javascript">

    Dropzone.autoDiscover = false;

    function setupAwardSelect2(classname, classname_id1, classname_id2) {
        $('.'+classname).select2({
            ajax: {
                url: "{{ $app['url_generator']->generate('bonustype.ajaxfilter') }}",
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
                            return { id: data.id, text: data.bonus_name+ ((data.bonus_code.length > 0) ? " [\""+data.bonus_code+"\"]" : ""), bonus_code: data.bonus_code, bonus_type: data.bonus_type };
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
            if (classname_id1 !== undefined) {
                $('#'+classname_id1+index).text(e.params.data.bonus_type || "[no bonus type]");
            }
            if (classname_id2 !== undefined) {
                $('#'+classname_id2+index).text(e.params.data.bonus_code || "[no bonus code]");
            }
        });
        $('.'+classname).on("select2:unselect", function (e) {
            var index = $(this).data('index') !== undefined ? $(this).data('index') : "";
            if (classname_id1 !== undefined) {
                $('#'+classname_id1+index).text("[no bonus type]");
            }
            if (classname_id2 !== undefined) {
                $('#'+classname_id2+index).text("[no bonus code]");
            }
        });
    }

    function updateTrophyAwardImage(uniqueid, new_alias) {
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

        setupAwardSelect2('select-trophyawards-award_id', 'span-trophyawards-bonus_id_bonus_type', 'span-trophyawards-bonus_id_bonus_code');

        var s2_attributes = ["trophyawards-type", "trophyawards-action"];
        for (let t of s2_attributes) {
            $(".select-"+t).select2({
                tags: true,
                selectOnBlur: true,
            });
        }
    }

    function enableDropZone() {
        var dropzone = new Dropzone('#trophyawards-images-upload', {
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

    function doUpdatedAliasUpdates() {
        var input_alias = $("#input-trophyawards-alias");
        var uniqueid    = input_alias.data('uniqueid');
        var new_alias   = input_alias.val();

        updateTrophyAwardImage(uniqueid, new_alias);
    }

    function createTrophyAward(button, callback_on_failure = null, callback_on_success = null, disable_redirect_on_success = false) {
        var trophyaward_form = $('#trophyaward-form').serializeArray();

        if (button) {
            button.prop( "disabled", true);
        }

        $.ajax({
            url: "{{ $app['url_generator']->generate('trophyawards.new') }}",
            type: "POST",
            data: trophyaward_form,
            success: function (data, text_status, jqXHR) {
                if (data.success == true) {

                    var message = 'Trophy Award created successfully.';

                    if (!disable_redirect_on_success) {
                        message += ' Loading new Trophy Award...';
                    }

                    displayNotifyMessage('success', message);

                    if (callback_on_success && typeof(callback_on_success) === "function") {
                        callback_on_success(data);
                    }

                    if (!disable_redirect_on_success) {
                        setTimeout(function() {
                            var redirect_to = "{{ $app['url_generator']->generate('trophyawards.edit', ['trophyaward' => -1]) }}";
                            redirect_to = redirect_to.replace("-1", data.trophyaward.id);
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
                    displayNotifyMessage('error', 'Trophy Award creation failed. '+attribute_errors+(data.error?data.error:''));
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

                displayNotifyMessage('error', 'Trophy Award creation failed. Error: '+error_thrown);
            }
        });
    }

    $(document).ready(function() {
        $("#input-trophyawards-alias").keyup(function(e) {
            //var uniqueid = input.data('uniqueid');
            //var new_alias = e.target.value;
            doUpdatedAliasUpdates();
        });

        $("#input-trophyawards-alias").change(function() {
            doUpdatedAliasUpdates();
        });
    });

</script>

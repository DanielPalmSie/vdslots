
@include('admin.gamification.trophies.partials.sharedjs')
@include('admin.partials.js')

<script type="text/javascript">



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

    function enableSelect2Controllers() {
        var s2_attributes = [ 'race_type'];
        for (let t of s2_attributes) {
            $(".select-"+t).select2({
                tags: true,
                selectOnBlur: true,
            });
        }
    }

    function createBonusType(button, callback_on_failure = null, callback_on_success = null, disable_redirect_on_success = false) {
        var bonustype_form = $('#bonustype-form').serializeArray();
        //console.log(bonustype_form);

        if (button) {
            button.prop("disabled", true);
        }

        $.ajax({
            url: "{{ $app['url_generator']->generate('bonustypes.new') }}",
            type: "POST",
            data: bonustype_form,
            success: function (data, text_status, jqXHR) {
                if (data.success == true) {

                    var message = 'Bonus Type created successfully.';

                    if (!disable_redirect_on_success) {
                        message += ' Loading new Bonus Type...';
                    }

                    displayNotifyMessage('success', message);

                    if (callback_on_success && typeof(callback_on_success) === "function") {
                        callback_on_success(data);
                    }

                    if (!disable_redirect_on_success) {
                        setTimeout(function() {
                            var redirect_to = "{{ $app['url_generator']->generate('bonustypes.edit', ['bonustype' => -1]) }}";
                            redirect_to = redirect_to.replace("-1", data.bonustype.id);
                            window.location.replace(redirect_to);
                        }, 3000);
                    }

                } else {
                    console.log(data);

                    if (button) {
                        button.prop("disabled", false);
                    }

                    if (callback_on_failure && typeof(callback_on_failure) === "function") {
                        callback_on_failure();
                    }


                    var attribute_errors = getAttributeErrors(data);
                    displayNotifyMessage('error', 'Bonus Type creation failed. '+attribute_errors+(data.error?data.error:''));
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

                displayNotifyMessage('error', 'Bonus Type creation failed. Error: '+error_thrown);
            }
        });
    }

    function addNetworkListeners() {
        $('#bonustype-form').on('change', '[name="bonus_tag"]', function() {
            var network = $(this).val();
            switch (network) {
                case 'evolution':
                case 'microgaming':
                    $('#input-ext_ids_override').closest('.col-sm-4').show();
                    break;
                default:
                    $('#input-ext_ids_override').closest('.col-sm-4').hide();
                    break;
            }
        })

        $('#bonustype-form [name="bonus_tag"]').trigger('change');
    }

    var extIds;
    function initializeEditExtIds() {
        var entries, entry, country, bonusId;
        var allowedBonusTags = ['microgaming', 'evolution'];
        if (!allowedBonusTags.includes($('#input-bonus_tag').val())) {
            return;
        }
        extIds = $('#input-ext_ids_override').val();

        if (extIds === '' || extIds == null) {
            return;
        }
        var entries = extIds.split('|');

        for (var i = 0; i<entries.length; i++) {
            entry = entries[i].split(':');
            if (i !== 0) {
                modal.fill(modal.clone(), entry);
            } else {
                modal.fill(modal.template, entry);
            }
        }
    }

    var modal = (function() {
        return {
            el: $('#edit-extids-table'),
            template: null,
            init: function() {
              this.reloadTemplate();
              this.el.on('click', '.clone-extid-entry-btn', function(e) {
                 var clon = this.clone();
                 this.empty(clon);
              }.bind(this));
              this.el.on('click', '.remove-extid-entry-btn', function(e) {
                  var n = $('#edit-extids-table tbody tr').length;
                  var target = e.currentTarget.closest('tr');
                  if (n > 1) {
                    target.remove();
                    this.reloadTemplate();
                  }else {
                      this.empty($(target));
                  }
              }.bind(this));
              $('#apply-extids-modalbtn').on('click', function() {
                  this.save();
              }.bind(this));
            },
            reloadTemplate: function() {
                this.template = $('#edit-extids-table tbody').find('tr:eq(0)');
            },
            clone: function () {
                var clon = this.template.clone();
                this.template.before(clon);
                return clon;
            },
            fill(target, info) {
                var countryInput = target.find('.select-extid-countries');
                countryInput.val(info[0]);
                countryInput.trigger('change');
                target.find('[name="bonuses[]"]').val(info[1]);
            },
            empty(target) {
                var countryInput = target.find('.select-extid-countries');
                countryInput.val('');
                countryInput.trigger('change');
                target.find('[name="bonuses[]"]').val('');
            },
            getValues(target) {
                return [$.trim(target.find('.select-extid-countries').val()), $.trim(target.find('[name="bonuses[]"]').val())];
            },
            save() {
                var values = '';
                var elementValues = [];
                $('#edit-extids-table > tbody > tr:visible').each(function(index, element) {
                    elementValues = this.getValues($(element));
                    if (elementValues[0] !== '' && elementValues[1] !== '') {
                        if (index > 0) {
                            values += '|';
                        }
                        values += elementValues[0] + ':' + elementValues[1];
                    }
                }.bind(this));
                $('#input-ext_ids_override').val(values);
            }
        };
    })()
    modal.init();

    $(document).ready(function() {
        enableSelect2Controllers();
        addNetworkListeners();
        initializeEditExtIds();
    });

</script>

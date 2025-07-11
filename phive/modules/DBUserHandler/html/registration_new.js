var regPrePops = {};

function setupRegistration()
{

    $('.top-play-bar').remove();

    $('#birthyear-cont').change(function() { validateMulti(Array('#birthyear','#birthmonth','#birthdate'),"#birth_error", '#birthyear'); });
    $('#birthyear').change(function() {
        validateMulti(Array('#birthyear','#birthmonth','#birthdate'),"#birth_error", '#birthyear');
        syncBirthDate($('#birthdate'), $('#birthmonth').val(), $('#birthyear').val());
    });
    $('#birthmonth').change(function() {
        validateMulti(Array('#birthyear','#birthmonth','#birthdate'),"#birth_error", '#birthmonth');
        syncBirthDate($('#birthdate'), $('#birthmonth').val(), $('#birthyear').val());
    });
    $('#birthdate').change(function() {	validateMulti(Array('#birthyear','#birthmonth','#birthdate'),"#birth_error", '#birthdate'); });
    $('#country').change(function() { validateSingle(this,Array('#country')); });
    $('#idtype').change(function() { validateSingle(this,Array('#idtype')); });
    $('#currency').change(function() { validateSingle(this,Array('#currency')); });
    $('#preferred_lang').change(function() { validateSingle(this,Array('#preferred_lang')); });
    $('#residence_country').change(function() { validateSingle(this,Array('#residence_country')); });
    $('#fiscal_region').change(function() { validateSingle(this,Array('#fiscal_region')); });
    $('#nationality').change(function() { validateSingle(this,Array('#nationality')); });
    $('#doc_issued_by').change(function() { validateSingle(this,Array('#doc_issued_by')); });
    $('#doc_type').change(function() { validateSingle(this,Array('#doc_type')); });
    $('#doc_year').change(function() { validateSingle(this,Array('#doc_year')); });
    $('#doc_month').change(function() { validateSingle(this,Array('#doc_month')); });
    $('#doc_date').change(function() { validateSingle(this,Array('#doc_date')); });

    $('#personal_number').blur(function () {
        var self = $(this);

        if (licFuncs.validatePersonalNumberOnRegister(self.val())) {
            self.removeClass('error');
            self.addClass('valid');
        } else {
            self.removeClass('valid');
            self.addClass('error');
            showInfoMessage(this);
        }
    });

    $('#email_code').keyup(function() {
        empty($(this).val()) ? addClassError(this) : addClassValid(this) ;
    });

    $('#password, #personal_number').focus(function() {
        showInfoMessage(this);
    });


    $('#password, #email, #secemail, #secpassword, #firstname, #lastname, #address, #building, #occupation, #zipcode, #city, #birth_country, #email_code, #other_occupation').blur(function() {        if(empty($(this).val())) {
            addClassError(this);
        }
    });

    $('#country, #preferred_lang, #birthyear, #birthdate, #birthmonth, #currency, .select-validation, #residence_country, #fiscal_region, #nationality, #doc_year, #doc_month, #doc_day').on('change', function() {
        if(empty($(this).val())) {
            addClassError(this);
            $(this).parent().removeClass('styled-select-valid');
            $(this).parent().addClass('styled-select-error');
        } else {
            addClassValid(this);
            $(this).parent().removeClass('styled-select-error');
            $(this).parent().addClass('styled-select-valid');
        }
    });

    $('#validation_step1 :input[type="text"]').keyup(function() {
        validate('#validation_step1',this);
    });

    $('#validation_step1 :input[type="password"]').keyup(function() {
        validate('#validation_step1',this);
    });

    $('#validation_step1 :input[type="checkbox"]').click(function() {
        validate('#validation_step1',this);
    });

    const dependentFields = {
        'address':  {
            'field': 'building',
            'counter_field': 'building'
        },
        'building': {
            'field': 'address',
            'counter_field': 'building'
        },
        'occupation' : {
            'field': 'industry',
            'counter_field': 'occupation'
        },
        'industry': {
            'field': 'occupation',
            'counter_field': 'occupation'
        }
    }

    function validateDependentField(object) {
        const activeField = $(object).attr("name");
        const maxlen = 50;

        const data = dependentFields[activeField];
        if (data === undefined) {
            return;
        }

        const dependentField = data['field'];
        const counter_field = data['counter_field'];

        $(`.field-${counter_field}`).css("position", "relative");
        $(`#${counter_field}_count`).addClass('form-validation-counter')
            .removeClass('form-validation-counter-active')
            .removeClass('form-validation-counter-failure');

        let activeFieldStr = $(`input[name=${activeField}]`).val() === undefined ?
            $(`select[name=${activeField}]`).val() : $(`input[name=${activeField}]`).val();

        let dependentFieldStr = $(`input[name=${dependentField}]`).val() === undefined ?
            $(`select[name=${dependentField}]`).val() : $(`input[name=${dependentField}]`).val();

        if (activeFieldStr === undefined || dependentFieldStr === undefined) {
            return;
        }

        let remainingCount = maxlen-activeFieldStr.length-dependentFieldStr.length;

        if (remainingCount >= 0) {
            $(`#${counter_field}_count`).addClass('form-validation-counter-active');
        }else{
            $(`#${counter_field}_count`).addClass('form-validation-counter-failure');
        }

        $(`.field-${counter_field} #${counter_field}_count`).html(remainingCount);

    }

    $('#validation_step2 :input[type="text"]').keyup(function() {
        validateDependentField(this);
        validate('#validation_step2',this);
    });

    $('#validation_step2 :input[type="checkbox"]').click(function() {
        validate('#validation_step2',this);

        let $current_ele = $(this).attr('id');

    });

    $('#validation_step2 select').change(function() {
        validateDependentField(this);
    });

    $.each(regPrePops, function(key, value){
        $("#"+key).val(value);
    });

    $('#bonus_code_text').click(function() {
        const bonusCodeField = $('.field-bonus_code');
        $('#bonus_code').show();
        $('#bonus_code').parent().parent().show(); // harmless on old version, used on refactored registration
        $('#bonus_code_text').hide();
        detectIOS15AndAddClass(bonusCodeField);
    });

    $('#change_email_mobile').click(function() {
        goBackToStep1();
    });
    lic('validateRegistrationStep2');
    setupResizeHandlers();
}

function syncBirthDate(dayField, month, year) {
    if (year === '') {
        year = new Date().getFullYear();
    }

    var lastDayOfMonth = 31;
    if (month !== '') {
        lastDayOfMonth = new Date(year, parseInt(month), 0).getDate();
    }

    if (parseInt(dayField.val()) > lastDayOfMonth) {
        dayField.val('');
    }

    var dayFieldOptions = dayField.find('option:not([selected="selected"])');
    dayFieldOptions.each(function () {
        if (parseInt($(this).val()) > lastDayOfMonth) {
            if (!($(this).parent().is('span'))) {
                $(this).wrap('<span></span>');
            }
            $(this).addClass('hidden');
        } else {
            if (($(this).parent().is('span'))) {
                $(this).unwrap();
            }
            $(this).removeClass('hidden');
        }
    });
}

function validateStep1(){
    validateArray('#validation_step1', $('#validation_step1 :input'));

    doPostCheckForMobile('#validation_step1', '#mobile', 'check_mobile', '', '#country_prefix');

    valid = validateSingle($('#country'), Array('#country'));

    resizeRegistrationbox(1); // we resize disordered popup.
    return $('#validation_step1').valid() && valid;
}

function submitStep1(additional_fields){
    return handleRegistrationStep1(additional_fields);
}

function goBackToStep1() {
    var formdata = {
        "personal_number":  $('#personal_number').val(),
        "password":         $('#password').val(),
        "firstname":        $('#firstname').val(),
        "lastname":         $('#lastname').val(),
        "email":            $('#email').val(),
        "address":          $('#address').val(),
        "zipcode":          $('#zipcode').val(),
        "city":             $('#city').val(),
        "mobile":           $('#mobile').val(),
        "country_prefix":   $('#country_prefix').val(),
        "country":          $('#country').val(),
        "birth_country":    $('#birth_country').val(),
        "newsletter":       ($('#newsletter').is(':checked')) ? 1 : 0,
        "over18":           ($('#eighteen').is(':checked')) ? 1 : 0,
        "bonus_code":       $('#bonus_code').val(),
        "sex":              ($('#male').is(':checked')) ? "Male" : "Female",
        "preferred_lang":   $('#preferred_lang').val(),
        "birthyear":        $('#birthyear').val(),
        "birthmonth":       $('#birthmonth').val(),
        "birthdate":        $('#birthdate').val(),
        "email_code":       $('#email_code').val(),
        "currency":         $('#currency').val(),
        "lastname_second":  $('#lastname_second').val(),
        "fiscal_region":    $('#fiscal_region').val()
    };

    // put form values in session  (this is an async call, which is why we have to put goTo in the callback)
    mgAjax({action: "save-step-2-in-session", data: formdata}, function(){
        if(typeof mobileStep != 'undefined') {
            goTo(llink('/mobile/register/'));
        } else {
            goTo(llink(registration_step1_url));
        }
    });
}

function submitStep2() {
    return handleRegistrationStep2();
}

function validateSingle(obj,arr)
{
    if ($(obj).length === 0 || $(obj).is(':disabled')) {
        return true;
    }

    if( (valid = checkDropDown(arr))) {
        addClassValid(obj);
        $(obj).parent().removeClass('styled-select');
        $(obj).parent().removeClass('styled-select-error');
        if (obj[0].tagName === 'SELECT') {
            $(obj).parent().addClass('styled-select-valid');
        }
    }
    else {
        addClassError(obj);
        $(obj).parent().removeClass('styled-select');
        $(obj).parent().removeClass('styled-select-valid');
        if (obj[0].tagName === 'SELECT') {
            $(obj).parent().addClass('styled-select-error');
        }
    }

    return valid;
}

function validateMulti(arr, error, element)
{
    if( valid = checkDropDown(arr)) {
        $(error).removeClass('icon_fail');
        $(error).addClass('icon_ok');
    }
    else{
        $(error).removeClass('icon_ok');
        $(error).addClass('icon_fail');
    }

    if(empty($(element).val())) {
        $(element).removeClass('valid');
        $(element).addClass('error');
        showInfoMessage(element);
        $(element).parent().removeClass('styled-select');
        $(element).parent().removeClass('styled-select-valid');
        $(element).parent().addClass('styled-select-error');
    } else {
        $(element).removeClass('error');
        $(element).addClass('valid');
        $(element).parent().removeClass('styled-select');
        $(element).parent().removeClass('styled-select-error');
        $(element).parent().addClass('styled-select-valid');
    }
    return valid;
}

function checkDropDown(arr) {
    $('#info').hide();

    for (let i = 0; i < arr.length; i++) {
        const value = $(arr[i]).val();
        if (empty(value)) {
            return false;
        }
    }

    return true;
}

function validateAllDropDowns() {
    var rules = [
        validateSingle($('#country'), Array('#country')),
        validateSingle($('#preferred_lang'), Array('#preferred_lang')),
        validateSingle($('#currency'), Array('#currency')),
        validateMulti(Array('#birthyear', '#birthmonth', '#birthdate'), "#birth_error", '#birthyear'),
        validateMulti(Array('#birthyear', '#birthmonth', '#birthdate'), "#birth_error", '#birthmonth'),
        validateMulti(Array('#birthyear', '#birthmonth', '#birthdate'), "#birth_error", '#birthdate'),
        validateSingle($('#residence_country'), Array('#residence_country')),
        validateSingle($('#fiscal_region'), Array('#fiscal_region')),
        validateSingle($('#nationality'), Array('#nationality')),
        validateSingle($('#birth_country'), Array('#birth_country')),
        validateSingle($('#doc_issued_by'), Array('#doc_issued_by')),
        validateSingle($('#doc_type'), Array('#doc_type')),
        validateSingle($('#doc_year'), Array('#doc_year')),
        validateSingle($('#doc_month'), Array('#doc_month')),
        validateSingle($('#doc_date'), Array('#doc_date')),
        validateSingle($('#main_province'), Array('#main_province')),
        validateSingle($('#industry'), Array('#industry')),
        validateSingle($('#birth_province'), Array('#birth_province')),
        validateSingle($('#birth_city'), Array('#birth_city')),
        validateSingle($('#main_country'), Array('#main_country')),
        validateSingle($('#main_city'), Array('#main_city')),
        validateSingle($('#cap'), Array('#cap')),
        validateSingle($('#fiscal_code'), Array('#fiscal_code')),
        validateSingle($('#main_address'), Array('#main_address')),
    ];

    for (var i = 0; i < rules.length; i++) {
        if (!rules[i]) {
            return false;
        }
    }
    return true;
}

function validateArray(form,objects)
{
    for(var i = 0; i < objects.length; i++) {
        validate(form,objects[i]);
        showInfoMessage(objects[i]);
    }
}

function addIconOk(object)
{
  $("#"+$(object).attr("id") + "_error").removeClass('icon_fail');
  $("#"+$(object).attr("id") + "_error").addClass('icon_ok');
}

function addIconFail(object)
{
  $("#"+$(object).attr("id") + "_error").removeClass('icon_ok');
  $("#"+$(object).attr("id") + "_error").addClass('icon_fail');
}

function addClassValid(object)
{
    if ($(object).attr('id') === "password") {
        $(".password-eye-icon").each(function() {
            const newUrl = $(this).attr("src").replace("eye-error-", "eye-");;
            $(this).attr("src", newUrl);
        })
    };
    $("#"+$(object).attr("id")).removeClass('error');
    $("#"+$(object).attr("id")).addClass('valid');
}

function addClassError(object, resize=true)
{
    if ($(object).attr('id') === "password") {
        $(".password-eye-icon").each(function() {
            let newUrl = $(this).attr("src");
            if(!newUrl.includes("error")) {
                newUrl = newUrl.replace("eye-", "eye-error-");
            };
            $(this).attr("src", newUrl);
        })
    };
    $("#"+$(object).attr("id")).removeClass('valid');
    $("#"+$(object).attr("id")).addClass('error');
    showInfoMessage(object, resize);
}

function addClassStyledSelectValid(element) {
    element.parent().removeClass('styled-select');
    element.parent().removeClass('styled-select-error');
    element.parent().addClass('styled-select-valid');
}

function addClassStyledSelectError(element) {
    element.parent().removeClass('styled-select');
    element.parent().removeClass('styled-select-valid');
    element.parent().addClass('styled-select-error');
}

function validate(form,object)
{
    $('#info').hide();

    try {
        if($(form).validate().element($(object))) {
            addIconOk(object);
            // add class to sibling if type = checkbox
            if($(object).is(':checkbox')) {

                $(object).siblings('#privacy-span').removeClass('error');
                $(object).siblings('#terms-span').removeClass('error');
                $(object).siblings('#bonus-terms-span').removeClass('error');
                $(object).siblings('#gambling-span').removeClass('error');
                $(object).siblings('#eighteen-span').removeClass('error');
                $(object).siblings('#honest_player-span').removeClass('error');
                $(object).siblings('#aml-span').removeClass('error');
                $(object).siblings('#pep_check-span').removeClass('error');
                $(object).siblings('#legal_age-span').removeClass('error');
            }
        }
        else {
            addIconFail(object);
            if($(object).is(':checkbox')) {
                $(object).siblings('#privacy-span').addClass('error');
                $(object).siblings('#terms-span').addClass('error');
                $(object).siblings('#bonus-terms-span').addClass('error');
                $(object).siblings('#gambling-span').addClass('error');
                $(object).siblings('#eighteen-span').addClass('error');
                $(object).siblings('#honest_player-span').addClass('error');
                $(object).siblings('#aml-span').addClass('error');
                $(object).siblings('#pep_check-span').addClass('error');
                $(object).siblings('#legal_age-span').addClass('error');
            }
        }
    }
    catch(err){ }
}

function resizeRegistrationAfterInfoMessageUpdated() {
    if (typeof mobileStep !== 'undefined' && !!mobileStep) {
        return
    }
    resizeRegistrationbox($("#step2").length > 0 ? 2 : 1)
}
function showInfoMessage(object, resize=true) {
    var inputId = $(object).attr("id"); // Get the ID of the input element
    var messageDivId = inputId + '_msg'; // Construct the ID of the message div

    // Use the constructed ID to select the message div
    var messageDiv = $("#" + messageDivId);

    // Check if the input is not valid, the message div is hidden, and it contains text
    if (!$(object).hasClass('valid') && messageDiv.is(":hidden") && !!messageDiv.text()) {
        messageDiv.show(); // Show the message div
        if (resize) {
            resizeRegistrationAfterInfoMessageUpdated(); // Optionally resize the registration form
        }
    }
}

function hideInfoMessage(object) {
    var item = $("#" + $(object).attr("id") + '_msg');
    if (item.is(":visible")) {
        item.hide();
        resizeRegistrationAfterInfoMessageUpdated()
    }
}

/**
 * Note: when adding a new required field, add attribute name=minlen to that field
 */
function addFormForValidation(element){
  $(element).validate({
    rules: {
      firstname: {
          minlen: true,
          maxlen50: true,
          required: true,
          notEmoji: true,
          customFormat: true,
      },
      lastname: {
         minlen: true,
         maxlen50:true,
         required: true,
         notEmoji: true,
         customFormat: true,
      },
      address: {
          totalCheck: ['building'],
          minlen: true,
          maxlen100: true,
          notEmoji: true,
          required: true,
          customAddressFormat: true,
      },
      building: {
        totalCheck: ['address'],
        minlen: true,
        notEmoji: true,
      },
      zipcode: {
        minlen: true,
        notEmoji: true,
        customZipcodeFormat: true,
      },
      zipcode_min_max: {
        minlen3: true,
        maxlen20: true,
        notEmoji: true,
        required: true,
        customZipcodeFormat: true,
      },
      city: {
        minlen: true,
        maxlen50: true,
        notEmoji: true,
        required: true,
        customFormat: true,
      },
      place_of_birth: {
        minlen: true,
        maxlen50: true,
        required: true,
        customFormat: true,
      },
      industry: {
        totalCheck: ['occupation'],
        minlen: true,
        notEmoji: true,
      },
      occupation: {
        totalCheck: ['industry'],
        minlen: true,
        notEmoji: true,
      },
      minlen: "required minlen notEmoji",
      number: "required number",
      white : "required white notEmoji",
      email : "required email notEmoji",
      digits: "required digits notEmoji",
      mobile: "required number",
      confirm_password: {
        password: true,
        notEmoji: true,
        noWhitespace: true,
      },
      secpassword: {
        required: true,
        minlength: 8,
        noWhitespace: true
      },
      check : "required check",
      legal_age_check : "required check",
      aml_check : "required check",
      pep_check : "required check",
      conditions_check: "required check",
      privacy_check: "required check",
      iban: {
        iban: true,
        required: true,
        notEmoji: true,
      }
    },
    highlight: function(element) {
        addClassError(element);
        showInfoMessage(element);
    },
    unhighlight: function(element) {
        addClassValid(element);
        hideInfoMessage(element);
    },
    errorPlacement: function(error, element) {}
  });
}

function initialValidationPreCheck (form) {
    const whiteListIds = ['legal_age', 'aml', 'pep_check']

    const objects = $('#validation_step2 :input:not([disabled])').filter(function () {
        return !!this.value && !whiteListIds.includes(this.id)
    })

    validateArray(form, objects);
}

function addPostCheck(form, element, bindEvent, post, error, tooManyAttemptsError, emailAlreadyRegisteredMessage) {
    let emailValidationMessage = '';
    // just to avoid JS errors
    if(typeof tooManyAttemptsError == undefined) {
        tooManyAttemptsError = '';
    }
    if(typeof emailAlreadyRegisteredMessage == undefined) {
        emailAlreadyRegisteredMessage = '';
    }
    
    $(element).blur(function() {
        // check if value is not empty
        if(!empty($(element).val())) {
            if($(form).validate().element($(this))){
                mgAjax({ action: post, attr: $(element).val() }, function(ret) {
                    const elemId = $(element).attr('id');
                    const standardMsgSelector = '#' + elemId + '_msg';
                    const emailTakenMsgSelector = '#email_taken_msg';
                    
                    if(ret == 'toomanyattempts'){
                        $(element).val("");
                        $(element).val(tooManyAttemptsError);
                        $(element).one(bindEvent, function() { $(element).val(""); });
                        // add error class to element
                        $(element).removeClass('valid');
                        $(element).addClass('error');
                    } else if(ret == 'taken'){
                        if (emailValidationMessage === '') {
                            emailValidationMessage = $(standardMsgSelector).text();
                        }
                        
                        $(element).val("");
                        validate(form, $(element));
                        
                        if (elemId === 'email') {
                            $(standardMsgSelector).hide();
                            $(emailTakenMsgSelector).text(emailAlreadyRegisteredMessage).show();
                        }
                        
                        $(element).val(error);
                        $(element).one(bindEvent, function() { 
                            $(element).val(""); 
                            
                            if (elemId === 'email') {
                                $(emailTakenMsgSelector).hide();
                                if (emailValidationMessage !== '') {
                                    $(standardMsgSelector).text(emailValidationMessage).show();
                                }
                            }
                        });
                        
                        $(element).removeClass('valid');
                        $(element).addClass('error');
                    } else {
                        if (elemId === 'email') {
                            $(emailTakenMsgSelector).hide();
                        }
                        $(element).off(bindEvent);
                    }
                });
            }
        }
    });
}

function addPostCheckForMobile(form,element,bindEvent,post,error,prefix,tooManyAttemptsError){
  // just to avoid JS errors
  if(typeof tooManyAttemptsError == undefined) {
    tooManyAttemptsError = '';
  }
  $(element).blur(function() {
    if($(form).validate().element( $(this) )){
      mgAjax({ action: post, attr: $(prefix).val() + $(element).val() }, function(ret) {
        if(ret == 'toomanyattempts'){
          $(element).val("");
          $(element).val(tooManyAttemptsError);
          $(element).on(bindEvent,function() { $(element).val(""); });
          // add error class to element
          $(element).removeClass('valid');
          $(element).addClass('error');
        } else if(ret == 'taken'){
          $(element).val("");
          validate(form,$(element));
          $(element).val(error);
          $(element).on(bindEvent,function() { $(element).val(""); });
          // add error class to element
          $(element).removeClass('valid');
          $(element).addClass('error');
          showInfoMessage(element);
        }else
          $(element).off(bindEvent);
      });
    }
  });
}

function doPostCheckForMobile(form, element, post, error, prefix) {
    mgAjax({ action: post, attr: $(prefix).val() + $(element).val() }, function(ret) {
        if(ret == 'taken'){
            $(element).val("");
            validate(form,$(element));
            //$(element).val(error);
            // add error class to element
            $(element).removeClass('valid');
            $(element).addClass('error');
        }
    });
}

function doubleCheckStrings(form,e1,e2,err_val){
  $(e1).blur(function() {
    if($(e1).val() !=  $(e2).val()){
      var tmp = $(e1).val();
      $(e1).val(err_val);
      validate(form,$(e1));
      $(e1).on("focus.doubleCheckStrings",function() { $(e1).val(tmp); });
    }
    else
      $(e1).off("focus.doubleCheckStrings");
  });
}

$.validator.addMethod('white', function (value) { if(value.length <= 1) return false; return !/\s/.test(value); },'');
$.validator.addMethod('check', function (value,element) { if(element.checked) return true; },'');
$.validator.addMethod('minlen', function (value,element) { if(value.length < 1) return false; return true },'');
$.validator.addMethod('minlen3', function (value,element) { if(value.length < 3) return false; return true },'');
$.validator.addMethod('hasletter', function (value,element){ return /[a-zA-Z]+/.test(value); }, '');
$.validator.addMethod('mobileLength', function (value, element) {
    return licFuncs.validateMobileLength(value)
}, '');

$.validator.addMethod('hasUpper', function (value,element){ return /[A-Z]+/.test(value); }, '');
$.validator.addMethod('hasLower', function (value,element){ return /[a-z]+/.test(value); }, '');
$.validator.addMethod('hasTwoDigits', function (value,element){
    var matches = value.match(/\d/g);
    var cnt = matches != null ? matches.length : 0;
    return cnt < 2 ? false : true;
}, '');
$.validator.addMethod('noWhitespace', function(value) {
    return !/\s/.test(value);
}, '');
$.validator.addMethod('maxlen50', function (value,element) { if(value.length > 50) return false; return true },'');
$.validator.addMethod('maxlen10', function (value,element) { if(value.length > 10) return false; return true },'');
$.validator.addMethod('maxlen20', function (value,element) { if(value.length > 20) return false; return true },'');
$.validator.addMethod('maxlen100', function (value,element) { if(value.length > 100) return false; return true },'');
$.validator.addMethod('totalCheck', function(value, element, params) {
    const field_1 = $('input[name="' + params[0] + '"]').val() ?? $('select[name="' + params[0] + '"]').val() ?? "";
    let totalstr = (value+field_1).trim();
    return (totalstr.length > 0 && totalstr.length <= 50);
}, '');

$.validator.addMethod('notEmoji', function(value, element) {
    return !hasEmoji(value);
}, 'Emojis are not allowed');

$.validator.addMethod("customFormat", function(value, element) {
    return /^[a-zA-Z\u00C0-\u017F'\-\s]{1,50}$/.test(value);
}, "Please enter a valid name (letters, accented characters, apostrophes, hyphens, spaces only)");


$.validator.addMethod("customAddressFormat", function(value) {
    return  /^[a-zA-Z0-9_\u00C0-\u00FF\u0100-\u017F,.\-'&/#\s:åäöÅÄÖ]{3,50}$/u.test(value);
}, "");

$.validator.addMethod("customZipcodeFormat", function(value) {
    const province = $('#main_province').val();
    const isOntario = (cur_country === 'CA' && province === 'ON');
    if (isOntario) {
        return /^[A-Z][0-9][A-Z][ ]?[0-9][A-Z][0-9]$/i.test(value.toUpperCase());
    } else {
        return /^[A-Za-z0-9][A-Za-z0-9\- ]{2,19}$/.test(value);
    }
}, '');

function hasEmoji(value) {
    if(empty(value)) return false;
    const res = value.match(/(?![*#0-9]+)[\p{Emoji}\p{Emoji_Modifier}\p{Emoji_Component}\p{Emoji_Modifier_Base}\p{Emoji_Presentation}]/gu);
    return res?.length > 0;
}

/**
 * Resizes the registration box container to ensure appropriate dimensions for the form.
 * - Step 1: Adds 10px to maxHeight to prevent unnecessary scrollbars when all content is visible
 * - Step 1:  Applies scroll container for 720px displays to ensure complete scroll control accessibility and full content navigation.
 *   (Browser rendering inconsistencies sometimes require this small buffer)
 * - Special case for 768px height. Adjusts positioning for optimal viewing
 *
 * @param {number} step - Current registration step (1 or 2)
 */
function resizeRegistrationbox(step) {
    if (isMobile()) return;

    step = parseInt(step);
    const windowHeight = window.screen.height;
    const topBarHeight = parent.$('#rg-top-bar').length > 0 ? parent.$('#rg-top-bar').outerHeight(true) || 0 : 0;
    const footerHeight = $(".registration-footer").outerHeight(true) ?? 0;
    let maxHeight = parent.window.innerHeight;
    let newHeight = calculateHeight(step, footerHeight);

    if(windowHeight == 768 && topBarHeight == 0) {
        parent.$("#registration-box").css("top", "0px");
    }

    if (step === 1) {
        maxHeight = parent.window.innerHeight + 10;
        if (windowHeight == 720) {
            newHeight = applyScrollableContainer(maxHeight);
        } else {
            removeScrollableContainer();
        }
    } else {
        if (newHeight > maxHeight) {
            newHeight = applyScrollableContainer(maxHeight);
        } else {
            removeScrollableContainer();
        }
    }

    parent.$.multibox('resize', 'registration-box', $('#registration-wrapper').width(), newHeight, false, false, true);
}

/**
 * Calculates the height needed for the registration form based on the current step
 * This makes sure we always have 20 pixels below the submit button or the banner.
 * @param {number} step - Current registration step
 * @param {number} footerHeight - Height of the footer element
 * @returns {number} - Calculated height
 */
function calculateHeight(step, footerHeight) {
    if (step === 1) {
        const content_right = $(".registration-content-right").position().top
            + $(".registration-content-right").outerHeight(true);
        const content_left = $(".registration-content-left").position().top
            + $(".registration-content-left").outerHeight(true);
        return Math.max(content_right, content_left) + footerHeight + 20;
    } else {
        const bottom_submit = $("#submit_step_2").position();
        return bottom_submit.top + 100;
    }
}

/**
 * Applies the scrollable container styling
 * - 10ms setTimeout: Ensures DOM updates complete before applying height adjustments.
 * @param {number} height - Maximum height available
 * @returns {number} - The adjusted height
 */
function applyScrollableContainer(height) {
    $(".registration-container").addClass("scrollable");
    setTimeout(function() {
        const fixedFooterHeight = parent.$('.games-footer').outerHeight() ?? 0;
        const adjustedMaxHeight = height - fixedFooterHeight;
        $(".registration-container").removeAttr("style");
        $(".registration-container")[0].offsetHeight;
        $(".registration-container").height(adjustedMaxHeight);
    }, 10);

    return height;
}

/**
 * Removes the scrollable container styling
 */
function removeScrollableContainer() {
    $(".registration-container").removeClass("scrollable");
    $(".registration-container").removeAttr("style");
}

function setupResizeHandlers() {
    if (!isMobile()) {
        let resizeTimer;
        $(parent.window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                const step = $("#step2").length > 0 ? 2 : 1;
                resizeRegistrationbox(step);
            }, 250);
        });
    }
}

function showHidePassword() {
    $('.password-field-icon, .password-field-icon-mobile').click(function (event) {
        event.preventDefault();
        event.stopPropagation();
        $(this).find('img').toggle();
        var input = document.getElementById('password');
        if (input.getAttribute('type') === "password") {
            input.setAttribute("type", "text");
        } else {
            input.setAttribute("type", "password");
        }
    });
}

/**
 * Setup registration step 1 form based on selected country
 *
 * @param initialRegistrationData
 */
function handleRegistrationFields(initialRegistrationData) {
    var handleResponse = function (res, iso) {
        // check if we are in the correct domain for this country
        if (res.iso_domain_redirection !== false) {
            replaceUrl(res.iso_domain_redirection, true);
        }

        var fields = res.fields;

        // country changed so hide and disable input fields
        $(".registration-step-1-control").hide();
        $(".registration-step-1-control input").attr('disabled', 'true');
        $(".registration-step-1-control .form-item").removeClass('form-item');

        // show proper input fields based on response
        for (var key in fields) {
            if (!fields.hasOwnProperty(key)) {
                continue;
            }

            // $elem targets the div which holds the input
            var $elem = $("#" + key).parent().parent();
            var $input = $("#" + key);
            var $container = $("#" + key).parent().parent();
            // props is an object where key is the input html attribute
            var props = fields[key];

            // show and enable input fields, if some needs to be disabled, it'll be done based on `props`
            $container.show();
            $input.removeAttr('disabled');
            $input.show();
            $input.addClass('form-item');

            for (var prop in props) {
                if (!props.hasOwnProperty(prop)) {
                    continue;
                }

                const propValue = props[prop];

                // install default error messages
                if (prop === 'error_message') {
                    $container.find("#" + key + "_msg").text(propValue);
                    continue;
                }

                // value is empty, so we have to remove the attribute(mainly used for 'disabled')
                if (propValue === null || propValue === '') {
                    // we want to keep the value from the user if none provided from server
                    if (prop !== 'value') {
                        $elem.removeAttr(prop);
                    }
                } else {
                    // value is not null so we just overwrite the attribute with the received value
                    $input.attr(prop, propValue);
                    if (prop === 'value' && $input.is('select')) {
                        $input.val(propValue.toString());
                    }
                }
            }
        }

        // set button details
        if (res.disabled_mitid){
            var register_button_class = 'register-button verification-btn-' + iso.toUpperCase();
            if (res.button.disabled) {
                register_button_class += ' btn-disabled';
            }
            $("#submit_step_1").attr('onclick', res.button.click).attr('class', register_button_class);
            $("#submit_step_1 > div").text(res.button.message);
        }

        $("#second-register-button").remove();
        $(res.extra_button).insertAfter($("#submit_step_1"));

        // setup checkboxes
        res.checkboxes.forEach(function (checkbox) {
            $('label[for="' + checkbox + '"]').show();
            $('label[for="' + checkbox + '"] input').removeAttr('disabled').addClass('form-item');
        });

        // fix the box height
        resizeRegistrationbox(1);

        // load the appropriate lic files
        window.reloadLicFuncs(res.scripts);

        setupRegistration();
    };
    $('#country').change(function () {
        var iso = $(this).val();

        // receives only the required input fields for selected country
        mgJson({iso: iso, action: "get_registration_country_info"}, function (res) {
            handleResponse(res, iso);
        });
    });

    if (initialRegistrationData) {
        handleResponse(initialRegistrationData, initialRegistrationData.iso);
    } else {
        $('#country').trigger('change');
    }
}

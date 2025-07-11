

function getPopupDim(dim){
    if(isMobile()){
        return '100%';
    }
    return dim;
}

function resetSourceOfFundsScroll() {
    window.scrollTo(0, 0);
    const container = document.querySelector('.registration-container');
    if (container) {
        container.scrollTop = 0;
    }
    document.body.style.overflow = 'hidden';
}

/**
 * This function is only executed the first time the iframe get's opened.
 */
function showSourceOfFundsBox(url) {
    var iframename = 'sourceoffunds1', boxId = 'sourceoffunds-box';

    resetSourceOfFundsScroll();

    url = url || '/sourceoffunds1/';
    url = llink(url);

    var options = {
        url: url,
        id: boxId,
        name: iframename,
        type: 'iframe',
        width: getPopupDim('969px'),
        height: getPopupDim('80%'),
        cls: 'mbox-deposit',
        globalStyle: {overflow: 'hidden'},
        overlayOpacity: 0.7,
        callb: function() {
            $(document.getElementById(boxId)).css("max-height", getPopupDim('80%'));
        }
    };

    $.multibox(options);
}

function showProofOfWealthPopup() {
    var iframename = 'proofofwealthpopup';

    var url = '/proofofwealthpopup/';

    url = llink(url);

    $.multibox({
        url: url,
        id: 'proofofwealthpopup-box',
        name: iframename,
        type: 'iframe',
        width: getPopupDim('669px'),
        height: getPopupDim('700px'),
        cls: 'mbox-deposit',
        globalStyle: {overflow: 'hidden'},
        overlayOpacity: 0.7
    });
}

function setupSourceOfFundsBox() {
    // set up required fields
    $('#name_of_account_holder, #address, #occupation, #annual_income, #name, #password').blur(function() {
        if(empty($(this).val())) {
            addClassError(this);
        }
    });
    $('#submission_day, #submission_month, #submission_year').blur(function() {
        if(empty($(this).val())) {
            addClassError(this);
            $(this).parent().addClass('styled-select-error');
        }
    });
}

function submitSourceOfFunds(occupation_dropdown_enabled){

    var data = {
        "name_of_account_holder":  $('#name_of_account_holder').val(),
        "address":                 $('#address').val(),
        "salary":                  ($('#salary').is(':checked'))       ? 1 : 0,
        "business":                ($('#business').is(':checked'))     ? 1 : 0,
        "income":                  ($('#income').is(':checked'))       ? 1 : 0,
        "dividend":                ($('#dividend').is(':checked'))     ? 1 : 0,
        "interest":                ($('#interest').is(':checked'))     ? 1 : 0,
        "gifts":                   ($('#gifts').is(':checked'))        ? 1 : 0,
        "pocket_money":            ($('#pocket_money').is(':checked')) ? 1 : 0,
        "others":	               $('#others').val(),
        "industry":                $('#industry').val(),
        "occupation":	           $('#occupation').val(),
        "annual_income":           $("input[name='annual_income']:checked").val(),
        "no_income_explanation":   $('#no_income_explanation').val(),
        "your_savings":            $("input[name='your_savings']:checked").val(),
        "savings_explanation":     $('#savings_explanation').val(),
        "name":	                   $('#name').val(),
        "password":                $('#password').val(),
        "submission_day":          $('#submission_day').val(),
        "submission_month":        $('#submission_month').val(),
        "submission_year":         $('#submission_year').val(),
        "document_id":	           $('#document_id').val(),
        "form_version":	           $('#form_version').val(),
        "actor_id":	               $('#actor_id').val(),
        "user_id":	               $('#user_id').val()
    };

    showLoader(function() {
        postSourceOfFundsForm(data, occupation_dropdown_enabled);
    }, true);
}

function closeSourceOfFundsBox(reload){
    if (getWebviewParams()) {
        sendToFlutter({
            status: 'success',
            trigger_id: 'success.close.sourceoffunds'
        });
    }
    //parent.$.multibox('close', 'casher-box');
    //parent.$.multibox('close', 'mp-box');
    parent.$.multibox('close', 'sourceoffunds-box');
    if(reload) {
        parent.location.reload();
    }
}

function closeSourceOfFundsBoxMobile(){
    parent.location.reload();
}

function closeProofOfWealthBox() {
    parent.location.reload();
}

function closeProofOfWealthBoxMobile() {
    history.go(-1);
}

function postSourceOfFundsForm(data, occupation_dropdown_enabled) {
    $.post("/phive/modules/Micro/source_of_funds.php", data, function(res){
        if(res.status != 'error') {
            function handleCloseSourceOfFunds(res) {
                // note: desktop and mobile are the same
                if (res.mobile == '1') {
                    closeSourceOfFundsBoxMobile()
                } else {
                    if (getWebviewParams()) {
                        sendToFlutter({
                            status: 'success',
                            trigger_id: 'success.close.sourceoffunds'
                        });
                    }
                    // reload page after submitting the form
                    closeSourceOfFundsBox(true);
                }
            }

            function updateOccupationData() {
                mgAjax({
                    action: 'update-occupation-data',
                    occupation: data.occupation,
                    industry: data.industry,
                    isSowdForm: true
                }, function(ret) {
                    if (ret === 'ok') {
                        handleCloseSourceOfFunds(res);
                    } else {
                        jsReloadBase();
                    }
                });
            }

            updateOccupationData();

        } else {
            if (getWebviewParams()) {
                sendToFlutter({
                    data:res.errors,
                    status: 'failed',
                    trigger_id: 'error.validation.sourceoffunds'
                });
            }
            // get errors from the json, and add class error to each field which has an error
            $(".error").removeClass('error');
            $.each(res.errors, function(key, value) {
                if(key === 'job.title') {
                    key = 'occupation';
                }
                addClassError($("#"+key));
            });

            // remove element to prevent showing double errors
            $('#errorZone').remove();
            $('#sourceoffundsbox_step1').after(res.info);  // this adds the errorZone div

            hideLoader();
        }

    }, "json");
}



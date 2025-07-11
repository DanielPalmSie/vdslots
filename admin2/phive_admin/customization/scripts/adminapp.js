$(document).ready(function () {
  $('.datepicker').daterangepicker({
      singleDatePicker: true,
      showDropdowns: true,
      autoApply: true,
      locale: {
        format: 'YYYY-MM-DD',
        firstDay: 1
      }
  });

   $('.datetimepicker').daterangepicker({
     singleDatePicker: true,
     timePicker: true,
     timePicker24Hour: true,
     timePickerSeconds: true,
     showDropdowns: true,
     autoApply: true,
     locale: {
       format: 'YYYY-MM-DD HH:mm:ss'
     }
   });
});


const swalDefaultStyle = {
  position: 'top',
  customClass: {
    popup: "card card-primary",
    confirmButton: "btn btn-sm btn-primary w-15",
    cancelButton: "btn btn-sm btn-default w-15 ml-3",
    title: "card-header",
    htmlContainer: "card-body text-left text-sm p-3 m-0",
    actions: "d-flex w-100 justify-content-end pr-3",
  },
}

//show confirmation dialog

function showConfirmBtn(dialogTitle, dialogMessage, dialogUrl) {
  Swal.fire({
    title: '<h3 class="card-title">' + (dialogTitle || "") + '</h3>',
    html: dialogMessage,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-check"></i> Yes',
    cancelButtonText: '<i class="fas fa-times"></i> No',
    buttonsStyling: false,
    showLoaderOnConfirm: true,
    allowOutsideClick: false,
    ...swalDefaultStyle,
    preConfirm: async () => {
      try {
        let data = await $.get(dialogUrl);
        let response = JSON.parse(data);

        if (response.success) {
          displayNotifyMessage('success', response.message);
          setTimeout(() => {
            location.reload();
          }, 2000);
        } else {
          displayNotifyMessage('warning', response?.message || '');
          return Promise.reject(response);
        }
      } catch (error) {
        displayNotifyMessage('error', 'Error connecting to the server');
        return Promise.reject(error);
      }
    }
  });
}


/**
 * The same confirm dialog with different response handling.
 *
 * @param {string} dialogTitle
 * @param {string} dialogMessage
 * @param {string} dialogUrl
 * @returns {void}
 */
function showConfirmBtn2(dialogTitle, dialogMessage, dialogUrl){
    Swal.fire({
        title: '<h3 class="card-title">' + (dialogTitle || "") + '</h3>',
        html: dialogMessage,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> Yes',
        cancelButtonText: '<i class="fas fa-times"></i> No',
        buttonsStyling: false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        ...swalDefaultStyle,
        preConfirm: async () => {
            try {
                var response = await $.ajax({
                    method: 'GET',
                    url: dialogUrl,
                    dataType: 'json'
                });

                if (response.success) {
                    displayNotifyMessage('success', response.message)

                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        setTimeout(() => location.reload(), 2000);
                    }
                } else {
                    displayNotifyMessage('warning', response.message);
                    return Promise.reject(response);
                }
            } catch (error) {
                displayNotifyMessage('error', 'Error connecting to the server');
                return Promise.reject(error);
            }
        }
    })
}

/**
 * The same confirm dialog, but without a page reload and with the option to
 * pass a function and parameters which will be executed on success
 *
 * @param {string} dialogTitle
 * @param {string} dialogMessage
 * @param {string} dialogUrl
 * @param {function} successAction
 * @param {string} successActionArgument   // TODO: make it possible to pass an array of parameters to the function
 * @returns {void}
 */
function showConfirmBtnAjax(dialogTitle, dialogMessage, dialogUrl, successAction, successActionArgument) {
    Swal.fire({
        title: '<h3 class="card-title">'+ (dialogTitle || "") +'</h3>',
        html: dialogMessage,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> Yes',
        cancelButtonText: '<i class="fas fa-times"></i> No',
        buttonsStyling: false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        ...swalDefaultStyle,
        preConfirm: async () => {
            try {
                let data = await $.get(dialogUrl);
                let response = typeof data === 'string' ? JSON.parse(data) : data;

                if(response && response.success) {
                      displayNotifyMessage('success', response.message)
                      successAction(successActionArgument);
                } else {
                    displayNotifyMessage('warning', response?.message || 'Something went wrong..')
                    return Promise.reject(response)
                }
            } catch (error) {
                displayNotifyMessage('error', 'Error connecting to the server')
                return Promise.reject(error)
            }
        }
    })
}

/**
 * The same confirm dialog, but without a page reload and with the option to
 * pass a function and parameters which will be executed on success,
 * AND the response of the dialogUrl will also be passed to the successAction
 *
 * @param {string} dialogTitle
 * @param {string} dialogMessage
 * @param {string} dialogUrl
 * @param {function} successAction
 * @param {string} successActionArgument
 * @returns {void}
 */
function showConfirmBtnAjaxUsingResponse(dialogTitle, dialogMessage, dialogUrl, successAction, successActionArgument, successActionArgument2) {
    Swal.fire({
        title: '<h3 class="card-title">'+ (dialogTitle || '') +'</h3>',
        html: dialogMessage,
        showCancelButton: true,
        showCloseButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> Yes',
        cancelButtonText: '<i class="fas fa-times"></i> No',
        buttonsStyling: false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        ...swalDefaultStyle,
        preConfirm: async () => {
            try {
                let data = await $.get(dialogUrl);
                let response = jQuery.parseJSON(data);

                if(response.success) {
                    displayNotifyMessage('success', response?.message || '')
                    successAction(response, successActionArgument, successActionArgument2);
                } else {
                    displayNotifyMessage('warning',response?.message || 'Something went wrong..')
                    return Promise.reject(response)
                }
            } catch(error) {
                displayNotifyMessage('error', 'Error connecting to the server')
                return Promise.reject(error)
            }
        }
    })
}


/**
 * The same confirm dialog, but without the dialogUrl to be called this only has an action to be performed when the user click 'yes'
 *
 * @param {string}    dialogTitle
 * @param {string}    dialogMessage
 * @param {function}  action
 * @param {mixed}     actionArgument
 * @returns {void}
 */
function showConfirmNoUrl(dialogTitle, dialogMessage, action, actionArgument){
      Swal.fire({
        title: '<h3 class="card-title">'+ (dialogTitle || '') +'</h3>',
        html: dialogMessage,
        showCancelButton: true,
        showCloseButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> Yes',
        cancelButtonText: '<i class="fas fa-times"></i> No',
        buttonsStyling: false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        ...swalDefaultStyle,
    }).then(result => {
        if(result.isConfirmed) {
          action(actionArgument)
        }
    })
}

function showConfirm(dialogTitle, dialogMessage, dialogUrl){
    Swal.fire({
        title: '<h3 class="card-title">'+ (dialogTitle || '') +'</h3>',
        html: dialogMessage,
        showCancelButton: true,
        showCloseButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        buttonsStyling: false,
        allowOutsideClick: false,
        showLoaderOnConfirm: true,
        reverseButtons: true,
        position: 'top',
        customClass: {
            ...swalDefaultStyle.customClass,
            confirmButton: 'btn btn-sm btn-danger ml-2'
        },
        preConfirm: async () => {
            try {
                let data = await $.get(dialogUrl);
                let response = jQuery.parseJSON(data);

                if(response.success) {
                    displayNotifyMessage('success', response?.message || '')
                    document.location.reload()
                } else {
                    displayNotifyMessage('warning','Something went wrong..')
                    return Promise.reject(response)
                }
            } catch(error) {
                displayNotifyMessage('error', 'Error connecting to the server')
                return Promise.reject(error)
            }
        }
    })
}

function showConfirmOnLink(dialogTitle, dialogMessage, dialogUrl){
    Swal.fire({
      title: '<h3 class="card-title">'+ (dialogTitle || '') +'</h3>',
      html: dialogMessage,
      showCancelButton: true,
      showCloseButton: true,
      confirmButtonText: '<i class="fas fa-check"></i> Yes',
      cancelButtonText: '<i class="fas fa-times"></i> No',
      buttonsStyling: false,
      allowOutsideClick: false,
      ...swalDefaultStyle,
    }).then(result => {
        if(result.isConfirmed) {
            window.location = dialogUrl;
        }
    })
}

function showConfirmInForm(dialogTitle, dialogMessage, form) {
    Swal.fire({
        title: '<h3 class="card-title">'+ (dialogTitle || '') +'</h3>',
        html: dialogMessage,
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        buttonsStyling: false,
        reverseButtons: true,
        position: 'top',
        customClass: {
          ...swalDefaultStyle.customClass,
          confirmButton: 'btn btn-sm btn-danger w-15 ml-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            form.trigger('submit');
        }
    });
}


function showValidationErrorInForm(dialogTitle, dialogMessage) {
    Swal.fire({
        title: '<h3 class="card-title">'+ (dialogTitle || '') +'</h3>',
        html: dialogMessage,
        showCancelButton: false,
        confirmButtonText: 'Ok',
        buttonsStyling: false,
        position: 'top',
        customClass: {
          ...swalDefaultStyle.customClass,
          popup: "card card-warning",
          confirmButton: 'btn btn-sm btn-default'
        }
    });
}

/**
* @param {string} email
 *@returns {null||array}
 */
function validateEmail(email) {
    if(email.length > 0){
    return String(email)
        .toLowerCase()
        .match(
            /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        );
    };
    return  true;
};

/**
 * @param {string} paypal_payer_id
 *@returns {boolean}
 */
function checkPayerIdLength(paypal_payer_id){
    if(paypal_payer_id.length > 13){
        return false;
    }
    return true;
}

/**
 * @param {string} email
 * @param {string} paypal_payer_id
 *@returns {null||string}
 */
function validatePayPalInputs(email, paypal_payer_id){
    var errorMessage = '';
    if(!validateEmail(email)){
         errorMessage = 'Please Provide a correct email';
    } else if (!checkPayerIdLength(paypal_payer_id)){
         errorMessage = 'Paypal payer id is not of correct length';
    }
  return errorMessage;
}

var confirm = {

    dlgObj: null,

    yes: function() {
        // set your custom actions for each dialog (confirm.yes = function() { alert('Do it here'); };)
        return false;
    },

    no: function() {
        // set your custom actions for each dialog (confirm.no = function() { alert('Do it here'); };). PS: NOT MANDATORY!
        return false;
    },

    dlg: function(title, msg) {
        this.dlgObj = Swal.fire({
            title: '<h3 class="card-title">'+ (title || '') +'</h3>',
            html: msg,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            buttonsStyling: false,
            reverseButtons: true,
            position: 'top',
            customClass: {
              ...swalDefaultStyle.customClass,
              confirmButton: 'btn btn-sm btn-danger w-15 ml-2'
            }
        }).then(result => {
            if(result.isConfirmed) {
                this.yes()
            } else if (result.dismiss) {
                this.no()
            }
        })

    },

    close: function() {
        if(this.dlgObj) {
            Swal.close();
        }
    }
};

/** Documents page */

$(document).ready(function() {
    // show browse buttons when we have selected an id type
    $('#idtype').change(function() {
            showBrowseButtons();
    });
});

// This function is already in phive, but not loaded here
function showBrowseButtons() {
    if ($("#country option:selected").val() !== '' && $("#idtype option:selected").val() !== '') {
        $('#image-front-container').show();
        $('#image-front').removeAttr('disabled');
        if($("#idtype option:selected").val() !== 'PASSPORT') {
            $('#image-back-container').show();
            $('#image-back').removeAttr('disabled');
        }
        if($("#idtype option:selected").val() === 'PASSPORT') {
            $('#image-back-container').hide();
            $('#image-back').attr('disabled','disabled');
        }
    } else {
        $('#image-front-container').hide();
        $('#image-front').attr('disabled','disabled');
        $('#image-back-container').hide();
        $('#image-back').attr('disabled','disabled');
    }
}

$(document).ready(function(){
    // limit the amount of fields a user can add
    var max_browse_fields = 10;
    $(".add-file-field").click(function(){
        
        // give the file input a unique name, by counting how many files input fields we have
        var inputs = $(this).siblings(".uploadfields").find($("input"));
        
        if((inputs.length - 2) < max_browse_fields) {
            var input_element = "<input name='file" + inputs.length + "' type='file' />"
            $(this).siblings(".uploadfields").append(input_element);

            // bind the enableSubmit event to the new form element
            enableSubmit();
            
            inputs = $(this).siblings(".uploadfields").find($("input"));
            if((inputs.length - 2) == max_browse_fields) {
                // disable this button
            $(this).attr('disabled', 'disabled');
            }
        }
    });
});

/* Enable the submit button when a file is selected */
function enableSubmit() {
    $('input[type=file]').change(function(){
        if ($(this).val()) {
            $(this).closest('form').children('input:submit').removeAttr('disabled'); 
        }
    });
}



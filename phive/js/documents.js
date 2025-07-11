/** Documents page */

$(document).ready(function() {
    // show browse buttons when we have selected an id type
    $('#idtype').change(function() {
            showBrowseButtons();
    });

    $('#idtype').change()
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

function showFilename(id_selector, inputs_length) {
    if (empty(inputs_length)) {
        inputs_length = '';
    }
    var filename_element = $('#' + id_selector + '_choose_file_filename' + inputs_length);
    var no_file_chosen_text = filename_element.siblings('.choose-file-field-container').find('.document_no_file_chosen').first().text();
    var files = $('#' + id_selector + '_choose_file_field' + inputs_length).prop('files');
    if (files.length < 1) {
        filename_element.text(no_file_chosen_text);
    } else {
        var filename = files[0].name;
        var filename_short = files[0].name;
        if (filename_short.length > 16 && !isMobile()) {
            filename_short = filename.substring(0, 7) + '...' + filename.substring(filename.length - 6, filename.length);
        }
        filename_element.text(filename_short);
        filename_element.attr('title', filename);
    }
}
$(document).ready(function(){
    // limit the amount of fields a user can add
    var max_browse_fields = 10;
    $(".choose-file-field-container").on('click', function(){
        $(this).find('input').one('click');
    });
    $(".add-file-field").click(function(){
        
        // give the file input a unique name, by counting how many files input fields we have
        var inputs = $(this).siblings(".uploadfields").find($("input"));
        
        if((inputs.length - 2) < max_browse_fields) {
            var select = $(this).siblings(".uploadfields").find($("select")).first();

            if (select) {
                $(this).siblings(".uploadfields").append(select.clone());
            }
            var input_id = $(this).attr('data-for');
            var choose_file_text = $(this).siblings(".uploadfields").find('.document_choose_file').first().text();
            var no_file_chosen_text = $(this).siblings(".uploadfields").find('.document_no_file_chosen').first().text();
            var input_element = '<div class="choose-file-container">\n' +
                '                    <div id="choose_file_field' + inputs.length  + '" class="choose-file-field-container">\n' +
                '                        <label for="' + input_id + '_choose_file_field' + inputs.length + '" class="choose-file-field">' + choose_file_text + '</label>\n' +
                '                        <input id="' + input_id + '_choose_file_field' + inputs.length + '" type="file" name="file' + inputs.length + '" class="hidden" onchange="showFilename(\'' + input_id + '\', ' + inputs.length + ')">\n' +
                '                        <span class="hidden document_choose_file">' + choose_file_text + '</span>' +
                '                        <span class="hidden document_no_file_chosen">' + no_file_chosen_text + '</span>' +
                '                    </div>\n' +
                '                    <span id="' + input_id + '_choose_file_filename' + inputs.length + '" style="cursor: default">' + no_file_chosen_text + '</span>\n' +
                '                </div>';

            $("#choose_file_field" + inputs.length).on('click', function(){
                $("#" + input_id + "_choose_file_field" + inputs.length).one('click');
            });

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

$(document).ready(function(){
    enableSubmit();
});

/* Enable the submit button when a file is selected */
function enableSubmit() {
    $('input[type=file]').change(function(){
        if ($(this).val()) {
            $(this).closest('form').children('input:submit').removeAttr('disabled'); 
        }
    });
}



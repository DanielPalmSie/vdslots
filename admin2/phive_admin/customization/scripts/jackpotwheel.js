// Vars used by the code in this page to do power controls.
var wheelPower = 0;
var wheelSpinning = false;
var theWheel = "";
var win_wheel_colors = [];

if (typeof variable === 'undefined') {
    posturl = "";
    selectposturl = "";
}

// makes the table sortable by drag and drop
$('tbody').sortable({
    axis	:"y",
    stop 	: function(event, ui){
	// check that the row at least moved up or down 35px (35 is the height of the table row) before updating
	// like this it will not update on the slightest move
	if ( (ui.position.top - ui.originalPosition.top < -35) || (ui.position.top - ui.originalPosition.top > 35))
	    setTimeout(updateSliceOrder(urlUpdateSliceOrder), 100);
    },
    connectWith: ".con"
});




function getRandomInt(min, max) {
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min)) + min;
}

function loadWheel(imagePaths, colors = '') {
	var wheelNumSegments = imagePaths.length; // number of slices the wheel will have
	if(colors && colors.split('|').length > 0)
		win_wheel_colors = colors.split('|');

    /*
     * ----------------------------------------------------------------
     * Created dynamic segments images that are retrieved from database
     * ----------------------------------------------------------------
     */
    var wheelSegments = [];
    var color = "";
    var obj = "";
    var path = "";
    var text = "";

    imagePaths.forEach(function(imagePath, index) {

	color = win_wheel_colors[index%win_wheel_colors.length];

	// get the icon file names from database
	path = imagePath;
	obj = {
	    'image' : path,
	    'strokeStyle' : '',
	    'fillStyle' : color
	};
	text = text + path;
	wheelSegments.push(obj);

    })


	// Create new wheel object specifying the parameters at creation time.
	theWheel = new Winwheel({
	    'outerRadius' : 342, // Set outer radius so wheel fits inside the
	    // background.
	    'innerRadius' : 102, // Make wheel hollow so segments don't go all way to
	    // center.
	    'textFontSize' : 5, // 24 Set default font size for the segments.
	    'textOrientation' : 'vertical', // Make text vertial so goes down from
	    // the outside of wheel.
	    'textAlignment' : 'outer', // Align text to outside of wheel.
	    'numSegments' : wheelNumSegments, // Specify number of segments.
	    'drawText' : true,
	    'imageDirection' : 'N',
	    'lineWidth' : 7, // width of the segment borders
	    'segments' : wheelSegments,
	    'drawMode' : 'segmentImage',

	    'animation' : // Specify the animation to use.
	    {
		'type' : 'spinToStop',
		'duration' : 8, // Duration in seconds.
		'spins' : 2, // Default number of complete spins.
		'easing' : 'Power4.easeOut',
		'callbackFinished' : 'alertPrize()'
	    },

	    'imageOverlay' : true, // this will show the line dividers on top of
	    // images

	    'segmentLineOnly' : true, // Added new Jonathan Aber
		'segmentImageTopLayer' : true, // Added new Jonathan Aber
		'segmentColorGradient' : true,
		'gradientRadiusMultipler': 0.8,

	});
}

// We need to update the text of the element ourselves, because xeditable cannot
// do this when the new value is not in the source for the select anymore
//function updateText(element, response) {
//	$(document).ajaxComplete(function() {
//		element.text(response.newText);
//		element.removeClass('editable-empty');
//	});
//}


//-------------------------------------------------------
// Update Probability Text
//-------------------------------------------------------
function updateProbabilityAjax(url) {

    $.ajax({
	type : "GET",
	url : url,
	cache : false,
	dataType : 'json',
	success : function(response) {
	    if (response.newText > 10000000 || response.newText < 10000000) {
		$("#total_probability").css('color', 'red');
	    } else {
		$("#total_probability").css('color', 'black');
	    }
	    $("#total_probability").text(response.newText);
	}
    });
}

// -------------------------------------------------------
// Add Table Row
// -------------------------------------------------------
function createSliceTableRows(sliceOrder, keyNum, sliceId, sliceAwardName,
		              sliceProbability, sliceName, datasourceUrl)
{
    var source = $("#sliceRowTemplate").html();
    var template = Handlebars.compile(source);

    var data = {
	slice_sort_order : parseInt(sliceOrder) + 1,
	key : parseInt(keyNum) + 1,
	slice_id : sliceId,
	datasource_url : datasourceUrl,
	slice_award_name : sliceAwardName,
	slice_probability : sliceProbability,
	slice_name : sliceName
    };

    var newRow = template(data);

    // X-Editable new row
    $('.pUpdateTable').append(newRow);

    // Addnew row to sortable
    $(newRow).sortable
}

//-------------------------------------------------------
//Activate the Wheel
//-------------------------------------------------------
function activateWheel(url) {

    $.ajax({
	type : "GET",
	url : url,
	dataType : 'json',
	cache : false,
	success : function(response) {
	    if (response.success && response.active) {
		$("#wheeldeactivebox").show();
		$("#wheelactivebox").hide();
                // show flash messages
                showAjaxFlashMessages();
		$('.pUpdate').editable('option', 'disabled', false);
	    } else if (response.success && response.active == 0) {
		$("#wheeldeactivebox").hide();
		$("#wheelactivebox").show();
                // show flash messages
                showAjaxFlashMessages();
		$('.pUpdate').editable('option', 'disabled', false);
	    } else if (!response.success) {
		showAjaxFlashMessages();
	    }
	}
    });
}


function enableXeditable(isActive) {
    if (isActive) {
	$('.pUpdate').editable('option', 'disabled', true);
    }
}

//-------------------------------------------------------
// Enable active button
//-------------------------------------------------------
/* Not needed anymore, we can have multiple wheels active...
function enableActivateButton(url) {

    $.ajax({
	type : "GET",
	url : url,
	cache : false,
	success : function(response) {
	    if (response) {
		$("#activate-wheel-btn").prop('disabled', false);
	    } else {
		$("#activate-wheel-btn").prop('disabled', true);
	    }
	}
    });
}
*/

/*
   $("#activate-wheel-btn, #deactivate-wheel-btn").click(function() {
   activateWheel(url);
   });
 */

// -------------------------------------------------------
// Add Slice
// -------------------------------------------------------
function addSlice(url, datasource) {

    $.ajax({
	type : "GET",
	url : url,
	cache : false,
	dataType : 'json',
	success : function(response) {
	    if (response.success) {
		// Add row to table
		createSliceTableRows(response.slice.sort_order,
				     response.slice.sort_order, response.slice.id, '',
				     '0', '', datasource);
		loadWheel(response.imageFilename);
		// refreshWheelData(response.imageFilename);

		refreshAwardEditable();
		refreshProbabiltyEditable();
		// show flash messages
                showAjaxFlashMessages();
	    }
	}
    });
}

// -----------------------------------------------------------------------
// Update the orders of the slices once there is a drag and drop row
// -----------------------------------------------------------------------
function updateSliceOrder(url) {

    var sortedArray = [];

    // Order displayed table
    $("#sliceTable tr").each(
	function(i) {
	    $(this).find('td').eq(0).html(
		"<span class='fa fa-sort'></span>&nbsp;&nbsp;&nbsp;"
	      + i);
	});

    // Create array of ordered data-pk and build table
    $("#sliceTable tr td a").each(function() {
	if ($(this).attr('data-name') == "award_id") {
	    sortedArray.push($(this).attr('data-pk'));
	}
    });

    $.ajax({
	type : "GET",
	url : url,
	data : {
	    "sortedArray" : sortedArray
	},
	dataType : 'json',
	cache : false,
	success : function(response) {

	    // refresh wheel with updated ordered slices
	    loadWheel(response.imageFilename);

	}
    });

}

// -------------------------------------------------------
// Delete Slice
// -------------------------------------------------------
function deleteSlice(sliceId, sliceHtmlId, url) {

    $.ajax({
	type : "GET",
	url : url,
	cache : false,
	dataType : 'json',
	data : {
	    'slice_id' : sliceId
	},
	success : function(response) {
	    if (response.success) {

		// remove the row immediately in order not to refresh the page
		$('#' + sliceHtmlId).parents('tr').remove();
		updateSliceOrder(urlUpdateSliceOrder);
		updateProbabilityAjax(urlProbability);

		// refresh wheel with updated slices
		loadWheel(response.imageFilename);

		// show flash messages
                showAjaxFlashMessages();
	    }
	}
    });
}


//-------------------------------------------------------
//Delete Wheel
//-------------------------------------------------------
function deleteWheel(wheelId, url) {

    $.ajax({
	type : "GET",
	url : url,
	cache : false,
	dataType : 'json',
	data : {
	    'wheel_id' : wheelId
	},
	success : function(response) {
	    if (response.success) {
		$('#wheel_deleted_' + wheelId).html("DELETED");
		$('#delete_wheel_' + wheelId).replaceWith("");
		$('#wheel_name_' + wheelId + ' a').contents().unwrap();
		// show flash messages
                showAjaxFlashMessages();
	    }
	}
    });
}

// -------------------------------------------------------
// Click handler for spin button.
// -------------------------------------------------------
function startSpin(numberOfSlices, replay) {
    // config values
    // the slice the wheel will land on
    if (replay == false ) {
	luckySegmentWinner = getRandomInt(1, numberOfSlices);
    } else {
	luckySegmentWinner = numberOfSlices;
    }

    // Ensure that spinning can't be clicked again while already running.
    if (wheelSpinning == false) {

	// Disable the spin button so can't click again while wheel is spinning.
	$('#spin_button').addClass('not_clickable');
	$('#spin_button').removeClass('clickable');

	// rig the prize
	calculatePrize(theWheel, luckySegmentWinner);

	// Get random angle inside specified segment of the wheel.
	var stopAt = theWheel.getRandomForSegment(luckySegmentWinner);

	// Important thing is to set the stopAngle of the animation before
	// stating the spin.
	theWheel.animation.stopAngle = stopAt;

	// Begin the spin animation by calling startAnimation on the wheel
	// object.
	theWheel.startAnimation();

	// Set to true so that spin button can't be clicked during the current
	// animation.
	wheelSpinning = true;
    }
}




// -------------------------------------------------------------------------------------
// Function with formula to work out stopAngle before spinning animation.
// -------------------------------------------------------------------------------------
function calculatePrize(theWheel, numberOfSlices) {

    var wheelNumSegments = numberOfSlices;

    // This formula always makes the wheel stop somewhere inside the rigged
    // prize at least
    // 1 degree away from the start and end edges of the segment.
    var angleToStartFrom = 360 / wheelNumSegments * (luckySegmentWinner - 1) + 1;
    // var.log(angleToStartFrom);
    var segmentDegree = 360 / wheelNumSegments;
    // var.log(segmentDegree);
    var stopAt = (angleToStartFrom + Math.floor((Math.random() * segmentDegree)))

	// Important thing is to set the stopAngle of the animation before stating
	// the spin.
	theWheel.animation.stopAngle = stopAt;

}

// -------------------------------------------------------
// Function for reset button.
// -------------------------------------------------------
function resetWheel() {

    // Stop the animation, false as param so does not call callback function.
    //theWheel.stopAnimation(false);

    theWheel.rotationAngle = 0; // Re-set the wheel angle to 0 degrees.
    wheelSpinning = false; // Reset to false to power buttons and spin can be
    // clicked again.
}

// -----------------------------------------------------------------------
// Called when the spin animation has finished by the callback feature of
// the wheel because I specified callback in the parameters.
// -----------------------------------------------------------------------
function alertPrize() {
    // Prize functionality must be added in here

    // make Spin button clickable again.
    $('#spin_button').addClass('clickable');
    $('#spin_button').removeClass('not_clickable');

    resetWheel();
}




function refreshAwardEditable() {
    //refresh is used to add editable to dynamic content added after the page is created

    /* ************** XEditable with PUpdate **************************************** */

    //edit form style - popup or inline
    $.fn.editable.defaults.mode = 'popup';


    $('.pUpdateAwards').editable({
	type: 'select2',
	url: posturl,
	pk: 1,
	onblur: 'submit',
	inputClass: 'input-xxlarge',
	ajaxOptions: {
	    dataType: 'json'
	},
        params: function(params){
            params.award_alias = $(this).attr('data-award_alias');
            return params;
        },
	emptytext: 'None',
	select2: {
	    width:'300px',
	    minimumInputLength: 3,
	    ajax: {
		url: selectposturl,
		dataType: 'json',
		data: function (term, page) {
		    return { filter: term };
		},
		results: function (data, page) {
		    return { results: data };
		}
	    },
	},
	success: function(response, newValue) {

	    // Refresh Wheel
	    //loadWheel(response.imageFilename);
            window.location.reload();
	}
    });
}

function removeAwardFromSlice(sliceId, awardAlias){
    $.post(posturl, {pk: sliceId, award_alias: awardAlias, value: 0, name: 'award_id'}, function(ret){
        window.location.reload();
    });
}

function refreshProbabiltyEditable() {

    //refresh is used to add editable to dynamic content added after the page is created
    $('.pUpdate').editable({
        validate: function(value) {

            if($.trim(value) == '' || isNaN($.trim(value))){
                return 'Value is required.';
            } else if ( $(this).hasClass('probability') && ( ($.trim(value) > 10000000) || ($.trim(value) < 1)) ) { 						//Check if the number is greater than 1 for probability field only
              	return 'Value must be greater than 1 and less than 10,000,000.';
            }

        },
        //selector: 'a',
        type: 'text',
        url: posturl,
        placement: 'top',
        send:'always',
        sourceCache: false,
        ajaxOptions: {
            dataType: 'json'
        },
        success: function(response, newValue) {

            var element = $(this);

            // update text for award elements only
            if(element.hasClass('update_awards')) {
                updateText(element, response);
            }

            // refresh total probability
            if(element.hasClass('probability')) {
              	updateProbabilityAjax(urlProbability);
            }

            // determine if the wheel can be activated
            //enableActivateButton();

            // Refresh Wheel
            loadWheel(response.imageFilename);

        }
    });
}



function redrawWheel()
{
    theWheel.draw();
}


$(function(){

    //Assign editable to Awards and Probabilty
    refreshAwardEditable();
    refreshProbabiltyEditable();


    $(".pUpdateTable").on("click",".fa-trash-o", function(){
	var sliceHtmlId = $(this).attr('id');
	var url = $(this).attr('data-url');
	var sliceId = $(this).attr('data-value');

	deleteSlice(sliceId, sliceHtmlId, url);

    });

    $(".wheel-list").on("click",".fa-trash-o", function(){
	var url = $(this).attr('data-url');
	var wheelId = $(this).attr('data-value');

	deleteWheel(wheelId, url);

    });


    $('#spin_button').hover(function(){
	$('#spinButton1').hide();
	$('#spinButton2').show();
    }, function(){
	$('#spinButton2').hide();
	$('#spinButton1').show();
    });
});




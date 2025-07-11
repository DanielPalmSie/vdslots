$(function(){

	refreshJackpotEditable();
	updateTotalContribution();
       
});





function refreshJackpotEditable() {
	

	
	//refresh is used to add editable to dynamic content added after the page is created
    $('.pUpdate').editable({    	
    		validate: function(value) {
       	   
              if($.trim(value) == '' || isNaN($.trim(value))){
            	  return 'Numeric value is required.';
              } else if ( ($(this).hasClass('contribution_share_nextjp')) && ($.trim(value) >= 1 || $.trim(value) <= 0)){ 	//Check if the number is greater than 1 for probability field only
            	  return 'Value must be less than 1 and not negative.';
              } else if ( ($(this).hasClass('jackpot_amount_minimum')) && ($.trim(value) <= 1)){ 	//Check if the number is greater than 1 for probability field only
            	  return 'Value must be greater than 1.';
              } else if ( ($(this).hasClass('contribution_share')) && ($.trim(value) >= 1 || $.trim(value) <= 0)){ 	//Check if the number is greater than 1 for probability field only
            	  return 'Value must be less than 1 and not negative.';
              }               
          },
          //selector: 'a',
          type: 'text',
          url: updateURL,
          placement: 'top',
          send:'always',
          sourceCache: false,
          ajaxOptions: {
              dataType: 'json'
          },
          success: function(response, newValue) {

              var element = $(this);
              
              // refresh total probability
              if(element.hasClass('contribution_share')) {
            	  updateTotalContribution();
              }
          }
      });
}





function updateTotalContribution() {
	
    $.ajax({
        type: "GET",
        url: updateTotContribution,
        cache: false,
        dataType: 'json',
        success: function(response) {
           $("#total_contribution").text(response.newText);
           if(response.newText === '1.0000') {
               $("#total_contribution").removeClass('red');
               $("#save-jackpot-btn").prop('disabled', false);
           } else {
               $("#total_contribution").addClass('red');
               $("#save-jackpot-btn").prop('disabled', true);
           }
        }
    });
}
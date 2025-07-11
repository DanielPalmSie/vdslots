<script>
    var stepCount = 1;
    var count = 0;
    var rgTestResult = '';

    function initializeDepositLimit() {
        stepCount = 1;
        count = 0;
        rgTestResult = '';
    }

    $(document).on('click', '#next-question', function(){
        if(stepCount < 10) {
            var checkedValue = $('input[name=choice-radio]:checked').val();
            if(checkedValue === 'yes') {
                rgTestResult = 'fail';
            }
            $('#question-'+count).hide();
            stepCount++;
            count++;
            $("#question-step").html(stepCount+"/10");
            $('#question-0').hide();
            $('#question-'+count).show();
            $('input[name=choice-radio]').prop('checked',false);
            $('#next-question').prop('disabled', true);
            updateProgressBar();
        } else {
            stepCount = 1;
            count = 0;
            licFuncs.depositLimitPopupsHandler().showTestCompletePopup();
        }
    });

    function updateProgressBar() {
        var progressSteps = document.querySelectorAll(".progress-step");
        progressSteps.forEach((progressStep, idx) => {
            if(idx < stepCount * 1) {
                progressStep.classList.add('progress-step-active');
            }
        })
    }

    $(document).on('click', '.rg-radio', function() {
        $('#next-question').prop('disabled', false);
    });

    $(document).on('click', '.test-ok-btn', function() {
        licFuncs.depositLimitPopupsHandler().sendRGResponseStatus(rgTestResult);
    });
</script>
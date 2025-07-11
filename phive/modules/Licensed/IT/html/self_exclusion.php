<div id="understand-permanent-self-excluding-proceed" style="display: none;"><?php et('permanent-self-excluding.proceed.html') ?></div>
<div id="understand-self-excluding-confirm" style="display: none;"><?php et('self-excluding.confirm.html') ?></div>

<?php
/**
 * The cancel button label
 * @var string
 */
$cancel  = t('cancel');
/**
 * The confirm button label
 * @var string
 */
$confirm = t('confirm');
/**
 * The proceed button label
 * @var string
 */
$proceed = t('proceed');
?>

<script>
    /**
     * just to make sure we are checking if licFuncs exists and if not
     * create an empty object
     */
    if(!licFuncs) {
        licFuncs = {};
    }

    /**
     * Showing the self-exclusion confirm box.
     * This is the last popup shown for both self-exclusion or permanent self-exclusion
     *
     * @param duration  the duration of the self-eclusion. can be a integer or a string 'permanent'
     * @return void
     */
    licFuncs.showSelfExclusionConfirmBox = function(duration) {
        mboxDialog($("#understand-self-excluding-confirm").html(), 'mboxClose()', '<?= $cancel ?>', function(){
            licFuncs.exclude(duration);
        }, '<?= $confirm ?>', undefined, undefined, false);
    }

    /**
     * Calling method to store settings on backend
     * This is the last popup shown for both self-exclusion or permanent self-exclusion
     *
     * @param duration  the duration of the self-eclusion. can be a integer or a string 'permanent'
     * @return void
     */
    licFuncs.exclude = function (duration) {
        action = (duration == 'permanent') ? 'exclude-permanent' : 'exclude';

        saveAccCommon(action, {rg_duration: duration}, function(ret){
            if(typeof ret['msg'] !== 'undefined'  && ret['msg'] != '') {
                mboxMsg(ret['msg'], true, function(){ goTo('/?signout=true'); }, 500, undefined, true)
            } else {
                goTo('/?signout=true');
            }
        });
    }

    /**
     * Showing the self-exclusion popup flow
     *
     * @param duration  the duration of the self-eclusion. can be a integer or a string 'permanent'
     * @return void
     */
    licFuncs.showSelfExclusionMsgBox = function (duration) {
        if(duration == 'permanent') {
            // if permanent we have to show another dialog to make sure that the player understand
            mboxDialog($("#understand-permanent-self-excluding-proceed").html(), 'mboxClose()', '<?= $cancel ?>', function(){
                mboxClose('mbox-msg', function() {
                    // showing the last confirm dialog box
                    licFuncs.showSelfExclusionConfirmBox(duration);
                });
            }, '<?= $proceed ?>', undefined, undefined, false);
        } else {
            // showing the last confirm dialog box
            licFuncs.showSelfExclusionConfirmBox(duration);
        }
    }
</script>

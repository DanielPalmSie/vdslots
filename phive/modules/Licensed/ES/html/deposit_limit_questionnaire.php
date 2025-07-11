<?php
    $questions = array_map(function($i) { return "questionnaire.increase.deposit.limit.step$i"; }, range(1,10));
?>
<div>
    <div class="center-stuff">
        <p>
            <?php et('statement') ?> <span id="question-step">1/10</span>
        </p>
        <div class="progressbar">
            <div class="progress" id="progress"></div>

            <div class="progress-step progress-step-active"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
        </div>
        <br/>
        <div class="rg-questionnaire">
            <p id="question-0"><?php et('questionnaire.increase.deposit.limit.step1')?> </p>
            <?php foreach($questions as $key => $q): ?>
                <p hidden id="question-<?= $key ?>"><?php et($q) ?> </p>
            <?php endforeach; ?>
        </div>
        <div>
            <label class="rg-radio-yes">
                <input class="rg-radio" type="radio" name="choice-radio" value="yes">
                <span class="rg-radio-text">Yes</span>
            </label>
            <label>
                <input class="rg-radio" type="radio" name="choice-radio" value="no">
                <span class="rg-radio-text">No</span>
            </label>
        </div>
    </div>
    <br/>
    <div>
        <button class="btn btn-l btn-default-l next-question" id="next-question" disabled >
            <?php et('goto') ?> <span class="next-arrow"> > </span> </button>
    </div>
</div>
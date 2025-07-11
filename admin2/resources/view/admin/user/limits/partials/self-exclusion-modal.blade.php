<?php
/**
 * @var \App\Models\User $user
 */
use App\Repositories\LimitsRepository;

if(empty($self_exclusion_options)) {
    $self_exclusion_options = LimitsRepository::getUserSelfExclusionTimeOptions($user);
}
?>
<div class="modal fade" id="self-exclusion-modal" tabindex="-1" role="dialog" aria-labelledby="self-exclusion-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="self-exclusion-modal-title">Self Exclusion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <form id="self-exclusion-modal-form" role="form">
                    <input type="hidden" id="self-exclusion-key" name="key">
                    <input type="hidden" id="self-exclusion-type" name="type">
                    <input type="hidden" id="self-exclusion-subtype" name="subtype">

                    <!-- Radio Options -->
                    <div class="form-group row" id="self-exclusion-modal-group">
                        <label class="col-12">Select Exclusion Duration:</label>
                        @if(empty($self_exclusion_options))
                            <div class="col-12">
                                <label class="radio-inline mr-3">
                                    <input type="radio" name="time" id="inlineRadio1" value="183"> 6 months
                                </label>
                                <label class="radio-inline mr-3">
                                    <input type="radio" name="time" id="inlineRadio2" value="365"> 1 year
                                </label>
                                <label class="radio-inline mr-3">
                                    <input type="radio" name="time" id="inlineRadio3" value="730"> 2 years
                                </label>
                                <label class="radio-inline mr-3">
                                    <input type="radio" name="time" id="inlineRadio4" value="1095"> 3 years
                                </label>
                                <label class="radio-inline mr-3">
                                    <input type="radio" name="time" id="inlineRadio5" value="1825"> 5 years
                                </label>
                                <label class="radio-inline mr-3">
                                    <input type="radio" name="time" id="inlineRadio6" value="indefinite"> Indefinite
                                </label>
                            </div>
                        @else
                            @foreach($self_exclusion_options as $id_number => $self_exclusion_option)
                                <label class="radio-inline mr-3">
                                    <input type="radio" name="time" id="inlineRadio{{ $id_number }}" value="{{ $self_exclusion_option['duration'] }}"> {{ $self_exclusion_option['label'] }}
                                </label>
                            @endforeach
                        @endif
                    </div>

                    <!-- Intervention Cause -->
                    @if (lic('showInterventionTypes', [], $user->id))
                        <div class="form-group">
                            <label for="intervention_cause">Intervention Cause</label>
                            <select id="intervention_cause" class="form-control select2" style="width: 100%;" data-placeholder="Select one">
                                <option value="">Select one</option>
                                @foreach(\App\Helpers\DataFormatHelper::getInterventionCauses() as $key => $text)
                                    <option value="{{ $key }}">{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </form>
                <small id="help-block-self-exclusion" class="form-text text-muted mt-3">* Please choose carefully as changes cannot be undone.</small>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary self-exclusion-modal-close-btn" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary self-exclusion-modal-save-btn">Save changes</button>
            </div>
        </div>
    </div>
</div>

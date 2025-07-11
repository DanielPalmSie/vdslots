@if($method == 'wirecard' || $method == 'adyen' || $method == 'worldpay' || $method == 'credorax')
    <div class="form-group">
        <label for="card_id">Card</label>
        <select name="card_id" id="card_id" class="form-control select2" style="width: 100%;" data-placeholder="Select a card" data-allow-clear="true">
            <option></option>
            @foreach($form_data['cards_list'] as $card)
                @if ($card['active'] == 1)
                    <option value="{{ $card['id'] }}">{{ $card['card_num'] }}</option>
                @elseif ($card['active'] == -1)
                    <option value="{{ $card['id'] }}">{{ $card['card_num'] }} (Possible duplicate)</option>
                @elseif ($card['active'] == 0)
                    <option value="{{ $card['id'] }}">{{ $card['card_num'] }} (Deactivated)</option>
                @endif
            @endforeach
        </select>
    </div>
@else
    @foreach($form_data[$method] as $field)
        <div class="form-group">
            <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
            @if($field['name'] === 'actual_payment_method')
                <div class="form-group">
                    <select required name="{{ $field['name'] }}" id="provider_list" class="form-control" style="width: 100%;" data-placeholder="Select one from the list"
                            data-allow-clear="true" >
                        <option></option>
                         @foreach($bankProviders as $method)
                            <option value="{{ $method }}">{{ ucwords($method) }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($field['name'] === 'account')
                <?php
                    $accountColors = [
                        '1' => 'green',
                        '0' => 'darkorange',
                        '-1' => 'red',
                    ];
                ?>
                <div class="form-group">
                    <select required name="{{ $field['name'] }}" id="account" class="form-control" style="width: 100%;" data-placeholder="Select one from the list"
                            data-allow-clear="true">
                        <option value="">Select Account</option>
                        @foreach($form_data['accounts'] as $account)
                            <?php
                                $accountData = [
                                    'sub_supplier' => $account->subSupplier,
                                    'account_number' => $account->accountNumber,
                                    'account_external_id' => $account->externalId,
                                ];
                            ?>
                            <option
                                value="{{ json_encode($accountData) }}"
                                style="color: {{ $method !== Supplier::Swish ? $accountColors[$account->verificationStatus] : 'black' }}"
                            >
                                {{
                                    $method === Supplier::Swish ?
                                    $account->externalId :
                                    $account->subSupplier . ' - ' . $account->accountNumber
                                }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="text" id="{{ $field['validation'] }}" value="{{ $field['value'] }}" name="{{ $field['name'] }}" class="form-control"
                       placeholder="{{ $field['placeholder'] }}" onkeyup="validate('{{ $field['validation'] }}')">
            @endif

        </div>
    @endforeach
@endif

<script>
    function validate (input) {
            let fieldById = document.getElementById(input);
        if(fieldById != undefined){
            fieldById.value= fieldById.value.replace(/\s/g, "");
        }
    }
</script>

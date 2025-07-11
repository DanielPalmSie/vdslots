@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <form id="add-liability-adjustment-form"
          method="post"
          action="{{ $app['url_generator']->generate('admin.user-liability-adjust', ['user' => $user->id]) }}">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-6">
                <div class="card card-info border border-info">
                    <div class="card-header">
                        <h5 class="card-title">Add liability adjustment for: {{ $user->id }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="amount">Amount <b>in cents</b></label>
                            <input type="text" name="amount" class="form-control" id="deposit_amount_in_cents"
                                   placeholder="Amount in cents" maxlength="10" required>
                        </div>
                        <div class="form-group">
                            <label for="target_month">Month</label>
                            <select name="target_month"
                                    id="target_month"
                                    data-placeholder="Select a month"
                                    class="form-control"
                                    style="width: 100%;"
                                    data-allow-clear="true"
                                    required>
                                @foreach($target_month_list as $target_month)
                                    <option value="{{ $target_month }}">{{ $target_month }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="scheme">Description</label>
                            <input type="text" name="description" class="form-control"
                                   placeholder="Reason for the adjustment" maxlength="255" required>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div>
                            <h4>
                                <strong>Unallocated Amount</strong><br>
                            </h4>
                            <h5>
                                Current Month: <strong>{{ $unallocated_amounts['current']['unallocated'] ?? 0 }}
                                </strong> <br>
                                Previous Month: <strong>{{ $unallocated_amounts['previous']['unallocated'] ?? 0 }}
                                </strong>
                                <h4>
                                    <strong>Recommended adjustments</strong><br>
                                </h4>
                                <h5>
                                    Current Month: <strong>{{ ($unallocated_amounts['current']['unallocated'] ?? 0) * -1 }}
                                    </strong> <br>
                                    Previous Month: <strong>{{ ($unallocated_amounts['previous']['unallocated'] ?? 0) * -1 }}
                                    </strong>
                                </h5>
                            </h5>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button name="adjust_liability" class="btn btn-info" type="submit" id="adjust_liability"
                                @if(!$form_submittable) disabled @endif>
                            @if($form_submittable)
                                Adjust Liability
                            @else
                                Liability Adjustment Not Allowed In {{ $jurisdiction }}
                            @endif
                        </button>
                    </div>

                    @if($success !== null)
                        @if($success)
                            <div class="card-footer" style="background-color: #5cb85c">
                                <div>
                                    <h4>
                                        <strong>Liability Adjustment Successful</strong><br>
                                    </h4>
                                    <h5>
                                        Amount:
                                        <strong>
                                            {{ $app['request_stack']->getCurrentRequest()->get('amount') }}
                                        </strong> <br>
                                        Month:
                                        <strong>
                                            {{ $app['request_stack']->getCurrentRequest()->get('target_month') }}
                                        </strong><br>
                                        Description:
                                        <strong>
                                            {{ $app['request_stack']->getCurrentRequest()->get('description') }}
                                        </strong>
                                    </h5>
                                </div>
                            </div>
                        @else
                            <div class="card-footer" style="background-color: #d9534f">
                                <div>
                                    <h4>
                                        <strong>Liability Adjustment Failed</strong><br>
                                    </h4>
                                    <h5><strong>Errors:</strong></h5>
                                    @foreach($errors as $field => $field_errors)
                                        @foreach($field_errors as $error)
                                            <li><b> {{ $error }} </b></li>
                                        @endforeach
                                    @endforeach
                                    <h5>
                                        Amount:
                                        <strong>
                                            {{ $app['request_stack']->getCurrentRequest()->get('amount') }}
                                        </strong> <br>
                                        Month:
                                        <strong>
                                            {{ $app['request_stack']->getCurrentRequest()->get('target_month') }}
                                        </strong><br>
                                        Description:
                                        <strong>
                                            {{ $app['request_stack']->getCurrentRequest()->get('description') }}
                                        </strong>
                                    </h5>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </form>

@endsection

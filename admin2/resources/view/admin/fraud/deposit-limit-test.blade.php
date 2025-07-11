<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ $params['deposit_limit_test']['report_title'] }}</h3>
    </div><!-- /.card-header -->
    <div class="card-body">
        <table id="obj-datatable" class="table table-responsive table-striped table-bordered dt-responsive"
               cellspacing="0" width="100%">
            <thead>
            <tr>
                @foreach($params['deposit_limit_test']['columns'] as $k => $v)
                    <th>{{ $v }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($params['deposit_limit_test']['data'] as $element)
                <tr>
                    @foreach($element as $k => $v)
                        @if($k != id)
                            <td class="col-{{ $k }}">{{ $v }} </td>
                        @endif
                    @endforeach
                    @php
                        $id = $element->id;
                        $pass = "-pass";
                        $fail = "-fail";
                        $active_green = false;
                        $active_red = false;
                        $isDisabled = false;

                    @endphp
                    <td class="col-rg-evaluation">
                        @foreach($params['deposit_limit_test']['result_data'] as $elements => $element)
                            @php
                                $data = json_decode($element['descr']);
                                $printData = "$data->id-$data->result";
                                if($printData == $id.$pass){
                                    $active_green = true;
                                    $active_red = false;
                                    $isDisabled = true;
                                }
                                if($printData == $id.$fail){
                                    $active_red = true;
                                    $active_green = false;
                                    $isDisabled = true;
                                }
                            @endphp
                        @endforeach

                        <a href="{{ $app['url_generator']->generate('admin.rg-test-confirmation', ['user' => $user->id,
                    'action_id' => $id,
                    'user_id' => $user->id,
                    'result' => 'pass',
                    ]) }}" @class([
                            'btn',
                            'create-btn',
                            'btn-default',
                            'action-set-btn',
                            'pass-button' => $active_green,
                            'disabled' => $isDisabled
                            ])
                        data-dtitle="RG Test Confirmation"
                           data-dbody="Are you sure you want to PASS the user RG test for <b>{{ $user->id }}</b>?"
                           id="{{$id.$pass}}">
                            PASS
                        </a>

                        <a href="{{ $app['url_generator']->generate('admin.rg-test-confirmation', ['user' => $user->id,
                    'action_id' => $id,
                    'user_id' => $user->id,
                    'result' => 'fail',
                    ]) }}"
                           @class([
                            'btn',
                            'create-btn',
                            'btn-default',
                            'action-set-btn',
                            'fail-button' => $active_red,
                            'disabled' => $isDisabled,
                            ]) data-dtitle="RG Test Confirmation"
                           data-dbody="Are you sure you want to FAIL the user RG test for <b>{{ $user->id }}</b>?"
                           id="{{$id.$fail}}">
                            FAIL
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

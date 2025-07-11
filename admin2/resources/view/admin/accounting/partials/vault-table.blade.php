<div class="card card-solid card-primary">
        <div class="card-header">
            <h3 class="card-title">Player Vault Report @if(!empty($app['request_stack']->getCurrentRequest()->get('currency')))({{ $app['request_stack']->getCurrentRequest()->get('currency') }}) @endif</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered dt-responsive w-100 border-collapse"
                   id="liability-section-currency">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Country</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Net</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><b>Opening Vault Balance</b></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ \App\Helpers\DataFormatHelper::nf($opening_data['opening']) }}</td>
                </tr>
                @php

                   $totals = $data->groupBy('currency')->map(function ($group) {
                            $vaultBalance = $group->sum('vault_balance');
                            $fromVault = $group->sum('from_vault');
                            $toVault= $group->sum('to_vault');
                            return ['VaultBalance' => $vaultBalance, 'fromVault' => $fromVault, 'toVault' => $toVault];
                        });
                        $grouped = $data->groupBy('country');

                    @endphp
                @foreach($totals as $currency => $amounts)
                    <tr>
                        <td><a class="show-child-rows" data-currency="{{$currency}}"><i class="fa fa-caret-square-o-right"></i></a> {{$currency }}</td>
                        <td></td>
                        <td>{{$amounts['fromVault'] }}</td>
                        <td>{{$amounts['toVault'] }}</td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($amounts['VaultBalance']) }}</td>

                    </tr>
                    @foreach ($grouped as $singleData => $value)
                        @foreach ($value as $key => $data)
                            @if($currency == $data->currency)
                        <tr class="show-values-{{$currency}} d-none">
                            <td> </td>
                            <td> {{$singleData}} </td>
                            <td> {{  $data->from_vault }}</td>
                            <td> {{  $data->to_vault }}</td>
                            <td></td>
                            <td></td>
                            <td></td>

                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach

                </tbody>
                @if(count($data) > 0 || abs($closing_data['non_categorized_amount']) > 0)
                <tfoot>
                    <tr>
                        <td><b>Unallocated amount</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td @if(abs($closing_data['non_categorized_amount']) > 0)bgcolor="#f7bda8"@endif >
                            {{ \App\Helpers\DataFormatHelper::nf($closing_data['non_categorized_amount']) }}
                            @if(abs($closing_data['non_categorized_amount']) > 0 && empty($user))
                            <a href="{{ $app['url_generator']->generate('accounting-liability', array_merge($app['request_stack']->getCurrentRequest()->query->all(), ['m' => 1])) }}">
                                <b>[Breakdown]</b>
                            </a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><b>Total Net Liability</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['net_liability']) }}</td>
                    </tr>
                    <tr>
                        <td><b>Closing Vault Balance</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['closing_balance']) }}</td>
                    </tr>

                </tfoot>
                @endif
            </table>
        </div>
    </div>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $('.show-child-rows').on('click', function (e) {
            e.preventDefault();
            $('.show-values-'+ $(this).data('currency')).toggle();
        });
    </script>
@endsection

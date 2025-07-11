@extends('admin.layout')


@section('header-css')
    @parent
    <link rel="stylesheet" href="/phive/admin/customization/plugins/bootstrap4-editable/css/bootstrap-editable.css">
@endsection


@section('content')
    <style>
        .editableform .form-group.has-error {
            display: block;
            color: red;
        }
    </style>
    <div class="container-fluid">
        @include('admin.gamification.jackpot.partials.topmenu')

        @include('admin.partials.flash')

        <p><a href="{{ $app['url_generator']->generate('jackpots.getwinhistory') }}"><i class="fa fa-asterisk"></i> View Jackpots Winnings History</a></p>
        <div class="card card-solid card-primary">
            <div class="card-header with-border">
                <h3 class="card-title">List of Jackpots</h3>
            </div>

            <div class="card-body">
                <table class="table table-striped table-bordered">
                    <tr>
                        <th style="width: 20%">Name</th>
                        <th style="width: 20%">Jackpot Amount</th>
                        <th style="width: 20%">Jackpot Minimum Amount</th>
                        <th style="width: 20%">Contribution to New Jackpot</th>
                        <th style="width: 20%">Contribution Share</th>
                    </tr>
                    @foreach($jackpots as $key => $jackpot)
                    <tr>
                        <td><a class="pUpdate"
                                id="jackpot_name_{{ $key+1 }}"
                                data-pk="{{ $jackpot->id }}"
                                data-name='name'
                                data-title='Set jackpot name'
                                data-emptytext='Click to set jackpot name'
                                >{{ $jackpot->name }}</a>
                        </td>
                        <td><b>{{ $jackpot->amount }}</b></td>
                        <td>
                            <a class="pUpdate jackpot_amount_minimum"
                                id="jackpot_amount_minimum_{{ $key+1 }}"
                                data-pk="{{ $jackpot->id }}"
                                data-name='amount_minimum'
                                data-title='Set mimimum amount to be won'
                                data-emptytext='Click to set jackpot minimum amount'
                                >{{$jackpot->amount_minimum}}</a>
                        </td>
                        <td>
                            <a class="pUpdate contribution_share_nextjp"
                                id="jackpot_contribution_next_jp_{{ $key+1 }}"
                                data-pk="{{ $jackpot->id }}"
                                data-name='contribution_next_jp'
                                data-title='Set contribution for next jackpot'
                                data-emptytext='Click to set contribution for next jackpot'
                                >{{$jackpot->contribution_next_jp}}</a>
                        </td>
                        <td>
                            <a class="pUpdate contribution_share"
                                id="jackpot_contribution_share_{{ $key+1 }}"
                                data-pk="{{ $jackpot->id }}"
                                data-name='contribution_share'
                                data-title='Set contribution for jackpot'
                                data-emptytext='Click to set contribution share jackpot'
                                >{{$jackpot->contribution_share}}</a>
                        </td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="4">
                            Total contribution share:
                        </td>
                        <td>
                            <span id="total_contribution" class="red">0</span>
                        </td>
                    </tr>
                </table>
                <div style="padding-top:10px;">
                        <p>The total contribution share needs to be exactly 1.0000 (100%)</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/customization/plugins/bootstrap4-editable/js/bootstrap-editable.min.js"></script>
    <script type="text/javascript" src="/phive/admin/customization/scripts/jackpots.js"></script>
    <script type="text/javascript">
        var updateURL = "{{ $app['url_generator']->generate('jackpots.updatejackpots', ['jackpot_id' => $jackpot->id]) }}";
		var updateTotContribution = "{{ $app['url_generator']->generate('wheelofjackpots-gettotalcontribution') }}";
    </script>
@endsection


@extends('admin.layout')fghdfhdfhdf

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.jackpot.partials.topmenu')

        <div class="card card-solid card-primary">

            <div class="card-header">
                <h3 class="card-title">Jackpot Winning Log</h3>
            </div>

            <div class="card-body">
                <table class="table table-striped table-bordered">
                    @if(count($winhistlog)==0)
                    <tr>
                        <td colspan="4" align="center">The Wheel Of Jackpots was never spun.</td>
                    </tr>
                    @else
                    <tr>
                        <th style="width: 20%">Date</th>
                        <th style="width: 20%">UserId</th>
                        <th style="width: 20%">Currency</th>
                        <th style="width: 20%">Amount Won</th>
                        <th style="width: 20%">Description</th>
                    </tr>
                        @foreach($winhistlog as $key => $data)
                        <tr>
                            <td>{{$data->created_at}}</td>
                            <td>{{$data->user_id}}</td>
                            <td>{{$data->user_currency}}</td>
                            <td>{{$data->win_jp_amount}}</td>
                            <td>{{$data->description}}</td>
                        </tr>
                        @endforeach
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
@endsection


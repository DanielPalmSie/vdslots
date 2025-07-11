<table class="table table-hover">
    <thead>
    <tr>
        <th>Title</th>
        <th>Score</th>
    </tr>
    </thead>
    <tbody>
    @foreach($parent->found_children as $child)
        <tr>
            <td>
                <span>{{$child->title}}</span>

                <button class="btn pull-right collapse-button">
                    <i class="fa fa-plus"></i>
                </button>
                <div class="hidden col-xs-12 collapse-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th class="col-xs-1">Number of deposits</th>
                            <th class="col-xs-2">Total deposits amount</th>
                            <th class="col-xs-1">Number of withdrawals</th>
                            <th class="col-xs-2">Total withdrawals amount</th>
                            <th class="col-xs-1">Number of bets</th>
                            <th class="col-xs-2">Total bets amount</th>
                            <th class="col-xs-1">Number of wins</th>
                            <th class="col-xs-2">Total wins amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>{{ $child->metadata['deposits_count'] }}</td>
                            <td>{{ $child->metadata['deposits_sum'] ?  \App\Helpers\DataFormatHelper::nf($child->metadata['deposits_sum'],1) . ' EUR' : '-' }}</td>
                            <td>{{ $child->metadata['withdrawals_count'] }}</td>
                            <td>{{ $child->metadata['withdrawals_sum'] ?  \App\Helpers\DataFormatHelper::nf($child->metadata['withdrawals_sum'],1) . ' EUR' : '-' }}</td>
                            <td>{{ $child->metadata['bets_count'] }}</td>
                            <td>{{ $child->metadata['bets_sum'] ?  \App\Helpers\DataFormatHelper::nf($child->metadata['bets_sum'],1) . ' EUR' : '-' }}</td>
                            <td>{{ $child->metadata['wins_count'] }}</td>
                            <td>{{ $child->metadata['wins_sum'] ?  \App\Helpers\DataFormatHelper::nf($child->metadata['wins_sum'],1) . ' EUR' : '-' }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </td>
            <td>{{$child->score}}</td>
        </tr>
    @endforeach
    </tbody>
</table>

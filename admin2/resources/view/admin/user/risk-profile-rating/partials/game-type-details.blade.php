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
                <span class="badge pull-right" style="margin-top: 10px;margin-right: 10px;">{{$child->metadata['percent']}} %</span>
                <div class="hidden col-xs-12 collapse-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th class="col-xs-6">Total bets</th>
                            <th class="col-xs-6">Total wins</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>{{ $child->metadata['total_bets'] ?  \App\Helpers\DataFormatHelper::nf($child->metadata['total_bets'], 1) . ' EUR' : '-' }}</td>
                            <td>{{ $child->metadata['total_wins'] ?  \App\Helpers\DataFormatHelper::nf($child->metadata['total_wins'], 1) . ' EUR' : '-' }}</td>
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

<table class="table table-hover table-borderless">
    <thead>
    <tr>
        <th>Title</th>
        <th>Score</th>
    </tr>
    </thead>
    <tbody>
    @foreach($parent->found_children as $child)
        <?php list($w, $d) = $child->metadata;  ?>
        <tr>
            <td>
                <span>{{$child->title}}</span>

                <button class="btn pull-right collapse-button">
                    <i class="fa fa-plus"></i>
                </button>
                <span class="badge pull-right" style="margin-top: 10px;margin-right: 10px;">{{$d['percent']}} %</span>
                <div class="hidden col-xs-12 collapse-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th class="col-xs-3">Total amount of deposit transactions</th>
                            <th class="col-xs-3">Total amount of withdraw transactions</th>
                            <th class="col-xs-3">Total deposited sum</th>
                            <th class="col-xs-3">Total withdrawn sum</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>{{$d['count'] ?? '-'}}</td>
                            <td>{{$w['count'] ?? '-'}}</td>
                            <td>{{ $d['sum'] ?  \App\Helpers\DataFormatHelper::nf($d['sum'], 1) . ' EUR' : '-' }}</td>
                            <td>{{ $w['sum'] ?  \App\Helpers\DataFormatHelper::nf($w['sum'], 1) . ' EUR' : '-' }}</td>
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

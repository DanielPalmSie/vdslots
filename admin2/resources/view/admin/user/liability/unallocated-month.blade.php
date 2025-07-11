@if(empty($data))
    <div class="callout callout-info">
        <h4>Info</h4>
        <p>No differences found</p>
    </div>
@else
    <div class="card card-primary border border-primary">
        <div class="card-header">
            <h3 class="card-title">Unallocated amount breakdown</h3>
            @if(p('user.liability.transactions.download.csv'))
                <a class="float-right" style="color: white" href="{{
                    $app['url_generator']->generate('admin.user-liability',
                        [
                            'user' => $user->id,
                            'export_monthly_transactions' => 1,
                            'type' => 'monthly',
                            'year' => !empty($date->format('m')) ?
                                $date->format('Y') : \Carbon\Carbon::now()->format('Y'),
                            'month' => !empty($month = $app['request_stack']->getCurrentRequest()->get('month')) ?
                                $date->format('m') : \Carbon\Carbon::now()->format('m')
                        ]) }}">
                    <i class="fa fa-download"></i>
                    Download All
                </a>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Opening balance</th>
                        <th>Net liability</th>
                        <th>Closing balance</th>
                        <th>Unallocated amount</th>
                        <th>Display transactions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($data as $elem)
                        <tr>
                            <td>{{ $elem['date'] }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($elem['opening']) }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($elem['liability']) }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($elem['closing']) }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($elem['unallocated']) }}</td>
                            <td><a class="liab-unallocated-transaction-list-link" data-transactions="liability" data-date="{{ $elem['date'] }}" href="javascript:void(0);"> Liability related</a>
                                - <a class="liab-unallocated-transaction-list-link" data-transactions="all" data-date="{{ $elem['date'] }}" href="javascript:void(0);"> All</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
<div id="user-liability-sub-content">

</div>


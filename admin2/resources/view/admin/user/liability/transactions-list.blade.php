<div class="card card-primary border border-primary">
    <div class="card-header">
        <h3 class="card-title">Player Liability Transaction List</h3>
        @if(count($page['data']) > 0 && p('user.liability.transactions.download.csv'))
            <a href="{{ $app['url_generator']->generate('admin.user-liability', ['user' => $user->id, 'export' => 1, 'type' => 'daily', 'day' => $day, 'filter' => $filter]) }}"
               class="float-right"><i class="fa fa-download"></i> Download
            </a>
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="liability-transaction-list-datatable" class="table table-striped table-bordered"
                   cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Transaction Id</th>
                    <th>Amount (cents)</th>
                    <th>Balance (cents)</th>
                    <th>Calculated Balance</th>
                    <th>Difference</th>
                    <th>Description</th>
                    <th>More Info.</th>
                </tr>
                </thead>
                <tbody>
                @foreach($page['data'] as $element)
                    <tr>
                        <td>{{ $element->date }}</td>
                        <td>{{ $element->type }}</td>
                        <td>{{ $element->id }}</td>
                        <td>{{ $element->amount }}</td>
                        <td>{{ $element->balance }}</td>
                        <td>{{ $element->running_balance }}</td>
                        <td>{{ $element->difference }}</td>
                        <td>{{ $element->description }}</td>
                        <td>{{ $element->more_info }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

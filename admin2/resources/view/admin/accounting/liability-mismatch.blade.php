@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.liability-filter')
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li><a href="{{ $app['request_stack']->getCurrentRequest()->headers->get('referer') }}">
                    Player Liability Report @if(!empty($app['request_stack']->getCurrentRequest()->get('currency')))({{ $app['request_stack']->getCurrentRequest()->get('currency') }}) @endif
                </a>
            </li>
            <li class="active"><a>Unallocated Amount Breakdown ({{ $app['request_stack']->getCurrentRequest()->get('currency') }})</a></li>
            @if(count($data) > 0 && p('accounting.section.liability.download.csv'))
                <li class="float-right"><a href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_data, ['m' => 1]) }}"><i class="fa fa-download"></i> Download</a></li>
            @endif
        </ul>
        <div class="tab-content">
            <div class="tab-pane active">
                <div class="table-responsive">
                    <table id="plr-mismatches-datatable"
                           class="table table-responsive table-striped table-bordered dt-responsive w-100 border-collapse">
                        <thead>
                        <tr>
                            <th>User Id</th>
                            <th>Username</th>
                            <th>Opening Balance</th>
                            <th>Net Liability</th>
                            <th>Closing Balance</th>
                            <th>Unallocated Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($data as $row)
                            <tr>
                                <td>{{ $row->user_id }}</td>
                                <td><a target="_blank" href="{{ $app['url_generator']->generate('admin.user-liability', [
                                        'user' => $row->user_id,
                                        'type' => 'monthly',
                                        'year' => $query_data['year'],
                                        'month' => $query_data['month']
                                    ])}}">
                                        {{ $row->username }}
                                    </a>
                                </td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($row->opening) }}</td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($row->net_liab) }}</td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($row->closing) }}</td>
                                <td><b><span @if($row->diff < 0) class="text-danger" @endif>{{ \App\Helpers\DataFormatHelper::nf(abs($row->diff)) }}</span></b></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            var table = $("#plr-mismatches-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 5, "desc"]]
            });
        });
    </script>
@endsection

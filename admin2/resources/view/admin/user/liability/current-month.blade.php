<div class="card card-primary border border-primary">
    <div class="card-header">
        <h3 class="card-title">Player Liability Report [Current month]</h3>
        {{--@if(count($data) > 0 && p('user.liability.report.download.csv'))
            <a style="float: right" href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_data, ['breakdown' => 1]) }}"><i class="fa fa-download"></i> Download including breakdown</a>
            <a style="margin-right: 10px; float: right" href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_data) }}"><i class="fa fa-download"></i> Download</a>
        @endif--}}
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered dt-responsive"
                   id="liability-section-currency" cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Net</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><b>Opening Balance</b></td>
                    <td colspan="2"></td>
                    <td>{{ \App\Helpers\DataFormatHelper::nf($opening_data['net']) }}</td>
                </tr>
                @foreach($data as $k => $v)
                    @if (is_array($v))
                        @if (!empty($v['in']))
                            <tr>
                                <td>{{ \App\Repositories\LiabilityRepository::getLiabilityCategoryName($k) }} (IN)</td>
                                <td></td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($v['in']) }}</td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($v['in']) }}</td>
                            </tr>
                        @endif
                        @if (!empty($v['out']))
                            <tr>
                                <td>{{ \App\Repositories\LiabilityRepository::getLiabilityCategoryName($k) }} (OUT)</td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf(abs($v['out'])) }}</td>
                                <td></td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($v['out']) }}</td>
                            </tr>
                        @endif
                    @else
                        <tr>
                            <td>{{ \App\Repositories\LiabilityRepository::getLiabilityCategoryName($k) }}</td>
                            @if ($v > 0)
                                <td>{{ \App\Helpers\DataFormatHelper::nf(abs($v)) }}</td>
                                <td></td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($v) }}</td>
                            @elseif($v < 0)
                                <td></td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($v) }}</td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($v) }}</td>
                            @endif
                        </tr>
                    @endif
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td><b>Unallocated amount</b></td>
                    <td colspan="2"></td>
                    <td @if(abs($closing_data['non_categorized_amount']) > 0)bgcolor="#f7bda8"@endif>{{ \App\Helpers\DataFormatHelper::nf($closing_data['non_categorized_amount']) }}</td>
                </tr>
                <tr>
                    <td><b>Total Net Liability</b></td>
                    <td colspan="2"></td>
                    <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['net_liability'] + $closing_data['non_categorized_amount']) }}</td>
                </tr>
                <tr>
                    <td><b>Closing Player Liability</b></td>
                    <td colspan="2"></td>
                    <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['net_liability'] + $closing_data['non_categorized_amount'] + $opening_data['net']) }}</td>
                </tr>
                @if($app['debug'])
                    <tr>
                        <td><b>Closing Balance</b></td>
                        <td colspan="2"></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['closing_balance']) }}</td>
                    </tr>
                @endif
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if($app['debug'])
<div class="card card-primary">
    <div class="card-body">
    @include('admin.partials.content-profilling')
    </div>
</div>
@endif
@if($app['slow-queries']) <?php \App\Helpers\Common::logSlowQueries($app); ?> @endif

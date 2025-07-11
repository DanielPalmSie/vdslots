<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Gaming Revenue Report</h3>
        @if(count($data_array['result']) > 0 && p('accounting.section.gaming-revenue.download.csv') )
            <a target="_blank" class="mr-2 float-right"
               href="{{ \App\Helpers\DownloadHelper::generateDownloadPath([
                   'month-range-start' => $month_range->getStart()->format('Y-m'),
                   'month-range-end' => $month_range->getEnd()->format('Y-m'),
                   'jurisdiction' => $jurisdiction,
                   ]) }}">
                <i class="fa fa-download"></i> Download
            </a>
        @endif
    </div>
    <div class="card-body">
        <table class="table table-striped table-bordered dt-responsive w-100 border-collapse"
               id="liability-section-currency">
            <thead>
            <tr>
                <th>Currency</th>
                <th>Wagers</th>
                <th>Total Winnings</th>
                <th>Bonus wagers and other incentives</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data_array['result'] as $currency => $element)
                <tr>
                    <td>{{$currency}}</td>
                    <td>{{\App\Helpers\DataFormatHelper::nf(abs($element['wagers']->amount))}}</td>
                    <td>{{\App\Helpers\DataFormatHelper::nf($element['total_winnings']->amount)}}</td>
                    <td>{{\App\Helpers\DataFormatHelper::nf(abs($element['wagers_and_other_incentives']->amount))}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

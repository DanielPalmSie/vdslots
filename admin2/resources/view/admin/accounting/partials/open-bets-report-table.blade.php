<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Open Bets Report</h3>
        @if(count($data_array['result']) > 0 && p('accounting.section.open-bets.download.csv') )
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
                <th>Number of Bets</th>
                <th>Total Amount Open</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data_array['result'] as $currency => $element)
                <tr>
                    <td>{{$currency}}</td>
                    <td>{{$element['number_bets']}}</td>
                    <td>{{\App\Helpers\DataFormatHelper::nf($element['total_amount_open'])}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

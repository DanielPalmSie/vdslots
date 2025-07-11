

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><b>{{ ucwords($title) }}</b></h3>
    </div>
    <div class="card-body p-0">
        <table class="table">
            <tbody>
            <tr>
                <th></th>
                <th><b>During period</b></th>
                <th><b>All time</b></th>
            </tr>
            <tr>
                <td>Deposits</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($period['dep_amount']) }}</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($total['dep_amount']) }}</td>
            </tr>
            <tr>
                <td># Deposits</td>
                <td>{{ !isset($period['dep_total']) ? 0 : $period['dep_total'] }}</td>
                <td>{{ !isset($total['dep_total']) ? 0 : $total['dep_total'] }}</td>
            </tr>
            <tr>
                <td># Unique deposits</td>
                <td>{{ !isset($period['dep_unique']) ? 0 : $period['dep_unique'] }}</td>
                <td>{{ !isset($total['dep_unique']) ? 0 : $total['dep_unique'] }}</td>
            </tr>
            <tr>
                <td>Withdrawals</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($period['w_amount']) }}</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($total['w_amount']) }}</td>
            </tr>
            <tr>
                <td># Withdrawals</td>
                <td>{{ !isset($period['w_total']) ? 0 : $period['w_total'] }}</td>
                <td>{{ !isset($total['w_total']) ? 0 : $total['w_total'] }}</td>
            </tr>
            <tr>
                <td># Unique withdrawals</td>
                <td>{{ !isset($period['w_unique']) ? 0 : $period['w_unique'] }}</td>
                <td>{{ !isset($total['w_unique']) ? 0 : $total['w_unique'] }}</td>
            </tr>
            <tr>
                <td>Total transactions</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($period['total_trans']) }}</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($total['total_trans']) }}</td>
            </tr>
            <tr>
                <td>Deducted fee</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($period['deducted_fees']) }}</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($total['deducted_fees']) }}</td>
            </tr>
            <tr>
                <td>Transfer fees</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($period['transfer_fees']) }}</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($total['transfer_fees']) }}</td>
            </tr>
            <tr>
                <td>Effective</td>
                <td>{{ !isset($period['effective']) ? 0 : $period['effective'] }} %</td>
                <td>{{ !isset($total['effective'])? 0 : $total['effective'] }} %</td>
            </tr>
            <tr>
                <td>Balance on account</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($period['balance']) }}</td>
                <td>{{ $currency }} {{ \App\Helpers\DataFormatHelper::nf($total['balance']) }}</td>
            </tr>
            </tbody></table>
    </div>
    <!-- /.card-body -->
</div>

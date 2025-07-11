@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.site-balance-filter')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Site Balance Report @if(!empty($query_data['currency'])) | Currency: {{ $query_data['currency'] }} @endif @if(!empty($query_data['country'])) | Country: {{ $query_data['country'] }} @endif</h3>
            @if(!empty($data) && p('accounting.section.balance.download.csv'))
                <a class="mr-2 float-right" href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_data) }}"><i class="fa fa-download"></i> Download</a>
            @endif
        </div><!-- /.card-header -->
        <div class="card-body">
            <table class="table table-striped table-bordered dt-responsive w-100 border-collapse"
                   id="liability-section-currency">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Cash balance <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="right" title="Pending included"></i></th>
                    @if ($query_data['country'] == 'all')
                        <th>Pending balance</th>
                    @endif
                    @if ($use_bonus_balance)
                        <th>Bonus balance</th>
                        <th>Extra balance <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="right" title="Including Booster Vault"></i></th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @foreach($data as $element)
                    <tr>
                        <td>{{ $element['date'] }}</td>
                        <td>{{ empty($element['real']) ? 0 : \App\Helpers\DataFormatHelper::nf($element['real']) }}</td>
                        @if ($query_data['country'] == 'all')
                            <td>{{ empty($element['pending']) ? 0 : \App\Helpers\DataFormatHelper::nf($element['pending']) }}</td>
                        @endif
                        @if ($use_bonus_balance)
                            <td>{{ empty($element['bonus']) ? 0 : \App\Helpers\DataFormatHelper::nf($element['bonus']) }}</td>
                            <td>{{ empty($element['extra']) ? 0 : \App\Helpers\DataFormatHelper::nf($element['extra']) }}</td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div><!-- /.card-body -->
    </div>

@endsection


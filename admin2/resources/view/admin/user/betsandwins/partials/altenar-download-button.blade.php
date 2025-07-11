@if(p('view.account.betswins.download.csv'))
    <a href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_data, ['vertical_export' => 'altenar']) }}">
        <i class="fa fa-download"></i>
        Download
    </a>
@endif

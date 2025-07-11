<table id="sms-campaigns-list-datatable" class="table table-striped table-bordered"
       cellspacing="0" width="100%">
    <thead>
    <tr>
        <th>Sent Time</th>
        <th>Template name</th>
        <th>Bonus</th>
        <th>Voucher Name</th>
        <th>Status</th>
        <th>Contacts filter</th>
        <th>No. of filtered contacts</th>
        <th>No. of messages sent</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php $contact_list_permission = p('messaging.contacts'); ?>
    @foreach($past_list as $schedule)
        <tr>
            <td>{{ $schedule->sent_time }}</td>
            <td>{{ $schedule->template_name }}</td>
            <td>{{ $schedule->bonus_name }}</td>
            <td>{{ $schedule->voucher_name }}</td>
            <td>{{ $schedule->getStatusName() }}</td>
            @if ($contact_list_permission)
                <td>
                    <a href='{{ $app['url_generator']->generate("messaging.contact.list-contacts", ['filter-id' => $schedule->named_search_id]) }}'>{{ $schedule->named_search_name ?? $schedule->contacts_list_name  }}</a>
                </td>
            @else
                <td>{{ $schedule->named_search_name ?? $schedule->contacts_list_name }}</td>
            @endif
            <td>{{ $schedule->contacts_count }}</td>
            <td>{{ $schedule->sent_count }}</td>
            <td>
                <a href="{{ $app['url_generator']->generate("messaging.campaigns.stats", ['campaign-id' => $schedule->id]) }}"><i class="far fa-chart-bar"></i> Stats</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#sms-campaigns-list-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 0, "desc"]],
                "columnDefs": [{"targets": 7, "orderable": false, "searchable": false}]
            });

        });
    </script>
@endsection

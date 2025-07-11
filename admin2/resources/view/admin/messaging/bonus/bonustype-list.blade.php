<table id="bonus-type-list-databable" class="table table-striped table-bordered" cellspacing="0" width="100%">
    <thead>
    <tr>
        <th>ID</th>
        <th>Expire Time</th>
        <th>Bonus Name</th>
        <th>Bonus Type</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @foreach($data as $element)
        <tr>
            <td>{{ $element['id'] }}</td>
            <td>{{ $element['expire_time'] }}</td>
            <td>{{ $element['bonus_name'] }}</td>
            <td>{{ $element['bonus_type'] }}</td>
            <td>
                <a href="{{ $app['url_generator']->generate('messaging.bonus.create-template', ['step' => 2, 'bonus-id' => $element['id']]) }}">
                    <i class="far fa-circle"></i> Select</a> -
                <a href="#" class="detail-link" data-bonus="{{ $element['id'] }}" data-table="bonus_types"><i
                            class="fa fa-eye"></i> View details</a></td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('admin.messaging.bonus.partials.bonus-details-modal')

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#bonus-type-list-databable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[0, "desc"]],
                "columnDefs": [{"targets": 4, "orderable": false, "searchable": false}]
            });
        });
    </script>
@endsection
@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $(".fraud-section-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]]
            });
        });
    </script>

@endsection
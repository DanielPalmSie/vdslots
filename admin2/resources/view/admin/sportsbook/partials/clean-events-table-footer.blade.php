@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $(".clean-events-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                }
            });
        });
    </script>

@endsection
@section('footer-javascript')
    @parent
    <script>
        $(function () {
            const $orderSelect = $('#select-order');
            const orderValue = ($orderSelect.val() || 'desc').toLowerCase();

            const tableOptions = {
                paging: false,
                ordering: true,
                order: [[2, orderValue]],
                language: {
                    emptyTable: "No results found.",
                    lengthMenu: "Display _MENU_ records per page"
                }
            };

            const tableIds = [
                '#user-bets-datatable',
                '#user-wins-datatable',
                '#user-betsandwins-datatable',
                '#user-transactions-datatable'
            ];

            const tables = tableIds
                .filter(id => $(id).length)
                .map(id => {
                    const $table = $(id);
                    const hasRows = $table.find('tbody tr').length > 0;

                    return $table.DataTable({
                        ...tableOptions,
                        responsive: hasRows
                    });
                });

            $orderSelect.on('change', function() {
                const newOrderValue = $(this).val().toLowerCase();
                tables.forEach(table => table.order([0, newOrderValue]).draw());
            });
        });
    </script>
@endsection

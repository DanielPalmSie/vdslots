<div id="detail-view-modal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detailed View</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@section('header-css')
    @parent
    <style>
        .modal .modal-dialog { width: 800px; }
    </style>
@endsection

@section('footer-javascript')
    @parent
    <script>
            $('.detail-link').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.bonus.get-bonus-type-details') }}",
                    type: "POST",
                    data: {bonus: $(this).data('bonus'), table: $(this).data('table')},
                    success: function (response, textStatus, jqXHR) {
                        $(".modal-body").html(response['html']);
                        $('#detail-view-modal').modal('show');
                        return false;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });
    </script>
@endsection
<div class="modal fade" id="descriptionModal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="descriptionModalTitle"></h4>
            </div>
            <div class="modal-body">
                <p id="descriptionModalContent"></p>
            </div>
        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        function descriptionModal(id, description) {
            $('#descriptionModalTitle').html(`<strong>Description</strong>: #${id}`);
            $('#descriptionModalContent').html(description);
            $('#descriptionModal').modal('show');
        }
    </script>
@endsection

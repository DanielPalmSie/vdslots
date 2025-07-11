<div id="check-delete-modal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Info</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div id="modal-confirm-msg"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-flat btn-ok" data-action="show">Yes</button>
                <button type="button" class="btn btn-danger btn-flat btn-close" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(document).ready(function() {
            $('.href-confirm').on('click', function (e) {
                e.preventDefault();
                var self = $(this);

                var previous_content = self.html();
                self.html('Processing...');
                self.prop('disabled', true);

                var modal_alert = $('#modal-confirm-msg');
                modal_alert.html('<p><b>'+self.data('message')+'</b></p>');

                $('#check-delete-modal').modal({
                    show: true
                }).one('click','.btn-ok', function(){
                    $('#check-delete-modal').modal('hide');
                    location.replace(self.attr('href'));
                }).one('click','.btn-close',function() {
                    self.prop('disabled', false);
                    self.html(previous_content);
                }).on('hide.bs.modal', function(e){
                    self.prop('disabled', false);
                    self.html(previous_content);
                });
            });
        });
    </script>
@endsection

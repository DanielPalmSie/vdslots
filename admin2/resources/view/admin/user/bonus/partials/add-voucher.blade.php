<div class="row">
    <div class="col-6">
        <form action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get" class="d-flex w-100 h-100">
            <div class="card border-top border-top-3 flex-fill">
                <div class="card-header">
                    <h3 class="card-title">Filter</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 col-lg-4">
                            <label>From</label>
                            <input autocomplete="off"  data-date-format="yyyy-mm-dd"
                                   type="text"
                                   name="start_date"
                                   class="form-control daterange-picker" placeholder="Date"
                                   value="{{ \Carbon\Carbon::parse($sort['start_date'])->format('Y-m-d') }}">
                        </div>
                        <div class="col-6 col-lg-4">
                            <label>To</label>
                            <input autocomplete="off" data-date-format="yyyy-mm-dd"
                                   type="text"
                                   name="end_date"
                                   class="form-control daterange-picker" placeholder="Now"
                                   value="{{ isset($sort['end_date']) ? \Carbon\Carbon::parse($sort['end_date'])->format('Y-m-d') : null}}">
                        </div>

                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info">Search</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-6">
        <form id="voucher-form" method="post" class="d-flex w-100 h-100">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <div class="card border-top border-top-3 flex-fill">
                <div class="card-header"><h3 class="card-title">Redeem voucher</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 col-lg-4">
                            <div id="vc-group" class="form-group @if(!empty($error)) has-error @endif">
                                <label for="vcode">Voucher code</label>
                                <input id="code-input" type="text" name="vcode" class="form-control" placeholder="Code" aria-describedby="helpBlockError">
                                <span id="helpBlockError" class="help-block">{{ !empty($error) ? $error : null }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button formaction="{{ $app['url_generator']->generate('admin.user-vouchers', ['user' => $user->id]) }}"
                            id="voucher-btn" class="btn btn-info" type="submit">Submit
                    </button>
                    <h4 id="ajax-message-vocuher" class="d-none"></h4>
                </div><!-- /.box-footer-->
            </div>
        </form>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $('.daterange-picker').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });

            $('#voucher-btn').click(function (e) {
                e.preventDefault();
                var self = $(this);
                $.ajax({
                    url: self.attr('formaction'),
                    type: "POST",
                    data: $('#voucher-form').serialize(),
                    success: function (data, textStatus, jqXHR) {
                        response = data;
                        $("#helpBlockError").text(response['message']);
                        if (response['success'] == true) {
                            $("#vc-group").addClass('has-success');
                            $('#code-input').val('');
                        } else {
                            $("#vc-group").addClass('has-error');
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });
        });
    </script>
@endsection

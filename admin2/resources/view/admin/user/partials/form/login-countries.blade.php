<form id="allowed_country_form" class="form-horizontal"
      action="{{ $app['url_generator']->generate('admin.user-allowed-countries', ['user' => $user->id]) }}"
      method="post">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <div class="card border-top border-top-3">
        <div class="card-header">
            <h3 class="card-title">Allowed login countries</h3>
        </div><!-- /.box-header -->
        <div class="card-body">
            <table class="table table-striped">
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Country</th>
                    <th style="width: 40px">Action</th>
                </tr>
                @foreach($user->settings_repo->getAllowedLoginCountries($user) as $allowed_iso => $allowed_name)
                    <tr>
                        <td></td>
                        <td>{{ $allowed_name }}</td>
                        <td>@if(p('login.country.manage'))
                                <a name="delete_allowed_country" href="javascript:void(0)" data-iso="{{ $allowed_iso }}"><span
                                            class="badge badge-danger">Delete</span></a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div><!-- /.box-body -->
        @if(p('login.country.manage'))
            <div class="card-footer">
                <div class="row"><div class="col-12"><span id="ajax-message"></span></div></div>
                <div class="row">
                    <div class="col-6">
                        <select class="form-control login-countries-select2" style="width: 100%;" name="iso" data-placeholder="Select an option">
                            <option></option>
                            @foreach($user->settings_repo->getAllBankCountries() as $country)
                                <option id="opt-country-val-{{ $country->iso }}" value="{{ $country->iso }}">{{ $country->printable_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info">Add new country</button>
                </div>
            </div>
        @endif
    </div>
</form>

@section('footer-javascript')
    @parent
    <script>
        $(function () {

            var select2_a_country = $(".login-countries-select2").select2();

            $('#allowed_country_form').submit(function (e) {
                e.preventDefault();
                var self = $(this);

                var countryName = $('#opt-country-val-' + select2_a_country.val()).text();

                Swal.fire({
                    title: 'Add Country',
                    html: `Are you sure you want to add <b>${countryName}</b> as an allowed login country?`,
                    icon: 'question',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    customClass: {
                        confirmButton: 'btn btn-primary w-25',
                        cancelButton: 'btn btn-danger w-25',
                        actions: 'd-flex justify-content-around mt-3 w-50',
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: self.attr('action'),
                            type: "POST",
                            data: self.serialize(),
                            success: function (data, textStatus, jqXHR) {
                                const response = jQuery.parseJSON(data);
                                $("#ajax-message").text(response['message']);
                                if (response['success'] === true) {
                                    location.reload();
                                }
                            },
                            error: function () {
                                displayNotifyMessage('warning', 'AJAX ERROR');
                            }
                        });
                    }
                });
            });


            $('#allowed_country_form a').click(function (e) {
                e.preventDefault();
                var self = $(this);

                Swal.fire({
                    title: 'Remove Country',
                    text: 'Are you sure you want to remove this as an allowed login country?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary',
                        actions: 'd-flex justify-content-around mt-3 w-50',
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ $app['url_generator']->generate('admin.user-delete-allowed-country', ['user' => $user->id]) }}',
                            type: "POST",
                            data: { iso: self.data('iso') },
                            success: function (data, textStatus, jqXHR) {
                                const response = jQuery.parseJSON(data);
                                if (response['success'] === true) {
                                    location.reload();
                                }
                            },
                            error: function () {
                                displayNotifyMessage('warning', 'AJAX ERROR');
                            }
                        });
                    }
                });
            });


        });
    </script>
@endsection

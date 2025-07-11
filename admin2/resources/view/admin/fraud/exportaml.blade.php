@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    Fraud Section
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fraud</a></li>
                    <li class="breadcrumb-item active" aria-current="page">GoAML</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')

    <div class="modal fade" id="confirm-export-dialog" tabindex="-1" role="dialog" aria-labelledby="myModalExportLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalExportLabel">Confirm Export</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>You are about to export a goAML report</p>
                    <p>Do you want to proceed?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" id="confirm-export" class="btn btn-danger" data-dismiss="modal">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ $app['url_generator']->generate('fraud.go-aml') }}" method="post" id = 'export-form'>
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">goAML export</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-xl-2">
                        <label>User ID</label>
                        <input autocomplete="off" type="text" name="user_id"
                               class="form-control" placeholder="User ID">
                    </div>
                    <div class="form-group col-4 col-xl-2">
                        <label for="jurisdiction">Jurisdiction</label>
                        <select name="jurisdiction" id="jurisdiction-select" class="form-control select2"
                                data-placeholder="Jurisdiction" data-allow-clear="false">
                            <option value="SE">Sweden</option>
                            <option value="DK">Denmark</option>
                            <option value="MT">Malta</option>
                        </select>
                    </div>

                </div>

                <div class="row">
                    <div class="form-group col-4 col-xl-2">
                        <label for="jurisdiction">Report type</label>
                        <select name="report_type" id="report-type-select" class="form-control select2"
                                data-placeholder="" data-allow-clear="false">
                            <option value="STR" selected>STR</option>
                            <option value="SAR">SAR</option>
                        </select>

                    </div>
                </div>

                <div class="row">
                    <div class="col-6 col-xl-6">
                        <label>Indicators
                            <span class='float-right text-secondary'><span id = 'char-limit'>4000</span> characters left (4000 character limit)</span>
                            <textarea autocomplete="off" name="indicators"  cols = "95" rows = "10" id = "go-aml-indicators"
                                      class="form-control" placeholder="Indicators" maxlength="4000" ></textarea>
                        </label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 col-xl-8">
                       <label>Comment
                           <span class='float-right text-secondary'><span id = 'char-limit'>4000</span> characters left (4000 character limit)</span>
                        <textarea autocomplete="off" name="comment"  cols = "95" rows = "10" id = "go-aml-comment"
                      class="form-control" placeholder="Comment" maxlength="4000" ></textarea>
                       </label>
                    </div>
                </div>

                <hr>
                <div class="row">
                    <div class="col-6 col-xl-8">
                    <h2>Please write the description that will be used for deposits and withdrawals</h2>

                    <labe>You can use replacers from the list</labe>

                        <p> <b>__PROVIDER_NAME__</b>  which will be replaced with the name of the provider of the transaction </p>
                        <p> <b>__ACCOUNT_NUMBER__</b>  which will be replaced with credit card number or bank account number depending on the type </p>
                        <p> <b>__PAYMENT_TYPE__</b>  which will be replaced with the type of the transaction</p>

                    </div>
                    <div class="col-6 col-xl-6">
                        <label>Deposit Description
                            <span class='float-right text-secondary'><span id = 'char-limit'>4000</span> characters left (4000 character limit)</span>
                            <textarea autocomplete="off" name="deposit_description"  cols = "95" rows = "10" id = "go-aml-comment"
                                      class="form-control" placeholder="Deposit Description" maxlength="4000" ></textarea>
                        </label>
                    </div>

                    <div class="col-6 col-xl-6">
                        <label>Withdrawal Description
                            <span class='float-right text-secondary'><span id = 'char-limit'>4000</span> characters left (4000 character limit)</span>
                            <textarea autocomplete="off" name="withdraw_description"  cols = "95" rows = "10" id = "go-aml-comment"
                                      class="form-control" placeholder="Withdrawal Description" maxlength="4000" ></textarea>
                        </label>
                    </div>
                </div>


                <h3>User Reporting on</h3>
                <div class="row">

                @foreach($user_fields as $user_field => $value)
                    <div class="col-6 col-xl-2">
                        <label>{{$value}}</label>
                        <input autocomplete="off" type="text" name="{{$user_field}}"
                               class="form-control" placeholder="{{$value}}">
                    </div>
                    @endforeach
                </div>
                <h4>User Reporting on phone number</h4>
                <div class="row">
                @foreach($phones_fields as $phone_key => $value)
                    <div class="col-6 col-xl-2">
                        <label>{{$value}}</label>
                        <input autocomplete="off" type="text" name="{{$phone_key}}"
                               class="form-control" placeholder="{{$value}}">
                    </div>
                    @endforeach
                </div>
                <h4>User Reporting on address</h4>
                <div class="row">
                @foreach($addresses_fields as $address_key => $value)
                    <div class="col-6 col-xl-2">
                        <label>{{$value}}</label>
                        <input autocomplete="off" type="text" name="{{$address_key}}"
                               class="form-control" placeholder="{{$value}}">
                    </div>
                    @endforeach
                </div>
                <h4>User Reporting on Identification</h4>
                <div class="row">
                @foreach($identification as $identification_key => $value)
                    <div class="col-6 col-xl-2">
                        <label>{{$value}}</label>
                        <input autocomplete="off" type="text" name="{{$identification_key}}"
                               class="form-control" placeholder="{{$value}}">
                    </div>
                    @endforeach
                </div>
            </div><!-- /.card-body -->
            <div class="card-footer">
                <button class="btn btn-info" id = 'export-report-btn' data-toggle="modal" data-target="#confirm-export-dialog">Export Report</button>
            </div><!-- /.card-footer-->
        </div>
    </form>

@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script>
        $('#export-report-btn').on('click', function(e) {
            e.preventDefault();
        });

        $('#confirm-export').on('click', function(e) {
            $('#export-form').submit();
        });

        $('#jurisdiction-select').select2();
        $('#report-type-select').select2();

        $('#go-aml-comment').keyup(function () {
            const text_max = 4000;
            const text_length = $('#go-aml-comment').val().length;
            const text_remaining = text_max - text_length;

            $('#char-limit').html(text_remaining);
        });
    </script>
@endsection

@include('admin.fraud.partials.fraud-footer')

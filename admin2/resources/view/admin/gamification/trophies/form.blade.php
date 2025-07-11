@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.trophies.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Create New Trophy</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('trophies.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            <div class="card-body">
                <form id="sms-template-form" method="post">
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <div class="col-4 col-lg-4">
                        <div class="form-group">
                            <label for="voucher_name">Trophy Alias</label>
                            <input name="voucher_name" class="form-control" type="text" value="{{ $voucherTemplate->voucher_name }}">
                        </div>
                        <div class="form-group">
                            <label for="voucher_code">Trophy code</label>
                            <input name="voucher_code" class="form-control" type="text" value="{{ $voucherTemplate->voucher_code }}">
                        </div>
                        <div class="form-group">
                            <label for="count">Count</label>
                            <input name="count" class="form-control" type="number" value="{{ $voucherTemplate->count }}">
                        </div>
                        <div class="form-group">
                            <label for="bonus_type_template_id">Bonus type template</label>
                            @if($bonusTypeTemplate) //<font color="red">{{$bonusTypeTemplate->bonus_name}}</font> @endif
                            <select name="bonus_type_template_id" id="select-bonus-type-template" class="form-control select2-class" style="width: 100%;" data-placeholder="Select bonus type template" data-allow-clear="true">
                                <option></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="trophy_award_id">Trophy award</label>
                            @if($trophyAward) //<font color="red"> {{$trophyAward->description}}</font> @endif
                            <select name="trophy_award_id" id="select-trophy_award" class="form-control select2-class" style="width: 100%;" data-placeholder="Select trophy award" data-allow-clear="true">
                                <option></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="exclusive">Exclusive</label>
                            <select name="exclusive" id="select-exclusive" class="form-control select2-class" style="width: 100%;" data-placeholder="Exclusive"  data-allow-clear="true">
                                <option></option>
                                <option value="0">0</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-info" id="save-voucher-btn">Save</button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $("#select-exclusive").select2();

            $("#select-trophy_award").select2({
                ajax: {
                    url: "{{ $app['url_generator']->generate('trophyawards.ajaxfilter') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: $.map(data, function(data) {
                                return { id: data.id, text: data.description };
                            }),
                            pagination: {
                                more: (params.page * 30) < data.total_count
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 3,
            });

            $("#select-bonus-type-template").select2({
                ajax: {
                    url: "{{ $app['url_generator']->generate('bonustype-templates.ajaxfilter') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: $.map(data, function(data) {
                                return { id: data.id, text: data.bonus_name };
                            }),
                            pagination: {
                                more: (params.page * 30) < data.total_count
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 3,
            });
        });
    </script>
@endsection

@extends('admin.layout')

@section('content')
    @include('admin.user.partials.topmenu')

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Regulatory reports</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <form role="form">
                    <div class="form-group col-xs-12 col-md-4">
                        <label for="country">Country</label>
                        <select name="country" id="country" class="form-control select2" style="width: 100%;">
                            @foreach($countries as $c)
                                <option value="{{$c}}" {{$country === $c ? 'selected' : ''}}>
                                    {{$formatted_countries[$c]['printable_name']}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-xs-12 col-md-4 country-filter SE">
                        <label for="interval">Time interval</label>
                        <select name="interval" id="interval" class="form-control select2" style="width: 100%;">
                            @foreach($intervals as $interval)
                                <option value="{{$interval}}" {{$interval == $request->get('interval') ? 'selected' : ''}}>
                                    {{str_replace(':',' ', $interval)}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <br>
                    <input type="submit" class="btn btn-primary" value="View">
                    <input type="submit" class="btn btn-warning" name="export" value="Export">
                </form>
            </div>

            <hr style="margin-top: 0;">

            <div class="row">
                @yield('report-results')
            </div>
        </div><!-- /.box-body -->
    </div>
    <div class="clear"></div>
@endsection

@section('footer-javascript')
    @parent

    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $("#interval").select2();
            $("#country").select2().change(function() {
                $(".country-filter").hide();
                $("." + this.value).show();
            }).change();

        });
    </script>
@endsection

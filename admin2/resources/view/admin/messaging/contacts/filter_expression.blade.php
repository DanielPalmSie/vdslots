

@if ($expression['expr_type'] == 'and' || $expression['expr_type'] == 'or')
    @if ($expression['expr_type'] == 'and')
        <div style="padding: 10px; background-color: #CC8010; border-style: dashed">
    @elseif($expression['expr_type'] == 'or')
        <div style="padding: 10px; background-color: #b3d4fc; border-style: dashed">
    @endif

    @foreach ($expression['operands'] as $operand)
       @include('admin.messaging.contacts.filter_expression', array('expression' => $operand, 'type' => $operand['expr_type']))
    @endforeach

    </div>
@else

    <div class="row">
        <div class="col-6 col-sm-6 col-md-6 col-lg-4 col-xl-4 col-fhd-4">
            <input type="text" value="{{$expression['fields'][0]}}" class="form-control">
        </div>
        <div class="col-6 col-sm-6 col-md-6 col-lg-4 col-xl-4 col-fhd-4">
            <input type="text" value="{{$expression['fields'][1]}}" class="form-control">
        </div>
        <div class="col-6 col-sm-6 col-md-6 col-lg-4 col-xl-4 col-fhd-4">
            <input type="text" value="{{$expression['fields'][2]}}" class="form-control">
        </div>
    </div>

@endif


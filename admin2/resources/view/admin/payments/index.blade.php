@extends('admin.layout')

@section('content')

<div class="container-fluid">
    @include('admin.payments.partials.topmenu')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payments</h3>
        </div>
        <div class="card-body">
            Index, yes it is.
        </div>
    </div>
</div>
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {

        });
    </script>
@endsection

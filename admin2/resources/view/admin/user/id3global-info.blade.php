@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <div class="card card-primary border border-primary">
        <div class="card-header">
            <h3 class="card-title">ID3global Check Result</h3>
        </div>
        <div class="card-body">
            <div id="id3-result">{{ $data }}</div>
        </div>
    </div>

@endsection

@section('footer-javascript')
    @parent

    <script>
        $(document).ready(function() {

            var id3 = $("#id3-result");
            var jsonobj = JSON.parse(id3.html());

            var pretty = JSON.stringify(jsonobj,null,'\t');

            id3.html('<pre>'+ pretty + '</pre>');
        });

    </script>
@endsection

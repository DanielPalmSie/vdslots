@extends('admin.layout')

@section('content')
    <div class="error-page">
        <h2 class="headline text-red">{{ $code }}</h2>
        <div class="error-content">
            <h3><i class="fa fa-warning text-red"></i> Something went wrong.</h3>
            <p>Until the issue is fixed, you may <a href="{{ $app['url_generator']->generate('home') }}">return to home page</a>.</p>
            <p>This error was identified with the following code: {{ $code }}</p>
        </div>
    </div><!-- /.error-page -->
@endsection

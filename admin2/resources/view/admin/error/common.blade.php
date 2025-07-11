@extends('admin.layout')

@section('content')
    <div class="error-page">
        <h2 class="headline text-yellow"> {{ $code }}</h2>
        <div class="error-content">
            <h3><i class="fa fa-warning text-yellow"></i> Error</h3>
            <p>{{ $message }}</p>
        </div><!-- /.error-content -->
    </div>
@endsection

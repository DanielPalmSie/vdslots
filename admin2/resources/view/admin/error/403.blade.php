@extends('admin.layout')

@section('content')
    <div class="error-page">
        <h2 class="headline text-yellow"> 403</h2>
        <div class="error-content">
            <h3><i class="fa fa-warning text-yellow"></i> Insufficient privileges</h3>
            <p>You do not have permission to do this.</p>
        </div>
    </div>
@endsection

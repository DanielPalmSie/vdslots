@extends('admin.layout')

@section('content')
@include('admin.payments.partials.topmenu')

<div class="container-fluid">
    <div class="card p-3">
        <a href="{{ $app['url_generator']->generate('bin-blacklist.create') }}">
        <i class="fa fa-plus"></i> Create New Blocked BIN
        </a>
    </div>

    @include('admin.payments.bin-blacklist.filter')

    <div class="card card-solid card-primary">
        <div class="card-header">
            <h3 class="card-title">BINs Blacklist</h3>
        </div>
            <div class="card-body">
            <div class="table-responsive">
            <table id="user-transactions-datatable" class="table table-striped table-bordered display nowrap w-100">
                <thead>
                    <tr>
                        <th>BIN</th>
                        <th>Block Status</th>
                        <th>Comment</th>
                        <th>Created by</th>
                        <th>Updated by</th>
                        <th>Created at</th>
                        <th>Updated at</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($blacklistedBins as $item)
                    <tr>
                        <td>{{ $item['bin'] }}</td>
                        <td>{{ $item['status'] ? 'Blocked' : 'Allowed' }}</td>
                        <td>{{ $item['comment'] }}</td>
                        <td>
                            <a target="_blank"
                            href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app, $item['created_by']) }}">
                                {{ $item['created_by'] }}
                            </a>
                        </td>
                        <td>
                            <a target="_blank"
                            href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app, $item['updated_by']) }}">
                                {{ $item['updated_by'] }}
                            </a>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($item['created_at'])->format('Y-m-d H:i:s') }}</td>
                        <td>{{ \Carbon\Carbon::parse($item['updated_at'])->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <a href="{{ $app['url_generator']->generate('bin-blacklist.edit', ['id' => $item['id']]) }}">
                                <i class="fa fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

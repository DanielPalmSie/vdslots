@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.consolidation-filter')
    <div class="card card-primary">
        <div class="card-header">
            <ul class="list-inline card-title">
                <li><b>Payment providers transactions history</b></li>
            </ul>
        </div>
        <div class="card-body">

                @include('admin.accounting.partials.consolidation-table', ['table_id' => 'dep_made', 'title' => 'Deposits made using', 'paginator' => $data['dep_made']])
                @include('admin.accounting.partials.consolidation-table', ['table_id' => 'dep_reported', 'title' => 'Deposits reported by', 'paginator' => $data['dep_reported']])

        </div>
    </div>

@endsection

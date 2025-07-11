@extends('admin.layout')

@section('content')
<div class="container-fluid">

    @include('admin.payments.partials.topmenu')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pending Deposits</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-transactions-datatable" class="table table-striped table-bordered nowrap w-100" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Internal ID</th>
                            <th>External ID</th>
                            <th>External Ref</th>
                            <th>Recorded IP</th>
                            <th>Currency</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deps as $deposit)
                            <tr>
                                <td>{{ $deposit->timestamp }}</td>
                                <td>{{ $deposit->dep_type }}</td>
                                <td>{{ $deposit->amount }}</td>
                                <td>{{ $deposit->id }}</td>
                                <td>{{ $deposit->ext_id }}</td>
                                <td>{{ $deposit->loc_id }}</td>
                                <td>{{ $deposit->ip_num }}</td>
                                <td>{{ $deposit->currency }}</td>
                                <td><button class="approvebtn btn btn-default" id="approve-{{ $deposit->id }}">Approve</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            $(".approvebtn").click(function(){
                var me = $(this);
                var id = me.attr('id').split('-').pop();
                // ajax query
                // on return
                $.post('/admin2/payments/pending-deposits/approve/', {id: id}, function(res){
                    me.attr('disabled', 'disabled').html(res);
                });

            });
        });
    </script>
@endsection

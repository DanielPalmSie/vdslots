<div class="card card-info">
    <div class="card-header with-border">
        <h3 class="card-title">Queued {{$type}}</h3>
    </div>
    <div class="card-body">
        <div class="col-12">
            <table id="sms-campaigns-list-datatable"
                   class="table table-striped table-bordered"
                   cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Message</th>
                    <th>Queued at</th>
                </tr>
                </thead>
                <tbody>
                @foreach($pending_sms as $pending)
                    <tr>
                        <td>{{ $pending->user_id }}</td>
                        <td>{{ $pending->msg }}</td>
                        <td>{{ $pending->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
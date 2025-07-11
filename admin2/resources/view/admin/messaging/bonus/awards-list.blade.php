<div class="card">
    <div class="card-header">
        <h3 class="card-title">Choose a reward from the list</h3>
    </div>
    <div class="card-body">
        <table id="award-list-databable" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>Created at</th>
                <th>Description</th>
                <th>Alias</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($rewards as $element)
                <tr>
                    <td>{{ $element['id'] }}</td>
                    <td>{{ $element['created_at'] }}</td>
                    <td>{{ $element['description'] }}</td>
                    <td>{{ $element['alias'] }}</td>
                    <td><a class="select-reward-link" onclick="setRewardField('{{ $element['id'] }}', '{{ $element['description'] }}');return false;" href="#"><i class="fa fa-circle-o"></i> Select</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

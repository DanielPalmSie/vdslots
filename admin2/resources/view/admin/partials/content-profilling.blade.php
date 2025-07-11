<?php
use App\Extensions\Database\FManager as DB;
$profile_result = DB::getAllConnectionsQueryLog();
?>

@if($profile_result['totals']['count'] > 0)
    <div class="card content-profiling">
            <div class="table-responsive">
                <table class="table table-hover">
                    <tbody>
                    <tr>
                        <th>Query</th>
                        <th>Count</th>
                        <th>Time</th>
                    </tr>
                    <tr style="background-color: #EEEEEE">
                        <td>All the queries</td>
                        <td><?= $profile_result['totals']['count'] ?></td>
                        <td><?= $profile_result['totals']['time'] / 1000 ?> sec.</td>
                    </tr>
                    @foreach($profile_result['query_list'] as $element)
                        <tr>
                            <td>[{{ $element['connection'] }}] {{ $element['name'] }}</td>
                            <td>{{ $element['count'] }}</td>
                            <td>{{ $element['time']/1000 }} sec.</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
    </div>
@endif

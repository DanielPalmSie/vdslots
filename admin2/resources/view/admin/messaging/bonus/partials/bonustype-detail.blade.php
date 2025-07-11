<table class="table">
    @foreach($data as $key => $value)
        @if($key == 'award_id')
            <tr>
                <td>Reward</td>
                <td><b>{{ empty($value) ? 'Not set' : \App\Models\TrophyAwards::find($value)->description }}</b></td>
            </tr>
        @else
            <tr>
                <td>{{ ucfirst(str_replace("_", " ", $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endif
    @endforeach
</table>


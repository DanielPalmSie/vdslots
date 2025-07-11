<table class="table">
    @foreach($data as $key => $value)
        @if($key == 'trophy_award_id')
            <tr>
                <td>Reward</td>
                <td>{{ empty($value) ? 'Not set' : \App\Models\TrophyAwards::find($value)->description }}</td>
            </tr>
        @elseif($key == 'bonus_type_template_id')
            @if(!empty($value))
            <?php $bonus_template = empty($value) ? null : \App\Models\BonusTypeTemplate::find($value); ?>
            <tr>
                <td>Bonus template</td>
                <td>{{ empty($bonus_template) ? 'Not found' : empty($bonus_template->template_name) ? $bonus_template->bonus_name : $bonus_template->template_name }}</td>
            </tr>
            @endif
        @else
            <tr>
                <td>{{ ucfirst(str_replace("_", " ", $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endif
    @endforeach
</table>


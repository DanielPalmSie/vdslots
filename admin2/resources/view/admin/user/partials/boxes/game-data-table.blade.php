<div class="table-responsive">
    <table class="table table-hover table-sm">
        <thead>
        <tr>
            <th>{{ strtoupper($title) }}</th>
            <th>@if($type != 1) {{ $user->currency }} @endif</th>
            <th>RTP</th>
        </tr>
        </thead>
        <tbody>
        @if(count($data) > 0)
            @foreach($data as $elem)
                <tr>
                    <td style="width: 80%"
                        data-toggle="tooltip"
                        data-placement="top"
                        title="Total wagered: {{ \App\Helpers\DataFormatHelper::nf($elem->wag_sum) }}">
                        <b>{{ !empty($elem->game_name) ? $elem->game_name : $elem->game_ref }}</b>
                    </td>
                    @if($type == 1)
                        <td style="width: 10%">
                            <b>{{ $elem->{$main} < 10 ? round($elem->{$main}, 2) : round($elem->{$main}) }}%</b>
                        </td>
                    @else
                        <td style="width: 10%">
                            <b>{{ abs($elem->{$main}) > 1 ?
                                \App\Helpers\DataFormatHelper::nf(round(abs($elem->{$main})), 100, 1) :
                                \App\Helpers\DataFormatHelper::nf(abs($elem->{$main})) }}</b>
                        </td>
                    @endif
                    <td style="width: 10%">{{ round($elem->rtp) }}%</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="3" class="text-center">No data available.</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

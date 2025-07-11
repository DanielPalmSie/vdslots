
<table class="table">
    <tr>
       <td>Template type</td>
       <td>{{ $data->isSMS() ? 'SMS' : 'E-mail' }}</td>
    </tr>
    <tr>
       <td>Contact list</td>
       <td>{{ $data->namedSearch()->first()->name }}</td>
    </tr>
    @if($data->isBonus())
        <tr>
            <td>Bonus template</td>
            <td>{{ $data->bonusTemplate()->first()->template_name }}</td>
        </tr>
    @elseif($data->isVoucher())
        <tr>
            <td>Voucher template</td>
            <td>{{ $data->voucherTemplate()->first()->template_name }}</td>
        </tr>
    @else
        <tr>
            <td>Promotion type</td>
            <td>Without promotion</td>
        </tr>
    @endif
    @if($data->recurring_type == 'one')
        <tr>
            <td>Type</td>
            <td>One time only</td>
        </tr>
        <tr>
            <td>Start time</td>
            <td>{{ $data->start_date }} {{ $data->start_time }}</td>
        </tr>
    @else
        <tr>
            <td>Type</td>
            <td>{{ $data->getRecurringTypeName() }}</td>
        </tr>
        <tr>
            <td>Start time</td>
            <td>{{ $data->start_time }}</td>
        </tr>
        <tr>
            <td>End date</td>
            <td>{{ $data->recurring_end_date }}</td>
        </tr>
        @if($data->recurring_type == 'day')
            <tr>
                <td>Start time</td>
                <td>{{ $data->start_time }}</td>
            </tr>
        @elseif($data->recurring_type == 'week')
            <tr>
                <td>Recurring days</td>
                <td>{{ $data->recurring_days }}</td>
            </tr>
        @elseif($data->recurring_type == 'month')
            <tr>
                <td>Recurring days</td>
                <td>{{ $data->recurring_days }}</td>
            </tr>
        @endif
    @endif

    @if($data->status == 1)
        <tr>
            <td>Status</td>
            <td>Archived</td>
        </tr>
    @endif
    <tr>
        <td>Created at</td>
        <td>{{ $data->created_at }}</td>
    </tr>
    <tr>
        <td>Updated at</td>
        <td>{{ $data->updated_at }}</td>
    </tr>
</table>


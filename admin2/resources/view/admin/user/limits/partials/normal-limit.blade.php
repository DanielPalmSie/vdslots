<style>
    .limit-form {
        box-shadow: 0px 1px 1px grey;
        margin-bottom: 20px;
    }
    .actions {
        margin-top: 6px;
    }
</style>

<?php
$form_id = "$limit-limit";
$limits = $user->rgLimits->groupBy('type')[$limit] ?? collect();
$has_limit = $limits->count() > 0;
$limits = $limits->keyBy('time_span');

// Show 'New limit will be active on' when at least one of the limit variant (daily, weekly, monthly) is set
$changes_at = empty($limits->max('changes_at')) ? '-' : $limits->max('changes_at');
$has_removed_limits = false;
?>

<form action="{{ $app['url_generator']->generate('admin.user-edit-gaming-limits', ['user' => $user->id]) }}" method="post" class="limit-form" id="{{$form_id}}">
    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>{{$title}}</th>
            <th>Active limit</th>
            <th>Remaining</th>
            <th>New limit - cents</th>
        </tr>
        </thead>
        <tbody>
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" class="hidden-title" value="Force {{$title}}">
        <input type="hidden" name="type" value="{{$limit}}">
        @foreach($time_span_list as $time_span => $meta)
            <?php
                if ($limit == 'net_deposit' && $time_span == 'month' && !in_array($jurisdiction, $net_deposit_month_jurisdictions)) {
                    continue;
                } elseif ($limit === 'customer_net_deposit' && $time_span !== 'month') {
                    continue;
                }

                $limits[$time_span]->new_lim = empty($limits[$time_span]->new_lim) ? null : $limits[$time_span]->new_lim;
                $limit_removed = $limits[$time_span]->new_lim == '-1';
                $placeholder = $limit_removed ? "Limit will be removed on: $changes_at" : "Choose Limit";
                $has_removed_limits = $has_removed_limits || $limit_removed;
            ?>
            <tr>
                <td class="col-3">
                    {{$meta['title']}} <br> {{$limit == 'net_deposit' && $time_span == 'month' ? '(Calendar Month)' : $meta['description']}}
                </td>
                <td class="col-3">
                    <input type="text" class="form-control" value="{{$limits[$time_span]->cur_lim}}" placeholder="Choose Limit" disabled>
                    <input type="hidden" value="{{$limits[$time_span]->cur_lim}}" name="limits[{{$time_span}}][cur_lim]" class="cur-lim">
                    @if (!empty($limits[$time_span]->cur_lim))
                        <span>Value: {{ $user->currency }} {{ \App\Helpers\DataFormatHelper::nf($limits[$time_span]->cur_lim) }}</span>
                    @endif
                </td>
                <td class="col-3">
                    <input type="text" class="form-control" value="{{$limits[$time_span]->remaining}}" disabled>
                    <span>Resets: {{$limits[$time_span]->resets_at ?? '-'}} Value: {{ $user->currency }} {{ \App\Helpers\DataFormatHelper::nf($limits[$time_span]->remaining) }}</span>
                </td>
                <td class="col-3">
                    <input type="number" class="form-control new-limit" value="{{$limit_removed ? null : $limits[$time_span]->new_lim}}" name="limits[{{$time_span}}][new_lim]" placeholder="{{$placeholder}}">
                    @if (!empty($limits[$time_span]->new_lim))
                        <span>Value: {{ $user->currency }} {{ \App\Helpers\DataFormatHelper::nf($limits[$time_span]->new_lim) }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="row">
        @include("admin.user.limits.partials.footer", [
            'forced_until' => $limits->first()->forced_until,
            'has_removed_limits' => $has_removed_limits
        ])
    </div>
    <br>
</form>

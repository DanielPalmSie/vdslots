<style>
    .limit-form {
        box-shadow: 0px 1px 1px grey;
        margin-bottom: 20px;
    }
</style>

<?php
$form_id = "$limit-limit";
$limit_name = $limit;
$has_limit = true;
$limit = $user->rgLimits->first(function ($el) use ($limit) {return $el->type == $limit;});
if (empty($limit)) {
    $limit = new \App\Models\RgLimits(['type' => $limit]);
    $has_limit = false;
}
$changes_at = $limit->changes_at ?? '-';
?>

<form action="{{ $app['url_generator']->generate('admin.user-edit-gaming-limits', ['user' => $user->id]) }}" method="post" class="limit-form" id="{{$form_id}}">
    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>{{\App\Helpers\DataFormatHelper::getLimitsNames($limit_name)}}</th>
            <th>Active limit</th>
            <th>Remaining</th>
            <th>New limit - minutes</th>
        </tr>
        </thead>
        <tbody>
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" name="type" value="{{$limit_name}}">
        <?php
        $limit->new_lim = empty($limit->new_lim) ? null : $limit->new_lim;
        $placeholder = $limit->new_lim == '-1' ? "Limit will be removed on: $changes_at" : "Choose Limit";
        ?>
        <tr>
            <td class="col-xs-3">
                Set a time limit on how many minutes this user can play before game play is interrupted.
            </td>
            <td class="col-xs-3">
                <input type="text" class="form-control" value="{{$limit->cur_lim ? $limit->cur_lim . ' minutes' : ''}}" placeholder="Choose Limit" disabled>
                <input type="hidden" value="{{$limit->cur_lim}}" name="limits[na][cur_lim]" class="cur-lim">
            </td>
            <td class="col-xs-3">
                <input type="text" class="form-control" value="{{$limit->remaining ?? '-'}} minutes" disabled>
                <span>Resets: {{$limit->resets_at  ?? '-'}}</span>
            </td>

            <td class="col-xs-3">
                <input type="number" class="form-control new-limit" value="{{$limit->new_lim == -1 ? null : $limit->new_lim}}" name="limits[na][new_lim]" placeholder="{{$placeholder}}">
            </td>
        </tr>
        </tbody>
    </table>
    <div class="row">
        @include("admin.user.limits.partials.footer", [
            'forced_until' => $limit->forced_until,
            'has_removed_limits' => $limit->new_lim == '-1'
        ])
    </div>
    <br>
</form>

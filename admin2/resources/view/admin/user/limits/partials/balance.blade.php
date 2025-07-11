<?php
/** @var string $limit */
$limit_name = $limit;
$form_id = "{$limit_name}-limit";

/** @var \App\Models\User $user */
$limit = $user->rgLimits->first(function ($el) use ($limit) {
    return $el->type == $limit;
});

$has_limit = true;
if (empty($limit)) {
    $has_limit = false;
    $limit = new \App\Models\RgLimits(['type' => 'balance']);
}
$changes_at = $limit->changes_at ?? '-';
?>

<form action="{{ $app['url_generator']->generate('admin.user-edit-gaming-limits', ['user' => $user->id]) }}"
      method="post" class="limit-form" id="{{ $form_id }}">
    <input type="hidden" name="token" value="{{ $_SESSION['token'] }}">
    <input type="hidden" name="type" value="{{ $limit_name }}">
    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>{{ \App\Helpers\DataFormatHelper::getLimitsNames($limit_name) }}</th>
            <th>Active limit</th>
            <th>Remaining</th>
            <th>New limit - cents</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="col-xs-3">
                Set a maximum allowed balance for this user.
            </td>
            <td class="col-xs-3">
                <input type="text" class="form-control" value="{{ $limit->cur_lim }}" placeholder="Choose Limit"
                       disabled>
                <input type="hidden" value="{{ $limit->cur_lim }}" name="limits[na][cur_lim]" class="cur-lim">
                <span>Value: {{ $limit->cur_lim ? $user->currency . ' ' . \App\Helpers\DataFormatHelper::nf($limit->cur_lim) : '-' }}</span>
            </td>
            <td class="col-xs-3">
                <input type="text" class="form-control" value="{{ $limit->remaining ?? '-' }}" disabled>
            </td>

            <td class="col-xs-3">
                <input type="number" class="form-control new-limit"
                       value="{{ $limit->new_lim == -1 ? null : $limit->new_lim }}" name="limits[na][new_lim]"
                       placeholder="Choose Limit">
                <span>Value: {{ $limit->new_lim ? $user->currency . ' ' . \App\Helpers\DataFormatHelper::nf($limit->new_lim) : '-' }}</span>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="row">
        @include("admin.user.limits.partials.footer", [
            'forced_until' => $limit->forced_until,
            'has_removed_limits' => true,
        ])
    </div>
    <br>
</form>

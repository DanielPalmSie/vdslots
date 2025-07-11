<?php
/**
 *
 */
$username_in_forums = $user->repo->getUsernameInForums();
?>

<div class="card card-outline card-warning @if($forums_collapse == 1) collapsed-box @endif" id="forums-box">
    <div class="card-header">
        <h3 class="card-title text-lg">Usernames on popular forums</h3>
        <div class="card-tools">
            <button class="btn btn-tool" id="forums-box-btn" data-boxname="forums-box" data-widget="collapse"
                    data-toggle="tooltip" title="Collapse">
                <i class="fa fa-{{ $forums_collapse == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    @if(count($username_in_forums) == 0)
        <div class="card-body">
            <p>Data not available yet. Go to <a href="{{ $app['url_generator']->generate('admin.user-edit', ['user' => $user->id]) }}">edit profile</a> to add usernames in popular forums.</p>
        </div>
    @else
        <div class="card-body">
            <ul style="margin-bottom: -5px" class="list-group list-group-flush">
                @foreach($username_in_forums as $username)
                    <li class="list-group-item d-flex justify-content-between">
                        <b>{{ \App\Helpers\DataFormatHelper::getPopularForums(explode('-', $username->setting)[2]) }}</b>
                        <p>{{ $username->value }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

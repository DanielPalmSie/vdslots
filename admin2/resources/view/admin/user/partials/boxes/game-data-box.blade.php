<?php
/**
 * @var \App\Models\User $user
 */
$box_name = "game-data-box";
$collapse = $_COOKIE["new-bo-$box_name"];
?>

<div class="card card-outline card-warning @if($collapse == 1) collapsed-box @endif" id="{{ $box_name }}">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center flex-grow-1">
        <h3 class="card-title text-lg mr-2">Game data - </h3>
        <input
            type="text"
            id="game-daterange-btn"
            name="daterange"
            class="form-control w-50"
            data-url="{{ $app['url_generator']->generate('admin.user-get-games-data-ajax', ['user' => $user->id]) }}"
            data-target="#ajax-container-game"
            value="{{ \Carbon\Carbon::parse($user->register_date)->format('Y-m-d') }} - {{ \Carbon\Carbon::now()->format('Y-m-d') }}"
        />
            <h3 class="card-title text-lg text-dark ml-2" id="initial-label-game">All time</h3>
        </div>
        <div class="card-tools">
            <button class="btn btn-tool" data-boxname="{{ $box_name }}" id="{{ $box_name }}-btn" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                <i class="fa fa-{{ $collapse == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="ajax-container-game">
            @include('admin.user.partials.boxes.game-data', ['start_date' => null, 'end_date' => null])
        </div>
        <div>

        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            manageCollapsible("{{ $box_name }}" + "-btn", 'box');
            manageFilteredData('game-daterange-btn', false, 'initial-label-game');
        });
    </script>
@endsection





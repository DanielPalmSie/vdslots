<div class="row">
    <div class="col-12 col-md-6 col-lg-6 col-xl-3">
        @include('admin.user.partials.boxes.game-data-table', ['data' => $user->repo->getGameStats($start_date, $end_date)->filter(function ($item) { return !empty($item->fav_id); })->sortByDesc('percentage')->splice(0,10)->all(), 'main' => 'percentage', 'type' => 1,'title' => 'Favourite games'])
    </div>
    <div class="col-12 col-md-6 col-lg-6 col-xl-3">
        @include('admin.user.partials.boxes.game-data-table', ['data' => $user->repo->getGameStats($start_date, $end_date)->sortByDesc('percentage')->splice(0,10)->all(), 'main' => 'percentage', 'type' => 1, 'title' => 'Most wagered'])
    </div>
    <div class="col-12 col-md-6 col-lg-6 col-xl-3">
        @include('admin.user.partials.boxes.game-data-table', ['data' => $user->repo->getGameStats($start_date, $end_date)->filter(function ($item) { return $item->gross > 0; })->sortByDesc('gross')->splice(0,10)->all(), 'main' => 'gross', 'type' => 0, 'title' => 'Most losses'])
    </div>
    <div class="col-12 col-md-6 col-lg-6 col-xl-3">
        @include('admin.user.partials.boxes.game-data-table', ['data' => $user->repo->getGameStats($start_date, $end_date)->filter(function ($item) { return $item->gross < 0; })->sortBy('gross')->splice(0,10)->all(), 'main' => 'gross', 'type' => 0, 'title' => 'Most won'])
    </div>
</div>

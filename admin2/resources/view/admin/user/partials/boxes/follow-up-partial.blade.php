<?php
    $selected_option = $user->settings_repo->settings->{$category.'-risk-group'};
    $selected_option = $selected_option ?? 'all';
?>

<div class="row">
    <div class="col-12">
        <form method="post" class="follow-up-form" id="{{ $category }}-follow-up-form">
            <input type="hidden" name="category" value="{{$category}}">
            <input type="hidden" name="form_id" value="follow-up-settings">
            <input type="hidden" name="token" value="{{ $_SESSION['token'] }}">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="comment">
            <div class="row">
                <div class="col-12">
                    <div class="form-group p-2 mb-1">
                        @foreach(\App\Helpers\DataFormatHelper::getFollowUpOptions() as $key => $text)
                            <div class="form-check form-check-inline">
                                <input name="{{$category}}-{{$key}}-follow-up" type="checkbox" value="1" class="form-check-input" {{ $follow[$category][$key] == 1 ? 'checked' : '' }}>
                                <label class="form-check-label">{{ $text }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="form-group form-group-sm">
                    <label for="select-risk-group-{{$category}}">Risk group</label>
                    <select name="{{$category}}-risk-group" id="select-risk-group-{{$category}}" class="form-control select2-class select2-risk-group"
                            data-placeholder="No risk group" data-allow-clear="true" data-default="{{$selected_option}}">
                        <option value="all" {{$selected_option == 'all' || empty($selected_option) ? 'selected' : ''}}>None</option>
                        @foreach(\App\Helpers\DataFormatHelper::getFollowUpGroups(null, $category) as $key => $text)
                            <option value="{{ $key }}" {{$selected_option === $key ? 'selected' : ''}}>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if(p('user.edit.follow.up'))
                <div class="row">
                    <div class="col-12">
                        <div class="card-footer bg-white">
                            <button type="button" class="btn btn-primary follow-up-form-submit-btn" data-form="{{ $category }}-follow-up-form">Save</button>
                        </div>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $(".select2-risk-group").select2();
        });
    </script>
@endsection

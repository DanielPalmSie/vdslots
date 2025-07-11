<form id="grs-report" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
    <div class="card border-top border-top-3">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        @php
            $is_aml = $menu == 'fraud' || $app['request_stack']->getCurrentRequest()->get('type') == 'AML';
            $params['user_id'] = $app['request_stack']->getCurrentRequest()->get('user_id');
        @endphp
        <div class="card-body">
            <div class="row">
                @include('admin.filters.date-range-filter', ['date_format' => 'date'])
                @if(!$user)
                    <div class="form-group col-4 col-lg-3">
                        @include('admin.filters.country-filter')
                    </div>
                    <div class="form-group col-4 col-lg-3">
                        @include('admin.filters.user-id-filter')
                    </div>
                @else
                    <div class="form-group col-4 col-md-4 col-lg-2">
                        <label for="select-status">Type</label>
                        <select name="type" id="select-type" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="" data-allow-clear="false" ;>
                            <option></option>
                            @foreach(\App\Repositories\RiskProfileRatingRepository::rating_settings as $key => $value)
                                <option value="<?=$key?>"><?=$key?></option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="form-group col-6 col-md-4 col-lg-3">
                    @include('admin.filters.slider-range-filter', [
                        'start' => $app['request_stack']->getCurrentRequest()->get('section_profile_rating_start', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG),
                        'end' => $app['request_stack']->getCurrentRequest()->get('section_profile_rating_end', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG),
                        'label' => 'Global Score Rating',
                        'type' => 'section',
                    ])
                </div>
            </div>
        </div><!-- /.card-body -->
        <div class="card-footer">
            <button type="submit" class="btn btn-info">Search</button>
        </div><!-- /.card-footer-->
    </div>
</form>

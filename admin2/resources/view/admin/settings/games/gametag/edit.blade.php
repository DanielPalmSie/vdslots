    <div class="card card-solid card-primary" id="gametag_form">
        <div class="card-header">
            <h3 class="card-title">Game Tag Connections</h3>
        </div>
        <div class="card-body">
            <div class="col-md-12">
                @foreach($data as $row)
                    <? $checked = $datatag->contains('tag_id', '=', $row->id) ? "checked='checked'" : ""; ?>
                    <div class="form-group col-6 mb-0">
                        <div class="checkbox mb-0">
                            <label><input type="checkbox" value="{{$row->id}}" {{$checked}}>{{ $row->alias }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer">
            <form class="float-left" method="POST" id="tags_form"
                  action="{{ $app['url_generator']->generate('settings.games.gametag.save', ['id' => $game->id]) }}">
                <input type="hidden" name="tag">
            </form>
        </div>
    </div>


@section('footer-javascript')
    @parent
    @include('admin.partials.jquery-ui-cdn')
    <script>
        $(function () {
            $("#gametag_form input[type='checkbox']").click(function () {
                gameTags.save();
            });
        });
    </script>
    <script language="JavaScript">

        var gameTags = {

            save: function () {

                var arr = $("#gametag_form input[type='checkbox']:checked")
                    .map(function (i, el) {
                        return $(el).attr('value').trim();
                    })
                    .filter(function (i, el) {
                        return el !== '';
                    })
                    .get();

                $("input[name='tag']").attr('value', arr.join(','));

                $.post("{{ $app['url_generator']->generate('settings.games.gametag.save', ['id' => $game->id]) }}", $('#tags_form').serialize())
                    .fail(function (xhr, textStatus, errorThrown) {
                        if (xhr.responseJSON) {
                            console.log(xhr.responseJSON);
                        }
                    });
            }
        };

    </script>

@endsection

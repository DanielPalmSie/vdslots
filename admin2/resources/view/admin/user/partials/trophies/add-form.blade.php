<form action="{{ $app['url_generator']->generate('admin.user-add-trophy', ['user' => $user->id]) }}"
      method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <div class="card border-top border-top-3">
                <div class="card-body d-lg-flex">
                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="trophy-category">Category</label>
                            <select name="trophy-category" id="trophy_category" class="form-control select2-tags"
                                    style="width: 100%;" data-placeholder="Select a category" data-allow-clear="true">
                                <option></option>
                                @foreach($categories as $category_id => $category)
                                    <option value="{{ $category_id }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="trophy-id">Trophy</label>
                            <select name="trophy-id" id="trophy_id" class="form-control select2-tags"
                                    style="width: 100%;" data-placeholder="Select a trophy" data-allow-clear="true">
                                <option></option>
                                @foreach($trophies as $trophy)
                                    <option value="{{ $trophy->id }}">{{ $trophy->alias }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info" type="submit">Add Trophy to this user</button>
                </div>
            </div>
</form>


@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        $(function () {
            $('#trophy_id').select2();
            $('#trophy_category').select2().on('change', function (e) {
                var trophy_select = $('#trophy_id');
                trophy_select.empty();
                trophy_select.select2('val', '');
                trophy_select.attr('placeholder', 'Loading ... ').select2();
                var category = this.value ? this.value : '';
                var url = "{{ $app['url_generator']->generate('admin.user-trophy-list', ['user' => $user->id]) }}";

                if (category) {
                    var a = url.split('/');
                    url = url.replace(a[a.length - 2] + '/', category + '/');
                }

                $.get(url, function (data) {
                    retValues = jQuery.parseJSON(data);
                    if (retValues.length == 0) {
                        trophy_select.data('placeholder', 'Trophies not found under the selected category.').select2();
                    } else {
                        trophy_select.append("<option></option>");
                        $.each(retValues, function (item, element) {
                            trophy_select.append("<option value='" + element.id + "'>" + element.alias + "</option>");
                        });
                        trophy_select.trigger("change");
                        trophy_select.data('placeholder', 'List loaded. Please select a trophy.');
                        trophy_select.select2();
                    }
                });
            });
        });
    </script>
@endsection

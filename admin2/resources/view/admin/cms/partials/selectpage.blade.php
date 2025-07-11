<div class="card">

    <div class="card-header">
        <h3 class="card-title">Select page</h3>
    </div>
    <div class="card-body">

        <form action="">
            <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                <select id="select-page" name="banner-page" class="form-control select2-class"
                        style="width: 100%;" data-placeholder="Select page" data-allow-clear="true">
                    <option></option>
                    @foreach($pages as $page)
                        <option value="{{$page['alias']}}">{{$page['name']}}</option>
                    @endforeach
                </select>
            </div>
        </form>

    </div>
</div>


<div class="card" id="select-alias-container">
    <div class="card-header">
        <h3 class="card-title">Select alias</h3>
    </div>
    <div class="card-body">

        <form action="">
            <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                <select id="select-alias" name="alias" class="form-control select2-class"
                        style="width: 100%;" data-placeholder="Select alias" data-allow-clear="true">
                    <option></option>
                    @foreach($aliases as $alias)
                        <option value="{{$alias->alias}}">{{$alias->alias}}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" id="type_of_page" name="type_of_page" value="">
        </form>

    </div>
</div>
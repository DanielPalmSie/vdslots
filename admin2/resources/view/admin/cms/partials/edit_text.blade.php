<div class="card" id='editstrings-container'>
    <div class="card-header">
        <h3 class="card-title">Localized strings for this alias: </h3>
    </div>
    <div class="card-body">

        @if($type_of_page == 'only_text')
            <div style="overflow: auto">
                @include('admin.cms.partials.select_type_for_text')
            </div>

            @include('admin.cms.partials.input_new_alias')

            <div id='new_alias_container' style="display: none">
                <p>You will be uploading text for a new alias: <strong>{{$new_alias}}</strong></p>
            </div>

        @endif
        <div id='editstringsbutton' style="display: none">
            <button id='editstrings'>Edit strings</button>
        </div>
    </div>
</div>


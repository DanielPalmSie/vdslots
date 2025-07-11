<p>Select which you want to upload</p>

<form action="">
    <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
        <select id="select-type-for-text" name="type" class="form-control select2-class"
                style="width: 100%;" data-placeholder="Select type" data-allow-clear="true"
                @if(strpos($alias, 'default') === false || !empty($new_alias))
                    disabled="disabled"
                @endif
                >

            {{-- If the selected banner alias is not a default alias, preselect bonus_code --}}
            <option></option>
            <option value="default">
                    Default text
            </option>
            <option value="bonus_code"
                    @if(strpos($alias, 'default') === false || !empty($new_alias))
                        selected="selected"
                    @endif
                    >
                    Text for bonus code
            </option>
        </select>
    </div>
</form>

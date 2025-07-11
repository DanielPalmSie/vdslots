<div class="card" id="editmail">
    <div class="card-header">
        <h3 class="card-title">Edit email </h3>
    </div>
    <div class="card-body">
        <div id="select-banner-type-container">
            <p>Select which you want to edit</p>

            <form action="">
                <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                    <select id="select-email-type" name="email-type" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select email type" data-allow-clear="true">
                        <option></option>
                        <option value="default">
                            Default email
                        </option>
                        <option value="bonus_code">
                                Email for bonus code
                        </option>
                    </select>
                </div>
            </form>
        </div>

        @include('admin.cms.partials.input_new_alias')

        <div id="select_email_language" style="display: none">
            <p>Select a language</p>

            <form action="">
                <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                    <select id="select-language" name="select-language" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select language" data-allow-clear="true"
                            >

                        <option></option>
                        @foreach($languages as $language)
                            <option value="{{$language->language}}" >{{$language->language}}</option>
                        @endforeach
                    </select>
                </div>
            </form>

        </div>

        <div id="edit-email-form">

        </div>
    </div>
</div>

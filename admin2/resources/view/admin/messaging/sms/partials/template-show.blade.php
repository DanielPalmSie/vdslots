<div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-fhd-6">
    <div class="card card-default">
        <div class="card-header with-border">
            <h3 class="card-title">{{ $c_type->getName() }} content [Language: <span id="template-language">{{ strtoupper($template_obj->language) }}</span>]</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <p>Language: {{ $template_obj->language }}</p>
                <div id="template-text">{!! $c_type->isEmail() ? $template_obj->html : $template_obj->template !!}</div>
            </div>
        </div>
    </div>
</div>
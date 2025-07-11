<?php $jurisdictions = lic('getBannerJurisdictions'); ?>
<div class="card" id='upload-banners-container'>
    <div class="card-header">
        <h3 class="card-title">Upload new banners: </h3>
    </div>

    <div class="card-body">

        <div id="select-banner-type-container">
            <p>Please upload banners with this size: <strong> {{ $dimensions['width'] }}x{{$dimensions['height']}}</strong> (width x height in pixels)</p>

            <p>Select which you want to upload</p>

            <form action="">
                <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                    <select id="select-banner-type" name="banner-type" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select banner type" data-allow-clear="true"
                            @if(strpos($_GET['alias'], 'default') === false)
                                disabled="disabled"
                            @endif
                            >

                        {{-- If the selected banner alias is not a default alias, preselect bonus_code and preselect the correct bonus_code --}}
                        <option></option>
                        <option value="default" @if(!empty($_GET['banner_type'])) selected="selected" @endif>Default banner</option>
                        <option value="bonus_code"
                                @if(!empty($_GET['new_alias']) || strpos($_GET['alias'], 'default') === false)
                                    selected="selected"
                                @endif
                                >
                                Banner for bonus code
                        </option>
                    </select>
                </div>
            </form>
        </div>

        @include('admin.cms.partials.input_new_alias')

        <div id='new_alias_container' @if(strpos($_GET['alias'], 'default') === false || empty($_GET['new_alias'])) style="display: none"  @endif>
            <p>You will be uploading images for a new alias: <strong>{{$_GET['new_alias']}}</strong></p>
        </div>


        <div id="upload-banners" @if(empty($_GET['new_alias']) && strpos($_GET['alias'], 'default') !== false) style="display: none" @endif>
            <p>
                Images need to be named on the following form: <strong>some-description_EUR_EN.ext</strong>,
                <strong>EN</strong> is the language{{!empty($jurisdictions) ? '(or the jurisdiction)' : ''}} and <strong>EUR</strong> is the currency, ext is one of
                <strong>jpg, png or gif</strong>.
            </p>
            <p>If the language can't be determined it will default to <strong>any</strong>.
               If the currency can't be determined it will default to
               <strong><?php echo phive('Currencer')->getSetting('base_currency') ?></strong>.</p>
            <p>To for instance upload an English USD image that should show in all languages simply name it xxxx_USD_AN.jpg.
               Since <strong>an</strong> is not a recognized language it will default to <strong>any</strong>.</p>
            @if(!empty($jurisdictions))
                <p><strong>Available jurisdictions:</strong> {{implode(', ', $jurisdictions)}}</p>
            @endif

            @include('admin.cms.partials.uploadfiles-dropzone')
        </div>

    </div>
</div>

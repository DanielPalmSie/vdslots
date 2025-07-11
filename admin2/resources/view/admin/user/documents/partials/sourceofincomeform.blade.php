<script>
    $(document).ready(function(){
        // limit the amount of fields a user can add
        var max_browse_fields = 10;
        $(".add-file-field").click(function(){

            // give the file input a unique name, by counting how many files input fields we have
            var inputs = $(this).siblings(".uploadfields").find($("input"));

            if((inputs.length - 2) < max_browse_fields) {
                var select = $(this).siblings(".uploadfields").find($("select")).first();
                if (select) {
                    $(this).siblings(".uploadfields").append(select.clone());
                }
            }
        });
    });
</script>

<?php
$income_types = [
    'payslip',
    'pension',
    'inheritance',
    'gifts',
    'tax_declaration',
    'dividends',
    'interest',
    'business_activities',
    'divorce_settlements',
    'gambling_wins',
    'sales_of_property',
    'rental_income',
    'capital_gains',
    'royalty_or_licensing_income',
    'other',
];

$options = [];
foreach ($income_types as $type) {
    $options[$type] = t('select.income.types.' . $type);
}
?>
Upload image
<form action="{{$app['url_generator']->generate('admin.user-document-add-multiple-files', ['user' => $user->id])}}"
      method="post" enctype="multipart/form-data" id="upload_form_{{$document['id']}}">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <div>
        <div class="uploadfields">
            <input type="hidden" name="document_type" value="{{$document['tag']}}">
            <?php dbSelect('income_types[]', $options, "", ["", t('select.income.types.options')], '', false, '', false); ?>
            <input type="file"
                id="upload_field_{{$document['id']}}"
                data-ddocumentid="{{$document['id']}}"
                name="file">
            @if(!empty($document['id']))
                <input type="hidden" name='document_id' value="{{$document['id']}}">
            @endif
        </div>

        <input type="button" value="{{ t("add.another.file") }}" name="add" class="add-file-field" id="add-file-field-{{ $document['id'] }}"><br>

        <input type="submit"
               value="{{t("submit")}}"
               class="submit upload_button"
               id="upload_button_{{$document['id']}}"
               data-ddocumentid="{{$document['id']}}"
        />
    </div>
</form>
    

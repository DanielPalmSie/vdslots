let modal = function (modalName) {
    var prefix = modalName + ' ';
    var modal = {
        type: null, // if we are editing or creating a new field
        options: [], // stores the different options for the select type input
        init() {
            this.addListeners();
            this.readOptions();
        },
        setOptions(newOptions) {
            this.options = newOptions;
            this.clearTable();
            for(let i = 0, length1 = newOptions.length; i < length1; i++){
                this.addTableRow(newOptions[i].name, newOptions[i].value);
            }
            this.writeOptions();
        },
        readOptions() {
            this.options = $(prefix + '#modal-form__value').data('select-options');
        },
        writeOptions() { // saves options to the data element
            $(prefix + '#modal-form__value').data('select-options', this.options);
        },
        addOption(name, value) {
            this.options.push({ name: name, value: value }); 
            this.writeOptions();
        },
        removeOption(name) {
            for(let i = 0, length1 = this.options.length; i < length1; i++){
                if (this.options[i].name == name) {
                    this.options.splice(i, 1); // remove the data from the options
                    this.writeOptions();
                    this.removeTableRow(name);
                    break; 
                }
            }
        },
        openModal(data) {
            if (data.title) this.setTitle(data.title);
            if (data.type) this.setFormType(data.type);
            if (data.fields){
                this.setFields(data.fields);    
            }else{
                this.clearFields();
            } 
             
            $(prefix).addClass('is-active');
        },
        closeModal() {
            $(prefix).removeClass('is-active');
        },
        isOpen() {
            return $(prefix).hasClass('is-active'); 
        },
        showTable() {
            $(prefix + '#select-table').show();
        },
        hideTable() {
            $(prefix + '#select-table').hide();
        },
        clearTable() {
            $(prefix + '.select-table-body__rows').remove();
        },
        addTableRow(name, value) {
            const bulk = '<tr id="new-field__field-select--item-'+this.nameSlug(name)+'" class="select-table-body__rows">'
                    +'<th>'+name+'</th>'
                    +'<th>'+value+'</th>'
                    +'<th><button class="button is-small new-field__field-select--remove-option" data-option="'+name+'"><i class="fa fa-times" style="cursor: pointer;"></i></button></th>'+
                '</tr>';
            $(prefix + '#select-table-body__inputs').before(bulk);
        },
        nameSlug(name) {
            return  String(name).replace(/ /g,"_");
        },
        removeTableRow(name) {
            $(prefix + '#new-field__field-select--item-'+this.nameSlug(name)).remove();
        },
        setTitle(title) {
            $(prefix + '#modal-title').html(title);
        },
        setFields(data) {
            this.setFieldName(data.field_name);
            this.setFieldProfile(data.profile);
            this.setFieldValue(data.value);
            this.setFieldType(data.type, data.value );
        },
        setFieldName(name) {
            $(prefix + '#modal-form__name').val(name);
        },
        setFieldProfile(profile) {
            $(prefix + '#modal-form__profile').val(profile);
        },
        setFieldValue(value) {
            $(prefix + '#modal-form__value').val(value);
        },
        setFieldType(type, options) {
            if (type == 'select') {
                this.setOptions( JSON.parse(options) );
            }
            $(prefix + '#modal-form__type').val(type).change();
        },
        setFormType(type) {
            this.type = type;
        },
        clearFields() {
            $(prefix + '#modal-form__profile').val('normal').change();
            $(prefix + '#modal-form__name').val('');
            $(prefix + '#modal-form__value').val('');
            $(prefix + '#modal-form__type').val('string').change();
        },
        addListeners() {         
            var self = this;               
            $(prefix + "#modal__close").click( e =>{ // close modal
                e.preventDefault();
                self.closeModal();
            });
            
            $(prefix + '#modal-form__type').change( () => { // show or hide the select table in the modal
                if ($(prefix + '#modal-form__type').val() == 'select'  ){
                    self.showTable();
                }else{
                    self.hideTable();
                }
            });
            
            $(prefix + '#new-field__field-select--add').click( e => { // add a new option
                e.preventDefault();
                const name = $(prefix +  '#new-field__field-select--name').val();
                const value = $(prefix + '#new-field__field-select--value').val();                            
                self.addOption(name, value);
                self.addTableRow(name, value); // add a new row to the table
            });

            $(prefix + '#select-table').on('click', '.new-field__field-select--remove-option', function(e) { // remove an option
                e.preventDefault();     
                const name = $(this).data('option');
                modal.removeOption(name);                       
            });

            $(prefix + '#modal-save').click( e => {
                e.preventDefault();                            
                $(prefix + '#modal-form__form-type').val(self.type); // set the modal type
                if ( $(prefix + '#modal-form__type').val() == 'select'  ) // if it's a type select value is a JSON of the options
                    $(prefix + '#modal-form__value').val( JSON.stringify( self.options ) ); // write the options
                $(prefix + '#modal-form').submit(); // submit the form
            });

            $(document).keyup(function(e) { // for clossing and send the form data with keyboard
              if (e.keyCode === 13 && self.isOpen()) $(prefix + '#modal-save').click();     // enter & save                  
              if (e.keyCode === 27  && self.isOpen()) $(prefix + "#modal__close").click();   // esc & close
            });

            $(document).mousedown(function(e) { // clossing the modal when user clicks outside
                var container = $(prefix +".modal-content");
                // if the target of the click isn't the container nor a descendant of the container
                if (!container.is(e.target) && container.has(e.target).length === 0) $(prefix +"#modal__close").click();
            });
        }
    };                                   
    modal.init();
    return modal;
}

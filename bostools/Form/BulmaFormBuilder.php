<?php 

    /**
     * Form BulmaFormBuilder: outputs inputs with Bulma css framework    
     */
    class BulmaFormBuilder 
    {
        
        function __construct()
        {
        }

        public function createForm($action, $method, $class, $inputs)
        {
            ?>
                <form action="<?= $action?>" method="<?= $method?>" class="<?= $class?>">
            <?php
                foreach ($inputs as $input) {
                    $this->input($input);
                }
                $this->buttonSubmit();
            ?>
                </form>
            <?php
        }

        public function input($options)
        {
            if ( !isset($options['type']) || !isset($options['value'])  || !isset($options['field_name']) ) {
                throw new Exception("Missing parameters ".implode(", ",$options), 1);                
            }
            switch ($options['type']) {
                case 'string':
                case 'text':
                    ?>
                     <div class="field">
                      <label class="label <?= $options['extraClass'] ?? '' ?>"><?= $options['field_name'];?></label>
                      <div class="control">
                        <input class="input" type="text" placeholder="<?= $options['field_name'];?>" name="<?= $options['field_name'];?>" value="<?= $options['value'];?>" >
                      </div>
                    </div>
                    <?php       
                    break;
                case 'integer':
                case 'int':
                case 'number':
                    ?>
                     <div class="field">
                      <label class="label"><?= $options['field_name'];?></label>
                      <div class="control">
                        <input class="input" type="number" placeholder="<?= $options['field_name'];?>" name="<?= $options['field_name'];?>" value="<?= $options['value'];?>" >
                      </div>
                    </div>       
                    <?php
                    break;
                
                case 'select':                 
                   ?> 
                    <div class="field">
                      <label class="label"><?= $options['field_name'];?></label>
                      <div class="control">
                        <div class="select is-primary">
                          <select name="<?= $options['field_name']?>">
                        <?php foreach (json_decode($options['value'], true) as $option): ?>                
                            <option value="<?= $option['value']?>"><?= $option['name'] ?></option>
                         <?php endforeach ?> 
                          </select>
                        </div>
                      </div>
                    </div> 
                    <?php
                    break;
                default:
                    # code...
                    break;
            }
        }

        public function buttonSubmit()
        {
            ?>
            <div class="control">
              <button class="button is-primary">Submit</button>
            </div>
            <?php
        }
    }
?>

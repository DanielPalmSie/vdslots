<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class WeekendBoosterInfoBoxBase extends DiamondBox
{

    function init()
    {
        $this->handlePost(
            array(
                'old_deal_string',
                'new_deal_string',
                'box_class'
            ),
            array(
                'old_deal_string' => 'weekend.booster.external.info',
                'new_deal_string' => 'weekend.booster.external.info.vault',
                'box_class' => 'frame-block'
            )
        );

        $this->cur_lang = phive('Localizer')->getLanguage();
    }

    /**
    *
    * Check if editcontent query string is passed
    *
    * @return bool
    */
    public function isEditing()
    {
        return isset($_GET['editcontent']);
    }

    /**
    *
    * Used to write javascript blocks
    *
    * @return void
    */
    public function js()
    { ?>
      <script>
          function showContent(params) {
              params.operator = $("#operator :selected").val();
              var func1Params = Object.assign(
                  {},
                  params,
                  { func: 'printContent' },
                  { page_id: '<?= phive('Pager')->getId() ?>' },
              );

              new Promise((resolve, reject) => {
                  ajaxGetBoxHtml(func1Params, '<?php echo $this->cur_lang ?>', <?php echo $this->getId() ?>, function (ret) {
                      $("#show-page-content").html(ret);
                      resolve();
                  });
              });
          }

          $(document).ready(function () {
              showContent({});
          });
      </script>
    <?php
    }

    /**
    *
    * Used to check if ajax call or normal print content
    *
    * @return void
    */
    function printHtml()
    {
        // Show plain content if no page found OR isEditing is true
        // Else show ajax content
        if (!phive()->isAjaxCacheAdded() || $this->isEditing()) {
            $this->printContent();
        } else {
            $this->js();
        ?>
          <div id="show-page-content"></div>
        <?php
        }
    }

    /**
    *
    * Used to print content
    *
    * @return void
    */
    public function printContent()
    {
        $page_id                = (!empty($_REQUEST['page_id'])) ? $_REQUEST['page_id'] : phive('Pager')->getId();
        $box_id                 = $this->getId();
        ?>
        <div class="<?php echo $this->box_class ?>">
            <div class="frame-holder <?php echo $page_id; echo $box_id; ?>">
                <?php
                if(phive('Pager')->edit_content) {
                    ?>
                    <h1 style="color:red;">-- Old deal content --</h1>
                    <?php echo t($this->old_deal_string); ?>
                    <br>
                    <hr>
                    <br>
                    <h1 style="color:red;">-- New deal content --</h1>
                    <?php echo t($this->new_deal_string);
                } else {
                    $user = cu();
                    if (empty($user) || phive('DBUserHandler/Booster')->hasBoosterVault($user) === true) {
                        echo t($this->new_deal_string);
                    } else {
                        echo t($this->old_deal_string);
                    }
                }
              ?>
            </div>
        </div>
      <?php
    }

    function printExtra()
    { ?>
        <p>
            <label>Old deal string:</label>
            <input type="text" name="old_deal_string" value="<?php echo $this->old_deal_string ?>"/>
        </p>
        <p>
            <label>New deal string: (Booster vault)</label>
            <input type="text" name="new_deal_string" value="<?php echo $this->new_deal_string ?>"/>
        </p>
        <p>
            <label>Box class: </label>
            <input type="text" name="box_class" value="<?php echo $this->box_class ?>"/>
        </p>
        <?php
    }
}

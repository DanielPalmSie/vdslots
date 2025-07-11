<?php

class RealityCheckButtons 
{
  private $buttons = [];
  private $urls = [];

  function __construct($buttons = [])
  {
    $this->buttons = $buttons;
    foreach ($this->buttons as $button) {
        $this->urls[$button['action']] = $button['url'];
    }
  }

  public function printButtons($in_game = false)
  {
    $base_id = 'dialogRc';
    $base_class= 'button-rc dialogWindowDualButton';

    foreach ($this->buttons as $button) {
      $suffix = ucfirst($button['action']) . 'Button';
      $id = $base_id . $suffix;
      $class = $base_class . $suffix;
      $style = $this->getCustomStyle($button['action']);
      $on_click = "doOnClick$suffix()";

      ?>
      <div id="<?= $id ?>" class="<?= $class?>" style="<?= $style ?>" onclick="<?= $on_click?>" data-ingame="<?= $in_game ? 'true' : '' ?>">
          <?= t($button['string']) ?>
      </div>
      <?  
    }
  }

  public function getCustomStyle($action='')
  {
    switch ($action) {
      case 'continue':
      case 'gameHistory':
      case 'responsibleGaming':
        $style = 'display: inline-block;';
        break;
      case 'leaveGame':
        $style = "display: ". (isset($_POST['show_leave_game_button']) && $_POST['show_leave_game_button'] == "false" ? 'none' : 'inline-block') .";";
        break;      
    }
    return $style;
  }

  public function printJs()
  {
    ?>
    <script type="text/javascript">
      function doOnClickContinueButton(){      
        var isDefaultLicenseRcPopup = <?= isset($_POST['isDefaultLicenseRcPopup']) ? 1 : 0 ?>;

        if (isDefaultLicenseRcPopup) {
          mboxClose('rc-msg');
          return;
        }
        // We've to check if we have MessageProcessor defined, as this could be called from a different context
        if (typeof MessageProcessor !== 'undefined' && typeof MessageProcessor.request === 'function') {
          MessageProcessor.resumeGame().then(function(){ mboxClose('rc-msg') });
        } else {
          mboxClose('rc-msg');
        }
      }

      function doOnClickGameHistoryButton(){
        var isDefaultLicenseRcPopup = <?= isset($_POST['isDefaultLicenseRcPopup']) ? 1 : 0 ?>;
        if (isDefaultLicenseRcPopup) {
          window.open("<?= $this->urls['gameHistory'] ?>","_self");
          mboxClose('rc-msg');
          return;
        }
        window.open("<?= $this->urls['gameHistory'] ?>");
      }

      function doOnClickResponsibleGamingButton(){
          var isDefaultLicenseRcPopup = <?= isset($_POST['isDefaultLicenseRcPopup']) ? 1 : 0 ?>;
          if (isDefaultLicenseRcPopup) {
              window.open("<?= $this->urls['responsibleGaming'] ?>","_self");
              mboxClose('rc-msg');
              return;
          }
          window.open("<?= $this->urls['responsibleGaming'] ?>");
      }

      function doOnClickLeaveGameButton(){
        window.top.location.href = '<?= $this->urls['leaveGame'] ?>';
      }
      function doOnClickAcceptButton(){
        if (licFuncs.acceptRealityCheck) {
            licFuncs.acceptRealityCheck()
        } else {
            window.top.location.href = '<?= $this->urls['accept'] ?>';
        }
      }
    </script>
    <?
  }
}

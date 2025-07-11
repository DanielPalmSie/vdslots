<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/DepositBoxBase2.php';
class DepositBox2 extends DepositBoxBase2{
  function printHTML(){
    parent::printHTML();
    ?>
    <script>
     $(document).ready(function(){
       setIframeColor('black', 100);
     });
    </script>
    <?php
  }

}

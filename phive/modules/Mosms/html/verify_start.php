<?php
$user = cu();
if(empty($user))
    die("Timed out");
?>
<script>
  function submitMobile(callb, psp){
    $.post(
      '/phive/modules/Mosms/ajax/verify.php',
      {
        action: "send-sms",
        mobile: $("#mobile").val(),
        country: $("#country").val(),
        lang: "<?php echo phive('Localizer')->getLanguage() ?>",
        psp: psp
      },
      function(res){
        if(res.indexOf("<script") != -1) {
          $("#verify-start").replaceWith(res);
          if(typeof callb != 'undefined')
            callb.call();
        } else {
          $("#infotext").html(res);
        }
    });
  }

  function fbCloseDepGo(){
    <?php echo depGo(true) ?>;
    fbClose();
  }
</script>
<div id="verify-start" class="margin-ten mobileVer">
  <b><?php et('mobile.verify.start') ?></b>

  <br/>
  <div>
    <?php et('mobile.verify.start'.($_REQUEST['show_cancel'] == 'yes' ? '.cancel' : '').'.html') ?>
  </div>
  <br/>

  <input id="mobile" name="mobile" class="cashierDefaultInput" type="text" value="<?php echo $user->getAttribute('mobile') ?>">
  <br/>
  <?php if(phive('UserHandler')->userBankCountry($user) == false): ?>
    <div style="width: 300px;">
      <?php et('mobile.verify.nocountry') ?>
    </div>
    <br/>
    <?php dbSelect("country", phive('Cashier')->getBankCountries(''), $_POST['country'], array('', t('choose.country'))) ?>
    <br/>
    <br/>
  <?php endif ?>
  <table class="device-adjustment1">
    <tr>
      <td>
	<div class="cashierBtnOuter">
	  <div class="cashierDefaultBtnInner" onclick="submitMobile(undefined, '<?php echo $_REQUEST['psp'] ?>')">
	    <h4><?php et('submit'); ?></h4>
	  </div>
	</div>
      </td>
      <?php if($_REQUEST['show_cancel'] == 'yes'): ?>
	<td>
	  <div class="cashierBtnOuter">
	    <div class="cashierDefaultBtnInner" onclick="fbCloseDepGo()">
	      <h4><?php et('cancel'); ?></h4>
	    </div>
	  </div>
	</td>
      <?php endif ?>
    </tr>
  </table>

  <div id="infotext" class="errors">
  </div>
</div>

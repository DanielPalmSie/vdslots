<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/DiamondBoxBase.php';
class DiamondBox extends DiamondBoxBase{

  function newsArchive($archived_months, $base_link){
    $amonths = array();

    foreach($archived_months as $m){
      $amonths[] = array($m[0], date('Y-m-t', strtotime($m[0])));
    }

  ?>
  <h3 class="big_headline"><?php echo t('news.archive.headline') ?></h3>
  <?php foreach($amonths as $m): ?>
    <div class="archive-month">
      <a class="a-big" href="<?php echo phive('Localizer')->langLink('', "/$base_link/?start_date={$m[0]}&end_date={$m[1]}" ) ?>">
        <?php echo et('month.'.date('m', strtotime($m[0]))). " " .date('Y', strtotime($m[0])); ?>
      </a>
    </div>
  <?php endforeach ?>
  <?php
  }

  function hbars($item, $key, $hbars = false){
    echo $hbars ? '{{'.$key.'}}' : $item[$key];
  }

    /**
     * Checks if the user is allowed to play Netnet games based on his location
     *
     * TODO refactor the canNetent function to work properly as all countries have netent now but the FS banner might be different
     *
     * @return bool
     */
    public function canNetent()
    {
        return true;

        $country = !empty($_SESSION['rstep1']['country']) ? $_SESSION['rstep1']['country'] : phive('IpBlock')->getCountry();

        // check if Netent games are allowed for users from this country
        $fspin = phive('Config')->getByTagValues('freespins');
        $can_do = function($key) use ($country, $fspin){
            return in_array(strtolower($country), explode(',', strtolower($fspin[$key])));
        };
        $can_netent = $can_do('netent-reg-bonus-countries');

        return $can_netent;
    }

    /**
     * Move this function here, so we can use it in other box classes too.
     * TODO check if we can move this into mg_casino.js and avoid having a wrapper only for this /Paolo
     *  add common variable on JS for yes/no/user_id (probably already exist)
     */
    function printAccSaveDialog()
    {
        ?>
        <script type="text/javascript">
            function accSaveDialog(action, options, func){
                if(empty(options))
                    options = {};

                const html = options.html ? options.html : `<?= t('responsible.confirm.html') ?>`;
                const onOk = function() {
                    options.rg_duration = $('#rg-duration-'+action).find('input:checked').val();

                    saveAccCommon(action, options, function(ret) {
                        func.call(this, ret);
                        $("#limform_"+action).hide(200);
                        $("#lowerform_"+action).hide(200);
                        $("#start_"+action).hide(200);
                    });
                };

                var saveDialogImage = !is_old_design ? 'max-bet-limit-reached.png' : '';

                mboxDialog(
                    html,
                    'mboxClose()',
                    '<?php et('no') ?>',
                    onOk,
                    '<?php et('yes') ?>',
                    'mboxClose()',
                    undefined,
                    true,
                    'rg-limit-popup__btn rg-limit-popup__cancel-btn',
                    null,
                    'rg-limit-popup__btn rg-limit-popup__confirm-btn',
                    'rg-limit-popup__container',
                    saveDialogImage
                );
            }
        </script>
        <?php
    }

    /**
     * Return the language variation of the attribute (if exist and not empty)
     * based on the currently selected language, otherwise return default one.
     * Default attribute: attribute_name
     * Language attribute: attribute_name_xx (xx = language)
     *
     * @param $key
     * @return mixed
     */
    protected function getBoxAttributeOverrideByLanguage($key)
    {
        $languages = [phive('Localizer')->getDomainLanguageOverwrite(), cLang()];

        foreach ($languages as $lang) {
            $lang_attribute = $this->{"{$key}_{$lang}"};
            if (!empty($lang_attribute)) {
                return $lang_attribute;
            }
        }

        return $this->{$key};
    }

    protected function getJQueryUIVersion() {
        return phive('BoxHandler')->getSetting('new_version_jquery_ui') ?? '';
    }
}

<?php
require_once __DIR__ . '/../../api/PhModule.php';
define("IMAGE_LOCALE_ANY", "any");

class ImageHandler extends PhModule{
  private $uploadErrors = array(
    UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE',
    UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE',
    UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL',
    UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE',
    UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
    UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
    UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION');

  private $error;

  private $editMode = false;

    function __construct(){
        $this->fh = phive('Filer');
    }

  public function ImageHandler(){
    $this->editMode = false;
  }

    /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
    function uploadMulti()
    {
        $fs = $_FILES['files'];
        if (empty($fs['name']))
            return false;

        $alias = $_REQUEST['alias'];
        if(empty($alias))
            return false;

        $isos = cisos(true);

        if(!empty($_GET['new_alias'])) {
            // change the image alias
            $alias = $_GET['new_alias'];
        }

        $langs = array_keys(phive('Localizer')->getLangSelect());
        if (empty($_GET['existing_id'])) {

            // look for an existing id for this alias
            $eid = $this->getID($alias);
            if(empty($eid)) {
                $eid = phive('SQL')->nextAutoId('image_data');
            }

            $iarr = array('alias' => $alias, 'image_id' => $eid);
            phive('SQL')->save('image_aliases', $iarr);
        } else
            $eid = $_GET['existing_id'];

        $width = $_GET['width'];
        $height = $_GET['height'];

        for ($i = 0; $i < count($fs['name']); $i++) {
            $fname = $fs['name'][$i];
            if (strpos($fname, '.php') !== false)
                continue;

            $tmp = explode('.', $fname);
            $ext = array_pop($tmp);
            $target = array_pop($tmp);
            $tmp = explode('_', $target);
            $lang = strtolower(array_pop($tmp));
            $currency = strtoupper(array_pop($tmp));
            if (!in_array($currency, $isos))
                $currency = phive('Currencer')->baseCur();
            if (!in_array($lang, $langs))
                $lang = 'any';

            $filename = $this->getFilename($eid, $width, $height, $lang, $currency);
            if (empty($filename))
                $filename = $this->generateFilename($ext);
            $insert = array(
                'image_id' => $eid,
                'width' => $width,
                'height' => $height,
                'lang' => $lang,
                'original' => 1,
                'filename' => $filename,
                'currency' => $currency);

            if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {

                phive('Dmapi')->uploadPublicFile($fs["tmp_name"][$i], 'image_uploads', $filename);

                phive('SQL')->save('image_data', $insert);

            } else {
                $to_dir = $this->getSetting("UPLOAD_PATH") . "/" . $filename;
                if ($this->fh->moveUploadedFile($fs["tmp_name"][$i], $to_dir)) {
                    chmod($to_dir, 0777);
                    phive('SQL')->save('image_data', $insert);
                }
            }
        }
    }

    /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
  public function generateFilename($extension) {
    $ALPHABET = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = '';
    for($i = 0; $i < 10; ++$i)
      $str.=$ALPHABET[rand(0, strlen($ALPHABET)-1)];

    $str .= '.'.$extension;
    $tbl = $this->getSetting('TABLE_IMAGE_DATA');

    phive('SQL')->query("SELECT * FROM $tbl WHERE `filename` = '$str'");

    if(phive('SQL')->result() !== false)
      return $this->generateFilename($extension, $category);
    else
      return $str;
  }

  function getImageFromId($id, $all = false){
    $id = intval($id);
    $func = $all ? 'loadArray' : 'loadAssoc';
    return phive('SQL')->$func("SELECT * FROM image_data WHERE image_id = $id");
  }

  public function getID($alias) {
    $tbl = $this->getSetting("TABLE_IMAGE_ALIASES");
    $tbldata = $this->getSetting("TABLE_IMAGE_DATA");
    $alias = phive('SQL')->escape($alias);
    $str = "SELECT `$tbl`.`image_id` FROM $tbl INNER JOIN `$tbldata` ON `$tbl`.`image_id` = `$tbldata`.`image_id` WHERE `alias` = $alias LIMIT 1";
    return phive('SQL')->getValue( $str );
  }

  public function createAlias($alias, $id) {
    $entry = array('alias' => $alias, 'image_id' => $id);
    return phive('SQL')->insertArray($this->getSetting('TABLE_IMAGE_ALIASES'), $entry, null, true);
  }

  /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
  public function createImageFromUpload($postName, $existing_id = null, $locale = IMAGE_LOCALE_ANY, $cur = '') {

    if($existing_id)
      $this->deleteImage($existing_id, $locale, $cur);

    $info = pathinfo($_FILES[$postName]['name']);

    $extension = strtolower($info['extension']);

    list($width, $height, $type, $attr) = getimagesize($_FILES[$postName]['tmp_name']);

    if ($err=$_FILES[$postName]['error']){
      $this->error = $this->uploadErrors[$err];
      return false;
    }

    $tryext = substr(str_replace("jpeg", "jpg", image_type_to_extension($type)), 1);

    if($tryext != $extension){
      trigger_error("File upload attack? Extension used: $extension, extension determined from file: $tryext", E_USER_NOTICE);
      return false;
    }

    if(!in_array($extension, explode(" ", $this->getSetting('ALLOWED_TYPES')))) {
      trigger_error("$extension is not an allowed filetype", E_USER_NOTICE);
      return false;
    }

    $dir = $this->getSetting("UPLOAD_PATH");

    $salted_name = $this->generateFilename($extension);

    if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
        phive('Dmapi')->uploadPublicFile($_FILES[$postName]['tmp_name'], 'image_uploads', $salted_name);
    } else {
        if(!$this->fh->moveUploadedFile($_FILES[$postName]['tmp_name'], $dir.'/'.$salted_name)){
          trigger_error("move_uploaded_file({$_FILES[$postName]['tmp_name']}, $dir.'/'.$salted_name) failed!", E_USER_ERROR);
          return false;
        }

        chmod($dir.'/'.$salted_name, 0777);
    }

    $cache_dir = $this->getSetting("CACHE_PATH");

    if(!empty($cache_dir)){
      copy($dir.'/'.$salted_name, $cache_dir.'/'.$salted_name);
      chmod($cache_dir.'/'.$salted_name, 0777);
    }

    $entry = array("filename" => $salted_name, "width" => $width, "height" =>$height, "lang" => $locale, "original" => 1);

    $entry['image_id'] = empty($existing_id) ? phive("SQL")->nextAutoId('image_data') : $existing_id;

    if(!empty($cur))
      $entry['currency'] = $cur;

    if(phive('SQL')->insertArray($this->getSetting("TABLE_IMAGE_DATA"), $entry))
      return $entry['image_id'];

    return false;
  }

  /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
  function deleteAlias($alias){
    phive('SQL')->query("DELETE FROM image_aliases WHERE `alias` = '$alias'");
  }

  function getUnique($id){
    $id = intval($id);
    return phive('SQL')->loadAssoc("SELECT * FROM image_data WHERE id = $id");
  }

  /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
    function uniqueDelete($id, $unlink = true)
    {
        if ($image = $this->getUnique($id)) {
            if ($unlink) {
                if (phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                    phive('Dmapi')->deletePublicFile('image_uploads', $image['filename']);
                } else {
                    unlink($this->getSetting("UPLOAD_PATH") . '/' . $image['filename']);
                }
            }
            return phive("SQL")->delete('image_data', array('id' => $id));
            return $do_ok;
        }
    }

    /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
    public function deleteImage($id, $locale = null, $cur = '')
    {
        $id = phive('SQL')->escape($id);

        if ($locale !== null)
            $wc = " AND `lang` = " . phive('SQL')->escape($locale);

        if (!empty($cur))
            $where_cur = " AND currency = '$cur'";

        $tbl = $this->getSetting('TABLE_IMAGE_DATA');
        phive('SQL')->query("SELECT `filename` FROM $tbl WHERE `image_id` = $id $wc $where_cur");
        foreach (phive('SQL')->fetchArray() as $row) {
            if (phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                phive('Dmapi')->deletePublicFile('image_uploads', $row['filename']);
            } else {
                unlink($this->getSetting("UPLOAD_PATH") . '/' . $row['filename']);
            }
        }

        phive('SQL')->query("DELETE FROM $tbl WHERE `image_id` = $id $wc $where_cur");
    }

  public function getFilename($id, $width, $height, $locale = IMAGE_LOCALE_ANY, $cur = ''){
    $tbl = $this->getSetting('TABLE_IMAGE_DATA');
    $where_base = array('image_id' => $id, 'lang' => $locale);
    $where_size = array('width' => $width, 'height' => $height);
    if(!empty($cur)){
      $where_cur = array('currency' => $cur);
      $res = phive("SQL")->loadAssoc('', $tbl, array_merge($where_base, $where_size, $where_cur));
    }else
      $res = phive("SQL")->loadAssoc('', $tbl, array_merge($where_base, $where_size));

    if(!empty($res))
      return $res['filename'];
    else
      return false;
  }

  public function getOriginalFilename($id, $locale = IMAGE_LOCALE_ANY, $cur = ''){
    $where = array('image_id' => $id, 'lang' => $locale, 'original' => 1);
    if(!empty($cur))
      $where['currency'] = $cur;

    $res = phive("SQL")->loadAssoc('', $this->getSetting('TABLE_IMAGE_DATA'), $where);
    return empty($res) ? false : $res['filename'];
  }

  public function getOriginalSize($id, $locale = IMAGE_LOCALE_ANY, $cur = ''){
    $where = array('image_id' => $id, 'lang' => $locale, 'original' => 1);
    if(!empty($cur))
      $where['currency'] = $cur;

    $res = phive("SQL")->loadAssoc('', $this->getSetting('TABLE_IMAGE_DATA'), $where);
    if(empty($res))
      return false;
    return array($res['width'], $res['height']);
  }

    /**
     * Gets the filename of an image from the database and generate the uri to the file.
     * Returns false if the filename could not be determined.
     *
     * @param int     $id
     * @param int     $width
     * @param int     $height
     * @param string  $locale
     * @param string  $cur
     * @return mixed  string|boolean
     */
    public function getURI($id, $width = null, $height = null, $locale = IMAGE_LOCALE_ANY, $cur = '')
    {
        $filename = $this->getFilename($id, $width, $height, $locale, $cur);
        if($filename !== false) {
            return getMediaServiceUrl() . $this->getSetting("UPLOAD_PATH_URI") . '/' . $filename;
        }
        return false;
    }

  public function scaleSize($id, $max_width, $max_height, $locale=IMAGE_LOCALE_ANY, $cur = ''){
    list($w, $h) = $this->getOriginalSize($id, $locale, $cur);

    /*
     * These two if statements are here because sometimes we create "placeholder" image_data rows with height=0 and width=0
     * this is the failsafe that we will prevent the divide by zero problem.
     * We are setting into the vars maximum height/width because these are relatively reliable values for us
     * (because image of 0 width or height does not make sense.
     * So now the images stay always visible with sane values for width and height.
     */
    if ($h == 0) {
        $h = $max_height;
    }

    if ($w == 0) {
        $w = $max_width;
    }

    $r = $h/$w;
    if( $r*$max_width <= $max_height){
      $new_height = $r*$max_width;
      $new_width = $max_width;
    }
    else{
      $new_width = $max_height / $r;
      $new_height = $max_height;
    }
    $new_width = (float)round($new_width);
    $new_height = (float)round($new_height);
    if($new_width > $w)
      return array($w, $h);
    else
      return array($new_width, $new_height);
  }

  public function createScaledVersion($id, $width = null, $height = null, $locale = IMAGE_LOCALE_ANY, $cur = ''){
    $fn = $this->getSetting("UPLOAD_PATH").'/'.$this->getOriginalFilename($id, $locale, $cur);
    if($id === false)
      return false;

    $info = pathinfo($fn);
    switch($info['extension']){
      case 'jpg':
        $image = imagecreatefromjpeg($fn);
        break;
      case 'png':
        $image = imagecreatefrompng($fn);
        break;
      case 'gif':
        $image = imagecreatefromgif($fn);
        break;
      default:
        return false;
    }

    if ($image === false) {
      return false;
    }

    list($w, $h) = $this->getOriginalSize($id, $locale, $cur);
    $image_resized = imagecreatetruecolor($width, $height);
    imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $width, $height, $w, $h);

    $info = pathinfo($fn);
    $extension = strtolower($info['extension']);

    $salted_name = $this->generateFilename($extension);

    $fn = $this->getSetting("UPLOAD_PATH").'/'.$salted_name;
    if($extension == 'jpg')
      imagejpeg($image_resized, $fn, 85);
    else if($extension == 'png')
      imagepng($image_resized, $fn);
    else if($extension == 'gif')
      imagegif($image_resized, $fn);

    chmod($fn, 0644);
    $entry = array("image_id" => $id, "filename" => $salted_name, "width" => $width, "height" =>$height, "lang" => $locale);

    if(!empty($cur))
      $entry['currency'] = $cur;

    if(false === phive('SQL')->insertArray($this->getSetting("TABLE_IMAGE_DATA"), $entry))
      return false;
    else
      return $entry['image_id'];
  }

  function getColCommon($id, $col){
    $tbl = $this->getSetting('TABLE_IMAGE_DATA');
    return phive("SQL")->loadCol("SELECT DISTINCT `$col` FROM $tbl WHERE `image_id` = ".phive('SQL')->escape($id), $col);
  }

  public function getLocales($id) {
    return $this->getColCommon($id, 'lang');
  }

  function getCurrencies($id){
    return $this->getColCommon($id, 'currency');
  }

  public function getEditMode() {
    return $this->editMode;
  }

  public function setEditMode($mode = true) {
    $this->editMode = $mode;
  }

  public function searchImages($tag_pattern = null, $localizations = array() ) {
    if(!empty($localizations)){
      $localstring = "( 0 ";
      foreach($localizations as $l)
        $localstring .= "OR `lang` = '$l' ";
      $localstring .= " )";
    }

    //$tbl_tags = $this->getSetting("TABLE_IMAGE_TAGS");
    $tbl_data = $this->getSetting("TABLE_IMAGE_DATA");
    //if($tag_pattern !== null){
      //$tag_pattern = phive('SQL')->escape($tag_pattern);
      //$query = "SELECT `image_id` FROM $tbl_tags WHERE `tag` LIKE $tag_pattern";
      //if(!empty($localstring))
      //  $query.=" AND $localstring";
      //phive('SQL')->query($query);
      //$ret = array();
      //foreach(phive('SQL')->fetchArray() as $row){
      //  array_push($ret, $row['image_id']);
     // }
     // return $ret;
    //}
    //else{
      $query = "SELECT DISTINCT `image_id` FROM $tbl_data";
      if(!empty($localstring))
        $query.=" WHERE $localstring";
      phive('SQL')->query($query);
      $ret = array();
      foreach(phive('SQL')->fetchArray() as $row){
        array_push($ret, $row['image_id']);
      }
      return $ret;
    //}
  }

  public function getError(){
    return $this->error;
  }

  public function img($alias, $width = null, $height = null, $fallback_alias = null, $locale = null, $default_image = ''){

    if(!$width && $height)
      $width = 1000000;
    if(!$height && $width)
      $height = 1000000;
    if(!$width) {
      trigger_error("img() called without size constraints", E_USER_WARNING);
      return;
    }

    $img_id = false;
    if(!empty($alias)) {
        $img_id = $this->getID($alias);
    }

    if(!$img_id && $fallback_alias !== null){
      return $this->img($fallback_alias, $width, $height, null, null, $default_image);
    }

    if($locale === null){
      if(phive()->moduleExists('Localizer'))
        $locale = phive("Localizer")->getLanguage();
      else
        $locale = IMAGE_LOCALE_ANY;
    }

      $image_exists = true;
      $jurisdiction = phive('Localizer')->getDomainLanguageOverwrite();
      if ($img_id) {
          $locales = $this->getLocales($img_id);
          if (in_array(strtolower($jurisdiction), $locales)) {
              $locale = $jurisdiction;
          } elseif (!in_array($locale, $locales)) {
              if (in_array(IMAGE_LOCALE_ANY, $locales)) {
                  $locale = IMAGE_LOCALE_ANY;
              } else {
                  $image_exists = false;
              }
          }
      } else {
          $image_exists = false;
      }

    if(phive()->moduleExists('Currencer') && phive("Currencer")->getSetting('multi_currency')){
      $cur = ciso();
      $currency_exists = in_array($cur, $this->getCurrencies($img_id));
    }else
      $currency_exists = false;

    if($image_exists && $currency_exists)
      list($uri, $w, $h) = $this->fixScaleGetUri($img_id, $width, $height, $locale, $cur);
    else if($image_exists)
      list($uri, $w, $h) = $this->fixScaleGetUri($img_id, $width, $height, $locale);
    else{
      $w = $width;
      $h = $height;
      $uri = !empty($default_image) ? $default_image : phive()->getPath()."/media/ImageHandler/spacer.png";
    }

    return array($uri, $w, $h, $img_id, $image_exists);
  }

  function fixScaleGetUri($img_id, $width, $height, $locale, $cur = ''){
    list($w, $h) = $this->scaleSize($img_id, $width, $height, $locale, $cur);
    $uri = $this->getURI($img_id, $w, $h, $locale, $cur);
    if($uri === false) {
      $this->createScaledVersion($img_id, $w, $h, $locale, $cur);
      $uri = $this->getURI($img_id, $w, $h, $locale, $cur);
    }
    return array($uri, $w, $h);
  }

  function deleteById($id){
    phive("SQL")->delete('image_data', array('id' => $id));
  }

  public function imgInfo($alias, $width = null, $height = null, $fallback_alias){
    list($uri, $w, $h, $img_id, $image_exists) = $this->img($alias, $width, $height, $fallback_alias);

    if ($image_exists){
      $local_uri = $this->getSetting('UPLOAD_PATH').'/'.basename($uri);
      $size = filesize($local_uri);
      $info = getimagesize($local_uri);
      $ret = array(
        'uri'=>$uri,
        'width'=>$w,
        'height'=>$h,
        'img_id'=>$img_id,
        'size'=>$size,
        'mime'=>$info['mime']);
      return $ret;
    }
    else
    {
      return null;
    }
  }


    public function getImageAliasForBonusCode($base_alias)
    {
        if (lic('blockAffiliateBonusCode') === true) {
            return false;
        }

        $bonus_code = phive('Bonuses')->getBonusCode();

        return phive('SQL')->getValue(
            "SELECT image_alias
            FROM image_aliases_connections
            WHERE bonus_code = '{$bonus_code}'
            AND image_alias LIKE '{$base_alias}%'");
    }
}

function img($alias, $width = null, $height = null, $fallback_alias = null, $return = false, $locale = null, $uri_start = '', $default_image = '', $fetchPriority = true, $applyImageRatio = false){
  $ih = phive("ImageHandler");

  list($uri, $w, $h, $img_id) = $ih->img($alias, $width, $height, $fallback_alias, $locale, $default_image);   // if $alias does not exist, $img_id will always be the ID of $fallback_alias

  // Use $fallback_alias if $alias is empty or does not exist
  if(empty($alias) || $ih->getID($fallback_alias) == $img_id) {
      $alias = $fallback_alias;
  }

  $is_flash = strpos($uri, '.swf') === false ? false : true;

    if($return) ob_start();
    // Calculate aspect ratio if we have valid dimensions
    $style = '';
    $defaultHeightWidth = 1000000;
    if ($applyImageRatio && ($w > 0 && $w < $defaultHeightWidth) && ( $h > 0 && $h < $defaultHeightWidth) ) {
        $style = 'style="aspect-ratio: ' . $w . ' / ' . $h . ';"';
    }

  $editMode = $ih->getEditMode();
  if(!$editMode):?>
    <?php if($is_flash): ?>
      <object width="<?php echo $w ?>" height="<?php echo $h ?>">
        <param name="wmode" value="transparent" />
        <param name="movie" value="<?php echo $uri ?>"></param>
        <param name="allowFullScreen" value="false"></param>
        <embed wmode=transparent src="<?php echo $uri ?>" <?php echo $style; ?>  type="application/x-shockwave-flash" width="<?php echo $w ?>" height="<?php echo $h ?>">
        </embed>
      </object>
    <?php else: ?>
      <img <?php echo $fetchPriority ? 'fetchpriority="high"' : 'loading="lazy"'; ?> border="0" alt="<?=$alias?>" src="<?php echo $uri_start.$uri?>" <?php echo $style; ?>  />
    <?php endif ?>
  <?php else:
    $options = "&width=$width&height=$height";
    if($img_id && !empty($ih->getID($alias))) {     // Only add the existing_id to the url, if it is indeed the ID of $alias
      $options.="&existing_id=$img_id";
    }
    if($is_flash)
      $uri = phive()->getPath()."/media/ImageHandler/spacer.png";
    ?>

    <?php
    // link directly to new BO interface if it's enabled
    if(phive('ImageHandler')->getSetting('new_backoffice_interface')):
    ?>
    <a style="cursor: hand;" href="#" onclick="window.open('/admin2/cms/uploadimages?alias=<?=$alias?>&image_id=<?=$img_id?>');">
      <img src="<?=$uri?>" width="<?=$w?>" height="<?=$h?>" <?php echo $style; ?> />
    </a>
    <?php else: ?>
    <a style="cursor: hand;" href="#" onclick="window.open('/phive/modules/ImageHandler/html/uploader.php?alias=<?=$alias?><?=$options?>', 'picker_window', 'directories=0,location=0,toolbar=0,resizable=1,scrollbars=1,status=0,width=800,height=600');">
      <img src="<?=$uri?>" width="<?=$w?>" height="<?=$h?>" <?php echo $style; ?> />
    </a>
    <?php endif ?>
  <?php endif ?>

  <?php
  if($return) return ob_get_clean();
}

<?php
require_once __DIR__ . '/../../api/PhModule.php';

class Filer extends PhModule{
  private $error;

    function __construct(){
        $this->host_alias  = phive()->getSetting('host_alias');
        $this->cluster     = $this->getSetting('cluster');
        //Current box is implicitly a part of the cluster and a node in it
        $this->c_nodes     = $this->getSetting('cluster_nodes');
        $this->is_c_master = $this->getSetting('cluster_master');
    }

    /**
     * Always to be run as CLI root on the master, probably through Rabbit MQ, all nodes also need to be connected to the same Redis Cluster.
     * Requires all boxes to know of each other, if non-IPs are used the aliases have to be in /etc/hosts on all machines.
     * All directories have to look exactly the same on all machines.
     */
    function syncUpload($source_host, $source_dir){
        //Upload happened on master
        if($this->is_c_master && $source_host == $this->host_alias){
            $this->execOnNodes("scp $source_dir root@{{n}}:$source_dir", $source_host);
        }else{ //Upload happened on slave
            //We copy from slave to local first
            $this->execOnNode("scp root@$source_host:$source_dir $source_dir");
            $this->execOnNodes("scp $source_dir root@$node:$source_dir", $source_host);
        }
    }

    /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
    function moveUploadedFile($from, $to){
        $res = move_uploaded_file($from, $to);
        if($this->cluster === true && $res)
            phive('Site/Publisher')->single('main', 'Filer', 'syncUpload', [$this->host_alias, $to], true);
        return $res;
    }

    /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
  function uploadFile($postName, $subDir = '', $newName = null, $returnName = false, $allowed = array()){
    $info = pathinfo($_FILES[$postName]['name']);
    $extension = strtolower($info['extension']);
    if ($err=$_FILES[$postName]['error']){
      $this->error = $this->uploadErrors[$err];
      return false;
    }

    if(!empty($allowed) && !in_array(strtolower($extension), $allowed)){
      return false;
    }
    $dir = empty($subDir) ? $this->getSetting("UPLOAD_PATH") : $this->getSetting("UPLOAD_PATH").'/'.$subDir;
    if(!empty($newName)){
      $arr = explode('.', $_FILES[$postName]['name']);
      $ext = array_pop($arr);
      $fname = implode('', $arr);
      $newName = "$newName.$ext";
    }else
      $newName = $_FILES[$postName]['name'];

    $filepath = $dir . '/' . $newName;

    if(strpos($filepath, '.php') !== false)
      return false;

    if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
        phive('Dmapi')->uploadPublicFile($_FILES[$postName]['tmp_name'], 'file_uploads', $newName);
    } else {
        //$this->moveUploadedFile($_FILES[$postName]['tmp_name'], '/tmp/apa.jpg');
        if(!$this->moveUploadedFile($_FILES[$postName]['tmp_name'], $filepath)){
            trigger_error($this->error="move_uploaded_file({$_FILES[$postName]['tmp_name']}, $filepath) failed!", E_USER_ERROR);
            return false;
        }
        chmod($filepath, 0644); //let other users view the image (good for debugging etc.)
    }

    return $returnName == false ? $filepath : $newName;
  }

    /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
    function uploadMulti(){
        $fs = $_FILES['files'];
        if(empty($fs['name']))
            return;
        for($i = 0; $i < count($fs['name']); $i++){
            $fname = $fs['name'][$i];
            if(strpos($fname, '.php') !== false)
                continue;
            if(getimagesize($fs["tmp_name"][$i]) === false)
                die("Incorrect file type");
            $subdir = empty($_REQUEST['folder']) ? '' : '/'.$_REQUEST['folder']; //TODO If still used fix the Path Traversal vulnerability here
            $to_dir = $this->getSetting("UPLOAD_PATH").$subdir."/".$fname;

            if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                phive('Dmapi')->uploadPublicFile($fs["tmp_name"][$i], 'file_uploads', $fname, $subdir);
            } else {
                $this->moveUploadedFile($fs["tmp_name"][$i], $to_dir);
                chmod($to_dir, 0777);
            }
        }
    }

    /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
    public function multipleUpload($trustInput=false){
        //if (!is_array($_FILES["file"])) echo "_FILES[file] is not array";
        $upload_dir = $this->getSetting("UPLOAD_PATH")."/";

        foreach ($_FILES['file']['name'] as $key => $abs_filename) {
            if ($_FILES['file']['tmp_name'][$key]==null) continue;
            $tmp_name = $_FILES['file']['tmp_name'][$key];
            if(getimagesize($tmp_name) === false) die("Incorrect file type");
            if(strpos($abs_filename, '.php') !== false) die("Incorrect file type");
            $folder = (isset($_POST["file_".$key."_target"])) ? $_POST["file_".$key."_target"] : "";
            if ($folder != "" && $trustInput!==true) $folder = preg_replace('~[\W\s]~', "",$_POST["file_".$key."_target"]);
            $target =$upload_dir . $folder . "/" . $abs_filename;
            unset($_POST["file_{$key}_target"]);

            if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                phive('Dmapi')->uploadPublicFile($tmp_name, 'file_uploads', $abs_filename, $folder);
            } else {
                $this->moveUploadedFile($tmp_name, $target);
                chmod($target,0777);
            }
        }
    }


  function getFilePath($f){
    return $this->getSetting("UPLOAD_PATH").'/'.$f;
  }

    /**
     *
     * @param string $filepath
     * @return string
     */
    function getFileUri($filepath)
    {
        $url = getMediaServiceUrl() . $this->getSetting("UPLOAD_PATH_URI") . '/' . $filepath;

        return $url;
    }

  function hasFile($f){
    return file_exists($this->getFilePath($f));
  }

    /**
     * Has to be run as CLI root, on master, see syncUpload
     */
    function execOnNode($str, $node, $source_host){
        //If current node is same as upload happened on we don't do anything, the file is already deleted there
        if(!empty($node) && $node == $source_host)
            return;
        if($this->getSetting('debug') === true)
            phive('Logger')->debug('file_upload', $str);
        shell_exec($str);
    }

    function execOnNodes($str, $source_host){
        foreach($this->c_nodes as $node)
            $this->execOnNode(str_replace('{{n}', $node, $str), $node, $source_host);
    }

    /**
     * Has to be run as CLI root, on master, see syncUpload
     */
    function syncDelete($source_host, $source_dir){
        //We don't check if the file was deleted on the master or not, if it was the below line will simply return false, it doesn't "cost" much.
        unlink($source_dir);
        $this->execOnNodes("ssh root@{{n}} 'rm $source_dir'", $source_host);
    }

    /**
     * TODO: can be removed when we are using the new backoffice file uploads interface
     */
    function deleteFile($file){
        $dir = realpath($this->getSetting("UPLOAD_PATH"));
        $filepath = $dir.'/'.$file;
        //if (substr(realpath($filepath), 0, strlen($dir))==$dir)

        if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
            phive('Dmapi')->deletePublicFile('file_uploads', $file);        // ?? why don't we have subfolders here
        } else {
            unlink($filepath);
            if($this->cluster === true)
                phive('Site/Publisher')->single('main', 'Filer', 'syncDelete', [$this->host_alias, $filepath], true);
        }
    }

  function setDir($dir){
    $this->dir = $dir;
    return $this;
  }

  function getFolder($sort = 'name', $sort_type = SORT_ASC){
    $dir 	= empty($this->dir) ? $this->getSetting("UPLOAD_PATH") : $this->dir;
    $array 	= scandir($dir);
    $ret 	= array();
    foreach ($array as $file){
      $size = filesize($dir.'/'.$file);
      if ($file!='.' && $file!='..'){
        $ret[] = array(
          'name'			=> $file,
          'extension'		=> strtolower(pathinfo($file, PATHINFO_EXTENSION)),
          'size'			=> $size,
          'size_readable'	=> $this->formatSize($size));
      }
    }

    $sort_array = array();
    foreach ($ret as $key => $row)
        $sort_array[$key]  = $row[$sort];
    array_multisort($sort_array, $sort_type, $ret);

    return $ret;
  }

  function formatSize($size){
    if ($size > 1048576)
      return sprintf("%.2f&nbsp;MB", $size/1048576);
    else
    if ($size > 1024)
      return sprintf("%.2f&nbsp;KB", $size/1024);
    else
      return sprintf("%d&nbsp;B", $size);
  }

  function fixFileName($str){

    if(!function_exists('mb_convert_encoding'))
      return $str;

    $str = str_replace("#U", "\u", $str);

    if(!function_exists('replace_unicode_escape_sequence')){
      function replace_unicode_escape_sequence($match) {
          return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
      }
    }

    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);

  }

  function getError() { return $this->error; }
  function getFilesError($postName){
    $uploadErrors = array(
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
      );
    return $uploadErrors[$_FILES[$postName]['error']];
  }

  // Try generating a snippet of HTML from the file to include it onto
  //  a page.
  function generateHTML($file){
    $uri = $this->getPathURI() . '/' . $file['name'];
    switch($file['extension']){
    // Including Images
    case 'png':
    case 'jpg':
    case 'jpeg':
    case 'gif':
      return array('<img src="' . $uri . '" alt="" />', null);

    // Including Flash
    case 'swf':
      $html = <<<HTML
<!--[if !IE]> -->
<object type="application/x-shockwave-flash"
  data="$uri" width="__WIDTH__" height="__HEIGHT__">
<!-- <![endif]-->

<!--[if IE]>
<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
  codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"
  width="__WIDTH__" height="__HEIGHT__">
  <param name="movie" value="$uri" />
<!--><!--dgx-->	<param name="allowScriptAccess" value="sameDomain" />
  <param name="quality" value="high" />
  <param name="bgcolor" value="#000000" />
</object>
<!-- <![endif]-->
HTML;
      return array($html, array('__WIDTH__', '__HEIGHT__'));

    default:
      return array('<a href="'.$uri.'">'.$file['name'].'</a>',null);
    }
  }

  function getPathURI(){
    return $this->getSetting('UPLOAD_PATH_URI');
  }

  function downloadStr($str, $ftype, $fname){
    header("Pragma: public"); // required
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false); // required for certain browsers
    header("Content-Type: $ftype");
    header("Content-Disposition: attachment; filename=\"".$fname."\";" );
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".strlen($str));
    ob_clean();
      flush();
    echo $str;
  }

  function saveFile($save_path){
    if(!empty($_POST['file-contents'])){
      file_put_contents($save_path, $_POST['file-contents']);
      return true;
    }else
      return false;
  }

  /**
   * TODO: can be removed when we are using the new backoffice file uploads interface
   */
  function editForm($file_path, $box_id){
    ?>
    <form method="post">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <input type="hidden" name="box_id" value="<?php echo $box_id ?>" />
      <textarea name="file-contents" wrap="off" rows="30" cols="80">
        <?php echo file_get_contents($file_path) ?>
      </textarea>
      <input type="submit" value="Save"/>
    </form>
  <?php }


    /**
     *
     * @param string $url
     * @return boolean
     */
  /*
    function externalFileExists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($return_code == 200) {
            return true;
        }

        return false;
    }
  */

    /**
     * Checks for injected php code
     *
     * @param $fileContent
     * @return bool
     */
    function scanForMaliciousCode($fileContent) {
        // Sample patterns for common threats
        $patterns = array(
            '/\<\?\php/', // Basic PHP Tag
            '/base64_decode\([^\)]+\)/', // Base64 Decoding (potential malicious code)
            '/system\(\s*\'[^\']+\'\)\s*/',  // System command execution
            '/exec\(\s*\'[^\']+\'\)\s*/',  // Another command execution function
            '/eval\(\s*\'[^\']+\'\)\s*/',  // Another command execution function
        );

        // Loop through patterns and search for matches
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $fileContent)) {
                return true; // Potential malicious code found
            }
        }

        // No matches found
        return false;
    }

    /**
     * Validate uploaded file
     *
     * @param array $file_object
     * @param boolean $generate_new_name
     * @param null $file_size_limit
     * @return array
     */
    public function validateFileObject($file_object, $generate_new_name = false, $file_size_limit = null): array
    {
        // $messages currently not used, we'll seed them if we ever use the aliases
        // currently these are used only during automated tests to detect what type of issue was encountered
        $messages = [
            'file_upload.invalid_file_extension' => 'Invalid file extension.',
            'file_upload.invalid_file_type' => 'Invalid file type.',
            'file_upload.invalid_file_name_size' => 'Invalid file name, too many characters.',
            'file_upload.invalid_file_name_blocked' => 'Invalid file name, blocked characters found.',
            'file_upload.invalid_file_mime_type' => 'Invalid file mime type.',
            'file_upload.invalid_file_size' => 'Invalid file size.',
            'file_upload.invalid_file_size_internal' => 'Invalid file size.',
            'file_upload.invalid_content_type' => 'Invalid Content-Type header.',
            'file_upload.invalid_name_null' => 'Invalid file name.',
            'file_upload.invalid_multiple_extensions' => 'Invalid file name.',
            'file_upload.malicious_code' => 'Damaged file.',
        ];
        $valid_file_settings = $this->getSetting('valid_file_upload');
        $allowed_file_extensions = $valid_file_settings['allowed_file_extensions'];
        $allowed_file_mime_types = $valid_file_settings['allowed_file_mime_types'];
        $max_file_name_characters = $valid_file_settings['max_file_name_characters'];
        $blocked_characters = $valid_file_settings['blocked_characters'];

        $file_extension = strtolower(pathinfo($file_object['name'], PATHINFO_EXTENSION));
        if (!$file_size_limit) {
            $file_size_limit = $valid_file_settings['max_file_size'];
        }

        // Generate new file name
        if ($generate_new_name) {
            $file_name = phive()->uuid() . '.' . $file_extension;
        } else {
            $file_name = $file_object['name'];
        }

        // Test that there is only one extension used in the filename
        if (substr_count($file_name, '.') > 1) {
            return ['success' => false, 'error' => 'file_upload.invalid_multiple_extensions', 'data' => [$file_name]];
        }

        // Blacklist dangerous characters
        $matches = false;
        foreach ($blocked_characters as $char) {
            if (strpos($file_name, $char) !== false) {
                $matches = true;
                break;
            }
        }
        if ($matches) {
            return ['success' => false, 'error' => 'file_upload.invalid_file_name_blocked', 'data' => $file_name];
        }

        // Validate filename size - file name must be less than max number of characters
        if (mb_strlen($file_name) > $max_file_name_characters) {
            return ['success' => false, 'error' => 'file_upload.invalid_file_name_size', 'data' => [$file_name, $max_file_name_characters]];
        }

        // Validate file extension -  file extension must match one of the predefined list
        if (!in_array($file_extension, $allowed_file_extensions)) {
            return ['success' => false, 'error' => 'file_upload.invalid_file_extension', 'data' => $file_extension];
        }

        // Validate file type
        $mime_type = mime_content_type($file_object['tmp_name']);
        if (!in_array($mime_type, $allowed_file_mime_types)) {
            return ['success' => false, 'error' => 'file_upload.invalid_file_mime_type', 'data' => [$mime_type, $allowed_file_mime_types]];
        }

        // Validate Content-Type header - after the file type validation
        if (!in_array($file_object['type'], $allowed_file_mime_types)) {
            return ['success' => false, 'error' => 'file_upload.invalid_content_type', 'data' => [$mime_type, $file_object['type']]];
        }

        // Validate file size limit
        if (empty($file_object['size']) || $file_object['size'] > $file_size_limit) {
            return ['success' => false, 'error' => 'file_upload.invalid_file_size', 'data' => [$file_object['size'], $file_size_limit]];
        }

        // Validate file size limit of the file itself
        $uploaded_file_size = filesize($file_object['tmp_name']);
        if ($uploaded_file_size > $file_size_limit) {
            return ['success' => false, 'error' => 'file_upload.invalid_file_size_internal', 'data' => [$uploaded_file_size, $file_size_limit]];
        }

        // Prevent Null bytes
        if (strpos($file_name, "\0") !== false) {
            return ['success' => false, 'error' => 'file_upload.invalid_name_null', 'data' => [$file_name]];
        }

        //Prevent injected code
        $fileContent = file_get_contents($file_object['tmp_name']);
        if($this->scanForMaliciousCode($fileContent)){
            return ['success' => false, 'error' => 'file_upload.malicious_code', 'data' => [$file_name]];
        }

        return ['success' => true, 'filename' => $file_name];
    }

    /**
     * This is used by the document page to validate the uploaded files
     *
     * @param string $error_key
     * @return array
     */
    public function validateUploadedFiles($error_key): array
    {
        $valid_file_upload = phive('Filer')->getSetting('valid_file_upload');
        if (empty($valid_file_upload)) {
            phive('Logger')->error('File upload settings for key valid_file_upload in Filer are not set.');
            $errors[$error_key] = 'err.unknown'; // Unknown error, please contact support at...
            return $errors;
        }

        $files_count = 0; // make sure at least 1 file is uploaded
        $errors = [];

        foreach ($_FILES as $file) {
            $files_count++;

            $is_file_too_large = $file['size'] <= 0
                || $file['error'] === UPLOAD_ERR_INI_SIZE
                || $file['size'] > $valid_file_upload['max_file_size'];

            if ($is_file_too_large) {
                $errors[$error_key] = 'register.err.filesize.error';
                continue;
            }

            if (empty($file['tmp_name'])) {
                continue; // we didn't tmp store the file
            }

            $validation = phive('Filer')->validateFileObject($file);
            if ($validation['success']) {
                continue;
            }

            $errors[$error_key] = 'register.err.idpic.error';
            break;
        }

        if ($files_count === 0) {
            $errors[$error_key] = 'register.err.idpic.error';
        }

        return $errors;
    }
}


 // Global functions below this line


/**
 *
 * @param string  $filepath    The relative filepath, can have a subfolder too
 * @param bool    $return      Whether to return or echo the Uri
 * @param string  $default     Relative path to default file
 * @return string
 */
function fupUri($filepath, $return = false, $default = '')
{
    $folder = phive('Filer')->getSetting('UPLOAD_PATH_URI');  // => /file_uploads
    if(!empty($default) && !fileOrImageExists($folder, $filepath)) {
        $uri = phive('Filer')->getFileUri($default);
    }

    if(empty($uri)) {
        $uri = phive('Filer')->getFileUri($filepath);
    }

    if($return) {
        return $uri;
    }
    echo $uri;
}

/**
 * Checks if a file or image exists.
 * If setting media_service_url is not empty, it means we are getting
 * files and images from the external image service,
 * and we need to check if the file exists externally.
 *
 * @param string  $folder    The folder holding the file, ie image_uploads or file_uploads
 * @param string  $filepath  The filename optionally prepended with a subfolder, ie thumbs/filename.jpg
 * @return boolean
 */
function fileOrImageExists($folder, $filepath)
{
    switch ($folder) {
        case phive('ImageHandler')->getSetting('UPLOAD_PATH_URI'):  // ie: /image_uploads
            $upload_path = phive('ImageHandler')->getSetting('UPLOAD_PATH');
            break;

        case phive('Filer')->getSetting('UPLOAD_PATH_URI'):
            $upload_path = phive('Filer')->getSetting('UPLOAD_PATH');
            break;

        default:
            $upload_path = '';
            break;
    }

    $filepath = $upload_path . '/' . $filepath;

    if(file_exists($filepath)) {
        return true;
    }
    return false;
}

function getMediaServiceUrl($do_default = true)
{
    $url = phive('ImageHandler')->getSetting('media_service_url');  // will not end with a slash
    if(empty($url) && $do_default)
        return phive()->getSiteUrl();
    return $url;
}

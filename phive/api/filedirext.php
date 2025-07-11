<?php
class FileDirExt{

    /**
    * Opens a directory and stores the filenames in the array, has a directory object as parameter.
    */
    static function dirAsArr(&$d){
      $arr = array();
      for($i = 0; false !== ($entry = $d->read()); $i++){
        if($entry != "." && $entry != "..")
          array_push($arr,$entry);
      }
      return $arr;
  }

   /**
   * Same functionality as dirAsArr() but accepts a string representing the
   * directory name.
   *
   * @uses FileDirExt::dirAsArr()
   * @param string $strDir the directory path
   * @return array the array with filenames
   */
  static function list_dir($strDir){
    $d = dir($strDir);
    return self::dirAsArr($d);
  }

  static function listDirsInDir($strDir, $exclude = array()){
    return array_diff(self::list_dir($strDir), array_merge(self::list_files_in_dir($strDir), $exclude));
  }

  /**
   * Lists all the files in a directory, ignores subdirectories.
   *
   * @uses FileDirExt::dirAsArr() To get the filenames in an array
   * @param string $strDir The directory path to look in, must have trailing /
   */
  static function list_files_in_dir($strDir){
    $d = dir($strDir);
    $temp = self::dirAsArr($d);
    $rarr = array();
    foreach($temp as $item){
      if(is_file("$strDir/$item")){
        $rarr[] = $item;
      }
    }
    return $rarr;
  }

  static function findFileInDir($strDir, $needle){
    foreach(self::list_dir($strDir) as $f){
      if(strpos($f, $needle) !== false)
        return $f;
    }
    return '';
  }

  static function clearDir($strDir){
    $files = self::list_files_in_dir($strDir);
    foreach($files as $file){
      unlink("$strDir/$file");
    }
  }

  /**
  * Searches a file for needle and replaces with replacement
  */
  static function replaceInFile($fileName, $needle, $replacement){
      $lines = file($fileName);
      foreach($lines as &$line){
        $line = str_replace($needle, $replacement, $line);
    }
    return $lines;
  }

  /**
  * Gets the number of times the needle occurs in a file
  */
  static function searchCountInFile($fileName, $needle){
      $lines = file($fileName);
      if($lines === false)
        return 0;
      $count = 0;
      foreach($lines as &$line){
          $count += substr_count($line, $needle);
    }

    return $count;
  }


  /**
  * Writes an array to file
  */
  static function emptyWrite2D(&$arr, $fileName){
      $handle = fopen($fileName, "w+");
      foreach($arr as $line){
        fwrite($handle, $line);
    }
    fclose($handle);
  }

  /**
  * Replaces a needle in a whole directory
  */
  static function replaceInDir(&$dir, $needle, $replacement){
      $fileNames = self::dirAsArr($dir);
      foreach($fileNames as $fileName){
          $fileName = $dir->path."/".$fileName;
        $fileAsArr = self::replaceInFile($fileName, $needle, $replacement);
        self::emptyWrite2D($fileAsArr, $fileName);
    }
  }

  /**
  * Returns an array with all the files where the needle occurs
  */
  static function getTargetsInDir($dirstr, $needle){
    $dir = dir($dirstr);
      $fileNames = self::dirAsArr($dir);
      $files = array();
      foreach($fileNames as $fileName){
          $fileName = $dir->path."/".$fileName;
        if(self::searchCountInFile($fileName, $needle) > 0)
          array_push($files, $fileName);
    }

    return $files;
  }

  static function getTargetInDir($dirstr, $needle){
    $targets = self::getTargetsInDir($dirstr, $needle);
    return array_shift($targets);
  }

  /**
  * Creates a download.
  */
  static function download_file($filename, $ctype){
    header("Pragma: public"); // required
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false); // required for certain browsers
    header("Content-Type: $ctype");
    header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" );
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($filename));
    readfile("$filename");
  }
}

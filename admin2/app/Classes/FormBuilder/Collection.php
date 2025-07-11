<?php
namespace App\Classes\FormBuilder;
class Collection implements CollectionInterface {
  public function __construct() {
    
  }
  
  /**
   * Get form collections 
   * @param string $p_sCollectionName The collection name. Default: null
   * @return array
   */
  public function get($p_sCollectionName = null){
    // if $p_sCollectionName === null return all collection in a ordered manner
    
  }
}
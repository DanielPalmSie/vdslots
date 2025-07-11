<?php
require_once __DIR__ . '/../ArticleHandler/Article.php';
class LimitedArticle extends Article{
  protected $category_id 	= null;
  protected $start_date 	= null;
  protected $end_date 	= null;
  protected $tac 			= null;
  protected $header_image = null;
  protected $header_flash = null;
  public function __construct($pParent, $data=null,$user_id=null){
    parent::__construct($pParent, $data, $user_id);
    $keys = array(
      'category_id',
      'start_date',
      'end_date',
      'tac',
      'flash_header',
      'image_header',
      'header_image',
      'header_flash',
      'header_image_link',
      'image_link'
    );

    if(!is_array($data)){
      if ($this->contents !== false)
        $this->populateMe($this->contents, $keys);
    }else
      $this->populateMe($data, $keys);

  }

  function getTac(){ 			return $this->tac; 			}
  function getStartDate(){ 	return $this->start_date; 	}
  function getEndDate(){ 		return $this->end_date; 	}
  function getCategoryId(){ 	return $this->category_id; 	}
  function getHeaderImage(){	return $this->header_image;	}
  function getHeaderFlash(){	return $this->header_flash;	}
  function getImageLink(){	return $this->image_link;	}
  function getHeaderImageLink(){return $this->header_image_link;	}

  function getStatus(){
    $statuses = array(0 => array(t('active'), '#009900', 'active'), 1 => array(t('upcoming'), '#d5bf2f', 'upcoming'), 2 => array(t('finished'), '#cc0000', 'finished'));
    $sd = date($this->start_date);
    $ed = date($this->end_date);
    $d = date('Y-m-d');
    if($sd == '0000-00-00') return false;
    if($sd <= $d && $ed >= $d){
      return $statuses[0];
    }
    if($sd > $d){
      return $statuses[1];
    }
    if($ed < $d){
      return $statuses[2];
    }
    return false;
  }

  function setStartDate($start_date){    $this->setValue('start_date', $start_date);  }
  function setEndDate($end_date){    $this->setValue('end_date', $end_date);  }
  function setTac($tac){    $this->setValue('tac', $tac);  }

  function setImageHeadLink($link){
    $this->link = $link;
    $this->setValue('header_image_link', $link, false);
  }

  function setImageLink($link){
    $this->link = $link;
    $this->setValue('image_link', $link);
  }

  function setCategoryId($category_id){    $this->setValue('category_id', $category_id);  }
  function setAbstract($abstract){    $this->setValue('abstract', $abstract);  }
  function setHeaderFlash($flash_path){    $this->setValue('header_flash', $flash_path);  }
  function setHeaderImage($image_path){    $this->setValue('header_image', $image_path);  }

  function getTimeStatus(){
    if(!empty($this->time_status))
      return $this->time_status;

    $start_stamp 	= strtotime($this->getStartDate());
    $end_stamp 		= strtotime($this->getEndDate());

    if(time() < $start_stamp)
      $this->time_status = "upcoming";
    else if(time() > $end_stamp)
      $this->time_status = "old";
    else
      $this->time_status = "current";

    return $this->time_status;
  }

  function boxHtml($box_id){
    $box_name = phive('SQL')->queryAnd("SELECT box_class FROM boxes WHERE box_id = $box_id")->result();
    require_once __DIR__ . '/../../../'.$this->pParent->getSetting('site_folder')."/boxes/$box_name.php";
    $box = new $box_name($box_id);
    $box->init();
    ob_start();
    $box->printHTML();
    $content = ob_get_contents();
    ob_clean();
    return $content;
  }

  function callModule($m_name, $m_func){
    return phive($m_name)->$m_func();
  }

  function getParsedContent(){
    $rstr = '';
    foreach(explode('{}', $this->getContent()) as $piece){
      if(strpos($piece, 'box:') !== false){
        $tmp 	= explode(':', $piece);
        $rstr 	.= $this->boxHtml($tmp[1]);
      }else if(strpos($piece, 'module:') !== false){
        $tmp 	= explode(':', $piece);
        $rstr 	.= $this->callModule($tmp[1], $tmp[2]);
      }else
        $rstr 	.= $piece;
    }
      
    return rep($rstr);
  }
}

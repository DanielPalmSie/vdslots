<?php
/*
   use when you want to print the contents of a table, call with an array looking like this:
 * $all = array("width" => 680,
   "default_order" => "DESC",
   "order_by" => "user_id",
   "order" => "ASC",
   "top" => array("user_id","username","password"),
   "page" => 1,
   "pages" => 12,
   "data" => array(
   array(
   array("text" => "1"),
   array("text" => "admin","link" => "/profile/user/admin"),
   array("text" => "hemligt_losenord")
   ),
   array(
   array("text" => "2"),
   array("text" => "viljam","link" => "/profile/user/viljam"),
   array("text" => "banan")
   )
   ));
 */
function printTable($all){
    printStyles();
    $all['gets'] = (isset($all['gets']) && $all['gets'] != "")?$all['gets']."&":"";
?>
    <p style="text-align:center">Page: 
	<?php for($i = 1;$i<=$all['pages'];$i++):
	if($i == $all['page'])
	    echo " $i ";
	else
	    echo " <a href=\"?".$all['gets']."order=".$all['order']."&order_by=".$all['order_by']."&page=".$i."\">$i</a> ";
	endfor; ?>
    </p>
    <table style="width: <?php echo isset($all['width'])?$all['width']."px":"100%"?>;border-collapse:collapse">
	<?php printTop($all['top'],$all['order_by'],$all['order'],$all['default_order'],$all['gets']); 
	$i=0;
	foreach ($all['data'] as $d) {
	    printRow($d,$i++%2==0);
	}
	?>
	
    </table>
<?php
}

function printTh($text,$new_order,$order = null,$link = true,$gets="",$description=null){
?>
    <th class="header <?php if($order != null) echo 'choosen'?>">
	<?php if (!$link): ?>
	    <?php echo $text; ?>
	<?php else: ?>
	    <a href="<?php echo "?$gets"."order_by=$text&order=$new_order"; ?>">
                <?php echo ($description)?$description:$text; 
		if($order != null){
		    echo "&nbsp;";
		    echo ($order == "ASC")?"&darr;":"&uarr;";
		}
		?></a>
	<?php endif ?>
    </th>
<?php
}

function printTop($array,$selected,$order,$default_order,$gets){
    $new_order = ($order == "ASC")?"DESC":"ASC";
    echo "<tr>\n";
    foreach ($array as $k => $a) {
	$link = true;
	$description = null;
	if($k === "nolink")
	    $link = false;
	else if(!is_numeric($k)){
	    $description = $k;
	}
	if($selected == $a)
	    printTh($a,$new_order,$order,$link,$gets,$description);
	else
	    printTh($a,$default_order,null,$link,$gets,$description);
    }
    echo "</tr>\n";
}

function printRow($array,$gray=false){
    if(!$gray)
	echo "<tr>\n";
    else
	echo '<tr class="fill">';
    foreach ($array as $a) {
	if ($a['link'] != ""){
	    echo "\t<td class=\"sql_table\"><a href='".$a['link']."'>".$a['text']."</a></td>\n";
	}
	else{
	    echo "\t<td class=\"sql_table\">".$a['text']."</td>\n";
	}
    }
    echo "</tr>\n";
}
function getOrder($default_order){
    return ($_GET['order'] == "ASC")?"ASC":$default_order;
}
function getOrderBy($available,$default_order_by){
    return (in_array($_GET['order_by'],$available))?$_GET['order_by']:$default_order_by;
}
function getPage(){
    if(isset($_GET['page'])){
	return $_GET['page'];
    }
    return 1;
}

function printStyles(){
?>
    <style type="text/css" media="screen">
     th.header{
	 text-align:left;
	 color:white;
	 background-color:black;
	 padding-top:3px;
	 padding-bottom:3px;
	 padding-left: 3px;
	 text-align:center;
	 border: 1px solid gray;
     }
     th.header a:link,
     th.header a:visited,
     th.header a:hover,
     th.header a:active{
	 color:white;
	 font-weight:bold;
     }
     th.choosen{
	 background-color: #222;
     }
     td.sql_table{
	 border: 1px solid gray;
	 padding:1px;
     }
     tr.fill td{
	 background-color: #ccc;
     }
    </style>
<?php

}

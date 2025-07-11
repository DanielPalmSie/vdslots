<?php

$arr = explode('.', $_POST['alias']);
foreach($arr as &$sub){
    $sub = phive()->rmNonAlphaNums($sub);
}

et(implode('.', $arr), phive()->rmNonAlphaNums($_POST['lang']));

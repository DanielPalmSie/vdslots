<?php 
require_once __DIR__ . '/../../../phive.php';
$ih = phive("ImageHandler");
phive('Localizer')->setLanguage($_GET['lang']);
$ih->setEditMode();
?>

<?php img("bajsbild", 150, 150); ?>
<?php

require('ConfigTracer.php');
require_once '../../phive.php';
use Illuminate\Container\Container;

$config_dir = $_ENV['CONFIG_DIR'];

if (!$config_dir) {
    die();
}

$app_brand = $_ENV['APP_BRAND'] ?? 'videoslots';
$phive_path = phive()->getPath();
$tracer_path =  "$phive_path/utils/tracer/";
$local_config_file = "../../$config_dir/local.php";

if(!file_exists($local_config_file)){
    fopen($local_config_file, "w") or die("Unable create local.php file");
}

$local_config = include $local_config_file;
require_once phive('BrandedConfig')->getModulesFile();

if (phive('BrandedConfig')->isProduction() !== false) {
    die();
}
// if we are using the illuminate/container we need to make sure that all modules are initiated
if (phive('BrandedConfig')->newContainer()) {
    $app = Container::getInstance();
    // Retrieve all bindings
    $bindings = $app->getBindings();

    foreach ($bindings as $abstract => $binding) {
        $m = phive($abstract); // we don't do anything, just get it from the container so modules is populated
    }
}

//preloads all available modules
$modules = phive()->modules;
foreach ($modules as $module) {
    if (is_subclass_of($module, 'PhConfigurable')) {
        //for all PhConfigurable modules we are loading a configs
        $module->loadSettings();
    }
}



//gets tracer data from a BrandedConfig
$tracerData = tracer();
$order = $tracerData['order'];
$log = $tracerData['log'];

$action = $_GET['action'] ?? $_POST['action'];

if($action == 'open'){
    if($local_config == 1){
        $local_config = [];
    }
    echo json_encode($local_config);
    exit;
}


if($action == 'read'){
    $key = $_GET['key'];

    if($log['overriden'][$key]){
        $data = reset($log['overriden'][$key]);
    } else {
        $data = $log['defaults'][$key];
    }

    echo json_encode($data);

    exit;
}

if($action == 'save') {
    $key = $_POST['key'];
    $data = $_POST['data'];

    //empty local.php file
    if($local_config == 1){
        $local_config = [];
    }

    if($key){
        //updating variable
        $local_config[$key] = json_decode($data, true);
    } else {
        //updating local.php file
        $local_config = json_decode($data, true);
    }


    file_put_contents(
            $local_config_file,
            "<?php\nreturn " . var_export($local_config, true) . "\n ?>"
    );

    echo json_encode(['success'=>true, 'data'=>$data]); exit;
}


$n = new ConfigTracer();
$n->setConfigOrder($order);

ksort($log['overriden']);
ksort($log['defaults']);

?>
<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="<?= $tracer_path?>/nicer/nice_r.css?version=<?php echo filemtime('nice_r.css'); ?>"/>
    <script type="text/javascript" src="<?= $tracer_path?>/nicer/nice_r.js?version=<?php echo filemtime('nice_r.js'); ?>"></script>
    <link href="<?= $tracer_path?>/css/tracer.css" rel="stylesheet" type="text/css">
    <link href="<?= $tracer_path?>/jsoneditor/dist/jsoneditor.css" rel="stylesheet" type="text/css">
    <script src="<?= $tracer_path?>/jsoneditor/dist/jsoneditor.js"></script>
    <script src="<?= $tracer_path?>/js/tracer.js"></script>
    <meta name="csrf_token" content="<?php echo $_SESSION['token'];?>"/>
</head>
<body>
<div id="overriden">
    <h2>Overridden</h2>
    <?php
    $n->setData($log['overriden']);
    $n->render();
    ?>
</div>

<div id="defaults">
    <h2>Defaults</h2>
    <?php
    $n->setData($log['defaults']);
    $n->render();
    ?>
</div>

<div id="jsoneditor"></div>
<div id="informer">Changes will be saved to a <?= $local_config_file ?> file. 👉 <span class="open">Open file</span><span class="loader">⏳ Saving...</span></div>
</body>
</html>

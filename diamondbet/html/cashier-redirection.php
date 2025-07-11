<!doctype html>
<html lang="en">
<head>
    <meta name="robots" content="noindex">
</head>
<body>
<?php
$base_url = '/' . $_GET['to'];
unset($_GET['to']);
$url = $base_url . '?' . http_build_query($_GET);
?>
<script>
    // fix for non tracking cookie policy that causes redirection to lobby after deposit ends. ch129598
    window.location = '<?= $url ?>';
</script>
</body>
</html>


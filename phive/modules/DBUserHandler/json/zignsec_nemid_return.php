<?php
    // intent is to just pass the $_GET data to the login method
?>
<script>
    window.parent.submitLogin(<?= json_encode($_GET) ?>)
</script>

<?php

chdir(__DIR__.'/..');
require_once 'commonAdmin.php';

restartSession();

?><!DOCTYPE html>
<html>
<body>
    <script type="text/javascript">
        var platform = window.opener ? window.opener : parent;
        if(platform && platform['auth'] && platform.auth['callbackLogout']) {
            platform.auth.callbackLogout();
        }
        window.close();
    </script>
</body>
</html>
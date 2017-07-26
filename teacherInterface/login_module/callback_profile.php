<?php

chdir(__DIR__.'/..');
require_once 'commonAdmin.php';
require_once 'login_module/auth.php';

try {
    $client = new FranceIOI\LoginModuleClient\Client($config->login_module_client);
    $authorization_helper = $client->getAuthorizationHelper();
    $user = $authorization_helper->queryUser();
    $row = createUpdateUser($db, $user);
    $result = [
        'success'=> true,
        'user' => $row
    ];
} catch(Exception $e) {
    die($e->getMessage());
    $result = [
        'success' => true,
        'message' => $e->getMessage()
    ];
}

?><!DOCTYPE html>
<html>
<body>
    <script type="text/javascript">
        var platform = window.opener ? window.opener : parent;
        if(platform && platform['auth'] && platform.auth['callbackProfile']) {
            platform.auth.callbackProfile(<?=json_encode($result)?>);
        }
        if(!platform || platform === window) {
            // If we get there, we weren't in a popup and we can redirect
            window.location = '<?php echo $config->teacherInterface->baseUrl ?>';
        } else {
            window.close();
        }
    </script>
</body>
</html>
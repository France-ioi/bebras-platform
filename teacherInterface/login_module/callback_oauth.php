<?php

chdir(__DIR__.'/..');
require_once 'commonAdmin.php';
require_once 'login_module/auth.php';

try {
    $client = new FranceIOI\LoginModuleClient\Client($config->login_module_client);
    $authorization_helper = $client->getAuthorizationHelper();
    $authorization_helper->handleRequestParams($_GET);
    $user = $authorization_helper->queryUser();
    $row = createUpdateUser($db, $user);
    setUserSession($row);
    $result = jsonUser($db, $row, ['success'=> true]);
} catch(Exception $e) {
    $result = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}
?><!DOCTYPE html>
<html>
<body>
    <script type="text/javascript">
        var platform = window.opener ? window.opener : parent;
        if(platform && platform['auth'] && platform.auth['callbackLogin']) {
            platform.auth.callbackLogin(<?=json_encode($result)?>);
        }
        window.close();
        if(!platform || platform === window) {
            // If we get there, we weren't in a popup and we can redirect
            window.location = '<?php echo $config->teacherInterface->baseUrl ?>';
        }
    </script>
</body>
</html>

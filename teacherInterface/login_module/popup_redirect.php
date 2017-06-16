<?php

chdir(__DIR__.'/..');
require_once 'commonAdmin.php';

try {
    $client = new FranceIOI\LoginModuleClient\Client($config->login_module_client);
    $redirect_helper = $client->getRedirectHelper();
} catch(Exception $e) {
    die($e->getMessage());
}


$action = isset($_GET['action']) ? $_GET['action'] : die('Empty action');
switch($action) {
    case 'login':
        if(isset($_SESSION["userID"])) {
            restartSession();
        }
        $authorization_helper = $client->getAuthorizationHelper();
        $url = $authorization_helper->getUrl();
        break;
    case 'logout':
        $url = $redirect_helper->getLogoutUrl($config->teacherInterface->baseUrl.'login_module/callback_logout.php');
        break;
    case 'profile':
        $url = $redirect_helper->getProfileUrl($config->teacherInterface->baseUrl.'login_module/callback_profile.php');
        break;
    default:
        die('Invalid action');
}
header('Location: '.$url);
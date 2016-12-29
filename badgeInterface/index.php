<?php

require './functions.php';

// Use the path (without leading slash) as the badge name.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!is_string($path)) {
  exitWithJsonFailure('invalid path');
}
$badgeName = ltrim($path, '/');

// The action is passed as a POST param.
$action = getRequiredParam('action');

if ($action == 'verifyCode') {
  $code = getRequiredParam('code');
  exitWithJson(verifyCode($badgeName, $code));
}

if ($action == 'removeByCode') {
  $code = getRequiredParam('code');
  exitWithJson(removeByCode($badgeName, $code));
}

if ($action == 'updateInfos') {
  $code = getRequiredParam('code');
  $idUser = getRequiredParam('idUser');
  exitWithJson(updateAlgoreaRegistration($badgeName, $code, $idUser));
}

exitWithJsonFailure($action.' is not a valid action');

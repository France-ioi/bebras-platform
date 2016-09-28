<?php

if (!isset($_POST['errormsg'])) {
	echo json_encode(['success' => false, 'error' => 'missing errormsg argument']);
}

require_once("../shared/common.php");

initSession();

$teamID = isset($_SESSION["teamID"]) ? $_SESSION["teamID"] : null;
$questionKey = isset($_POST["questionKey"]) ? $_POST["questionKey"] : null;

$errormsg = $_POST['errormsg'];

$browserStr = 'Unknown-tmp';

$stmt = $db->prepare('insert into error_log (date, teamID, message, browser, questionKey) values (UTC_TIMESTAMP(), :teamID, :errormsg, :browserStr, :questionKey);');
$stmt->execute(['teamID' => $teamID, 'errormsg' => $errormsg, 'browserStr' => $browserStr, 'questionKey' => $questionKey]);
echo json_encode(['success' => true]);
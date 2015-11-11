<?php

if (!isset($_POST['errormsg'])) {
	echo json_encode(['success' => false, 'error' => 'missing errormsg argument']);
}

require_once("../shared/common.php");

initSession();

$teamID = isset($_SESSION["teamID"]) ? $_SESSION["teamID"] : null;

$errormsg = $_POST['errormsg'];

$stmt = $db->prepare('insert into error_log (date, teamID, message) values (NOW(), :teamID, :errormsg);');
$stmt->execute(['teamID' => $teamID, 'errormsg' => $errormsg]);
echo json_encode(['success' => true]);
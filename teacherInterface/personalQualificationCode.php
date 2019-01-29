<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("../commonFramework/modelsManager/modelsTools.inc.php");

if (!isset($_SESSION['userID'])) {
   die(json_encode(array('success' => false, 'error' => translate("session_expired"))));
   exit();
}

function getPersonalCode($contestID) {
	global $db;
	$stmt = $db->prepare("SELECT `code` FROM `algorea_registration` WHERE userID = :userID AND `code` LIKE 'prof%'");
	$stmt->execute(['userID' => $_SESSION['userID']]);
	$res = $stmt->fetchColumn();
   if (!$res) {
      return 0;
   }
   return $res;
}

function generateRandomCode() {
   global $db;
   srand(time() + rand());
   $charsAllowed = "0123456789";
   $base = 'prof';
   $query = "SELECT ID FROM algorea_registration WHERE code = :code;";
   $stmt = $db->prepare($query);
   while(true) {
      $code = $base;
      for ($pos = 0; $pos < 14; $pos++) {
         $iChar = rand(0, strlen($charsAllowed) - 1);
         $code .= substr($charsAllowed, $iChar, 1);
      }
      $stmt->execute(array('code' => $code));
      $row = $stmt->fetchObject();
      if (!$row) {
         return $code;
      }
      error_log("Error, code ".$code." is already used");
   }
}

function getPublicGroupID($contestID) {
	global $db;
	$stmt = $db->prepare('select ID from `group` where contestID = :contestID and isPublic=1;');
	$stmt->execute(['contestID' => $contestID]);
	return $stmt->fetchColumn();
}

function createPersonalCode($contestID) {
	global $db, $config;
	$code = generateRandomCode();
	$stmt = $db->prepare('SELECT firstName, lastName, gender FROM user WHERE ID = :userID;');
	$stmt->execute(['userID' => $_SESSION['userID']]);
	$user = $stmt->fetch();
	if (!$user) {
		die(json_encode(array('success' => false, 'error' => "Error, cannot find user ID ".$_SESSION['userID']."!")));
	}
	$genre = $user['gender'] == 'F' ? 1 : 2;
   /*
	$groupID = getPublicGroupID($contestID);
	if (!$groupID) {
		die(json_encode(array('success' => false, 'error' => "Error, cannot find public group for current contest!")));
	}
   */
	$registrationID = getRandomID();
   // TODO: make category depend on contest ?
	$stmt = $db->prepare("INSERT INTO `algorea_registration` (`ID`, `firstName`, `lastName`, `genre`, `userID`, `code`, `category`) VALUES ".
       "(:ID, :firstName, :lastName, :genre, :userID, :code, 'tour 2');");
	$stmt->execute([
		'ID' => $registrationID,
		'firstName' => $user['firstName'],
		'lastName' => $user['lastName'],
		'genre' => $genre,
		'code' => $code,
		'userID' => $_SESSION['userID'],
	]);
	return $code;
}

$contestID = $config->teacherInterface->teacherPersonalCodeContestID;
if (!$contestID) {
	die(json_encode(array('success' => true, 'code' => false)));
}
$personalCode = getPersonalCode($contestID);
if (isset($_POST['create']) && !$personalCode) {
	$personalCode = createPersonalCode($contestID);
}
echo json_encode(array('success' => true, 'code' => $personalCode));
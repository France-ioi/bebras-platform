<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("../commonFramework/modelsManager/modelsTools.inc.php");

if (!isset($_SESSION['userID'])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit();
}

function getPersonalCode() {
	global $db;
	$stmt = $db->prepare('select algoreaCode from contestant where userID = :userID;');
	$stmt->execute(['userID' => $_SESSION['userID']]);
	return $stmt->fetchColumn();
}

function generateRandomCode() {
   global $db;
   srand(time() + rand());
   $charsAllowed = "0123456789";
   $base = 'prof';
   $query = "SELECT ID as nb FROM contestant WHERE algoreaCode = :code;";
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

function getPreRankingContestID() {
	global $db;
	$stmt = $db->prepare('select ID from contest where status = \'PreRanking\';');
	$stmt->execute();
	return $stmt->fetchColumn();
}

function getPublicGroupID($contestID) {
	global $db;
	$stmt = $db->prepare('select ID from `group` where contestID = :contestID and isPublic=1;');
	$stmt->execute(['contestID' => $contestID]);
	return $stmt->fetchColumn();
}

function createPersonalCode() {
	global $db;
	$code = generateRandomCode();
	$stmt = $db->prepare('select firstName, lastName, gender from user where ID = :userID;');
	$stmt->execute(['userID' => $_SESSION['userID']]);
	$user = $stmt->fetch();
	if (!$user) {
		die("Error, cannot find user ID ".$_SESSION['userID']."!");
	}
	$genre = $user['gender'] == 'F' ? 1 : 2;
	$contestID = getPreRankingContestID();
	if (!$contestID) {
		die("Error, cannot find current contest!");
	}
	$groupID = getPublicGroupID($contestID);
	if (!$contestID) {
		die("Error, cannot find public group for current contest!");
	}
	$teamID = getRandomID();
	$stmt = $db->prepare('insert into contestant (firstName, lastName, genre, teamID, userID, algoreaCode) values (:firstName, :lastName, :genre, :teamID, :userID, :code);');
	$stmt->execute([
		'firstName' => $user['firstName'],
		'lastName' => $user['lastName'],
		'genre' => $genre,
		'teamID' => $teamID,
		'code' => $code,
		'userID' => $_SESSION['userID'],
	]);
	$password = genAccessCode($db);
	$stmt = $db->prepare('insert into team (ID, groupID, password) values (:teamID, :groupID, :password);');
	$stmt->execute([
		'groupID' => $groupID,
		'teamID' => $teamID,
		'password' => $password
	]);
	return $code;
}

$personalCode = getPersonalCode();
if (isset($_POST['create']) && !$personalCode) {
	$personalCode = createPersonalCode();
}
echo json_encode(array('code' => $personalCode));
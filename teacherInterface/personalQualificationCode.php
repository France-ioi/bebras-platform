<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("../commonFramework/modelsManager/modelsTools.inc.php");

if (!isset($_SESSION['userID'])) {
   die(json_encode(array('success' => false, 'error' => "Votre session a expiré, veuillez vous reconnecter.")));
   exit();
}

function getPersonalCode($contestID) {
	global $db;
	$stmt = $db->prepare('select algoreaCode from contestant join team on team.ID = contestant.teamID join `group` on `group`.ID = team.groupID where contestant.userID = :userID and `group`.contestID = :contestID;');
	$stmt->execute(['userID' => $_SESSION['userID'], 'contestID' => $contestID]);
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

function getPublicGroupID($contestID) {
	global $db;
	$stmt = $db->prepare('select ID from `group` where contestID = :contestID and isPublic=1;');
	$stmt->execute(['contestID' => $contestID]);
	return $stmt->fetchColumn();
}

function createPersonalCode($contestID) {
	global $db, $config;
	$code = generateRandomCode();
	$stmt = $db->prepare('select firstName, lastName, gender from user where ID = :userID;');
	$stmt->execute(['userID' => $_SESSION['userID']]);
	$user = $stmt->fetch();
	if (!$user) {
		die(json_encode(array('success' => false, 'error' => "Error, cannot find user ID ".$_SESSION['userID']."!")));
	}
	$genre = $user['gender'] == 'F' ? 1 : 2;
	$groupID = getPublicGroupID($contestID);
	if (!$groupID) {
		die(json_encode(array('success' => false, 'error' => "Error, cannot find public group for current contest!")));
	}
	$teamID = getRandomID();
	$contestantID = getRandomID();
	$stmt = $db->prepare('insert into contestant (ID, firstName, lastName, genre, teamID, userID, algoreaCode) values (:ID, :firstName, :lastName, :genre, :teamID, :userID, :code);');
	$stmt->execute([
		'ID' => $contestantID,
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

$contestID = $config->teacherInterface->teacherPersonalCodeContestID;
if (!$contestID) {
	die(json_encode(array('success' => true, 'code' => false)));
}
$personalCode = getPersonalCode($contestID);
if (isset($_POST['create']) && !$personalCode) {
	$personalCode = createPersonalCode($contestID);
}
echo json_encode(array('success' => true, 'code' => $personalCode));
<?php

/*
 * updates algorea_registration
 */

if (!isset($_GET['badgeName']) || !$_GET['badgeName'] || !isset($_POST['userInfos']) || !$_POST['userInfos']) {
	echo json_encode(['success' => false, 'error' => 'missing badgeName or userInfos']);
	exit();
}

$badgeName = $_GET['badgeName'];
$userInfos = $_POST['userInfos'];

if (!isset($userInfos['idUser']) || !isset($userInfos['code'])) {
	echo json_encode(['success' => false, 'error' => 'missing idUser or code in userInfos']);
	exit();
}

$code = $userInfos['code'];
$idUser = $userInfos['idUser'];

require_once '../shared/connect.php';

$stmt = $db->prepare('select algorea_registration.* from algorea_registration
	join contestant on contestant.ID = algorea_registration.contestantID
	join team on team.ID = contestant.teamID
	join `group` on `group`.ID = team.groupID
	join contest on contest.ID = `group`.contestID
	where algoreaCode = :code and contest.badgeName = :badgeName;');
$stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

$infos = $stmt->fetch(PDO::FETCH_ASSOC);

if ($infos) {
	if ($infos['franceioiID'] != $idUser) {
		echo json_encode(['success' => false, 'error' => 'code is already registered by someone else']);
	} else {
		echo json_encode(['success' => true]);
	}
	exit();
}

$stmt = $db->prepare('select contestant.ID from contestant
	join team on team.ID = contestant.teamID
	join `group` on `group`.ID = team.groupID
	join contest on contest.ID = `group`.contestID
	where algoreaCode = :code and contest.badgeName = :badgeName;');
$stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

$contestantID = $stmt->fetchColumn();

if (!$contestantID) {
	echo json_encode(['success' => false, 'error' => 'code is not valid']);
	exit();
}

$stmt = $db->prepare('insert into algorea_registration (code, contestantID, franceioiID) values (:code, :contestantID, :franceioiID);');
$stmt->execute(['code' => $code, 'contestantID' => $contestantID, 'franceioiID' => $idUser]);

echo json_encode(['success' => true]);

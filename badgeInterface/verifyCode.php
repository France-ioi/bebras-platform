<?php

/*
 * This file checks for a contestant.algoreaCode in the database and returns the information in json format.
 */

$code = null;
if (isset($_GET['code'])) {
	$code = $_GET['code'];
} elseif (isset($_POST['code'])) {
	$code = $_POST['code'];
}

if (!$code) {
	echo json_encode(null);
	exit();
}

if (!isset($_GET['badgeName']) || !$_GET['badgeName']) {
	echo json_encode(null);
	exit();
}

$badgeName = $_GET['badgeName'];

require_once '../shared/connect.php';

$stmt = $db->prepare('select contestant.lastName as sLastName, contestant.firstName as sFirstName, contestant.genre as genre, contestant.email as sEmail, contestant.zipcode as sZipcode from contestant 
	join team on team.ID = contestant.teamID
	join `group` on `group`.ID = team.groupID
	join contest on contest.ID = `group`.contestID
	where algoreaCode = :code and contest.badgeName = :badgeName;');
$stmt->execute(['code' => $code, 'badgeName' => $badgeName]);

$contestant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contestant) {
	echo json_encode(null);
	exit();
}

$contestant['sSex'] = ($contestant['genre'] == 2 ? 'Male' : 'Female');

echo json_encode($contestant);

<?php
require_once("commonAdmin.php");
require_once("./config.php");
header('Content-type: text/html');

$htmlHeader = '<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="'. $config->faviconfile . '" />
<title>Download participants</title>
</head>
<body class="body-margin">';

if (!isset($_SESSION["userID"])) {
    echo $htmlHeader;
    echo "<p>" . translate("session_expired") . "</p>";
    echo "<p>" . translate("go_to_index") . "</p>";
    echo "</body></html>";
    exit;
}

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
    echo $htmlHeader;
    echo "<p>" . translate("admin_restricted") . "</p>";
    echo "<p>" . translate("go_to_index") . "</p>";
    echo "</body></html>";
    exit;
}

if (!isset($_GET["contestID"])) {
    echo $htmlHeader;
    echo "<p>" . translate("invalid_link") . "</p>";
    echo "<p>" . translate("go_to_index") . "</p>";
    echo "</body></html>";
    exit;
}

$contestID = $_GET["contestID"];
// ID, nom/prénom, classe, heure de démarrage pour chaque participant

$stmt = $db->prepare("
SELECT algorea_registration.code, algorea_registration.firstName, algorea_registration.lastName, algorea_registration.grade, team.startTime
FROM team
JOIN contestant ON team.ID = contestant.teamID
JOIN algorea_registration ON contestant.registrationID = algorea_registration.ID
JOIN contest ON team.contestID = contest.ID
WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
AND algorea_registration.code NOT LIKE 'c%'");
$stmt->execute(['contestID' => $contestID]);

$filename = "participants_" . date('Y-m-d_H-i-s') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$csv = fopen('php://output', 'w');
fputcsv($csv, ['ID', 'Name', 'Grade', 'Start time']);
while($row = $stmt->fetchObject()) {
    fputcsv($csv, [$row->code, $row->firstName . ' ' . $row->lastName, $row->grade, $row->startTime]);
}

fclose($output);
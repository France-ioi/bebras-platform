<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

$db2 = new PDO($dbConnexionString2, $dbUser2, $dbPasswd2);

$query = "SELECT algorea_registration.franceioiID FROM algorea_registration JOIN contestant ON contestant.ID = algorea_registration.contestantID ".
"JOIN team ON team.ID = contestant.teamID ".
"JOIN `group` ON `group`.ID = team.groupID ".
"WHERE `group`.userID = :userID";

$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);

$foreignIDs = array();
while ($row = $stmt->fetchObject()) {
   $foreignIDs[] = $row->franceioiID;
}

//echo implode($foreignIDs, ',');

$query = "SELECT GROUP_CONCAT(CONCAT(firstName, ' ', lastName, ' (', userName , ')')) as users, IFNULL(totalScore,0) as score FROM ".
"(SELECT teams.team_id, SUM(maxScore) totalScore, participation_id FROM (".
"SELECT attempts.participation_id, round_task_id, MAX(answers.score) as maxScore FROM attempts JOIN answers ON answers.attempt_id = attempts.id WHERE started_at > '2017-01-01' ".
"GROUP BY round_task_id, participation_id ".
") att ".
"JOIN participations ON participations.id = participation_id ".
"RIGHT JOIN (SELECT DISTINCT team_id FROM users WHERE foreign_id in (".implode($foreignIDs, ",").")) as teams ON teams.team_id = participations.team_id ".
"GROUP BY participation_id ".
") as teamRes ".
"JOIN users ON teamRes.team_id = users.team_id ".
"GROUP BY users.team_id";

$stmt = $db2->prepare($query);
$stmt->execute();

echo "<html><body><style>table tr td { border: solid black 1px; padding:2px }</style><P>Voici les résultats actuels de vos équipes pour le tour 2 :</p><table cellspacing=0><tr><td>Membres de l'équipe</td><td>Score</td></tr>";

while ($row = $stmt->fetchObject()) {
   echo "<tr><td>".$row->users."</td><td>".$row->score."/1000</td></tr>";
}
echo "</table>";

?>
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

$query = "SELECT GROUP_CONCAT(CONCAT('<br/>', firstName, ' ', lastName, ' (', userName , ')')) as users, IFNULL(totalScore,0) as score, participations.access_code, participations.score as score3, participations.rank_regional, participations.rank_national, participations.rank_big_regional, regions.name as region, regions.big_region_name as big_region FROM ".
"(SELECT teams.team_id, teams.region_id, SUM(maxScore) totalScore FROM (".
"SELECT attempts.participation_id, round_task_id, MAX(answers.score) as maxScore FROM attempts JOIN answers ON answers.attempt_id = attempts.id WHERE started_at > '2017-01-01' ".
"GROUP BY round_task_id, participation_id ".
") att ".
"JOIN participations ON (participations.id = participation_id AND participations.round_id = 6) ".
"RIGHT JOIN (SELECT DISTINCT team_id, region_id FROM users JOIN teams ON teams.id = team_id WHERE foreign_id in (".implode($foreignIDs, ",").")) as teams ON teams.team_id = participations.team_id ".
"GROUP BY participation_id ".
") as teamRes ".
"JOIN users ON teamRes.team_id = users.team_id ".
"JOIN regions ON regions.id = teamRes.region_id ".
"LEFT JOIN participations ON (teamRes.team_id = participations.team_id AND participations.round_id = 7) ".
"GROUP BY users.team_id";

$stmt = $db2->prepare($query);
$stmt->execute();

echo "<html>
<meta charset='utf-8'>
<body><style>table tr td { border: solid black 1px; padding:2px; }</style><p>Vous trouverez ci-dessous les résultats de vos équipes pour les tours 2 et 3.</p><table cellspacing=0><tr><td>Membres de l'équipe</td><td>Score<br/>tour 2</td><td>Qualification<br/>tour 3</td><td>Score au 3ème tour</td><td>Rang national</td><td>Rang grande région</td><td>Rang académie</td></tr>";

while ($row = $stmt->fetchObject()) {
   $result = "Non qualifiée";
   $access_code = "-";
   $score3 = "";
   $rank_regional = "";
   $rank_national = "";
   $rank_big_regional = "";
   if (intval($row->score) >= 500) {
      $result = "Qualifiée au 3ème tour";
      $access_code = $row->access_code;
   }
   if ($row->rank_national != null) {
      $rank_national = $row->rank_national;
      $rank_regional = $row->rank_regional." (".$row->region.")";
      $rank_big_regional = $row->rank_big_regional." (".$row->big_region.")";
      $score3 = $row->score3;
   }
   echo "<tr><td>".$row->users."</td><td>".$row->score."/1000</td><td>".$result."</td>".
   //"<td style='text-align:center;font-family:courier;font-size:22px'>".$access_code."</td>".
   "<td style='text-align:center'>".$score3."</td>".
   "<td style='text-align:center'>".$rank_national."</td>".
   "<td style='text-align:center'>".$rank_big_regional."</td>".
   "<td style='text-align:center'>".$rank_regional."</td>".
   "</tr>";
}
echo "</table>";

?>
<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}

if (!isset($_GET["groupID"])) {
   echo "paramètre groupID manquant";
   exit;
}

$groupID = $_GET["groupID"];

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<style>
table tr td { border: solid black 1px; min-width: 40px; }
table tr:first-child td { border: none }
.score { text-align: right; }
th.rotate {  height: 140px;   white-space: nowrap; }
th.rotate > div {   transform: translate(10px, 51px) rotate(315deg);  width: 30px; }
th.rotate > div > span {   border-bottom: 1px solid #ccc;   padding: 5px 10px; }
tr:hover { background-color: #ffff99; }
</style>
<?php
//.vertical-text {	transform: rotate(-45deg); 	transform-origin: left bottom 0; overflow:}</style>";

$query = "SELECT `group`.`startTime`, team.password, `team_question`.ffScore, team.score, ".
"GROUP_CONCAT(CONCAT(firstName, ' ', lastName, ' ') SEPARATOR ', ') as contestants, ".
"`group`.name as groupName, question.name as questionName, ".
"`group`.ID as groupID, team.ID as teamID, question.ID as questionID ".
"FROM `group` ".
"JOIN team ON team.groupID = `group`.ID ".
"JOIN `contestant` ON `contestant`.teamID = team.ID ".
"JOIN contest_question ON contest_question.contestID = `group`.contestID ".
"JOIN question ON question.ID = contest_question.questionID ".
"LEFT JOIN team_question ON (team_question.teamID = team.ID AND team_question.questionID = question.ID) ".
"WHERE `group`.ID = :groupID ".
"GROUP BY team.ID, question.ID ".
"ORDER BY `group`.ID, team.startTime DESC, contest_question.`order`";

$stmt = $db->prepare($query);
$stmt->execute(['groupID' => $groupID]);

$groups = array();
while ($row = $stmt->fetchObject()) {
   if (!isset($groups[$row->groupID])) {
      $groups[$row->groupID] = array("name" => $row->groupName, "startTime" => $row->startTime, "teams" => array());
   }
   $group = &$groups[$row->groupID];
   if (!isset($group["teams"][$row->teamID])) {
      $group["teams"][$row->teamID] = array("contestants" => $row->contestants, "password" => $row->password, "score" => $row->score, "questions" => array());
   }
   $team = &$group["teams"][$row->teamID];
   $team["questions"][$row->questionName] = $row->ffScore;
}


foreach ($groups as $group) {
   echo "<h2>Groupe ".$group["name"]."</h2>";
   if ($group["startTime"] == null) {
      echo "<p>Ce groupe n'a pas encore participé.</p>";
      break;
   }
   echo "<p>Participé le : ".$group["startTime"]." (UTC)</p>";
   echo "<p>Attention : <ul><li>Pour des raisons techniques, l'affichage des scores détaillés est temporairement désactivé pour les participations effectuées pendant la période du concours, mais sera réactivé ensuite.<li>Pour le concours lui-même, les scores affichés ne seront définitifs qu'après l'annonce officielle des résultats.</li></ul></p>";
   echo "<table cellspacing=0><tr><th class='rotate'><div><span>Équipe</span></div></th>";
   foreach ($group["teams"] as $teamID => $team) {
      foreach ($team["questions"] as $questionName => $question) {
         echo "<th class='rotate'><div><span>".$questionName."</span></div></th>";
      }
      echo "<th class='rotate'><div><span><b>Total</b></span></div></th>";
      break;
   }
   echo "</tr>";
   foreach ($group["teams"] as $teamID => $team) {
      echo "<tr><td><a href='http://concours.castor-informatique.fr?team=".$team["password"]."' target='_blank'>[ouvrir]</a> ".$team["contestants"]."</td>";
      $score = 0;
      foreach ($team["questions"] as $questionName => $ffScore) {
         if ($ffScore == null) {
            echo "<td>-</td>";
         } else {
            echo "<td class='score' title='".$questionName."'>".$ffScore."</td>";
            $score += $ffScore;
         }
      }
      if ($team["score"] != null) {
         $score = $team["score"];
      }
      echo "<td class='score'><b>".$score."</b></td>";
      echo "</tr>";
   }
   echo "</table><br/><br>";
}

?>
<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["userID"])) {
   echo translate("session_expired");
   exit;
}

if (!isset($_GET["groupID"])) {
   echo translate("groups_groupID_missing");
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

$query = "SELECT `group`.`startTime`, team.password, `team_question`.ffScore, team_question.score AS tqScore, team.score, ".
"GROUP_CONCAT(CONCAT(firstName, ' ', lastName, ' ') SEPARATOR ', ') as contestants, ".
"`group`.name as groupName, question.name as questionName, ".
"`group`.ID as groupID, team.ID as teamID, question.ID as questionID ".
"FROM `group` ".
"JOIN team ON team.groupID = `group`.ID ".
"JOIN `contestant` ON `contestant`.teamID = team.ID ".
"JOIN contest_question ON contest_question.contestID = `group`.contestID ".
"JOIN question ON question.ID = contest_question.questionID ".
"LEFT JOIN team_question ON (team_question.teamID = team.ID AND team_question.questionID = question.ID) ".
"WHERE `group`.ID = :groupID  OR `group`.`parentGroupID` = :groupID ".
"GROUP BY team.ID, question.ID ".
"ORDER BY `group`.ID, team.startTime DESC, team.ID, contest_question.`order`";

$stmt = $db->prepare($query);
$stmt->execute(['groupID' => $groupID]);

$groups = array();
while ($row = $stmt->fetchObject()) {
   if (!isset($groups[$row->groupID])) {
      $groups[$row->groupID] = array("name" => $row->groupName, "startTime" => $row->startTime, "teams" => array());
   }
   $groupRef = &$groups[$row->groupID];
   if (!isset($groupRef["teams"][$row->teamID])) {
      $groupRef["teams"][$row->teamID] = array("contestants" => $row->contestants, "password" => $row->password, "score" => $row->score, "questions" => array());
   }
   $score = $row->tqScore;
   if ($score === null) {
      $score = $row->ffScore;
   }
   $groupRef["teams"][$row->teamID]["questions"][$row->questionName] = $score;
}

foreach ($groups as $group) {
   echo "<h2>Groupe ".$group["name"]."</h2>";
   if ($group["startTime"] == null) {
      echo "<p>".translate("groups_group_has_not_participated")."</p>";
      break;
   }
   echo "<p>".sprintf(translate("groups_participated_on"), $group["startTime"])."</p>";
   //echo "<p>".translate("groups_warning_disabled_during_contest")."</p>";
   echo "<p>".translate("groups_warning_temporary_scores")."</p>";
   echo "<table cellspacing=0><tr><th class='rotate'><div><span>".translate("groups_team")."</span></div></th>";
   foreach ($group["teams"] as $teamID => $team) {
      foreach ($team["questions"] as $questionName => $question) {
         echo "<th class='rotate'><div><span>".$questionName."</span></div></th>";
      }
      echo "<th class='rotate'><div><span><b>".translate("groups_total")."</b></span></div></th>";
      break;
   }
   echo "</tr>";
   foreach ($group["teams"] as $teamID => $team) {
      echo "<tr><td><a href='http://concours.castor-informatique.fr?team=".$team["password"]."' target='_blank'>[".translate("groups_open")."]</a> ".$team["contestants"]."</td>";
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
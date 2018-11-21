<?php 
echo "<!DOCTYPE html>";

/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
include_once("../shared/tinyORM.php");
include('./config.php');

// jquery 1.9 is required for IE6+ compatibility.
script_tag('/bower_components/jquery/jquery.min.js');
?>

<style>
.borders tr td {
   border: solid black 1px;
}
</style>

<?php


if (isset($_REQUEST["password"])) {
   if (md5($_REQUEST["password"]) == $config->teacherInterface->genericPasswordMd5) {
      $_SESSION["isAdmin"] = true;
   } else {
      echo translate("invalid_password");
      exit;
   }
}

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo translate("admin_restricted");
   exit;
}

if (!isset($_REQUEST["groupCode"])) {
   echo "missing groupCode parameter";
}

$groupCode = null;
$teamCode = null;
if (isset($_REQUEST["groupCode"])) {
   $groupCode = $_REQUEST["groupCode"];
}

$endDateTime = null;
if (isset($_REQUEST["endDateTime"]) && (trim($_REQUEST["endDateTime"]) != "")) {
   $endDateTime = trim($_REQUEST["endDateTime"]);
   $data = explode(" ", $endDateTime);
   if (count($data) != 2) {
      echo "Format error for endDateTime 1 :".$endDateTime;
      exit;
   }
   $date = explode("-", $data[0]);
   if (count($date) != 3) {
      echo "Format error for endDateTime 2";
      exit;
   }
   $time = explode(":", $data[1]);
   if (count($time) != 3) {
      echo "Format error for endDateTime 3";
      exit;
   }
}
$extraMinutes = "";
if (isset($_REQUEST["extraMinutes"])) {
   $extraMinutes = intval($_REQUEST["extraMinutes"]);
}

$query = "SELECT `group`.ID, `group`.name, `group`.startTime, `group`.grade, `group`.nbStudentsEffective, ".
         "`user`.`firstName`, `user`.`lastName`, `user`.`officialEmail`, `user`.`alternativeEmail` ".
         "FROM `group` ".
         "LEFT JOIN `user` ON `group`.`userID` = `user`.`ID` ".
         "WHERE `group`.`code` = :groupCode";
$stmt = $db->prepare($query);
$stmt->execute(array("groupCode" => $groupCode));
$row = $stmt->fetchObject();
if ($row == null) {
   echo "Invalid groupCode";
   exit;
}
echo "<table class='borders' cellspacing=0>".
     "<tr><td>Group</td><td>".$row->name."</td></tr>".
     "<tr><td>Teacher</td><td>".$row->firstName." ".$row->lastName." ".$row->officialEmail." ".$row->alternativeEmail."</td></tr>".
     "<tr><td>Starting time</td><td>".$row->startTime."    UTC</td></tr>".
     "<tr><td>Grade</td><td>".$row->grade."</td></tr>".
     "<tr><td>Participants</td><td>".$row->nbStudentsEffective."</td></tr>".
     "</table>";


function getTeamsData($groupID, $endDateTime, $extraMinutes) {
   global $db;
   $params = array("groupID" => $groupID);
   if ($endDateTime != null) {
      $strMinutesToAdd = ", team.nbMinutes - ((TIME_TO_SEC(:endDateTime) - TIME_TO_SEC(team.startTime))/60) - IFNULL(team.extraMinutes, 0) AS `minutesToAdd`";
      $params["endDateTime"] = $endDateTime;
   } else if ($extraMinutes != "") {
      $strMinutesToAdd = ", :extraMinutes AS `minutesToAdd`";
      $params["extraMinutes"] = $extraMinutes;
   } else {
      $strMinutesToAdd = ", '' AS `minutesToAdd`";
   }
   $query = "SELECT team.ID, GROUP_CONCAT(CONCAT(contestant.firstName, CONCAT(' ', contestant.lastName))) as students, ".
            "team.password, team.extraMinutes, team.startTime, team.endTime, team.extraMinutes ".$strMinutesToAdd.
            "FROM team ".
            "JOIN contestant ON team.ID = contestant.teamID ".
            "WHERE `team`.`groupID` = :groupID ".
            "GROUP BY team.ID ORDER BY students";
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   $rows = array();
   while ($row = $stmt->fetchObject()) {
      $row->checked = (isset($_REQUEST["team_".$row->ID]) && ($_REQUEST["team_".$row->ID] == "on"));
      $rows[] = $row;
   }
   return $rows;
}

$teams = getTeamsData($row->ID, $endDateTime, $extraMinutes);

$resetPasswords = false;
$strGenPassword = "";
if (isset($_REQUEST["resetPasswords"]) && ($_REQUEST["resetPasswords"] == "on")) {
   $resetPasswords = true;
   $strGenPassword = ", password = :newPassword";
}
$query = "UPDATE `team` SET endTime = NULL, extraMinutes = :minutesToSet ".$strGenPassword." WHERE ID = :teamID";
$stmt = $db->prepare($query);
$teamsMinutesAdded = array();
foreach ($teams as $team) {
   if ($team->checked) {
      $minutesToSet = $team->minutesToAdd;
      if (intval($team->extraMinutes) > 0) {
         $minutesToSet += intval($team->extraMinutes);
      }
      $params = array("teamID" => $team->ID, "minutesToSet" => $minutesToSet);
      $data = array("extraMinutes"  => $minutesToSet);
      if ($resetPasswords) {
         $params["newPassword"] = genAccessCode($db);
         $data["password"] = $params["newPassword"];
      }
      $stmt->execute($params);
      if ($config->db->use == "dynamoDB") {
         try {
            $tinyOrm->update('team', $data, array('ID'=>$team->ID));
         } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
            error_log($e->getAwsErrorCode() . " - " . $e->getAwsErrorType());
            error_log('DynamoDB error updating team for teamID: '.$teamID);
         }
      }
      $teamsMinutesAdded[$team->ID] = $team->minutesToAdd;
   }
}



$teams = getTeamsData($row->ID, $endDateTime, $extraMinutes);
echo "<form method='POST'>";
echo "<input type='hidden' name='groupCode' value='".$groupCode."'/>";
echo "<p><table class='borders' cellspacing=0><tr><td>Students</td><td>Access code</td><td>Start time</td><td>End time</td><td>Extra minutes</td><td>Select</td></tr>";

foreach ($teams as $team) {
   echo "<tr><td>".$team->students."</td><td>".$team->password."</td><td>".$team->startTime."</td><td>".$team->endTime."</td><td>".$team->extraMinutes."</td>";
   echo "<td><input type='checkbox' name='team_".$team->ID."' id='team_".$team->ID."' class='teamCheck'>";
   if ($team->minutesToAdd != "") {
      if ($team->checked) {
         echo $teamsMinutesAdded[$team->ID]." min added";
      }
   }
   echo "</td></tr>";
}
echo "<tr><td colspan=5 style='text-align:right'>Select all</td><td><input type='checkbox' id='selectAll'></td></tr>";
echo "</table></p>";

echo "<p>Add time assuming that participation was interrupted at: <input type='text' name='endDateTime'/>  format: YYYY-MM-DD HH:mm:ss</p>";
echo "<p>Add number of minutes: <input type='number' name='extraMinutes'/></p>";
echo "<p>Generate new access codes for these students (so that they can't restart from home): <input type='checkbox' name='resetPasswords'/></p>";
echo "<p><input type=submit value='Submit'></p>";
echo "</form>";

?>
<script>
$(function() {
   $("#selectAll").change(function() {
       $(".teamCheck").prop("checked", this.checked);
    });
});
</script>


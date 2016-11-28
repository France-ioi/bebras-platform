<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

function generateAlgoreaCodes($db, $contestID) {
   // retrieving awarded contestants through "award1" model
   $query = "update contestant
      join team on contestant.teamID = team.ID
      join `group` on `group`.ID = team.groupID
      join award_threshold on award_threshold.nbContestants = team.nbContestants and award_threshold.contestID = :contestID and award_threshold.gradeID = contestant.grade and award_threshold.awardID = 1
      set algoreaCode =  CONCAT(CONCAT('', FLOOR(RAND()*10000000)), CONCAT('', FLOOR(RAND()*10000000)))
      where
      group.contestID = :contestID and
      team.participationType = 'Official' and
      contestant.algoreaCode is null
      and team.score >= award_threshold.minScore;";
   $stmt = $db->prepare($query);
   $stmt->execute(['contestID' => $contestID]);
}

if ((!isset($_SESSION["isAdmin"])) || (!$_SESSION["isAdmin"])) {
   echo json_encode((object)array("success" => false, "message" => "Only an admin can do that!"));
   exit;
}

$contestID = $_REQUEST["contestID"];
if (!$contestID) {
   echo json_encode((object)array("success" => false, "message" => "contestID forgotten"));
   exit;
}

generateAlgoreaCodes($db, $contestID);
unset($db);

echo json_encode(array("success" => true));

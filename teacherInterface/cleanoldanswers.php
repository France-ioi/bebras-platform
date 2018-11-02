<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
      echo json_encode((object)array("status" => 'error', "message" => translate("admin_restricted")));
      exit;
}

$query = "delete team_question from team_question
join team on team.ID = team_question.teamID
join `group` on `group`.ID = team.groupID
where `group`.isPublic = 1 and team.startTime < (now() - interval 1 week);";
$stmt = $db->prepare($query);
$stmt->execute();

$query = "delete team from team
join `group` on `group`.ID = team.groupID
where `group`.isPublic = 1 and team.startTime < (now() - interval 1 week);";
$stmt = $db->prepare($query);
$stmt->execute();

echo json_encode(['success' => true]);
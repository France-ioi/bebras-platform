<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
include('./config.php');

?>
<style>
.queryResults tr td {
   border: solid black 1px;
}

.queryResults tr:first-child td {
   font-weight: bold;
   background: #808080;
}
</style>

<?php

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo "This page is for admins only.";
   exit;
}

if (!isset($_GET["contestID"])) {
   echo "contestID parameter is missing.";
   exit;
}
$contestID = $_GET["contestID"];

if (!isset($_GET["rejectedGroupCode"])) {
   echo "rejectedGroupCode parameter is missing.";
   exit;
}
$rejectedGroupCode = $_GET["rejectedGroupCode"];

$query = "SELECT `group`.name, ID FROM `group` WHERE `group`.code = :groupCode";
$stmt = $db->prepare($query);
$stmt->execute(array("groupCode" => $rejectedGroupCode));
echo "<h2>Rejected group</h2>";
if ($row = $stmt->fetchObject()) {
   echo "<p>".$row->name."</p>";
   $rejectedGroupID = $row->ID;
} else {
   echo "<p>Invalide</p>";
   exit;
}

$action = "";
if (isset($_GET["action"])) {
   $action = $_GET["action"];
}

$startUrl = "?contestID=".$contestID."&rejectedGroupCode=".$rejectedGroupCode;

function execQueryAndShowNbRows($description, $query, $params) {
   global $db;
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   $rowCount = $stmt->rowCount();
   echo "<p><b>".$description."</b> : ".$rowCount." rows affected.</p>";
}

function execSelectAndShowResults($description, $query, $params, $rowsOnly = false) {
   global $db;
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   $row = $stmt->fetch(PDO::FETCH_ASSOC);
   if (!$rowsOnly) {
      echo "<p><b>".$description."</b> :";
   }
   if (!$row) {
      if ($rowsOnly) {
         return;
      }
      echo "No results.</p>" ;
      return;
   }
   if (!$rowsOnly) {
      echo "</p><table cellspacing=0 class='queryResults'>";
      echo "<tr>";
      foreach ($row as $name => $value) {
         echo "<td>".$name."</td>";
      }
      echo "</tr>";
   }
   while ($row != null) {
      echo "<tr>";
      foreach ($row as $name => $value) {
         echo "<td>".htmlentities($value)."</td>";
      }
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      echo "</tr>";
   }
   if (!$rowsOnly) {
      echo "</table>";
   }
}

execSelectAndShowResults("Selected contest(s)", "
   SELECT contest.ID, contest.name FROM contest WHERE contest.ID = :contestID OR contest.parentContestID = :contestID",
   array("contestID" => $contestID));


echo "<h2><a href='".$startUrl."&action=showStats'>Some statistics</a></h2>";
if ($action == "showStats") {
   execSelectAndShowResults("Number of contestants", "
      SELECT team.participationType, count(*) FROM contestant
      JOIN team ON (contestant.teamID = team.ID)
      JOIN `group` ON (`team`.groupID = `group`.ID)
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      GROUP BY team.participationType
      ",
      array("contestID" => $contestID));

   execSelectAndShowResults("Number of contestants by subcontest", "
      SELECT contest.name, team.participationType, count(*)
      FROM contestant
      JOIN team ON (contestant.teamID = team.ID)
      JOIN `group` ON (`team`.groupID = `group`.ID)
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      GROUP BY contest.ID, team.participationType
      ORDER BY team.participationType ASC",
      array("contestID" => $contestID));

   execSelectAndShowResults("Number of contestants by grade", "
      SELECT contestant.grade, team.participationType, count(*)
      FROM contestant
      JOIN team ON (contestant.teamID = team.ID)
      JOIN `group` ON (`team`.groupID = `group`.ID)
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      GROUP BY team.participationType, contestant.grade
      ORDER BY team.participationType, contestant.grade",
      array("contestID" => $contestID));
}

echo "<h2><a href='".$startUrl."&action=fixSubgroups'>Fix subgroups</a></h2>";
if ($action == "fixSubgroups") {
   execQueryAndShowNbRows("Mark groups startTime if subgroup has startTime", "
      UPDATE `group` gchild
      JOIN `group` gparent ON gchild.parentGroupID = gparent.ID
      JOIN `contest` ON `gparent`.contestID = contest.ID
      SET gparent.startTime = gchild.startTime
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND gparent.startTime IS NULL
      AND gchild.startTime IS NOT NULL",
      array("contestID" => $contestID));

//   TODO : somme des students des enfants dans le parent ?

   execQueryAndShowNbRows("Set automatically created individual groups as official", "
      UPDATE `group`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET participationType = 'Official'
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND participationType IS NULL
      AND `group`.name LIKE 'Indiv%'",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Copy group participationType to subgroups that have it as NULL", "
      UPDATE `group` gchild
      JOIN `group` gparent ON gchild.parentGroupID = gparent.ID
      JOIN `contest` ON `gparent`.contestID = contest.ID
      SET gchild.participationType = gparent.participationType
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND gchild.participationType IS NULL",
      array("contestID" => $contestID));

   /*préparations to be marked as unofficial
   UPDATE `group` JOIN `contest` ON `group`.contestID = contest.ID SET participationType = 'Unofficial' WHERE participationType IS NULL AND `group`.name LIKE 'Indiv%' AND (contest.ID = 485926402649945250 OR contest.parentContestID = 485926402649945250);
   */

   execQueryAndShowNbRows("Fix bug where nbMinutes is 0 ! We set them at 46 to make them easy to find later", "
      UPDATE `group`
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team ON team.groupID = `group`.ID
      SET team.nbMinutes = 46
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND `group`.name LIKE 'Indiv 0%'
      AND team.nbMinutes = 0",
      array("contestID" => $contestID));
}

echo "<h2><a href='".$startUrl."&action=computeGenres'>Automatically determine genre of students (if not asked during the contest)</a></h2>";
if ($action == "computeGenres") {
/*
   Queries done once on a contest where the student's genres are provided

   //CREATE table firstName_genre AS SELECT * FROM contestants_2015.firstName_genre

   execQueryAndShowNbRows("Generate table with stas about firstNames and corresponding genre", "
      INSERT INTO firstName_genre (firstName, nbFemale, nbMale)
      (SELECT firstName, SUM(IF(genre = 1,1,0)) as nbFemale, SUM(IF(genre = 2,1,0)) as nbMale
      FROM contestant JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      WHERE (contest.ID = :contestID) OR (contest.parentContestID = :contestID)
      GROUP BY firstName",
      array("contestID" => $contestID));
   
   execQueryAndShowNbRows("Set female genre when it's obvious from firstname", "
      UPDATE `firstName_genre` SET genre = 1 WHERE nbFemale > nbMale * 5",
      array());
      
   execQueryAndShowNbRows("Set male genre when it's obvious from firstname", "
      UPDATE `firstName_genre` SET genre = 2 WHERE nbMale > nbFemale * 5",
      array());

   execSelectAndShowResults("Show stats for each genre", "
      SELECT SUM(nbMale), SUM(nbFemale), genre FROM `firstName_genre` GROUP BY genre",
      array());
*/

   execSelectAndShowResults("Current genres available :", "
      SELECT genre, count(*)
      FROM `contestant`
      JOIN `team` ON contestant.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.`contestID` = `contest`.ID
      WHERE `group`.participationType = 'Official'
      AND (contest.ID = :contestID) OR (contest.parentContestID = :contestID)
      GROUP BY genre",
      array("contestID" => $contestID));


   execQueryAndShowNbRows("Save original genre of contestants :", "
      UPDATE contestant
      JOIN `team` ON contestant.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      SET contestant.orig_genre = contestant.genre
      WHERE (contest.ID = :contestID) OR (contest.parentContestID = :contestID)",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Drop temporary table used to set genres :", "
      DROP TABLE IF EXISTS contestant_firstnames",
      array());
      
   execQueryAndShowNbRows("Recreate temporary table used to set genres, and fill with contestants :", "
      CREATE table contestant_firstnames
      AS SELECT contestant.ID, contestant.firstName, contestant.genre
      FROM contestant
      JOIN `team` ON contestant.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      WHERE (contest.ID = :contestID) OR (contest.parentContestID = :contestID)",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Add index", "
      ALTER TABLE `contestant_firstnames` ADD PRIMARY KEY(`ID`)",
      array());

   execQueryAndShowNbRows("Add index", "
      ALTER TABLE `contestant_firstnames` ADD INDEX(`firstName`)",
      array());
      
   execQueryAndShowNbRows("Set genre of contestants in temporary table, based on stats", "
      UPDATE `contestant_firstnames`
      JOIN firstName_genre ON `contestant_firstnames`.firstName = firstName_genre.firstName
      SET `contestant_firstnames`.genre = firstName_genre.genre
      WHERE `contestant_firstnames`.genre = 0
      AND firstName_genre.genre != 0
      AND `contestant_firstnames`.firstName != 'Anonymous'",
      array());

   execQueryAndShowNbRows("Update actual contestants table with genres", "
      UPDATE contestant_firstnames JOIN contestant ON contestant.ID = contestant_firstnames.ID
      SET contestant.genre = contestant_firstnames.genre
      WHERE contestant.genre = 0",
      array());

   execQueryAndShowNbRows("Drop temporary table used to set genres :", "
      DROP TABLE IF EXISTS contestant_firstnames",
      array());
}

echo "<h2><a href='".$startUrl."&action=hideInvalidParticipations'>Hide invalid participations in the rejected group</a></h2>";
if ($action == "hideInvalidParticipations") {
   
   //ALTER TABLE `team` ADD `old_groupID` BIGINT NOT NULL AFTER `groupID`;

   //TODO : what do we don with non-started groups ? 
   #UPDATE `group` SET contestID = 961428730144625174 WHERE contestID = :contestID AND startTime IS NULL;

   execQueryAndShowNbRows("Move to rejected group, teams created before today and that never started", "
      UPDATE team JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET old_groupID = groupID, groupID = :rejectedGroupID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND (team.createTime IS NULL OR (team.createTime < DATE_SUB(NOW(), INTERVAL 1 DAY) AND team.startTime IS NULL))",
      array("contestID" => $contestID, "rejectedGroupID" => $rejectedGroupID));
}

echo "<h2><a href='".$startUrl."&action=showUnofficialGroups'>Show infos about unofficial groups</a></h2>";
if ($action == "showUnofficialGroups") {
   execSelectAndShowResults("Users that have more than 3 users in unofficial groups", "
      SELECT user.ID, user.officialEmail, user.alternativeEmail, count(*) as nbStudents
      FROM `group`
      JOIN team ON `group`.ID = team.groupID
      JOIN contestant ON contestant.teamID = team.ID
      JOIN user ON `group`.userID = user.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND `group`.participationType = 'Unofficial'
      AND contestant.grade > 0
      GROUP BY user.ID
      HAVING nbStudents > 3
      ORDER BY nbStudents DESC",
      array("contestID" => $contestID));

   execSelectAndShowResults("Details of these groups", "
      SELECT user.ID, user.officialEmail, user.alternativeEmail, `group`.`name`, `group`.`grade`, count(*) as nbStudents
      FROM `group`
      JOIN team ON `group`.ID = team.groupID
      JOIN contestant ON contestant.teamID = team.ID
      JOIN user ON `group`.userID = user.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND `group`.participationType = 'Unofficial'
      AND contestant.grade > 0
      GROUP BY `group`.ID
      HAVING nbStudents > 3
      ORDER BY nbStudents DESC",
      array("contestID" => $contestID));
      
   echo "<p>Contact these teachers to check if it was intentional for them to set the groups as unofficial</p>";
}

/*      
      // Format des requêtes pour passer des groupes en officiel

      UPDATE `group` SET participationType = 'Official' WHERE code IN ('d5hg9gcx', 'jekhg3rq', 'jjd39hps');

      UPDATE `team` JOIN `group` ON team.groupID = `group`.ID  SET team.participationType = 'Official' WHERE `group`.code IN ('d5hg9gcx', 'jekhg3rq', 'jjd39hps');


      // Format des requêtes pour passer des groupes en non officiel

      UPDATE `group` SET participationType = 'Unofficial' WHERE code IN ('hy4dufwp');

      UPDATE `team` JOIN `group` ON team.groupID = `group`.ID  SET team.participationType = 'Unofficial' WHERE `group`.code IN ('hy4dufwp');
*/

echo "<h2><a href='".$startUrl."&action=resetIsOfficial'>Completely reset contestants official status, start with all teams set to the group participation type</a></h2>";
if ($action == "resetIsOfficial") { 
   execQueryAndShowNbRows("Set contestant.tmpIsOfficial to NULL for all contestants", "
      UPDATE contestant
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET contestant.tmpIsOfficial = NULL, contestant.reasonUnofficial = NULL
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Truncate duplicate_contestants table", "
      TRUNCATE TABLE duplicate_contestants",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Set team.participationType from NULL to participationType of group", "
      UPDATE team
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team.participationType = `group`.participationType
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.participationType IS NULL",
   array("contestID" => $contestID));
}      

echo "<h2><a href='".$startUrl."&action=detectDuplicates'>Detect duplicate contestants and make some of them unofficial</a></h2>";
if ($action == "detectDuplicates") {
   execQueryAndShowNbRows("Set tmpIsOfficial from NULL to 0 for contestants from unofficial groups", "
      UPDATE contestant
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET contestant.tmpIsOfficial = 0, contestant.reasonUnofficial = 'Unofficial group'
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND contestant.tmpIsOfficial IS NULL
      AND `group`.participationType = 'Unofficial'",
      array("contestID" => $contestID));
   
   execQueryAndShowNbRows("Set tmpIsOfficial from NULL to 1 for contestants from official groups", "
      UPDATE contestant
      JOIN `team` ON contestant.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET contestant.tmpIsOfficial = 1
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND contestant.tmpIsOfficial IS NULL
      AND `group`.`participationType` = 'Official'
      AND `team`.`participationType` = 'Official'",
      array("contestID" => $contestID));
      
   execQueryAndShowNbRows("Detect and store duplicate contestants", "
      INSERT IGNORE INTO duplicate_contestants (contestant1ID, contestant2ID)
      SELECT ID, duplicateID FROM (
         SELECT `contestant`.ID,
         @duplicateContestantID := IF(@prevFirstName=`contestant`.firstName AND @prevLastName = `contestant`.lastName AND @prevSchoolID = `contestant`.cached_schoolID AND @prevCategoryColor = `contest`.`categoryColor`, @prevID, NULL) AS duplicateID, 
         @prevFirstName := contestant.firstName,
         @prevLastName := contestant.lastName,
         @prevSchoolID := contestant.cached_schoolID,
         @prevCategoryColor := contest.categoryColor,
         @prevID := contestant.ID
         FROM contestant
         JOIN `team` ON contestant.teamID = team.ID
         JOIN `group` ON `team`.groupID = `group`.`ID`
         JOIN `contest` ON `group`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND tmpIsOfficial = 1
         ORDER BY contestant.cached_schoolID DESC , contestant.firstName DESC , contestant.lastName DESC, contest.categoryColor DESC
      ) tmp WHERE duplicateID IS NOT NULL",
      array("contestID" => $contestID));
         
   execQueryAndShowNbRows("Detect and store duplicate contestants (reverse order)", "
      INSERT IGNORE INTO duplicate_contestants (contestant1ID, contestant2ID)
      SELECT ID, duplicateID FROM (
         SELECT `contestant`.ID,
         @duplicateContestantID := IF(@prevFirstName=`contestant`.firstName AND @prevLastName = `contestant`.lastName AND @prevSchoolID = `contestant`.cached_schoolID AND @prevCategoryColor = `contest`.`categoryColor`, @prevID, NULL) AS duplicateID, 
         @prevFirstName := contestant.firstName,
         @prevLastName := contestant.lastName,
         @prevSchoolID := contestant.cached_schoolID,
         @prevCategoryColor := contest.categoryColor,
         @prevID := contestant.ID
         FROM contestant
         JOIN `team` ON contestant.teamID = team.ID
         JOIN `group` ON `team`.groupID = `group`.`ID`
         JOIN `contest` ON `group`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND tmpIsOfficial = 1
         ORDER BY contestant.cached_schoolID ASC, contestant.firstName ASC, contestant.lastName ASC, contest.categoryColor ASC
         ) tmp WHERE duplicateID IS NOT NULL",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Mark duplicate contestants types as former vs latter 1/4", "
      UPDATE
      duplicate_contestants
      JOIN contestant c1 ON (duplicate_contestants.contestant1ID = c1.ID) JOIN team t1 ON (c1.teamID = t1.ID)
      JOIN contestant c2 ON (duplicate_contestants.contestant2ID = c2.ID) JOIN team t2 ON (c2.teamID = t2.ID)
      SET t1.duplicateType = 'former', t2.duplicateType = 'latter'
      WHERE t1.startTime < t2.startTime",
      array());

   execQueryAndShowNbRows("Mark duplicate contestants types as former vs latter 2/4", "
      UPDATE
      duplicate_contestants
      JOIN contestant c1 ON (duplicate_contestants.contestant1ID = c1.ID) JOIN team t1 ON (c1.teamID = t1.ID)
      JOIN contestant c2 ON (duplicate_contestants.contestant2ID = c2.ID) JOIN team t2 ON (c2.teamID = t2.ID)
      SET t1.duplicateType = 'latter', t2.duplicateType = 'former'
      WHERE t1.startTime > t2.startTime",
      array());

   execQueryAndShowNbRows("Mark duplicate contestants types as former vs latter 3/4", "
      UPDATE
      duplicate_contestants
      JOIN contestant c1 ON (duplicate_contestants.contestant1ID = c1.ID) JOIN team t1 ON (c1.teamID = t1.ID)
      JOIN contestant c2 ON (duplicate_contestants.contestant2ID = c2.ID) JOIN team t2 ON (c2.teamID = t2.ID)
      SET t1.duplicateType = 'former'
      WHERE t1.startTime < t2.startTime",
      array());

   execQueryAndShowNbRows("Mark duplicate contestants types as former vs latter 4/4", "
      UPDATE
      duplicate_contestants
      JOIN contestant c1 ON (duplicate_contestants.contestant1ID = c1.ID) JOIN team t1 ON (c1.teamID = t1.ID)
      JOIN contestant c2 ON (duplicate_contestants.contestant2ID = c2.ID) JOIN team t2 ON (c2.teamID = t2.ID)
      SET t2.duplicateType = 'former'
      WHERE t1.startTime > t2.startTime",
      array());  
}

echo "<h2><a href='".$startUrl."&action=handleRecover'>Handle recovered answers</a></h2>";
if ($action == "handleRecover") {
   execQueryAndShowNbRows("Insert recovered answers", "
      INSERT IGNORE INTO team_question (teamID, questionID, answer, score, ffScore, `date`)
      SELECT teamID, questionID, answer, NULL, NULL, NOW()
      FROM team_question_recover
      JOIN team ON team_question_recover.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)",
      array("contestID" => $contestID));

   execSelectAndShowResults("Number of answers to recover", "
      SELECT count(*)
      FROM team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team_question_recover ON (team_question.teamID = team_question_recover.teamID AND team_question.questionID = team_question_recover.questionID AND team_question.answer != team_question_recover.answer)
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Update answers from recovered data", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team_question_recover ON (team_question.teamID = team_question_recover.teamID AND team_question.questionID = team_question_recover.questionID AND team_question.answer != team_question_recover.answer)
      SET team_question.answer = team_question_recover.answer, team_question.score = NULL, team_question.date = NOW()
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Reset score for recovered answers", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team_question_recover ON (team_question.teamID = team_question_recover.teamID AND team_question.questionID = team_question_recover.questionID )
      SET team_question.score = NULL
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)",
      array("contestID" => $contestID));
}

function getContestQuestions($contestID) {
   global $db;
   $query = "SELECT question.ID, question.name
      FROM question
      JOIN contest_question ON question.ID = contest_question.questionID
      JOIN contest ON contest_question.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)";
      
   $stmt = $db->prepare($query);
   $stmt->execute(array("contestID" => $contestID));
   $questions = array();
   while ($row = $stmt->fetchObject()) {
      $questions[] = $row;
   }
   return $questions;
}

echo "<h2><a href='".$startUrl."&action=recomputeScores'>Show recomputation status for each question</a></h2>";
if ($action == "recomputeScores") {
   execQueryAndShowNbRows("Store contestID in team table", "
      UPDATE team
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team.contestID = `group`.contestID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)",
      array("contestID" => $contestID));

   echo "<table class='queryResults'><tr><td style='width:300px'>Question</td><td>Score</td><td>Nombre</td></tr>";
   $questions = getContestQuestions($contestID);
   foreach ($questions as $question) {
      execSelectAndShowResults("Scores computation status", "
         SELECT question.name, question.ID, team_question.score, count(*)
         FROM team_question
         JOIN question ON question.ID = team_question.questionID
         JOIN team ON team_question.teamID = team.ID
         JOIN `contest` ON `team`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND team_question.questionID = :questionID
         AND (team_question.score IS NULL OR team_question.score < 0)
         GROUP BY team_question.questionID, team_question.score",
         array("contestID" => $contestID, "questionID" => $question->ID), true);
   }
   echo "</table>";
}

echo "<h2><a href='".$startUrl."&action=markRecomputeScores'>Mark questions to grade later (-1)</a></h2>";
if ($action == "markRecomputeScores") {
   execQueryAndShowNbRows("Set with NULL score to -1", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `contest` ON `team`.contestID = contest.ID
      SET team_question.score = -1
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score IS NULL AND team_question.ffScore IS NOT NULL",
      array("contestID" => $contestID));
}



echo "<h2><a href='".$startUrl."&action=markRecomputeScoresGroups'>Mark groups of questions to grade now</a></h2>";
if ($action == "markRecomputeScoresGroups") {
   $questions = getContestQuestions($contestID);
   foreach ($questions as $question) {
      execQueryAndShowNbRows("Set with -1 score to NULL FOR top 10000", "
         UPDATE team_question SET score = NULL WHERE score = -1 AND questionID = :questionID LIMIT 1000",
         array("questionID" => $question->ID));
   }
}


echo "<h2><a href='".$startUrl."&action=scoreErrors'>Detect score errors</a></h2>";
if ($action == "scoreErrors") {
   execSelectAndShowResults("Team_questions where score is != ffScore", "
      SELECT team_question.teamID, team_question.questionID, team_question.score, team_question.ffScore
      FROM team_question
      JOIN team_question_recover ON (team_question.teamID = team_question_recover.teamID AND team_question.questionID = team_question_recover.questionID )
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score < team_question.ffScore",
   array("contestID" => $contestID));
}

echo "<h2><a href='".$startUrl."&action=fixScoreErrors'>After verification only: replace score with ffScore (and vice-versa when ffScore is nul)</a></h2>";
if ($action == "fixScoreErrors") {
   execQueryAndShowNbRows("Reset score for recovered answers", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team_question.score = team_question.ffScore
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score < team_question.ffScore",
   array("contestID" => $contestID));
   
   
   execQueryAndShowNbRows("Save score to ffScore when ffScore IS NULL", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team_question.ffScore = team_question.score
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.ffScore IS NULL
      AND team_question.score IS NOT NULL",
   array("contestID" => $contestID));
}

echo "<h2><a href='".$startUrl."&action=gradeContest'>Lancement du calcul des scores</a></h2>";
if ($action == "gradeContest") {
   $language = $config->defaultLanguage;
   // JSON3 shim for IE6-9 compatibility.
   script_tag('/bower_components/json3/lib/json3.min.js');
   // jquery 1.9 is required for IE6+ compatibility.
   script_tag('/bower_components/jquery/jquery.min.js');
   // Ajax CORS support for IE9 and lower.
   script_tag('/bower_components/jQuery-ajaxTransport-XDomainRequest/jquery.xdomainrequest.min.js');
   script_tag('/bower_components/jstz/index.js'); // no proper bower packaging, must be updated by hand (in bower.json)
   script_tag('/bower_components/jquery-ui/jquery-ui.min.js');
   script_tag('/bower_components/i18next/i18next.min.js');
   script_tag('/bower_components/pem-platform/task-pr.js');
   script_tag('/gradeContest.js');
   echo "<p>Statut : <div id='gradeContestState'><span class='nbCurrent'></span><span class='current'></span><span class='gradeProcessing'></span></div></p>";
   echo "<iframe id='preview_question' src='' style='width:800px;height:800px;'></iframe>";
   echo "<script>gradeContestsWithRefresh('".$contestID."');</script>";
}
<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
include('./config.php');

// jquery 1.9 is required for IE6+ compatibility.
script_tag('/bower_components/jquery/jquery.min.js');
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
<script>
function markTeamAsTeacher(teamID) {
   $.post("prepareContestResults.php", { markAsTeacher: teamID }, function() {
      $("#markAsTeacher_" + teamID).hide();
   });
}

</script>
<?php

if (isset($_GET["password"])) {
   if (md5($_GET["password"]) == $config->teacherInterface->genericPasswordMd5) {
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


if (isset($_POST["markAsTeacher"])) {
   $query = "UPDATE contestant SET grade = -1 WHERE teamID = :teamID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("teamID" => $_POST["markAsTeacher"]));
   echo "Success";
   exit;
}


if (!isset($_GET["contestID"])) {
   echo "contestID parameter is missing.";
   exit;
}
$contestID = $_GET["contestID"];

if (!isset($_GET["discardedGroupCode"])) {
   echo "discardedGroupCode parameter is missing.";
   exit;
}
$discardedGroupCode = $_GET["discardedGroupCode"];

$query = "SELECT `group`.name, ID FROM `group` WHERE `group`.code = :groupCode";
$stmt = $db->prepare($query);
$stmt->execute(array("groupCode" => $discardedGroupCode));
echo "<h3>Discarded group</h3>";
if ($row = $stmt->fetchObject()) {
   echo "<p>".$row->name."</p>";
   $discardedGroupID = $row->ID;
} else {
   echo "<p>Invalide</p>";
   exit;
}

$action = "";
if (isset($_GET["action"])) {
   $action = $_GET["action"];
}

$startUrl = "?contestID=".$contestID."&discardedGroupCode=".$discardedGroupCode;

function execQueryAndShowNbRows($description, $query, $params) {
   global $db;
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   $rowCount = $stmt->rowCount();
   echo "<p><b>".$description."</b> : ".$rowCount." rows affected.</p>";
}

function execSelectAndShowResults($description, $query, $params, $rowsOnly = false, $extraFieldFunction = null) {
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
      if ($extraFieldFunction != null) {
         echo "<td></td>";
      }
      foreach ($row as $name => $value) {
         echo "<td>".$name."</td>";
      }
      echo "</tr>";
   }
   while ($row != null) {
      echo "<tr>";
      if ($extraFieldFunction != null) {
         echo "<td>".$extraFieldFunction($row)."</td>";
      }
      foreach ($row as $name => $value) {
         echo "<td>".htmlentities($value)."</td>";
      }
      echo "</tr>";
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
   }
   if (!$rowsOnly) {
      echo "</table>";
   }
}

function getListContestIDs($contestID) {
   global $db;
   $query = "SELECT contest.ID, contest.name
      FROM contest
      WHERE contest.ID = :contestID OR contest.parentContestID = :contestID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("contestID" => $contestID));
   $contestsIDs = array();
   while ($row = $stmt->fetchObject()) {
      $contestsIDs[] = $row->ID;
   }
   return $contestsIDs;
}

execSelectAndShowResults("Selected contest(s)", "
   SELECT contest.ID, contest.name FROM contest WHERE contest.ID = :contestID OR contest.parentContestID = :contestID",
   array("contestID" => $contestID));


echo "<h3><a href='".$startUrl."&action=showStats'>Some statistics</a></h3>";
if ($action == "showStats") {
   execSelectAndShowResults("Number of contestants (contestants participating in two subcontests are counted twice)", "
      SELECT team.participationType, count(*) FROM contestant
      JOIN team ON (contestant.teamID = team.ID)
      JOIN `group` ON (`team`.groupID = `group`.ID)
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      GROUP BY team.participationType
      ",
      array("contestID" => $contestID));

   execSelectAndShowResults("Number of distinct official contestants", "
      SELECT count(*) FROM (
         SELECT DISTINCT registrationID FROM contestant
         JOIN team ON (contestant.teamID = team.ID)
         JOIN `group` ON (`team`.groupID = `group`.ID)
         JOIN `contest` ON `group`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND team.participationType = 'Official'
         UNION
         SELECT DISTINCT contestant.ID FROM contestant
         JOIN team ON (contestant.teamID = team.ID)
         JOIN `group` ON (`team`.groupID = `group`.ID)
         JOIN `contest` ON `group`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND team.participationType = 'Official'
         AND contestant.registrationID IS NULL
         )
         tmp
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

   execSelectAndShowResults("Number of teams with NULL score (not yet computed)", "
      SELECT count(*)
      FROM team
      JOIN `group` ON (`team`.groupID = `group`.ID)
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL",
      array("contestID" => $contestID));
     
}

echo "<h3><a href='".$startUrl."&action=fixSubgroups'>Fix subgroups</a></h3>";
if ($action == "fixSubgroups") {
   execQueryAndShowNbRows("Mark groups startTime if subgroup has startTime, sum teams and contestants", "
      UPDATE `group` gparent
      JOIN 
      (SELECT
      gchild.parentGroupID,
      MIN(gchild.startTime) AS startTime,
      SUM(gchild.nbTeamsEffective) AS nbTeamsEffective,
      SUM(gchild.nbStudentsEffective) AS nbStudentsEffective
      FROM `group` gchild      
      JOIN `contest` ON `gchild`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND gchild.parentGroupID IS NOT NULL
      GROUP BY gchild.parentGroupID
      ) gchild
      ON gchild.parentGroupID = gparent.ID
      SET gparent.startTime = gchild.startTime,
      gparent.nbTeamsEffective = gchild.nbTeamsEffective,
      gparent.nbStudentsEffective = gchild.nbStudentsEffective
      ",
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

echo "<h3><a href='".$startUrl."&action=computeGenres'>Automatically determine genre of students (if not asked during the contest)</a></h3>";
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
      WHERE (contest.ID = :contestID) OR (contest.parentContestID = :contestID)
      AND contestant.orig_genre IS NULL",
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

echo "<h3><a href='".$startUrl."&action=showUnofficialGroups'>Check if unofficial groups should be official</a></h3>";
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

echo "<h3><a href='".$startUrl."&action=resetIsOfficial'>Completely reset contestants tmpOfficial status</a></h3>";
if ($action == "resetIsOfficial") { 
   execQueryAndShowNbRows("Set contestant.tmpIsOfficial to NULL for all contestants", "
      UPDATE contestant
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET contestant.tmpIsOfficial = NULL, contestant.reasonUnofficial = NULL
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Truncate duplicate_contestants table", "
      TRUNCATE TABLE duplicate_contestants",
      array("contestID" => $contestID));
}
      
echo "<p>TODO: the first time, make sure teamp participationType is null  and score of all teams are set to NULL.</p>";
      
echo "<h3><a href='".$startUrl."&action=setTeamParticipationType'>Set team participation type to group participationType</a></h3>";
if ($action == "setTeamParticipationType") { 
      
   execQueryAndShowNbRows("Set team.participationType from NULL to participationType of group", "
      UPDATE team
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team.participationType = `group`.participationType
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.participationType IS NULL
      AND team.score IS NULL",
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

echo "<h2>Grading</h2>";

echo "<h3><a href='".$startUrl."&action=handleRecover'>Handle recovered answers</a></h3>";
if ($action == "handleRecover") {
   execQueryAndShowNbRows("Insert recovered answers", "
      INSERT IGNORE INTO team_question (teamID, questionID, answer, score, ffScore, `date`)
      SELECT teamID, questionID, answer, NULL, NULL, NOW()
      FROM team_question_recover
      JOIN team ON team_question_recover.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL",
      array("contestID" => $contestID));

   execSelectAndShowResults("Number of answers to recover", "
      SELECT count(*)
      FROM team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team_question_recover ON (team_question.teamID = team_question_recover.teamID AND team_question.questionID = team_question_recover.questionID AND team_question.answer != team_question_recover.answer)
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Update answers from recovered data", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team_question_recover ON (team_question.teamID = team_question_recover.teamID AND team_question.questionID = team_question_recover.questionID AND team_question.answer != team_question_recover.answer)
      SET team_question.answer = team_question_recover.answer, team_question.score = NULL, team_question.date = NOW()
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Reset score for recovered answers", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN team_question_recover ON (team_question.teamID = team_question_recover.teamID AND team_question.questionID = team_question_recover.questionID )
      SET team_question.score = NULL
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL",
      array("contestID" => $contestID));
}

echo "<h3><a href='".$startUrl."&action=sumFFScores'>Compute temporary team scores (from ffScores) and maxDate  (may be slow)</a></h3>";
if ($action == "sumFFScores") {

   execQueryAndShowNbRows("Save score to ffScore when ffScore IS NULL, and compute maxDate for each team", "
      UPDATE team
      JOIN (
         SELECT teamID, MAX(`date`) AS maxDate, SUM(ffScore) AS score
         FROM team_question
         JOIN team ON team.ID = team_question.teamID
         JOIN `group` ON team.groupID = `group`.ID
         JOIN `contest` ON `group`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND (team.score IS NULL OR team.score = 0)
         GROUP BY team_question.teamID
      ) tmp ON team.ID = tmp.teamID
      SET team.tmpLastAnswerDate = maxDate, team.tmpScore = tmp.score
      WHERE team.score IS NULL OR team.score = 0",
      array("contestID" => $contestID));
}


echo "<h3><a href='".$startUrl."&action=markRecomputeScores'>Mark questions to grade later (-1)</a></h3>";
if ($action == "markRecomputeScores") {
   execQueryAndShowNbRows("Set with NULL score to -1", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `contest` ON `team`.contestID = contest.ID
      SET team_question.score = -1
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score IS NULL AND team_question.ffScore IS NOT NULL
      AND team.score IS NULL",
      array("contestID" => $contestID));
}


/*
Replaced with a change to regrading code so that only 1000 scores can be graded at once
echo "<h3><a href='".$startUrl."&action=markRecomputeScoresGroups'>Mark groups of questions to grade now</a></h3>";
if ($action == "markRecomputeScoresGroups") {
   $questions = getContestQuestions($contestID);
   foreach ($questions as $question) {
      execQueryAndShowNbRows("Set with -1 score to NULL FOR top 10000", "
         UPDATE team_question SET score = NULL WHERE score = -1 AND questionID = :questionID LIMIT 1000",
         array("questionID" => $question->ID));
   }
}
*/

echo "<h3><a href='".$startUrl."&action=markWithRecoveed'>Mark teams with recorvered answer be recomputed</a></h3>";
if ($action == "markWithRecoveed") {
   execQueryAndShowNbRows("Set scores to recompute if team has recovered answers", "
      UPDATE team_question
      JOIN team_question_recover ON team_question.teamID = team_question_recover.teamID
         AND team_question.questionID = team_question_recover.questionID
      JOIN `team` ON `team`.ID = team_question.teamID
      JOIN contest ON team.contestID = contest.ID
      SET team_question.score = NULL
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score = -1
      AND team.score IS NULL",
      array("contestID" => $contestID));
}


echo "<h3><a href='".$startUrl."&action=markAboveMinScore'>Mark teams above threshold to be recomputed</a></h3>";
if ($action == "markAboveMinScore") {
   if (!isset($_GET["minScore"])) {
      echo "Parameter minScore is missing";
      return;
   }
   $minScore = $_GET["minScore"];

   execQueryAndShowNbRows("Set scores to recompute if team.tmpScore >= " + $minScore, "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `contest` ON `team`.contestID = contest.ID
      SET team_question.score = NULL
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score = -1
      AND team_question.ffScore IS NOT NULL
      AND team.tmpScore >= :minScore
      AND team.score IS NULL",
      array("contestID" => $contestID, "minScore" => $minScore));
}
      


echo "<h3><a href='".$startUrl."&action=gradeContest'>Recompute scores</a></h3>";
if ($action == "gradeContest") {
   $language = $config->defaultLanguage;
   // JSON3 shim for IE6-9 compatibility.
   script_tag('/bower_components/json3/lib/json3.min.js');
   // Ajax CORS support for IE9 and lower.
   script_tag('/bower_components/jQuery-ajaxTransport-XDomainRequest/jquery.xdomainrequest.min.js');
   script_tag('/bower_components/jstz/index.js'); // no proper bower packaging, must be updated by hand (in bower.json)
   script_tag('/bower_components/jquery-ui/jquery-ui.min.js');
   script_tag('/bower_components/i18next/i18next.min.js');
   script_tag('/bower_components/pem-platform/task-pr.js');
   script_tag('/gradeContest.js');
   echo "<p>Statut : <div id='gradeContestState'><span class='nbCurrent'></span><span class='current'></span><span class='gradeProcessing'></span></div></p>";
   echo "<iframe id='preview_question' src='' style='width:800px;height:800px;'></iframe>";
   echo "<script>gradeContestWithRefresh('".$contestID."');</script>";
}

echo "<h3><a href='".$startUrl."&action=showScoresToCompute'>Remaining scores to compute (ignoring -1)</a></h3>";
if ($action == "showScoresToCompute") {
   $contestsIDs = getListContestIDs($contestID);
   $strContestsIDs = join(",", $contestsIDs);

   execSelectAndShowResults("Count team_question with score IS NULL)", "
      SELECT count(*) FROM team_question
      JOIN team ON team_question.teamID = team.ID
      WHERE team_question.score IS NULL
      AND contestID IN (".$strContestsIDs.")
      AND team.score IS NULL",
      array());
}

echo "<h3><a href='".$startUrl."&action=recomputeScores'>Show recomputation status for each question</a></h3>";
if ($action == "recomputeScores") {
   execQueryAndShowNbRows("Store contestID in team table", "
      UPDATE team
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team.contestID = `group`.contestID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL",
      array("contestID" => $contestID));

   echo "<table class='queryResults'><tr><td style='width:300px'>Question</td><td>ID</td><td>Score</td><td>Nombre</td></tr>";
   $questions = getContestQuestions($contestID);
   foreach ($questions as $question) {
      execSelectAndShowResults("Scores computation status", "
         SELECT question.name, question.ID, IF(team_question.score<=0 OR team_question.score IS NULL, team_question.score, 'positive') as scoreType, count(*)
         FROM team_question
         JOIN question ON question.ID = team_question.questionID
         JOIN team ON team_question.teamID = team.ID
         JOIN `contest` ON `team`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND team_question.questionID = :questionID
         AND team.score IS NULL
         GROUP BY team_question.questionID, scoreType",
         array("contestID" => $contestID, "questionID" => $question->ID), true);
//         AND (team_question.score IS NULL OR team_question.score < 0)

         }
   echo "</table>";
}


echo "<h3><a href='".$startUrl."&action=showScoreAnomaliesBelow'>List score anomalies (score < ffScore)</a></h3>";
if ($action == "showScoreAnomaliesBelow") {
   execSelectAndShowResults("List team_question where score < ffScore)", "
      SELECT team_question.teamID, team_question.questionID, team_question.score, team_question.ffScore, team.password
      FROM team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score < team_question.ffScore
      AND team_question.score >= 0
      AND team.score IS NULL
      ORDER BY team.ID",
      array("contestID" => $contestID));
}
echo "<h3><a href='".$startUrl."&action=showScoreAnomaliesAbove'>List score anomalies (score > ffScore)</a></h3>";
if ($action == "showScoreAnomaliesAbove") {
      execSelectAndShowResults("Team_questions where score is > ffScore", "
      SELECT team_question.teamID, team_question.questionID, team_question.score, team_question.ffScore, team.password
      FROM team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score > team_question.ffScore
      AND team_question.score >= 0
      AND team.score IS NULL
      ORDER BY team.ID",
      array("contestID" => $contestID));
}

echo "<h3><a href='".$startUrl."&action=fixScoreErrors'>After verification only: replace score with ffScore if ffScore is better (and vice-versa)</a></h3>";
if ($action == "fixScoreErrors") {
   execQueryAndShowNbRows("Reset score for recovered answers", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team_question.score = team_question.ffScore
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score < team_question.ffScore
      AND team.score IS NULL",
      array("contestID" => $contestID));
   
   execQueryAndShowNbRows("Save score into ffScore if better", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team_question.ffScore = team_question.score
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.score > team_question.ffScore
      AND team.score IS NULL",
      array("contestID" => $contestID));
   
   execQueryAndShowNbRows("Save score to ffScore when ffScore IS NULL", "
      UPDATE team_question
      JOIN team ON team_question.teamID = team.ID
      JOIN `group` ON `team`.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team_question.ffScore = team_question.score
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team_question.ffScore IS NULL
      AND team_question.score IS NOT NULL
      AND team.score IS NULL",
      array("contestID" => $contestID));
}

echo "<p>Compute temporary scores again.</p>";

echo "<h2>Attempts that should be discarded</h2>";


echo "<h3><a href='".$startUrl."&action=hideInvalidParticipations'>Hide invalid participations in the discarded group (never started)</a></h3>";
if ($action == "hideInvalidParticipations") {
   
   //ALTER TABLE `team` ADD `old_groupID` BIGINT NOT NULL AFTER `groupID`;

   //TODO : what do we don with non-started groups ? 
   #UPDATE `group` SET contestID = 961428730144625174 WHERE contestID = :contestID AND startTime IS NULL;

   execQueryAndShowNbRows("Move to discarded group, teams created before today and that never started", "
      UPDATE team JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET old_groupID = groupID, groupID = :discardedGroupID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND (team.createTime IS NULL OR (team.createTime < DATE_SUB(NOW(), INTERVAL 1 DAY) AND team.startTime IS NULL))
      AND team.score IS NULL",
      array("contestID" => $contestID, "discardedGroupID" => $discardedGroupID));
}

echo "<h3><a href='".$startUrl."&action=detectDuplicates'>Detect duplicate contestants and make some of them unofficial</a></h3>";
if ($action == "detectDuplicates") {
   execQueryAndShowNbRows("Set tmpIsOfficial from NULL to 0 for contestants from unofficial groups", "
      UPDATE contestant
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET contestant.tmpIsOfficial = 0, contestant.reasonUnofficial = 'Unofficial group'
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND contestant.tmpIsOfficial IS NULL
      AND `group`.participationType = 'Unofficial'
      AND team.score IS NULL",
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
      AND `team`.`participationType` = 'Official'
      AND team.score IS NULL",
      array("contestID" => $contestID));
      
   execQueryAndShowNbRows("Detect and store duplicate contestants", "
      INSERT IGNORE INTO duplicate_contestants (contestant1ID, contestant2ID)
      SELECT ID, duplicateID FROM (
         SELECT `conts`.ID,
         @duplicateContestantID := IF(@prevFirstName=`conts`.firstName AND @prevLastName = `conts`.lastName AND @prevSchoolID = `conts`.cached_schoolID AND (`conts`.`categoryColor` IS NULL OR @prevCategoryColor = `conts`.`categoryColor`), @prevID, NULL) AS duplicateID, 
         @prevFirstName := conts.firstName,
         @prevLastName := conts.lastName,
         @prevSchoolID := conts.cached_schoolID,
         @prevCategoryColor := conts.categoryColor,
         @prevID := conts.ID
         FROM
         (SELECT contestant.*, contest.categoryColor FROM 
         contestant
         JOIN `team` ON contestant.teamID = team.ID
         JOIN `group` ON `team`.groupID = `group`.`ID`
         JOIN `contest` ON `group`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND tmpIsOfficial = 1
         ORDER BY contestant.cached_schoolID DESC , contestant.firstName DESC , contestant.lastName DESC, contest.categoryColor DESC) conts,
         (
            SELECT
            @prevFirstName := 0,
            @prevLastName := 0,
            @prevSchoolID := 0,
            @prevCategoryColor := 0,
            @prevID := 0,
            @num := 0
         ) tmp1
         ) tmp2  WHERE duplicateID IS NOT NULL",
      array("contestID" => $contestID));
         
   execQueryAndShowNbRows("Detect and store duplicate contestants (reverse order)", "
      INSERT IGNORE INTO duplicate_contestants (contestant1ID, contestant2ID)
      SELECT ID, duplicateID FROM (
         SELECT `conts`.ID,
         @duplicateContestantID := IF(@prevFirstName=`conts`.firstName AND @prevLastName = `conts`.lastName AND @prevSchoolID = `conts`.cached_schoolID AND (`conts`.`categoryColor` IS NULL OR @prevCategoryColor = `conts`.`categoryColor`), @prevID, NULL) AS duplicateID, 
         @prevFirstName := conts.firstName,
         @prevLastName := conts.lastName,
         @prevSchoolID := conts.cached_schoolID,
         @prevCategoryColor := conts.categoryColor,
         @prevID := conts.ID
         FROM
         (SELECT contestant.*, contest.categoryColor FROM 
         contestant
         JOIN `team` ON contestant.teamID = team.ID
         JOIN `group` ON `team`.groupID = `group`.`ID`
         JOIN `contest` ON `group`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND tmpIsOfficial = 1
         ORDER BY contestant.cached_schoolID ASC , contestant.firstName ASC , contestant.lastName ASC, contest.categoryColor ASC) conts,
         (
            SELECT
            @prevFirstName := 0,
            @prevLastName := 0,
            @prevSchoolID := 0,
            @prevCategoryColor := 0,
            @prevID := 0,
            @num := 0
         ) tmp1
         ) tmp2  WHERE duplicateID IS NOT NULL",
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

echo "<h3><a href='".$startUrl."&action=markFailed'>Mark as failed, former participations that seem to be failed attempts</a></h3>";
if ($action == "markFailed") {

   execQueryAndShowNbRows("Mark as failed if no answer is stored at all", "
      UPDATE `team`
      JOIN `group` ON `team`.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET duplicateType = 'failed'
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL
      AND tmpLastAnswerDate IS NULL
      AND duplicateType = 'former'",
      array("contestID" => $contestID));

   execQueryAndShowNbRows("Mark as failed initial attempts that lasted less than 25 minutes", "
      UPDATE team
      JOIN `group` ON `team`.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET duplicateType = 'failed'
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.score IS NULL
      AND duplicateType = 'former'
      AND TIMEDIFF(tmpLastAnswerDate, team.startTime) < '00:25:00'",
      array("contestID" => $contestID));

      
   execQueryAndShowNbRows("Mark as failed second attempts that have a lower score than the first attempt", "
      UPDATE team
      JOIN (
         SELECT t1.ID
         FROM `team` t1
         JOIN `group` ON `t1`.`groupID` = `group`.ID
         JOIN `contest` ON `group`.contestID = contest.ID
         JOIN contestant c1 ON c1.teamID = t1.ID
         JOIN duplicate_contestants dc ON dc.contestant1ID = c1.ID
         JOIN contestant c2 ON dc.contestant2ID = c2.ID
         JOIN team t2 ON c2.teamID = t2.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND t1.score = 0
         AND t1.duplicateType = 'latter'
         AND t2.participationType = 'Official'
         AND t2.score > 0
      ) tmp ON team.ID = tmp.ID
      SET team.duplicateType = 'failed'",
      array("contestID" => $contestID));
}

echo "<h3><a href='".$startUrl."&action=removeFailed'>Set 'failed' participations as unofficial and move them to the discarded group.</a></h3>";
if ($action == "removeFailed") {
   execQueryAndShowNbRows("Mark failed participations as unofficial", "
      UPDATE team
      JOIN `group` ON `team`.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team.participationType = 'Unofficial'
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.duplicateType = 'failed'",
      array("contestID" => $contestID));
      
   execQueryAndShowNbRows("Save original group of failed participations", "
      UPDATE team
      JOIN `group` ON `team`.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team.old_groupID = team.groupID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.duplicateType = 'failed'
      AND team.groupID != :discardedGroupID",
      array("contestID" => $contestID, "discardedGroupID" => $discardedGroupID));

   execQueryAndShowNbRows("Move failed participations to discard group", "
      # on les déplace dans le groupe spécial
      UPDATE team
      JOIN `group` ON `team`.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team.groupID = :discardedGroupID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.duplicateType = 'failed'",
      array("contestID" => $contestID, "discardedGroupID" => $discardedGroupID));
}

echo "<h3><a href='".$startUrl."&action=teachersWithDuplicates'>Show teachers with a lot of duplicate students.</a></h3>";
if ($action == "teachersWithDuplicates") {
   echo "Contact these teachers to understand what happened";
   
   execSelectAndShowResults("Teachers with more than 20 duplicate students", "
      SELECT count(*) AS nb, userID, user.officialEmail, user.alternativeEmail
      FROM team
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN user ON user.ID = `group`.userID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND duplicateType = 'former'
      GROUP BY userID
      HAVING nb > 20",
      array("contestID" => $contestID));
}
      
echo "<h2>Handle incorrect grade issues. Make teachers unofficial</h2>";
      
echo "<h3><a href='".$startUrl."&action=wrongGrade'>Detect students that are in the wrong grade.</a></h3>";
if ($action == "wrongGrade") {
   echo "TODO: automate handling of these students.";
   
   execSelectAndShowResults("List students in the wrong grade", "
      SELECT `group`.`ID`, `group`.`name`, team.ID, `group`.`grade`, c1.firstName, c1.lastName, c1.ID, c1.grade, c2.firstName, c2.lastName, c2.ID, c2.grade
      FROM contestant c1 
      JOIN team ON c1.teamID = team.ID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN contestant c2 ON c2.teamID = team.ID AND c2.ID > c1.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.participationType = 'Official'
      AND ABS(c1.grade - c2.grade) > 2",
      array("contestID" => $contestID));      
}

echo "<h3><a href='".$startUrl."&action=otherGrade'>List users with grade 'other'.</a></h3>";
if ($action == "otherGrade") {
   execSelectAndShowResults("List participations with grades 'other' in official groups", "
      SELECT firstName, lastName, contestant.grade,`group`.grade, team.nbContestants, team.ID
      FROM contestant
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND group.participationType = 'Official'
      AND contestant.grade = -4",
      array("contestID" => $contestID));
}

function linkToMarkTeamAsTeacher($row) {
   return "<button type='button' id='markAsTeacher_".$row["teamID"]."' onclick='markTeamAsTeacher(\"".$row["teamID"]."\")'>Mark as teacher</button>";
}
      
echo "<h3><a href='".$startUrl."&action=fakeTeams'>Detect test participations, or teachers participations.</a></h3>";
if ($action == "fakeTeams") {
   execSelectAndShowResults("List participations with names like 'prof', 'test', 'essai', 'techo', 'maths'", "
      SELECT firstName, lastName, `contestant`.grade as contestantGrade, `group`.`grade` as groupGrade, `group`.name as groupName, teamID
      FROM team
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      JOIN contestant ON team.ID = contestant.teamID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.participationType = 'Official'
      AND contestant.grade != -1
      AND (contestant.firstName LIKE '%prof%'
         OR contestant.firstName LIKE '%test%'
         OR contestant.firstName LIKE '%essai%'
         OR contestant.firstName LIKE '%techno%'
         OR contestant.firstName LIKE '%maths%'
         OR contestant.firstName LIKE '%toto%'

         OR contestant.lastName LIKE '%prof%'
         OR contestant.lastName LIKE '%test%'
         OR contestant.lastName LIKE '%essai%'
         OR contestant.lastName LIKE '%techno%'
         OR contestant.lastName LIKE '%maths%'
         OR contestant.lastName LIKE '%toto%'
      )",
      array("contestID" => $contestID), false, "linkToMarkTeamAsTeacher");
}


echo "<h3><a href='".$startUrl."&action=teachersUnofficial'>Mark teachers as unofficial.</a></h3>";
if ($action == "teachersUnofficial") {
   execQueryAndShowNbRows("Mark teacher participations as unofficial", "
      UPDATE contestant
      JOIN team ON team.ID = contestant.teamID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET team.participationType = 'Unofficial'
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND contestant.grade = -1",
      array("contestID" => $contestID));
}

echo "<h2>Publication of results</h2>";

echo "<h3><a href='".$startUrl."&action=updateMaxGrade'>Update nbParticipants and max_grade in teams table (to prepare for ranking.</a></h3>";
if ($action == "updateMaxGrade") {
   execQueryAndShowNbRows("Update nbParticipants and max_grade", "
      UPDATE team JOIN (
         SELECT team.ID, count(*) as nbParticipants, MAX(contestant.grade) as max_grade
         FROM team
         JOIN `group` ON team.groupID = `group`.ID
         JOIN `contest` ON `group`.contestID = contest.ID
         JOIN contestant ON team.ID = contestant.teamID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND team.participationType = 'Official'
         GROUP BY team.ID
      ) t ON team.ID = t.ID
      SET team.nbContestants = t.nbParticipants, team.max_grade = t.max_grade",
      array("contestID" => $contestID));
}

echo "<h3><a href='".$startUrl."&action=showTeamScores'>Make team scores visible to teachers.</a></h3>";
if ($action == "showTeamScores") {
   execQueryAndShowNbRows("Copy tmpScore to score to make scores visible to teachers", "
      UPDATE team
      JOIN `group` ON team.groupID = `group`.`ID`
      JOIN `contest` ON `group`.contestID = contest.ID
      SET score = tmpScore
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.participationType = 'Official'",
      array("contestID" => $contestID));      
}

echo "<p>Set rankGrades and rankNbContestants in main contest record, as needed</p>";

echo "<p>Compute rankings from teacher interface</p>";

echo "<p>In the database: set contest.printCode, contest.showResults, and contest.printCertificates as needed</p>";

echo "<p>To allocate algoreaCodes, insert records such as INSERT INTO award_threshold (contestID, gradeID, awardID, nbContestants, minScore) VALUES ([contestID], 4, 1, 2, 0) for each contest</p>";

echo "<h3><a href='".$startUrl."&action=cleanRanksUnofficial'>Remove ranks of unofficial participants.</a></h3>";
if ($action == "cleanRanksUnofficial") {
   execSelectAndShowResults("Remove ranks of unofficial participants", "
      UPDATE contestant
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.groupID = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      SET rank = NULL, schoolRank = NULL
      WHERE team.participationType = 'Unofficial'
      AND (contest.ID = :contestID OR contest.parentContestID = :contestID)",
      array("contestID" => $contestID));
}

echo "<h3><a href='".$startUrl."&action=studyZeroes'>Study cases of teams with 0 points.</a></h3>";
if ($action == "studyZeroes") {
   execSelectAndShowResults("Number of official teams with zero point", "
      SELECT contest.name, count(*)
      FROM `team`
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.participationType = 'Official'
      AND team.score = 0
      GROUP BY contest.ID",
      array("contestID" => $contestID));

   execSelectAndShowResults("List of teams with zero points and misc information", "
      SELECT contest.name, team.ID, team.password, count(team_question.teamID), TIMESTAMPDIFF(MINUTE, team.startTime, team.endTime), TIMESTAMPDIFF(MINUTE, team.startTime, team.tmpLastAnswerDate)
      FROM `team`
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `group`.contestID = contest.ID
      LEFT JOIN team_question ON team.ID = team_question.teamID
      WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
      AND team.participationType = 'Official'
      AND team.score = 0
      GROUP BY team.ID
      ORDER BY TIMESTAMPDIFF(MINUTE, team.startTime, team.tmpLastAnswerDate) DESC LIMIT 0,1000",
      array("contestID" => $contestID));


   execSelectAndShowResults("Groups with lots of zeroes", "
      SELECT contest.name, `group`.name, `user`.officialEmail, `user`.alternativeEmail, nb
      FROM (SELECT count(*) as nb, groupID
      FROM (
         SELECT team.groupID
         FROM `team`
         JOIN `group` ON `group`.ID = team.groupID
         JOIN `contest` ON `group`.contestID = contest.ID
         WHERE (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND team.participationType = 'Official'
         AND team.score = 0
         GROUP BY team.ID
      ) tmp  GROUP BY groupID) tmp2
      JOIN `group` ON tmp2.groupID = `group`.ID
      JOIN `user` ON `group`.userID = user.ID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      ORDER BY user.ID",
      array("contestID" => $contestID));
}

echo "<h3><a href='".$startUrl."&action=makeGroupUnofficial'>Make a group unofficial (with parameter groupCode)</a></h3>";
if ($action == "makeGroupUnofficial") {
   if (!isset($_GET["groupCode"])) {
      echo "Missing groupCode parameter";
      exit;
   }
   $groupCode = $_GET["groupCode"];
   if (isset($_GET["confirm"])) {
      $confirm = true;
   } else {
      echo "<a href='".$startUrl."&action=makeGroupUnofficial&groupCode=".$groupCode."&confirm=1'>I confirm that this group should be unofficial</a>";
      $confirm = false;
   }
   
   execSelectAndShowResults("Show the teams and students in this group", "
      SELECT contest.name, `group`.name, `user`.officialEmail, `user`.alternativeEmail,
      GROUP_CONCAT(CONCAT(contestant.firstName, ' ', contestant.lastName, '(', contestant.grade, ')', contestant.algoreaCode)),
      team.score, team.rank
      FROM `group`
      JOIN `team`  ON `team`.groupID = `group`.ID
      JOIN `contestant` ON `contestant`.teamID = team.ID
      WHERE `group`.`code` = :groupCode
      GROUP BY contestant.ID",
      array("groupCode" => $groupCode));

   if ($confirm) {
      execQueryAndShowNbRows("Make group unofficial", "
         UPDATE `group`
         SET `participationType` = 'Unofficial'
         WHERE `code` = :groupCode",
         array("groupCode" => $groupCode));
         
      execQueryAndShowNbRows("Make team unofficial", "
         UPDATE `group`
         JOIN `team` ON `team`.`groupID` = `group`.`ID`
         SET `team``participationType` = 'Unofficial'
         WHERE `group`.`code` = :groupCode",
         array("groupCode" => $groupCode));

      execQueryAndShowNbRows("Remove associated registration", "
         DELETE algorea_registration.*
         FROM `group`
         JOIN `team` ON `team`.`groupID` = `group`.`ID`
         JOIN `contestant` ON `contestant`.`teamID` = `team`.`ID`
         JOIN `algorea_registration` ON `algorea_registration`.`code` = `contestant`.`algoreaCode`
         WHERE `group`.`code` = :groupCode",
         array("groupCode" => $groupCode));


      execQueryAndShowNbRows("Remove ranks, algoreaCode", "
         UPDATE `group`
         JOIN `team` ON `team`.`groupID` = `group`.`ID`
         JOIN `contestant` ON `contestant`.`teamID` = `team`.`ID`
         SET `contestant`.`rank` = NULL,
         `contestant`.`schoolRank` = NULL,
         `contestant`.`algoreaCode` = NULL
         WHERE `group`.`code` = :groupCode",
         array("groupCode" => $groupCode));

   }
}


echo "<h3><a href='".$startUrl."&action=makeGroupOfficial'>Make a group official (with parameter groupCode)</a></h3>";
if ($action == "makeGroupOfficial") {
   if (!isset($_GET["groupCode"])) {
      echo "Missing groupCode parameter";
      exit;
   }
   $groupCode = $_GET["groupCode"];
   if (isset($_GET["confirm"])) {
      $confirm = true;
   } else {
      echo "<a href='".$startUrl."&action=makeGroupOfficial&groupCode=".$groupCode."&confirm=1'>I confirm that this group should be official</a>";
      $confirm = false;
   }
   
   execSelectAndShowResults("Show the teams and students in this group", "
      SELECT contest.name, `group`.name, `user`.officialEmail, `user`.alternativeEmail,
      GROUP_CONCAT(CONCAT(contestant.firstName, ' ', contestant.lastName, '(', contestant.grade, ')', IFNULL(contestant.algoreaCode,''))),
      team.score, contestant.rank
      FROM `group`
      JOIN `team`  ON `team`.groupID = `group`.ID
      JOIN `contestant` ON `contestant`.teamID = team.ID
      JOIN `contest` ON `group`.contestID = `contest`.ID
      JOIN `user` ON `group`.userID = user.ID
      WHERE `group`.`code` = :groupCode
      GROUP BY contestant.ID",
      array("groupCode" => $groupCode));

   if ($confirm) {
      execQueryAndShowNbRows("Make group official", "
         UPDATE `group`
         SET `participationType` = 'Official'
         WHERE `code` = :groupCode",
         array("groupCode" => $groupCode));
         
      execQueryAndShowNbRows("Make teams official", "
         UPDATE `group`
         JOIN `team` ON `team`.`groupID` = `group`.`ID`
         SET `team`.`participationType` = 'Official'
         WHERE `group`.`code` = :groupCode",
         array("groupCode" => $groupCode));
         
      echo "<p>You will need to check for incorrect grades issues, recompute rankings, qualifications and algoreaCodes.</p>";
   }
}

echo "<h3><a href='".$startUrl."&action=mergeStudents'>Merge students with same name, grade, user and school (official participations only)</a></h3>";
if ($action == "mergeStudents") {

      execQueryAndShowNbRows("Attach students with no registrationID to identical registered students", "
         UPDATE contestant
         JOIN team ON contestant.teamID = team.ID
         JOIN `group` ON `group`.ID = team.groupID
         JOIN `contest` ON `group`.contestID = contest.ID
         JOIN algorea_registration ON `group`.userID = algorea_registration.userID
         AND `group`.schoolID = algorea_registration.schoolID
         AND contestant.firstName = algorea_registration.firstName
         AND contestant.lastName = algorea_registration.lastName
         AND contestant.grade = algorea_registration.grade
         SET contestant.registrationID = algorea_registration.ID
         WHERE contestant.registrationID IS NULL
         AND team.participationType = 'Official'",
         array());
}

echo "<h3><a href='".$startUrl."&action=newRegistrations'>Create registrations for students that don't have one yet</a></h3>";
if ($action == "newRegistrations") {
      execQueryAndShowNbRows("Create registrations for official contestants that don't have one yet", "
         INSERT IGNORE INTO algorea_registration (ID, firstName, lastName, genre, email, zipCode, grade, studentID, userID, code, contestantID, schoolID)
         SELECT contestant.ID, contestant.firstName, contestant.lastName, contestant.genre, '', '', contestant.grade, '', `group`.`userID`,
         CONCAT(CONCAT('a', FLOOR(RAND()*10000000)), CONCAT('', FLOOR(RAND()*10000000))),
         contestant.ID, `group`.schoolID
         FROM contestant
         JOIN team ON team.ID = contestant.teamID
         JOIN `group` ON `group`.ID = team.groupID
         JOIN `contest` ON `group`.`contestID` = `contest`.ID
         WHERE
         (contest.ID = :contestID OR contest.parentContestID = :contestID)
         AND contestant.registrationID IS NULL
         AND team.participationType = 'Official'",
      array("contestID" => $contestID));

      execQueryAndShowNbRows("Attach newly created registrations to corresponding students", "
         UPDATE contestant
         JOIN algorea_registration ON contestant.ID = algorea_registration.ID
         SET contestant.registrationID = algorea_registration.ID,
         contestant.algoreaCode = algorea_registration.code
         WHERE contestant.registrationID IS NULL OR contestant.algoreaCode IS NULL;",
      array());
}

echo "<h3><a href='".$startUrl."&action=updateCategories'>Update students category depending on their score</a></h3>";
if ($action == "updateCategories") {
   execQueryAndShowNbRows("Set category to 'blanche' if none is defined", "
      UPDATE
      algorea_registration
      JOIN contestant ON algorea_registration.ID = contestant.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      SET algorea_registration.category = 'blanche'
      WHERE algorea_registration.category IS NULL OR algorea_registration.category = ''",
      array());

   execQueryAndShowNbRows("Set category to 'jaune' if qualified by a contest", "
      UPDATE
      algorea_registration
      JOIN contestant ON algorea_registration.ID = contestant.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      SET algorea_registration.category = 'jaune'
      WHERE algorea_registration.category = 'blanche'
      AND team.score >= contest.qualificationScore
      AND contest.qualificationCategory = 'jaune'",
      array());

   execQueryAndShowNbRows("Set category to 'orange' if qualified by a contest", "
      UPDATE
      algorea_registration
      JOIN contestant ON algorea_registration.ID = contestant.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      SET algorea_registration.category = 'orange'
      WHERE (algorea_registration.category = 'blanche' OR algorea_registration.category = 'jaune')
      AND team.score >= contest.qualificationScore
      AND contest.qualificationCategory = 'orange'",
     array());

   execQueryAndShowNbRows("Set category to 'verte' if qualified by a contest", "
      UPDATE
      algorea_registration
      JOIN contestant ON algorea_registration.ID = contestant.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      SET algorea_registration.category = 'verte'
      WHERE (algorea_registration.category = 'blanche' OR algorea_registration.category = 'jaune' OR algorea_registration.category = 'orange')
      AND team.score >= contest.qualificationScore
      AND contest.qualificationCategory = 'verte'",
     array());

   execQueryAndShowNbRows("Set category to 'bleue' if qualified by a contest", "
      UPDATE
      algorea_registration
      JOIN contestant ON algorea_registration.ID = contestant.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON `group`.ID = team.groupID
      JOIN `contest` ON `contest`.ID = `group`.contestID
      SET algorea_registration.category = 'bleue'
      WHERE (algorea_registration.category = 'blanche' OR algorea_registration.category = 'jaune' OR algorea_registration.category = 'orange' OR algorea_registration.category = 'verte')
      AND team.score >= contest.qualificationScore
      AND contest.qualificationCategory = 'bleue'",
     array());
}

echo "<h3><a href='".$startUrl."&action=createRegistrationCategory'>Create records for each students best scores in each category</a></h3>";
if ($action == "createRegistrationCategory") {

   execQueryAndShowNbRows("Create records for white category", 
      "INSERT IGNORE INTO registration_category (registrationID, category) SELECT ID, 'blanche' FROM algorea_registration WHERE algorea_registration.category IN ('blanche', 'jaune', 'orange', 'verte', 'bleue')",
      array());

   execQueryAndShowNbRows("Create records for yellow category", 
      "INSERT IGNORE INTO registration_category (registrationID, category) SELECT ID, 'jaune' FROM algorea_registration WHERE algorea_registration.category IN ('jaune', 'orange', 'verte', 'bleue')",
      array());
   
   execQueryAndShowNbRows("Create records for orange category", 
      "INSERT IGNORE INTO registration_category (registrationID, category) SELECT ID, 'orange' FROM algorea_registration WHERE algorea_registration.category IN ('orange', 'verte', 'bleue')",
      array());
   
   execQueryAndShowNbRows("Create records for green category", 
      "INSERT IGNORE INTO registration_category (registrationID, category) SELECT ID, 'verte' FROM algorea_registration WHERE algorea_registration.category IN ('verte', 'bleue')",
      array());
}

echo "<h3><a href='".$startUrl."&action=updateRegistrationCategory'>Update students best scores in each category</a></h3>";
if ($action == "updateRegistrationCategory") {

   execQueryAndShowNbRows("Update best score for individual participations", 
      "UPDATE
      (SELECT registration_category.ID, GREATEST(IFNULL(registration_category.bestScoreIndividual, 0), MAX(team.score)) as maxScore
      FROM registration_category
      JOIN contestant ON contestant.registrationID = registration_category.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE team.participationType = 'Official'
      AND team.nbContestants = 1
      AND contest.categoryColor = registration_category.category
      GROUP BY registration_category.ID
      )
      tmp
      JOIN registration_category ON tmp.ID = registration_category.ID
      SET registration_category.bestScoreIndividual = tmp.maxScore",
      array());

   execQueryAndShowNbRows("Update date of best score for individual participations", 
      "UPDATE
      (SELECT registration_category.ID, MAX(team.startTime) as maxTime
      FROM registration_category
      JOIN contestant ON contestant.registrationID = registration_category.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE team.participationType = 'Official'
      AND team.nbContestants = 1
      AND team.score = registration_category.bestScoreIndividual
      AND contest.categoryColor = registration_category.category
      GROUP BY registration_category.ID
      )
      tmp
      JOIN registration_category ON tmp.ID = registration_category.ID
      SET registration_category.dateBestScoreIndividual = tmp.maxTime",
      array());

   execQueryAndShowNbRows("Update best score for team participations", 
      "UPDATE
      (SELECT registration_category.ID, GREATEST(IFNULL(registration_category.bestScoreTeam, 0), MAX(team.score)) as maxScore
      FROM registration_category
      JOIN contestant ON contestant.registrationID = registration_category.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE team.participationType = 'Official'
      AND team.nbContestants = 2
      AND contest.categoryColor = registration_category.category
      GROUP BY registration_category.ID
      )
      tmp
      JOIN registration_category ON tmp.ID = registration_category.ID
      SET registration_category.bestScoreTeam = tmp.maxScore",
      array());

   execQueryAndShowNbRows("Update best score for team participations", 
      "UPDATE
      (SELECT registration_category.ID, MAX(team.startTime) as maxTime
      FROM registration_category
      JOIN contestant ON contestant.registrationID = registration_category.registrationID
      JOIN team ON contestant.teamID = team.ID
      JOIN `group` ON team.`groupID` = `group`.ID
      JOIN `contest` ON `group`.contestID = contest.ID
      WHERE team.participationType = 'Official'
      AND team.nbContestants = 1
      AND team.score = registration_category.bestScoreTeam
      AND contest.categoryColor = registration_category.category
      GROUP BY registration_category.ID
      )
      tmp
      JOIN registration_category ON tmp.ID = registration_category.ID
      SET registration_category.dateBestScoreTeam = tmp.maxTime",
      array());
}

echo "<h3><a href='".$startUrl."&action=computeAlgoreaTotalScore'>Compute algorea total scores</a></h3>";
if ($action == "computeAlgoreaTotalScore") {
      execQueryAndShowNbRows("Reset total Algorea score", 
         "UPDATE algorea_registration
         SET totalScoreAlgorea = 0",
         array()
      );

      $categoryMinScore = array("blanche" => 0, "jaune" => 1000, "orange" => 2000, "verte" => 3000);
      
      foreach ($categoryMinScore as $category => $minScore) {
         execQueryAndShowNbRows("Update total Algorea score", 
            "UPDATE algorea_registration
            JOIN registration_category
            ON algorea_registration.ID = registration_category.registrationID
            SET totalScoreAlgorea = 
               ".$minScore." + GREATEST(IFNULL(bestScoreIndividual, 0), IFNULL(bestScoreTeam, 0))
            WHERE registration_category.category = '".$category."'
            AND (bestScoreIndividual > 0 OR bestScoreTeam > 0)
            ",
            array()
         );
      }
}


echo "<h3><a href='".$startUrl."&action=updateAlgoreaRanks'>Compute algorea ranks</a></h3>";
if ($action == "updateAlgoreaRanks") {
   execQueryAndShowNbRows("Update Algorea ranks (per grade)", 
       "UPDATE `algorea_registration` as `c1`,
       (
          SELECT 
              `algorea_registration2`.`ID`,
               @curRank := IF(@prevGrade=`grade`, IF(@prevScore=`algorea_registration2`.`totalScoreAlgorea`, @curRank, @studentNumber + 1), 1) AS algoreaRank, 
               @studentNumber := IF(@prevGrade=grade, @studentNumber + 1, 1) as studentNumber, 
               @prevScore := totalScoreAlgorea,
               @prevGrade := grade
       FROM 
       (
          SELECT 
             `ID`,
             `grade`,
             `totalScoreAlgorea`
         FROM `algorea_registration`
         WHERE 
             totalScoreAlgorea IS NOT NULL
         ORDER BY `grade`, `totalScoreAlgorea` DESC
      ) `algorea_registration2`,
      (
          SELECT 
             @curRank :=0, 
             @prevScore:=null, 
             @studentNumber:=0, 
             @prevGrade := null
            ) r
       ) as `c2`
       SET `c1`.`algoreaRank` = `c2`.`algoreaRank` 
       WHERE `c1`.`ID` = `c2`.`ID`",
       array());
       
   execQueryAndShowNbRows("Update Algorea school ranks (per grade)", 
        "UPDATE `algorea_registration` as `c1`,
          (
             SELECT 
                 `algorea_registration2`.`ID`,
                  @curRank := IF (@prevGrade=`grade`, IF(@prevSchool=`schoolID`, IF(@prevScore=`algorea_registration2`.`totalScoreAlgorea`, @curRank, @studentNumber + 1), 1), 1) AS algoreaSchoolRank, 
                  @studentNumber := IF(@prevGrade=grade, IF(@prevSchool=`schoolID`, @studentNumber + 1, 1), 1) as studentNumber, 
                  @prevScore := totalScoreAlgorea,
                  @prevSchool := `schoolID`,
                  @prevGrade := grade
          FROM 
          (
             SELECT 
                `ID`,
                `totalScoreAlgorea`,
                `grade`,
                `schoolID`
            FROM `algorea_registration`
            WHERE 
                totalScoreAlgorea IS NOT NULL
            ORDER BY `schoolID`, `grade`, `totalScoreAlgorea` DESC
         ) `algorea_registration2`,
         (
             SELECT 
                @curRank :=0, 
                @prevScore:=null, 
                @studentNumber:=0, 
                @prevGrade := null,
                @prevSchool:=null
               ) r
          ) as `c2`
          SET `c1`.`algoreaSchoolRank` = `c2`.`algoreaSchoolRank` 
          WHERE `c1`.`ID` = `c2`.`ID`",
          array());
          
          
   execQueryAndShowNbRows("Remove ranks when total Algorea score is 0", 
     "UPDATE algorea_registration SET algoreaRank = NULL, algoreaSchoolRank = NULL WHERE totalScoreAlgorea = 0" ,
        array());
}

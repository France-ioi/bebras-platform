<?php
require_once("connect.php");
include_once("../dataSanitizer/dataSanitizer.inc.php");

if (get_magic_quotes_gpc()) {
    function stripslashes_gpc(&$value)
    {
        $value = stripslashes($value);
    }
    array_walk_recursive($_GET, 'stripslashes_gpc');
    array_walk_recursive($_POST, 'stripslashes_gpc');
    array_walk_recursive($_COOKIE, 'stripslashes_gpc');
    array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}

// The encoding used for multi-bytes string in always UTF-8
mb_internal_encoding("UTF-8");

function initSession() {
   global $config;
   session_name('contest2');
   if (isset($_POST["SID"]) && $_POST["SID"] != "") {
      session_id($_POST["SID"]);
   }
   session_start();
   if (!isset($_SESSION['CREATED'])) {
       $_SESSION['CREATED'] = time();
   } else if (time() - $_SESSION['CREATED'] > $config->contestInterface->sessionLength) {
      restartSession();
   }
}

function restartSession() {
   if (isset($_SESSION)) {
      session_destroy();
      session_unset();
   }
   session_start();
   $_SESSION['CREATED'] = time();  // update creation time
}

function pickSubset($questionsData, $subsetSize, $contestID, $teamID) {
   $questionsSubset = array();
   $questions = array();
   foreach ($questionsData as $ID => $row) {
	  if (!isset($questions[$row->order])) {
		  $questions[$row->order] = array();
	  }
	  $questions[$row->order][] = $row;
   }
   // Ad-hoc system that selects 4 questions per group. There could be a json field in contest that describes for each
   // randGroup, the number of elements picked for this randGroup.
   $randomSeed = intval(substr($teamID, -8)) + intval(substr($contestID, -8));
   $curRand = $randomSeed;
   foreach ($questions as $orderGroup => $list) {
	   if (count($list) <= $subsetSize) {
		   foreach ($list as $row) {
			   $questionsSubset[$row->ID] = $row;
		   }
	   } else {
		   for ($pick = 0; $pick < $subsetSize; $pick++) {
			   if ($curRand < 10000) {
				   $curRand += $randomSeed;
			   }
			   $choice = $curRand % count($list);
			   $row = $list[$choice];
			   array_splice($list, $choice, 1);
			   $questionsSubset[$row->ID] = $row;
			   $curRand = round($curRand / count($list));
		   }
	   }
   }
   return $questionsSubset;
}

function getQuestions($db, $contestID, $subsetsSize = 0, $teamID = 0) {
   $stmt = $db->prepare("SELECT `question`.`ID`, `question`.`key`, `question`.`path`, `question`.`name`, `contest_question`.`minScore`, `contest_question`.`noAnswerScore`, `contest_question`.`maxScore`, `contest_question`.`options`, `question`.`answerType`, `contest_question`.`order` FROM `contest_question` LEFT JOIN `question` ON (`contest_question`.`questionID` = `question`.`ID`) WHERE `contest_question`.`contestID` = ?");
   $stmt->execute(array($contestID));
   $questionsData = array();
   $i = 0;
   while ($row = $stmt->fetchObject()) {
      $row->options = json_decode(html_entity_decode($row->options));
      // php must be compiled with mysqlnd to fetch values with their real type
      // see http://stackoverflow.com/questions/1197005/how-to-get-numeric-types-from-mysql-using-pdo
      // Warning: AWS has mysqlnd, so it might act differently than on test
      // machines without it.
      // To make things a bit safe:
      $row->maxScore = intval($row->maxScore);
      $row->minScore = intval($row->minScore);
      $row->noAnswerScore = intval($row->noAnswerScore);
	  $row->order = intval($row->order);
	  $questionsData[$row->ID] = $row;
   }
   if ($subsetsSize != 0) {
	   return pickSubset($questionsData, $subsetsSize, $contestID, $teamID);
   }
   return $questionsData;
}

function genAccessCode($db) {
   srand(time() + rand());
   $charsAllowed = "3456789abcdefghijkmnpqrstuvwxy";
   $query = "SELECT `ID` FROM `group` WHERE `password` = ? OR `code` = ? UNION ".
            "SELECT `ID` FROM `team` WHERE `password` = ? UNION ".
            "SELECT `ID` FROM `contestant` WHERE `algoreaCode` = ?";
   $stmt = $db->prepare($query); 
   while(true) {
      $code = "";
      for ($pos = 0; $pos < 8; $pos++) {
         $iChar = rand(0, strlen($charsAllowed) - 1);
         $code .= substr($charsAllowed, $iChar, 1);
      }
      $stmt->execute(array($code, $code, $code, $code));
      $row = $stmt->fetchObject();
      if (!$row) {
         return $code;
      }
      error_log("Error, code ".$code." is already used");
   }
}

function getTotalContestants($contestID, $grade, $nbContestants = null) {
   global $db;
   $params = ['contestID' => $contestID, 'grade' => $grade];
   $query = 'select count(*) from contestant
      join team on team.ID = contestant.teamID
      join `group` on `group`.ID = team.groupID
      where
      `group`.contestID = :contestID AND ';
      if ($nbContestants) {
         $query .= '`team`.nbContestants = :nbContestants AND ';
         $params['nbContestants'] = $nbContestants;
      }
      $query .= 'team.participationType = "Official" AND
      contestant.grade = :grade;';
   $stmt = $db->prepare($query);

   $stmt->execute($params);
   return $stmt->fetchColumn();
}

function translate($key) {
   global $config;
   
   static $teacherTranslationStrings = null;
   if (!$teacherTranslationStrings) {
      $teacherTranslationStrings = json_decode(file_get_contents(__DIR__.'/../teacherInterface/i18n/'.$config->defaultLanguage.'/translation.json'), true);
      if ($config->customStringsName) {
         $specificStrings = json_decode(file_get_contents(__DIR__.'/../teacherInterface/i18n/'.$config->defaultLanguage.'/'.$config->customStringsName.'.json'), true);
         $teacherTranslationStrings = array_merge($teacherTranslationStrings, $specificStrings);
      }
   }
   if (isset($teacherTranslationStrings[$key])) {
      return $teacherTranslationStrings[$key];
   } else {
      return $key;
   }
}

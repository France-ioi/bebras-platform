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
   session_name('contest2');
   if (isset($_POST["SID"]) && $_POST["SID"] != "") {
      session_id($_POST["SID"]);
   }
   session_start();
   if (!isset($_SESSION['CREATED'])) {
       $_SESSION['CREATED'] = time();
   } else if (time() - $_SESSION['CREATED'] > 3600) {
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

function getQuestions($db, $contestID) {
   $stmt = $db->prepare("SELECT `question`.`ID`, `question`.`key`, `question`.`folder`, `question`.`name`, `contest_question`.`minScore`, `contest_question`.`noAnswerScore`, `contest_question`.`maxScore`, `contest_question`.`options`, `question`.`answerType`, `contest_question`.`order` FROM `contest_question` LEFT JOIN `question` ON (`contest_question`.`questionID` = `question`.`ID`) WHERE `contest_question`.`contestID` = ?");
   $stmt->execute(array($contestID));
   $questionsData = array();
   while ($row = $stmt->fetchObject()) {
      $questionsData[$row->ID] = $row;
      $questionsData[$row->ID]->options = json_decode(html_entity_decode($row->options));
      // php must be compiled with mysqlnd to fetch values with their real type
      // see http://stackoverflow.com/questions/1197005/how-to-get-numeric-types-from-mysql-using-pdo
      // Warning: AWS has mysqlnd, so it might act differently than on test
      // machines without it.
      // To make things a bit safe:
      $questionsData[$row->ID]->maxScore = intval($row->maxScore);
      $questionsData[$row->ID]->minScore = intval($row->minScore);
      $questionsData[$row->ID]->noAnswerScore = intval($row->noAnswerScore);
   }
   return $questionsData;
}

function genAccessCode($db) {
   srand(time() + rand());
   $charsAllowed = "23456789abcdefghijkmnpqrstuvwxyz";
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

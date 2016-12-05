<?php

require_once 'connect.php';

$limitDate = '2015-11-17 17:48:50';

$handle = fopen('recentteamquestion.dump', 'r');
$handleout = fopen('recentteamquestion.sql', 'w');

$sqlTable = 'team_question';

fwrite($handleout, "INSERT IGNORE INTO `".$sqlTable."` (`teamID`, `questionID`, `answer`, `score`, `ffScore`, `date`) VALUES ");
$first =true;
$nbEntries = 0;
while (($line = fgets($handle)) !== false) {
	$entry = json_decode($line, true);
	if (isset($entry['date'])) {
	  if ($entry['date']['S'] < $limitDate) {
			continue;
		}
	}
  $nbEntries = $nbEntries + 1;
	$teamID = $entry['teamID']['N'];
	$questionID = $entry['questionID']['N'];
	$ffScore = isset($entry['ffScore']['N']) ? intval($entry['ffScore']['N']) : 'NULL';
	$score = isset($entry['score']['N']) ? intval($entry['score']['N']) : 'NULL';
	$answer = isset($entry['answer']['S']) ? $entry['answer']['S'] : '';
	$date = isset($entry['date']['S']) ? $entry['date']['S'] : 'NULL';
  if ($nbEntries > 50) {
     fwrite($handleout, ";\nINSERT IGNORE INTO `".$sqlTable."` (`teamID`, `questionID`, `answer`, `score`, `ffScore`, `date`) VALUES ");
     $first = true;
     $nbEntries = 0;
  }
	if (!$first) {
		fwrite($handleout, ", ");
	}
	$first= false;
	fwrite($handleout, "(".$db->quote($teamID).", ".$db->quote($questionID).", ".$db->quote($answer).", ".$score.", ".$ffScore.", ".
		$db->quote($date).")");
}
fwrite($handleout, ";\n");

fclose($handle);
fclose($handleout);

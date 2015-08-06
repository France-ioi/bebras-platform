

//*****************************************************************
// récupération des noms des sujets pour un level/year

/*
$query = "SELECT `question`.id, `question`.name, `contest_question`.maxScore
FROM `contest`, `contest_question`, `question`
WHERE `contest`.year = ?
AND `contest`.level = ?
AND `contest_question`.contestID = `contest`.id
AND `contest_question`.questionID = `question`.id
";

$stmt = $db->prepare($query);
$stmt->execute(array($year, $level));
$results = $stmt->fetchAll(PDO::FETCH_OBJ);
if ($results === FALSE) 
  die("no task");
$tasks = array();
foreach ($results as $row) 
   $tasks[$row->id] = $row;
//var_dump($tasks);

*/

/*
$query = "SELECT COUNT(*) as nbTeamsTotal
FROM `contest`, `group`, `team`
WHERE `contest`.year = ?
AND `contest`.level = ?
AND `group`.contestID = `contest`.id
AND `group`.isUnofficial = '2'
AND `team`.groupID = `group`.id
AND `team`.startTime IS NOT NULL
";
*/

/*
$query = "SELECT COUNT(*) as nbTeamsCorrect, `team_question`.questionID as questionID
FROM `team`, `contest_question`, `team_question`
WHERE `team`.cached_officialForContestID = ?
AND `team`.startTime IS NOT NULL
AND `contest_question`.contestID = `team`.cached_officialForContestID
AND `contest_question`.questionID = `team_question`.questionID
AND `team_question`.score = `contest_question`.maxScore
GROUP BY `team_question`.questionID
";
*/


$year = '2012';
$level = '1';

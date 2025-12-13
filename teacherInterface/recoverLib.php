<?php
require_once("commonAdmin.php");
require_once("./config.php");

if(!isset($db)) {
   $db = connect_pdo($config);
}
/*if(!isset($dynamoDB)) {
   $dynamoDB = connect_dynamodb($config);
}*/



$sessID = (string) mt_rand(1, 999999999);

function decodeWithAnswerKey($s, $ak) {
   $d = "";
   $b64c = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9','-','_');
   $b64d = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9','+','/');
   for ($i = 0; $i < strlen($s); $i++) {
       $d .= $b64d[(64 + array_search($s[$i], $b64c) - array_search($ak[$i % strlen($ak)], $b64d)) % 64];
   }
   return base64_decode($d);
}

$nbs = [
   'error' => 0,
   'notfound' => 0,
   'existing' => 0,
   'new' => 0,
   'replaced' => 0,
   'ignored' => 0,
   'classic' => 0,
   'qrcode' => 0,
   'corrupted' => 0,
   'ttl' => 0
   ];


function handleRecoverLine($data, $insert = false) {
   global $db, $config, $nbs, $teamAnswers, $dstTable;
   $html = "";
   $pwd = null;

   // Decode URL
   if(substr($data, 0, 41) == "https://backup.castor-informatique.fr/?s=") {
      $data = substr($data, 41);
      $data = urldecode($data);
   }

   $sc = strpos($data, ';');
   $type = null;
   if($sc !== false) {
      $type = 'qrcode';
      $pwd = substr($data, 0, $sc);
      $data = substr($data, $sc + 1);
      $stmt = $db->prepare("
         SELECT team.*, `group`.name, `group`.isGenerated, contest.name AS contestName, contest.parentContestID, parentcontest.name AS parentContestName FROM team
         JOIN `group` ON team.groupID = `group`.ID
         JOIN contest ON team.contestID = contest.ID
         LEFT JOIN contest AS parentcontest ON contest.parentContestID = parentcontest.ID
         WHERE team.password = :password");
      $stmt->execute(['password' => $pwd]);
      $team = $stmt->fetch(PDO::FETCH_OBJ);
      if(!$team->ID) {
         $nbs['notfound']++;
         $html .= "<p>Team with password $pwd not found.</p>";
      }
      $answerKey = md5($config->contestInterface->finalEncodeSalt . $team->ID);
      $data = decodeWithAnswerKey($data, $answerKey);
      $dataElements = explode(";", $data);
      if(count($dataElements) != 11 || $dataElements[0] != $pwd || ($dataElements[10] != $pwd && substr($dataElements[10], 0, strlen($pwd)) != $pwd)) {
         $nbs['corrupted']++;
         return "<p>Corrupted data (not enough elements, or team password not matching) : " . $data . "</p>";
      }
      if(!is_numeric($dataElements[1]) || !is_numeric($dataElements[9]) || $dataElements[1] != $dataElements[9]) {
         $nbs['corrupted']++;
         return "<p>Corrupted data (scores not numeric or not matching) : " . $data . "</p>";
      }
      $score = $dataElements[1];

      if($score != $team->score) {
         $html .= "<p style='color: red;'><b>Warning : the score in this QR code doesn't match the saved team score.</b> Please check below.</p>";
      } else {
         $html .= "<p>This information has already been stored in the database.</p>";
      }

      $html .= "<p>Data in the QR code :</p>";
      $html .= "<table>";
      $html .= "<tr><td>Team password</td><td>$pwd</td></tr>";
      $html .= "<tr><td>Score</td><td>$score</td></tr>";
      $html .= "<tr><td>All answers sent</td><td>" . ($dataElements[3] == 'true' ? 'yes' : 'no') . "</td></tr>";
      $html .= "<tr><td>Last answers sent at</td><td>" . $dataElements[4] . "</td></tr>";
      $html .= "<tr><td>Current date at QR code generation</td><td>" . $dataElements[6] . "</td></tr>";
      $html .= "<tr><td>Encoded scores of answers to be sent</td><td>" . $dataElements[7] . "</td></tr>";
      $html .= "</table>";
      $html .= "<p>Data in the database :</p>";
      $html .= "<table>";
      $html .= "<tr><td>Team ID</td><td>" . $team->ID . "</td></tr>";
      $html .= "<tr><td>Group ID (name)</td><td>" . ($team->isGenerated ? "[auto-generated group] " : "") . $team->groupID . " (" . $team->groupName . ")</td></tr>";
      $html .= "<tr><td>Contest ID (name)</td><td>" . $team->contestID . " (" . $team->contestName . ")</td></tr>";
      if($team->parentContestName) {
         $html .= "<tr><td>Parent contest ID (name)</td><td>" . $team->parentContestID . " (" . $team->parentContestName . ")</td></tr>";
      }
      $html .= "<tr><td>Create time</td><td>" . $team->createTime . "</td></tr>";
      $html .= "<tr><td>Start time</td><td>" . $team->startTime . "</td></tr>";
      $html .= "<tr><td>End time</td><td>" . $team->endTime . "</td></tr>";
      $html .= "<tr><td>Last answer time</td><td>" . $team->lastAnswerTime . "</td></tr>";
      $html .= "<tr><td>Final answer time</td><td>" . $team->finalAnswerTime . "</td></tr>";
      $html .= "<tr><td>Score</td><td>" . $team->score . "</td></tr>";
      $html .= "<tr><td>tmpScore</td><td>" . $team->tmpScore . "</td></tr>";
      $html .= "<tr><td>tmpScoreQr</td><td>" . $team->tmpScoreQr . "</td></tr>";

      $stmt = $db->prepare("SELECT COUNT(*) FROM team_question WHERE teamID = :teamID");
      $stmt->execute(['teamID' => $team->ID]);
      $nbTQ = $stmt->fetchColumn();
      $html .= "<tr><td>Number of team_questions saved</td><td>" . $nbTQ . "</td></tr>";

      $stmt = $db->prepare("
         SELECT contestant.*, algorea_registration.code FROM contestant
         LEFT JOIN algorea_registration ON contestant.registrationID = algorea_registration.ID
         WHERE teamID = :teamID");
      $stmt->execute(['teamID' => $team->ID]);
      $contestants = $stmt->fetchAll(PDO::FETCH_OBJ);
      if(count($contestants)) {
         $html .= "<tr><td>Contestant code(s) / name(s)</td><td>";
         foreach($contestants as $contestant) {
            $html .= $contestant->code . " (" . $contestant->firstName . " " . $contestant->lastName . ") ";
         }
         $html .= "</td></tr>";
      }

      $html .= "</table>";
      return $html;
   }
}

// ==================== DynamoDB Backup Recovery ====================

function scanDynamoDBBackups($filters = []) {
   global $db, $config, $dynamoDB;
   
   if(!isset($dynamoDB)) {
      require_once dirname(__FILE__).'/../vendor/autoload.php';
      $dynamoDB = connect_dynamodb($config);
   }
   
   $sessID = isset($filters['sessionID']) ? $filters['sessionID'] : mt_rand(1, 999999999);
   
   $stats = [
      'error' => 0,
      'notfound' => 0,
      'existing' => 0,
      'new' => 0,
      'replaced' => 0,
      'ignored' => 0,
      'classic' => 0,
      'qrcode' => 0,
      'corrupted' => 0,
      'scanned' => 0
   ];
   
   $teamAnswers = [];
   
   $params = ['TableName' => 'prod_backup'];
   
   while(true) {
      $scan = $dynamoDB->scan($params);
      
      foreach($scan['Items'] as $item) {
         $stats['scanned']++;
         try {
            $result = processDynamoDBItem($item, $teamAnswers, $sessID);
            if(isset($result['stats'])) {
               foreach($result['stats'] as $key => $val) {
                  $stats[$key] += $val;
               }
            }
            if(isset($result['answers'])) {
               $teamAnswers = $result['answers'];
            }
         } catch(Exception $e) {
            $stats['error']++;
         }
      }
      
      if(isset($scan['LastEvaluatedKey'])) {
         $params['ExclusiveStartKey'] = $scan['LastEvaluatedKey'];
      } else {
         break;
      }
   }
   
   // Insert collected answers
   foreach($teamAnswers as $teamID => $questions) {
      foreach($questions as $questionID => $answer) {
         if(!$answer) { continue; }
         $stmt = $db->prepare("INSERT INTO team_question_other (teamID, questionID, answer, source, insertDate, insertSessionID, date) VALUES(:teamID, :questionID, :answer, 'dynamodb', NOW(), :sessID, NOW())");
         $stmt->execute(['teamID' => $teamID, 'questionID' => $questionID, 'answer' => $answer, 'sessID' => $sessID]);
      }
   }
   
   return $stats;
}

function processDynamoDBItem($item, &$teamAnswers, $sessID) {
   global $db, $config;
   
   $stats = ['qrcode' => 0, 'classic' => 0, 'error' => 0, 'notfound' => 0, 'corrupted' => 0, 'existing' => 0, 'new' => 0, 'replaced' => 0, 'ignored' => 0];
   
   $data = $item['Data']['S'];
   $pwd = null;
   
   $sc = strpos($data, ';');
   if($sc !== false) {
      // QR code format
      $stats['qrcode']++;
      $pwd = $item['Password']['S'];
      $stmt = $db->prepare("SELECT ID FROM team WHERE password = :password");
      $stmt->execute(['password' => $pwd]);
      $id = $stmt->fetchColumn();
      if(!$id) {
         $stats['notfound']++;
         return ['stats' => $stats, 'answers' => $teamAnswers];
      }
      $data = substr($data, $sc + 1);
      $answerKey = md5($config->contestInterface->finalEncodeSalt . $id);
      $data = decodeWithAnswerKey($data, $answerKey);
      $dataElements = explode(";", $data);
      if(count($dataElements) != 11 || $dataElements[0] != $pwd || ($dataElements[10] != $pwd && substr($dataElements[10], 0, strlen($pwd)) != $pwd)) {
         $stats['corrupted']++;
         return ['stats' => $stats, 'answers' => $teamAnswers];
      }
      if(!is_numeric($dataElements[1]) || !is_numeric($dataElements[9]) || $dataElements[1] != $dataElements[9]) {
         $stats['corrupted']++;
         return ['stats' => $stats, 'answers' => $teamAnswers];
      }
      // QR code processed successfully - could extract individual answers if needed
      return ['stats' => $stats, 'answers' => $teamAnswers];
   }
   
   // Classic JSON format
   $stats['classic']++;
   try {
      $data = json_decode(base64_decode($data), true);
      if(isset($data['pwd'])) {
         $pwd = "" . $data['pwd'];
      }
   } catch(Exception $e) {
      $stats['error']++;
      return ['stats' => $stats, 'answers' => $teamAnswers];
   }
   
   if(!$pwd) {
      $stats['error']++;
      return ['stats' => $stats, 'answers' => $teamAnswers];
   }
   
   $stmt = $db->prepare("SELECT ID FROM team WHERE password = :password");
   $stmt->execute(['password' => $pwd]);
   $id = $stmt->fetchColumn();
   if(!$id) {
      $stats['notfound']++;
      return ['stats' => $stats, 'answers' => $teamAnswers];
   }
   
   if(!isset($teamAnswers[$id])) {
      $teamAnswers[$id] = [];
   }
   
   foreach($data['ans'] as $ans) {
      $questionId = $ans[0];
      $answer = $ans[1];
      if(!isset($teamAnswers[$id][$questionId])) {
         $stmt = $db->prepare("SELECT teamID FROM team_question_other WHERE teamID = :id AND questionID = :questionId");
         $stmt->execute(['id' => $id, 'questionId' => $questionId]);
         if($stmt->fetch()) {
            $teamAnswers[$id][$questionId] = false;
            $stats['existing']++;
         } else {
            $teamAnswers[$id][$questionId] = $answer;
            $stats['new']++;
         }
      } else {
         $curAnswer = $teamAnswers[$id][$questionId];
         if($curAnswer === false) { continue; }
         if(strlen($answer) > strlen($curAnswer)) {
            $teamAnswers[$id][$questionId] = $answer;
            $stats['replaced']++;
         } else {
            $stats['ignored']++;
         }
      }
   }
   
   return ['stats' => $stats, 'answers' => $teamAnswers];
}

// ==================== Error Log Recovery ====================

function scanErrorLogAnswers($dateRange = null) {
   global $db;
   
   $sessID = mt_rand(1, 999999999);
   $stats = ['processed' => 0, 'recovered' => 0, 'invalid' => 0, 'teamNotFound' => 0];
   
   $query = "SELECT * FROM error_log WHERE message LIKE '%sendAnswer error params%' AND teamID IS NOT NULL";
   $params = [];
   if($dateRange && isset($dateRange['start']) && $dateRange['start']) {
      $query .= " AND date >= :startDate";
      $params['startDate'] = $dateRange['start'];
   }
   if($dateRange && isset($dateRange['end']) && $dateRange['end']) {
      $query .= " AND date <= :endDate";
      $params['endDate'] = $dateRange['end'];
   }
   $query .= " ORDER BY ID ASC";
   
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   
   while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $stats['processed']++;
      try {
         $data = json_decode($row['message']);
      } catch(Exception $e) {
         $stats['invalid']++;
         continue;
      }
      try {
         $data = json_decode($data[2][1], true);
      } catch(Exception $e) {
         $stats['invalid']++;
         continue;
      }
      $teamID = $row['teamID'];
      if($teamID != $data['teamID']) {
         $stats['invalid']++;
         continue;
      }
      $stmt2 = $db->prepare("INSERT INTO team_question_other (teamID, questionID, answer, ffScore, date, source, insertDate, insertSessionID) VALUES(:teamID, :questionID, :answer, :score, :date, 'error_log_answers', NOW(), :sessID) ON DUPLICATE KEY UPDATE answer = :answer, ffScore = :score, date = :date");
      foreach($data['answers'] as $questionID => $questionData) {
         $stmt2->execute(['teamID' => $teamID, 'questionID' => $questionID, 'answer' => $questionData['answer'], 'score' => $questionData['score'], 'date' => $row['date'], 'sessID' => $sessID]);
         $stats['recovered']++;
      }
   }
   
   return $stats;
}

function scanErrorLogScores($dateRange = null) {
   global $db;
   
   $sessID = mt_rand(1, 999999999);
   $stats = ['processed' => 0, 'recovered' => 0, 'invalid' => 0];
   
   $query = "SELECT * FROM error_log WHERE message LIKE '%error from answer.php while sending answers%' AND teamID IS NOT NULL";
   $params = [];
   if($dateRange && isset($dateRange['start']) && $dateRange['start']) {
      $query .= " AND date >= :startDate";
      $params['startDate'] = $dateRange['start'];
   }
   if($dateRange && isset($dateRange['end']) && $dateRange['end']) {
      $query .= " AND date <= :endDate";
      $params['endDate'] = $dateRange['end'];
   }
   
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   
   while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $stats['processed']++;
      try {
         $data = json_decode($row['message']);
      } catch(Exception $e) {
         $stats['invalid']++;
         continue;
      }
      try {
         if(substr($data[3][1], 0, 5) != 'score' || substr($data[4][1], 0, 4) != 'time') {
            $stats['invalid']++;
            continue;
         }
      } catch(Exception $e) {
         $stats['invalid']++;
         continue;
      }
      $teamID = $row['teamID'];
      try {
         $score = intval(substr($data[3][1], 6));
      } catch(Exception $e) {
         $stats['invalid']++;
         continue;
      }
      
      // Store as a special entry in team_question_other
      $stmt2 = $db->prepare("INSERT INTO team_question_other (teamID, questionID, score, date, source, insertDate, insertSessionID) VALUES(:teamID, 0, :score, :date, 'error_log_scores', NOW(), :sessID)");
      $stmt2->execute(['teamID' => $teamID, 'score' => $score, 'date' => $row['date'], 'sessID' => $sessID]);
      $stats['recovered']++;
   }
   
   return $stats;
}

// ==================== Merge & Analysis ====================

function compareTeamAnswers($teamID) {
   global $db;
   
   $result = [
      'teamID' => $teamID,
      'improvements' => [],
      'unchanged' => 0,
      'hasImprovements' => false,
      'currentTotal' => 0,
      'recoveredTotal' => 0
   ];
   
   // Get all questions for this team from both tables
   $stmt = $db->prepare("SELECT questionID, answer, score, ffScore, scoreNeedsChecking, checkStatus FROM team_question WHERE teamID = :teamID");
   $stmt->execute(['teamID' => $teamID]);
   $currentAnswers = [];
   while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $currentAnswers[$row['questionID']] = $row;
      $currentScore = $row['score'] !== null ? $row['score'] : ($row['ffScore'] !== null ? $row['ffScore'] : 0);
      $result['currentTotal'] += $currentScore;
   }
   
   $stmt = $db->prepare("SELECT questionID, answer, score, ffScore, scoreNeedsChecking, checkStatus, source FROM team_question_other WHERE teamID = :teamID AND source != 'archived' ORDER BY CASE WHEN score IS NOT NULL THEN score WHEN ffScore IS NOT NULL THEN ffScore ELSE 0 END DESC");
   $stmt->execute(['teamID' => $teamID]);
   
   while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $questionID = $row['questionID'];
      $recoveredScore = $row['score'] !== null ? $row['score'] : ($row['ffScore'] !== null ? $row['ffScore'] : 0);
      
      if(isset($currentAnswers[$questionID])) {
         $currentScore = $currentAnswers[$questionID]['score'] !== null ? 
            $currentAnswers[$questionID]['score'] : 
            ($currentAnswers[$questionID]['ffScore'] !== null ? $currentAnswers[$questionID]['ffScore'] : 0);
         
         if($recoveredScore > $currentScore) {
            $result['improvements'][] = [
               'questionID' => $questionID,
               'currentScore' => $currentScore,
               'recoveredScore' => $recoveredScore,
               'source' => $row['source'],
               'recoveredAnswer' => $row['answer']
            ];
            $result['hasImprovements'] = true;
            $result['recoveredTotal'] += $recoveredScore;
         } else {
            $result['unchanged']++;
            $result['recoveredTotal'] += $currentScore;
         }
      } else {
         // New question not in current answers
         $result['improvements'][] = [
            'questionID' => $questionID,
            'currentScore' => 0,
            'recoveredScore' => $recoveredScore,
            'source' => $row['source'],
            'recoveredAnswer' => $row['answer']
         ];
         $result['hasImprovements'] = true;
         $result['recoveredTotal'] += $recoveredScore;
      }
   }
   
   return $result;
}

function mergeRecoveryData($contestID = null, $preview = true) {
   global $db;
   
   $stats = ['analyzed' => 0, 'improved' => 0, 'unchanged' => 0, 'archived' => 0, 'updated' => 0];
   $sessID = mt_rand(1, 999999999);
   
   // Get all teams with recovery data for this contest
   $query = "SELECT DISTINCT tqo.teamID FROM team_question_other tqo JOIN team ON tqo.teamID = team.ID JOIN contest ON contest.ID = team.contestID WHERE tqo.source != 'archived'";
   $params = [];
   if($contestID) {
      $query .= " AND (team.contestID = :contestID OR contest.parentContestID = :contestID)";
      $params['contestID'] = $contestID;
   }
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   $teamIDs = $stmt->fetchAll(PDO::FETCH_COLUMN);
   
   foreach($teamIDs as $teamID) {
      $stats['analyzed']++;
      $comparison = compareTeamAnswers($teamID);
      
      if(!$comparison['hasImprovements']) {
         $stats['unchanged']++;
         continue;
      }
      
      if($preview) {
         $stats['improved']++;
         continue;
      }
      
      // Execute merge without transaction
         
         foreach($comparison['improvements'] as $improvement) {
            $questionID = $improvement['questionID'];
            
            // Check if there's a current answer to archive
            $stmt = $db->prepare("SELECT * FROM team_question WHERE teamID = :teamID AND questionID = :questionID");
            $stmt->execute(['teamID' => $teamID, 'questionID' => $questionID]);
            $currentAnswer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($currentAnswer) {
               // Archive current answer
               $stmt = $db->prepare("INSERT INTO team_question_other (teamID, questionID, answer, score, ffScore, scoreNeedsChecking, checkStatus, date, source, insertDate, insertSessionID) VALUES(:teamID, :questionID, :answer, :score, :ffScore, :scoreNeedsChecking, :checkStatus, :date, 'archived', NOW(), :sessID)");
               $stmt->execute(['teamID' => $teamID, 'questionID' => $questionID, 'answer' => $currentAnswer['answer'], 'score' => $currentAnswer['score'], 'ffScore' => $currentAnswer['ffScore'], 'scoreNeedsChecking' => $currentAnswer['scoreNeedsChecking'], 'checkStatus' => $currentAnswer['checkStatus'], 'date' => $currentAnswer['date'], 'sessID' => $sessID]);
               $stats['archived']++;
            }
            
            // Get the best recovered answer
            $stmt = $db->prepare("SELECT * FROM team_question_other WHERE teamID = :teamID AND questionID = :questionID AND source != 'archived' ORDER BY CASE WHEN score IS NOT NULL THEN score WHEN ffScore IS NOT NULL THEN ffScore ELSE 0 END DESC LIMIT 1");
            $stmt->execute(['teamID' => $teamID, 'questionID' => $questionID]);
            $recoveredAnswer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($recoveredAnswer) {
               // Update or insert into team_question
               $stmt = $db->prepare("INSERT INTO team_question (teamID, questionID, answer, score, ffScore, scoreNeedsChecking, checkStatus, date) VALUES(:teamID, :questionID, :answer, :score, :ffScore, :scoreNeedsChecking, :checkStatus, :date) ON DUPLICATE KEY UPDATE answer = VALUES(answer), score = VALUES(score), ffScore = VALUES(ffScore), scoreNeedsChecking = VALUES(scoreNeedsChecking), checkStatus = VALUES(checkStatus), date = VALUES(date)");
               $stmt->execute(['teamID' => $teamID, 'questionID' => $questionID, 'answer' => $recoveredAnswer['answer'], 'score' => $recoveredAnswer['score'], 'ffScore' => $recoveredAnswer['ffScore'], 'scoreNeedsChecking' => $recoveredAnswer['scoreNeedsChecking'], 'checkStatus' => $recoveredAnswer['checkStatus'], 'date' => $recoveredAnswer['date']]);
               $stats['updated']++;
            }
         }
         
         $stats['improved']++;
   }
   
   return $stats;
}

// ==================== Score Comparison & Application ====================

function compareRecoveredScores($contestID = null) {
   global $db;
   
   $results = [];
   
   $query = "SELECT team.ID as teamID, team.score as currentScore, tqo.score as recoveredScore FROM team JOIN team_question_other tqo ON tqo.teamID = team.ID JOIN contest ON contest.ID = team.contestID WHERE tqo.questionID = 0 AND tqo.source = 'error_log_scores' AND tqo.score > team.score";
   $params = [];
   if($contestID) {
      $query .= " AND (team.contestID = :contestID OR contest.parentContestID = :contestID)";
      $params['contestID'] = $contestID;
   }
   
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   
   while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $results[] = $row;
   }
   
   return $results;
}

function applyRecoveredScores($teamIDs) {
   global $db;
   
   $stats = ['applied' => 0, 'archived' => 0];
   $sessID = mt_rand(1, 999999999);
   
   foreach($teamIDs as $teamID) {
      $stmt = $db->prepare("SELECT score FROM team_question_other WHERE teamID = :teamID AND questionID = 0 AND source = 'error_log_scores' ORDER BY score DESC LIMIT 1");
      $stmt->execute(['teamID' => $teamID]);
      $recoveredScore = $stmt->fetchColumn();
      
      if($recoveredScore === false) continue;
      
      $stmt = $db->prepare("SELECT score FROM team WHERE ID = :teamID");
      $stmt->execute(['teamID' => $teamID]);
      $currentScore = $stmt->fetchColumn();
      
      if($currentScore !== false) {
         $stmt = $db->prepare("INSERT INTO team_question_other (teamID, questionID, score, source, insertDate, insertSessionID, date) VALUES(:teamID, 0, :score, 'archived', NOW(), :sessID, NOW())");
         $stmt->execute(['teamID' => $teamID, 'score' => $currentScore, 'sessID' => $sessID]);
         $stats['archived']++;
      }
      
      $stmt = $db->prepare("UPDATE team SET score = :score WHERE ID = :teamID");
      $stmt->execute(['score' => $recoveredScore, 'teamID' => $teamID]);
      $stats['applied']++;
   }
   
   return $stats;
}
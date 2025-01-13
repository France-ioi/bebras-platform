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
      $stmt = $db->prepare("SELECT * FROM team WHERE password = :password");
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
         $html .= "<p><b>Warning : the score in this QR code doesn't match the saved team score.</b> Please check below.</p>";
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
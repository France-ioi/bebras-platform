<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../shared/connect.php');

function decodeRecoveryData($raw) {
   $raw = preg_replace('/\s+/', '', $raw);
   if (strlen($raw) >= 2 && $raw[0] === '"' && $raw[strlen($raw) - 1] === '"') {
      $raw = substr($raw, 1, -1);
   }
   $cleaned = preg_replace('/[^A-Za-z0-9+\/=]/', '', $raw);
   for ($trimStart = 0; $trimStart <= 10; $trimStart++) {
      for ($trimEnd = 0; $trimEnd <= 10; $trimEnd++) {
         $candidate = $cleaned;
         if ($trimStart > 0) {
            $candidate = substr($candidate, $trimStart);
         }
         if ($trimEnd > 0) {
            $candidate = substr($candidate, 0, -$trimEnd);
         }
         if (strlen($candidate) < 4) {
            continue;
         }
         $padded = $candidate . '==';
         $decoded = @base64_decode($padded, true);
         if ($decoded === false || $decoded === '') {
            continue;
         }
         $json = json_decode($decoded, true);
         if (is_array($json) && isset($json['pwd']) && isset($json['ans'])) {
            return $json;
         }
      }
   }
   return false;
}

$results = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['encodedData'])) {
   $rawData = trim($_POST['encodedData']);
   if ($rawData === '' && isset($_FILES['encodedFile']) && $_FILES['encodedFile']['error'] === UPLOAD_ERR_OK) {
      $rawData = file_get_contents($_FILES['encodedFile']['tmp_name']);
   }
   if ($rawData === '') {
      $results[] = array('success' => false, 'message' => 'No data provided. Please paste the encoded text or upload a file.');
   } else {
      $lines = explode("\n", $rawData);
      foreach ($lines as $lineNum => $line) {
         $line = trim($line);
         if ($line === '') {
            continue;
         }
         $json = decodeRecoveryData($line);
         if ($json === false) {
            $results[] = array('success' => false, 'line' => $lineNum + 1, 'message' => 'Invalid data format, make sure you paste the data exactly as it was provided.');
            continue;
         }
         $pwd = $json['pwd'];
         $ans = $json['ans'];

         $stmt = $db->prepare("SELECT `team`.`ID`, `group`.`name` as `groupName`, `contest`.`name` as contestName " .
            "FROM `team` " .
            "JOIN `group` ON team.groupID = `group`.ID " .
            "JOIN `contest` ON `group`.contestID = `contest`.ID " .
            "WHERE `team`.`password` = ?");
         $stmt->execute(array($pwd));
         if ($row = $stmt->fetchObject()) {
            $stmtInsert = $db->prepare("INSERT IGNORE INTO `team_question_recover` (`teamID`, `questionID`, `answer`) VALUES (?, ?, ?)");
            $stmtUpdate = $db->prepare("UPDATE `team_question_recover` SET `answer` = ? WHERE `teamID` = ? AND `questionID` = ?");
            $stmtReset = $db->prepare("UPDATE `team` SET `score` = NULL WHERE ID = ?");
            $teamID = $row->ID;
            $count = 0;
            foreach ($ans as $answer) {
               $stmtInsert->execute(array($teamID, $answer[0], $answer[1]));
               $stmtUpdate->execute(array($answer[1], $teamID, $answer[0]));
               $stmtReset->execute(array($teamID));
               $count++;
            }
            $results[] = array('success' => true, 'line' => $lineNum + 1, 'message' => 'Team ' . $teamID . ' (' . $row->groupName . ', ' . $row->contestName . '): ' . $count . ' answer(s) saved.');
         } else {
            $results[] = array('success' => false, 'line' => $lineNum + 1, 'message' => 'Team with password "' . htmlspecialchars($pwd) . '" not found.');
         }
      }
   }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title>Recover answers</title>
<?php
   stylesheet_tag('/style.css');
?>
</head>
<body>
<div id="divHeader">
  <div id="leftTitle"></div>
  <div id="rightTitle"></div>
  <div id="headerGroup">
    <h1 id="headerH1">Recover answers</h1>
    <h2 id="headerH2">Submit your unsent answers</h2>
  </div>
</div>

<div id="mainContent">
  <div class="dialog">
    <h3>Recover your answers</h3>
    <p>If your answers were not sent at the end of a contest, you can submit them here. Paste the encoded text you were given, or upload the downloaded text file.</p>
    <p>The encoded text was shown in a text box at the end of your contest participation, and may also have been saved as a <code>.txt</code> file on your computer.</p>

    <form method="post" action="recover.php" enctype="multipart/form-data">
      <p>
        <label for="encodedData"><b>Paste encoded text:</b></label><br>
        <textarea name="encodedData" id="encodedData" cols="80" rows="10" style="width:100%; max-width:700px; box-sizing:border-box;"></textarea>
      </p>
      <p>
        <label for="encodedFile"><b>Or upload a text file:</b></label><br>
        <input type="file" name="encodedFile" id="encodedFile" accept=".txt">
      </p>
      <p>
        <button type="submit" class="btn btn-primary">Submit answers</button>
      </p>
    </form>

<?php if (!empty($results)): ?>
    <div id="results" style="margin-top: 20px;">
      <h3>Results</h3>
<?php foreach ($results as $result): ?>
      <p style="color: <?= $result['success'] ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($result['message']) ?>
      </p>
<?php endforeach; ?>
    </div>
<?php endif; ?>

    <p style="margin-top:30px;"><a href="index.php">Back to contest page</a></p>
  </div>
</div>

</body>
</html>

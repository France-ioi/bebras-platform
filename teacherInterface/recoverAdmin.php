<?php
require_once("commonAdmin.php");
require_once("./config.php");
require_once("./recoverLib.php");
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title data-i18n="recover_admin_title">Recover interface</title>
<?php stylesheet_tag('/admin.css'); ?>
<style>
   textarea {
      width: 98%;
      max-width: 800px;
      height: 200px;
      border: 1px solid #ccc;
      padding: 6px 12px;
      border-radius: 4px;
      font-family: monospace;
   }

   table {
      border-collapse: collapse;
      margin: 10px 0;
   }
   tr, td {
      border: 1px solid #ddd;
      padding: 6px 12px;
   }
   
   .section {
      margin-bottom: 40px;
      padding-bottom: 30px;
      border-bottom: 1px solid #ddd;
   }
   
   .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 10px;
      margin: 15px 0;
   }
   
   .stat-box {
      padding: 15px;
      background: #e8f4f8;
      border: 1px solid #b8d4e0;
      border-radius: 4px;
      text-align: center;
      box-shadow: 1px 1px 3px rgba(0,0,0,0.1);
   }
   
   .stat-box .label {
      font-size: 12px;
      color: #666;
      margin-bottom: 5px;
   }
   
   .stat-box .value {
      font-size: 28px;
      font-weight: bold;
      color: #4D87CE;
   }
   
   .loading {
      display: none;
      margin: 15px 0;
   }
   
   .loading.active {
      display: block;
   }
   
   .error {
      color: #d32f2f;
      margin: 15px 0;
   }
   
   .success {
      color: #2e7d32;
      margin: 15px 0;
   }
   
   .improvement-row {
      padding: 10px;
      margin: 8px 0;
      background: white;
      border: 1px solid #ddd;
      border-radius: 4px;
      cursor: pointer;
      transition: all 150ms;
   }
   
   .improvement-row:hover {
      background: #f5f5f5;
      border-color: #999;
   }
   
   .improvement-row.selected {
      background: #e3f2fd;
      border-color: #4D87CE;
      box-shadow: 0 0 5px rgba(77, 135, 206, 0.3);
   }
   
   input[type="text"],
   input[type="date"] {
      border: 1px solid #ccc;
      padding: 6px 12px;
      border-radius: 4px;
      margin-right: 10px;
   }
   
   .results {
      margin-top: 15px;
   }
   
   .results table {
      border-collapse: collapse;
   }
   
   .results table td {
      padding: 5px 15px 5px 0;
      border: none;
   }
</style>
</head>
<body class="body-margin">
<?php
if (!isset($_SESSION["userID"])) {
   echo "<p>" . translate("session_expired") . "</p>";
   echo "<p>" . translate("go_to_index") . "</p>";
   echo "</body>";
   exit;
}

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo "<p>" . translate("admin_restricted") . "</p>";
   echo "<p>" . translate("go_to_index") . "</p>";
   echo "</body>";
   exit;
}
?>

<h1>Data Recovery Interface</h1>

<!-- Section 1: QR Code Recovery -->
<div class="section">
   <h2>QR Code Recovery</h2>
   <p>Paste QR code data below to decode and view team information.</p>
   
   <?php
   if(isset($_POST["data"])) {
      $dataLines = explode("\n", $_POST["data"]);
      foreach($dataLines as $data) {
         if(trim($data) == "") {
            continue;
         }
         echo "<p><strong>Processing:</strong> " . htmlspecialchars(substr($data, 0, 50)) . "...</p>";
         echo handleRecoverLine($data);
         echo "<hr>";
      }
   }
   ?>
   
   <form method="post" action="recoverNew.php">
      <textarea name="data" placeholder="Paste QR code data here (one per line)"></textarea>
      <br>
      <button type="submit" class="btn btn-primary">Decode QR Codes</button>
   </form>
</div>

<!-- Section 2: Backup Answer Service -->
<div class="section">
   <h2>Backup Answer Service</h2>
   <p>Note: Full scan can take several minutes.</p>
   
   <button class="btn btn-primary" onclick="scanDynamoDB()">Scan DynamoDB</button>
   
   <div id="dynamodb-loading" class="loading">
      <strong>Scanning DynamoDB...</strong> This may take a few minutes. Please wait.
   </div>
   
   <div id="dynamodb-results" class="results" style="display: none;">
      <h3>Scan Results</h3>
      <div id="dynamodb-stats" class="stats"></div>
      <div id="dynamodb-details"></div>
   </div>
</div>

<!-- Section 3: Error Log Recovery -->
<div class="section">
   <h2>Error Log</h2>
   <p>Scan error logs to recover answers and scores that failed to save.</p>
   
   <p>
      <label>Start Date: <input type="date" id="errorlog-start-date" value="<?= date('Y-m-d', strtotime('-1 day')) ?>"></label>
      <label>End Date: <input type="date" id="errorlog-end-date" value="<?= date('Y-m-d') ?>"></label>
   </p>
   
   <button class="btn btn-primary" onclick="scanErrorLogAnswers()">Scan Error Log (Answers)</button>
   <button class="btn btn-primary" onclick="scanErrorLogScores()">Scan Error Log (Scores)</button>
   
   <div id="errorlog-answers-loading" class="loading">
      <strong>Scanning error log for answers...</strong>
   </div>
   
   <div id="errorlog-answers-results" class="results" style="display: none;">
      <h4>Answer Recovery Results</h4>
      <div id="errorlog-answers-stats" class="stats"></div>
   </div>
   
   <div id="errorlog-scores-loading" class="loading">
      <strong>Scanning error log for scores...</strong>
   </div>
   
   <div id="errorlog-scores-results" class="results" style="display: none;">
      <h4>Score Recovery Results</h4>
      <div id="errorlog-scores-stats" class="stats"></div>
   </div>
</div>

<!-- Section 4: Merge & Apply -->
<div class="section">
   <h2>Merge team_question</h2>
   <p>Keep best score from recovered answers for all teams in contest</p>
   
   <p>
      <label>Contest ID: <input type="text" id="merge-contest" placeholder="Enter contest ID" required></label>
   </p>
   <p>
      <button class="btn btn-primary" onclick="previewMerge()">Preview Merge</button>
      <button class="btn btn-primary" onclick="executeMerge()">Execute Merge</button>
   </p>
   
   <div id="merge-loading" class="loading">
      <strong>Processing...</strong>
   </div>
   
   <div id="merge-results" class="results" style="display: none;">
      <h3>Results</h3>
      <div id="merge-stats"></div>
   </div>
</div>

<!-- Section 5: Score Comparison & Application -->
<div class="section">
   <h2>Recovered Total Scores</h2>
   <p>Find teams whose current score is lower than recovered total scores from error logs.</p>
   
   <p>
      <label>Contest ID: <input type="text" id="scores-contest" placeholder="Enter contest ID (optional)"></label>
      <button class="btn btn-primary" onclick="compareScores()">Find Teams with Better Scores</button>
   </p>
   
   <div id="scores-loading" class="loading">
      <strong>Comparing scores...</strong>
   </div>
   
   <div id="scores-results" class="results" style="display: none;">
      <h3>Teams with Improvements</h3>
      <div id="scores-list"></div>
      <div style="margin-top: 20px;">
         <button class="btn btn-primary" onclick="applyScores()">Apply Selected Scores</button>
         <button class="btn btn-default" onclick="selectAllScores()">Select All</button>
         <button class="btn btn-default" onclick="deselectAllScores()">Deselect All</button>
      </div>
   </div>
   
   <div id="scores-apply-results" style="display: none; margin-top: 20px;"></div>
</div>

<br>
<p><a href="index.php">Return to admin interface</a></p>

<?php
   script_tag('/bower_components/jquery/jquery.min.js');
   script_tag('/bower_components/i18next/i18next.min.js');
?>
<script>
   i18n.init(<?= json_encode([
      'lng' => $config->defaultLanguage,
      'fallbackLng' => [$config->defaultLanguage],
      'getAsync' => true,
      'resGetPath' => static_asset('/i18n/__lng__/__ns__.json'),
      'fallbackNS' => 'translation',
      'ns' => [
         'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation', 'country' . $config->teacherInterface->countryCode] : ['translation', 'country' . $config->teacherInterface->countryCode],
         'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
      ],
      'useDataAttrOptions' => true
   ]) ?>, function() {
      $("title").i18n();
      $("body").i18n();
   });
</script>
<script src="recoverAdmin.js"></script>
</body>
</html>

<?php
require_once("commonAdmin.php");
require_once("./config.php");
require_once("./recoverLib.php");

header('Content-Type: application/json');

if (!isset($_SESSION["userID"])) {
   echo json_encode(['success' => false, 'error' => 'Session expired']);
   exit;
}

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo json_encode(['success' => false, 'error' => 'Admin access required']);
   exit;
}

function exitWithJson($data) {
   die(json_encode($data));
}

function exitWithJsonError($error) {
   die(json_encode(['success' => false, 'error' => $error]));
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

function handleScanDynamoDB() {
   $filters = [];
   if(isset($_POST['sessionID'])) {
      $filters['sessionID'] = $_POST['sessionID'];
   }
   $stats = scanDynamoDBBackups($filters);
   exitWithJson(['success' => true, 'stats' => $stats, 'message' => 'DynamoDB scan completed']);
}

function handleScanErrorLogAnswers() {
   $dateRange = null;
   if((isset($_POST['startDate']) && $_POST['startDate']) || (isset($_POST['endDate']) && $_POST['endDate'])) {
      $dateRange = [];
      if(isset($_POST['startDate']) && $_POST['startDate']) $dateRange['start'] = $_POST['startDate'];
      if(isset($_POST['endDate']) && $_POST['endDate']) $dateRange['end'] = $_POST['endDate'];
   }
   $stats = scanErrorLogAnswers($dateRange);
   exitWithJson(['success' => true, 'stats' => $stats, 'message' => 'Error log answers scan completed']);
}

function handleScanErrorLogScores() {
   $dateRange = null;
   if((isset($_POST['startDate']) && $_POST['startDate']) || (isset($_POST['endDate']) && $_POST['endDate'])) {
      $dateRange = [];
      if(isset($_POST['startDate']) && $_POST['startDate']) $dateRange['start'] = $_POST['startDate'];
      if(isset($_POST['endDate']) && $_POST['endDate']) $dateRange['end'] = $_POST['endDate'];
   }
   $stats = scanErrorLogScores($dateRange);
   exitWithJson(['success' => true, 'stats' => $stats, 'message' => 'Error log scores scan completed']);
}

function handlePreviewMerge() {
   $contestID = isset($_POST['contestID']) && $_POST['contestID'] ? $_POST['contestID'] : null;
   $stats = mergeRecoveryData($contestID, true);
   exitWithJson(['success' => true, 'stats' => $stats, 'message' => 'Preview completed']);
}

function handleExecuteMerge() {
   $contestID = isset($_POST['contestID']) && $_POST['contestID'] ? $_POST['contestID'] : null;
   $stats = mergeRecoveryData($contestID, false);
   exitWithJson(['success' => true, 'stats' => $stats, 'message' => 'Merge executed: ' . $stats['improved'] . ' teams improved, ' . $stats['archived'] . ' answers archived']);
}

function handleGetRecoveryStats() {
   global $db;
   $stmt = $db->prepare("SELECT source, COUNT(*) as count, COUNT(DISTINCT teamID) as teams FROM team_question_other WHERE source != 'archived' GROUP BY source");
   $stmt->execute();
   $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
   $stmt = $db->prepare("SELECT COUNT(DISTINCT teamID) as teams FROM team_question_other WHERE source != 'archived'");
   $stmt->execute();
   $totalTeams = $stmt->fetchColumn();
   exitWithJson(['success' => true, 'sources' => $sources, 'totalTeams' => $totalTeams]);
}

function handleCompareScores() {
   $contestID = isset($_POST['contestID']) && $_POST['contestID'] ? $_POST['contestID'] : null;
   $results = compareRecoveredScores($contestID);
   exitWithJson(['success' => true, 'results' => $results, 'count' => count($results)]);
}

function handleApplyScores() {
   global $db;
   if(!isset($_POST['teamIDs']) || !is_array($_POST['teamIDs'])) {
      exitWithJsonError('teamIDs required');
   }
   $teamIDs = $_POST['teamIDs'];
   $stats = applyRecoveredScores($teamIDs);
   exitWithJson(['success' => true, 'stats' => $stats, 'message' => 'Scores applied: ' . $stats['applied'] . ' teams updated']);
}

if ($action == 'scanDynamoDB') {
   handleScanDynamoDB();
}

if ($action == 'scanErrorLogAnswers') {
   handleScanErrorLogAnswers();
}

if ($action == 'scanErrorLogScores') {
   handleScanErrorLogScores();
}

if ($action == 'previewMerge') {
   handlePreviewMerge();
}

if ($action == 'executeMerge') {
   handleExecuteMerge();
}

if ($action == 'getRecoveryStats') {
   handleGetRecoveryStats();
}

if ($action == 'compareScores') {
   handleCompareScores();
}

if ($action == 'applyScores') {
   handleApplyScores();
}

exitWithJsonError('Unknown action: ' . $action);
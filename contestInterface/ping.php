<?php
$noSessions = true;
require_once("../shared/connect.php");
include_once("common_contest.php");

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

function handlePing() {
   global $config, $db;
   $teamID = $_POST["teamID"];
   $teamPassword = $_POST["teamPassword"];

   if(!isset($db)) {
      // Need to connect to mysql
      $db = connect_pdo($config);
   }

   if($config->contestInterface->checkBrowserID) {
      $stmt = $db->prepare("SELECT browserID FROM team WHERE ID = :teamID");
      $stmt->execute(['teamID' => $teamID]);
      $curBrowserID = $stmt->fetchColumn();
      if($curBrowserID !== null && $curBrowserID != $_POST['browserID']) {
         error_log('teamID '.$teamID.' sent ping with browserID '.$_POST['browserID'].' instead of '.$curBrowserID);
         exitWithJsonFailure("Requête invalide (browserID)", ['browserIDChanged' => true]);
      }
   }

   // Update lastActivityTime and browserID
   $query  = "UPDATE team SET lastPingTime = UTC_TIMESTAMP()";
   if($config->contestInterface->checkBrowserID) {
      $query .= ", browserID = IFNULL(browserId, :browserID)";
   }
   $query .= " WHERE ID = :id AND password = :password;";
   $stmt = $db->prepare($query);
   $stmt->execute(['id' => $teamID, 'password' => $teamPassword, 'browserID' => $_POST['browserID']]);

   addBackendHint("ClientIP.ping:pass");
   addBackendHint(sprintf("Team(%s):ping", escapeHttpValue($teamID)));
   exitWithJson(array("success" => true));
}

if (!isset($_POST["teamID"]) || !isset($_POST["teamPassword"])) {
   error_log("teamID or teamPassword is not set : ".json_encode($_REQUEST));
   exitWithJsonFailure("Requête invalide", array('error' => 'invalid'));
}
handlePing();

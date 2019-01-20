<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("../commonFramework/modelsManager/csvExport.php");
require_once("../commonFramework/modelsManager/modelsTools.inc.php");
require_once("../schoolsMap/googleMap.inc.php");
require_once("domains.php");
//require_once("../modelsManager/modelsManager-DynamoDB.php");

function checkOfficialEmail($email) {
   global $config;
   if (!$email) {
      return true;
   }
   $start = strpos($email, "@");
   if ($start === FALSE) {
      return "user_invalid_email";
   }
   if ($config->teacherInterface->forceOfficialEmailDomain) {
      $domain = substr($email, $start + 1);
      $allowedDomains = getAllowedDomains();
      foreach ($allowedDomains as $allowedDomain) {
         if ($domain === $allowedDomain) {
            return true;
         }
      }
      return translate("user_invalid_domain");
   } else {
      return true;
   }
}

function sendValidationEmail($emailType, $sEmail, $sSalt) {
   global $config;
   if (!$sEmail) {
      return ['success' => false, 'error' => 'user_invalid_email'];
   }
   $coordinatorFolder = $config->teacherInterface->sCoordinatorFolder;
   $link = $coordinatorFolder."/validateEmail.php?".$emailType."=".urlencode($sEmail)."&check=".urlencode($sSalt);
   $sBody = sprintf($config->validationMailBody, $link);
   $sTitle = $config->validationMailTitle;
   return sendMail($sEmail, $sTitle, $sBody, $config->email->sEmailSender, $config->email->sEmailInsriptionBCC);
}

function sendValidationEmails($record) {
   $code = md5($record["salt"]."5263610");
   $params = array('code' => $code, 'email' => $record["officialEmail"], 'salt' => $record["salt"]);
   return sendValidationEmail("acEmail", $record["officialEmail"], $record["salt"]);
}

function getUser($db) {
   $query = "SELECT * FROM `user` WHERE ID = :userID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("userID" => $_SESSION["userID"]));
   return $stmt->fetchObject();
}

function checkUser($record) {
   $officialOk = checkOfficialEmail($record["officialEmail"]);
   if ($officialOk !== true) {
      return $officialOk;
   }
   if ($record["officialEmail"] === "") {
      $record["officialEmail"] = null;
   }
   if ($record["alternativeEmail"] === "") {
      $record["alternativeEmail"] = null;
   }
   return true;
}

function existingEmail($db, $email, $userID) {
   if (!$email) {
      return false;
   }
   $query = "SELECT `ID` FROM `user` WHERE (`officialEmail` = :email OR `alternativeEmail` = :email) AND `ID` <> :userID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("email" => $email, "userID" => $userID));
   if ($stmt->fetchObject()) {
      return true;
   }
   return false;
}

function groupContestChanged($db, $groupID, $contestID) {
   $query = "SELECT `contestID`, `startTime` FROM `group` WHERE `ID` = :groupID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("groupID" => $groupID));
   $row = $stmt->fetchObject();
   return (($row->startTime != null) && ($row->contestID !== $contestID));
}

function getContest($db, $year, $level) {
   $query = "SELECT `contest`.`ID` FROM `contest` WHERE `contest`.`year` = :year AND `contest`.`level` = :level";
   $stmt = $db->prepare($query);
   $stmt->execute(array("year" => $year, "level" => $level));
   $row = $stmt->fetchObject();
   if (!$row) {
      return false;
   }
   return ($row->ID);
}

function getLevel($grade) {
   $levels = array("6" => 1, "7" => 1, "8" => 2, "9" => 2, "10" => 3, "11" => 4, "12" => 4);
   $level = $levels[$grade];
   return $level;
}

function checkRequestGroup($db, &$request, &$record, $operation, &$roles) {
   // Generated fields
   if ($operation === "insert") {
     $record["code"] = genAccessCode($db);
     $record["password"] = genAccessCode($db);
     $roles[] = "generator";
   }
   unset($record["isPublic"]);
   /*
   if ((!isset($record["year"])) || (!isset($record["grade"]))) {
      error_log("year or level missing when updating group");
      return false;
   }
   */
   //$level = getLevel($record["grade"]);
   /*
   $contestID = getContest($db, $record["year"], $level);
   $record["contestID"] = $contestID;
   unset($record["year"]);
   */
   if (!$_SESSION["isAdmin"]) {
      $record["userID"] = $_SESSION["userID"];
   } else {
      die(json_encode(['success' => false, 'error' => translate("admins_cant_create_groups")]));
   }

   // Filters
   if (!$_SESSION["isAdmin"]) {
      if ($operation == "insert") {
         $request["filters"]["schoolID"] = $record["schoolID"];
      }
      $request["filters"]["userID"] = $_SESSION["userID"];
      $request["filters"]["statusNotHidden"] = true;
      $request["filters"]["checkOfficial"] = true;
   }   
   // This can't be done through a standard filter yet
   if (($operation === "update") && groupContestChanged($db, $record["ID"], $record["contestID"])) {
      $message = trasnalte("groups_cant_change_contest_of_started_group");
      error_log($message);
      echo json_encode(array("success" => false, "message" => $message));      
      return false;
   }
   return true;
}

function getContestantOwner($db, $contestantID) {
   $query = "SELECT `group`.`userID` FROM `contestant` JOIN `team` ON (`team`.`ID` = `contestant`.`teamID`) JOIN `group` ON (`group`.`ID` = `team`.`groupID`) WHERE `contestant`.`ID` = :contestantID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("contestantID" => $contestantID));
   $row = $stmt->fetchObject();
   if (!$row) {
      return null;
   }
   return $row->userID;
}

function hasWriteAccess($db, $targetUserID, $sourceUserID) {
   $query = "SELECT `accessType` FROM `user_user` WHERE `userID` = :sourceUserID AND `targetUserID` = :targetUserID";
   $stmt = $db->prepare($query);
   $stmt->execute(array("targetUserID" => $targetUserID, "sourceUserID" => $sourceUserID));
   $row = $stmt->fetchObject();
   if (!$row) {
      return false;
   }
   return ($row->accessType == "write");
}

function checkRequestContestant($db, &$request, &$record, $operation, &$roles) {
   // Generated fields
   list($record["firstName"], $record["lastName"], $record["saniValid"], $trash) = 
      DataSanitizer::formatUserNames($record["firstName"], $record["lastName"]);
   $roles[] = "generator";

   $ownerID = getContestantOwner($db, $record["ID"]);
   if (hasWriteAccess($db, $_SESSION["userID"], $ownerID)) {
      return true;
   }

   // Filters
   if (!$_SESSION["isAdmin"]) {
      $request["filters"]["ownerUserID"] = $_SESSION["userID"];
   }
   return true;
}

function checkRequestSchoolUser($db, &$request, &$record, $operation, &$roles) {
   if (!$_SESSION["isAdmin"]) {
      $record["userID"] = $_SESSION["userID"];
      $record["confirmed"] = 1;
   }
   // Filters
   if (!$_SESSION["isAdmin"]) {
      $request["filters"]["userID"] = $_SESSION["userID"];
   }
   return true;
}

function checkRequestUser($db, &$request, &$record, $operation, &$roles) {
   // Generated fields
   list($record["firstName"], $record["lastName"], $record["saniValid"], $trash) = 
      DataSanitizer::formatUserNames($record["firstName"], $record["lastName"]);
   if ($operation === "insert") {
      $record["salt"] = generateSalt();
      $record["passwordMd5"] = computePasswordMD5($record["password"], $record["salt"]);
   }
   $roles[] = "generator";
 
   if ($operation === "insert") {
      if (existingEmail($db, $record["officialEmail"], 0)) {
         $message = sprintf(translate("user_email_already_used"), $record["officialEmail"]);
         echo json_encode(array("success" => false, "message" => $message));
         error_log($message);
         return false;
      }
      if (existingEmail($db, $record["alternativeEmail"], 0)) {
         $message = sprintf(translate("user_email_already_used"), $record["alternativeEmail"]);
         echo json_encode(array("success" => false, "message" => $message));
         error_log($message);
         return false;
      }
      $curTimeDB = new DateTime(null, new DateTimeZone("UTC"));
      $record["registrationDate"] = $curTimeDB->format('Y-m-d H:i:s');
   }
   $userOk = checkUser($record);
   if ($userOk !== true) {
      echo json_encode(array("success" => false, "message" => $userOk));
      error_log($userOk);
      return false;
   }

   if ((!$_SESSION["isAdmin"]) && ($operation === "update")) {
      $record["ID"] = $_SESSION["userID"];
      $user = getUser($db);
      if ($record["password"] != "") {
         $oldPasswordMd5 = computePasswordMD5($record["old_password"], $user->salt);
         if ($oldPasswordMd5 !== $user->passwordMd5) { 
            echo json_encode(array("success" => false, "message" => translate("invalid_password")));
            error_log("Invalid password");
            return false;
         }
         $record["passwordMd5"] = computePasswordMD5($record["password"], $user->salt);
      }
      if ($record["alternativeEmail"] !== $user->alternativeEmail) {
         $record["alternativeEmailValidated"] = "0";
      }
   }

   // Filters
   if ((!$_SESSION["isAdmin"]) && ($operation === "update")) {
      // Could/should we use a filter for this ?
      if (($record["officialEmail"] !== $user->officialEmail) && $user->officialEmailValidated) {
         error_log("a validated official email can't be changed");
         return false;
      }
   }   
   return true;
}

function checkRequestSchool($db, &$request, &$record, $operation, &$roles) {
   // Generated fields
   list($record["name"], $record["city"], $record["country"], $record["saniValid"], $record["saniMsg"]) = 
      DataSanitizer::formatSchool($record["name"], $record["city"], $record["country"]);
   $roles[] = "generator";
 
   list($lat, $lng, $msg) = getCoordinatesSchool($record);
   $record["saniMsg"] .= $msg;
   $record['coords'] = $lng.",".$lat.",0";
   if ((!$_SESSION["isAdmin"]) || ($operation === "insert")) {
      $record["userID"] = $_SESSION["userID"];
   }

   // Filters
   if (!$_SESSION["isAdmin"]) {
      $request["filters"]["accessUserID"] = array('values' => array('userID' => $_SESSION["userID"]));
      $request["filters"]["userID"] = $_SESSION["userID"];
   }
   return true;
}

function checkRequestColleagues($db, &$request, &$record, $operation, &$roles) {
   // Filters
   if (!$_SESSION["isAdmin"]) {
      $request["filters"]["userID"] = $_SESSION["userID"];
   } else {
      return false; // Can't update colleagues as an admin.
   }
   return true;
}

function createRequest($modelName) {
   return array(
      "modelName" => $modelName,
      "model" => getViewModel($modelName),
      "filters" => array(),
      "fields" => array(),
      "records" => array()
   );
}

function checkRequest($db, &$request, &$record, $operation, &$roles) {
   $requestCheckFunctions = array(
      "group" => "checkRequestGroup",
      "contestant" => "checkRequestContestant",
      "user" => "checkRequestUser",
      "school" => "checkRequestSchool",
      "colleagues" => "checkRequestColleagues",
      "school_user" => "checkRequestSchoolUser"
   );
   $modelName = $request["modelName"];
   if (isset($requestCheckFunctions[$modelName])) {
      if (!$requestCheckFunctions[$modelName]($db, $request, $record, $operation, $roles)) {
         return false;
      }
   }
   return true;
}

function fillRequestWithRecords(&$request, $record) {
   $viewModel = $request["model"];
   foreach ($record as $fieldName => $fieldValue) {
      if (isset($viewModel["fields"][$fieldName])) {
         $request["fields"][] = $fieldName;
         $request["records"][0]["values"][$fieldName] = $fieldValue;
      }
   }
}

function updateRecord($db, $modelName, $record, $roles) {
   $request = createRequest($modelName);
   $request["records"][] = array("ID" => $record["ID"], "values" => array());
   if (!checkRequest($db, $request, $record, "update", $roles)) {
      return;
   }
   fillRequestWithRecords($request, $record);
   updateRows($db, $request, $roles);
   //updateRowsDynamoDB($request, $roles);
   echo json_encode(array("success" => true, "recordID" => $record["ID"]));
}

function insertRecord($db, $modelName, $record, $roles) {
   global $config;
   $request = createRequest($modelName);
   $request["records"][] = array("values" => array());
   if (!checkRequest($db, $request, $record, "insert", $roles)) {
      return;
   }
   fillRequestWithRecords($request, $record);
   $insertedIDs = insertRows($db, $request, $roles);
   //insertRowsDynamoDB($request, $roles, $insertedIDs);
   $insertID = $insertedIDs[0];

   if ($modelName == "school") {
      $querySchoolUser = "INSERT INTO `school_user` (`ID`, `schoolID`, `userID`, `confirmed`) VALUES (:ID, :insertID, :userID, 1)";
      $stmt = $db->prepare($querySchoolUser);
      $stmt->execute(array("ID" => getRandomID(), "insertID" => $insertID, "userID" => $record["userID"]));
   }
   if ($modelName === "user" && $config->email->bSendMailForReal) {
      $res = sendValidationEmails($record);
      echo json_encode($res);
   } else {
      echo json_encode(array("success" => true, "recordID" => $insertID));
   }
}

function deleteRecord($db, $modelName, $record, $roles) {
   if (!in_array("admin", $roles)) {
      if ($modelName === "school") {
         /* Check that there are no groups attached. We don't care anymore, and keep the groups.
         $query = "SELECT count(`group`.`ID`) as `nbGroups` FROM `group` WHERE `group`.`schoolID` = :schoolID AND `group`.`userID` = :userID";
         $stmt = $db->prepare($query);
         $stmt->execute(array("schoolID" => $record["ID"], "userID" => $_SESSION["userID"]));
         $row = $stmt->fetchObject();
         if ((!$row) || ($row->nbGroups > 0)) {
            return;
         }
         */
         $query = "SELECT `school_user`.`ID` FROM `school_user` WHERE `schoolID` = :schoolID AND `userID` = :userID";
         $stmt = $db->prepare($query);
         $stmt->execute(array("schoolID" => $record["ID"], "userID" => $_SESSION["userID"]));
         $row = $stmt->fetchObject();
         if (!$row) {
            return;
         }
         deleteRow($db, "school_user", array("ID" => $row->ID));
         return;
      } else if ($modelName === "group") {
         $query = "SELECT `group`.`userID`, count(`team`.`ID`) as `nbTeams` ".
            "FROM `group` LEFT JOIN `team` ON (`team`.`groupID` = `group`.`ID`) ".
            "WHERE `group`.`ID` = :ID GROUP BY `group`.`ID`";
         $stmt = $db->prepare($query);
         $stmt->execute(array("ID" => $record["ID"]));
         $row = $stmt->fetchObject();
         if ((!$row) || ($row->userID !== $_SESSION["userID"]) || ($row->nbTeams > 0)) {
            return;
         }
      } else if ($modelName === "school_user") {
      } else {
         return;
      }
   }
   if ($modelName === "team_view") { // TODO : should not be necessary
      $modelName = "team";
   }
   deleteRow($db, $modelName, $record);
   //deleteRowDynamoDB($modelName, $record);
}

function selectRecords($db, $modelName, $recordID, $roles, $extraFilters = array()) {
   $model = getViewModel($modelName);
   $request = array(
      "modelName" => $modelName,
      "model" => $model,
      "filters" => array(),
      "fields" => array()
   );
   foreach($model["fields"] as $fieldName => $field) {
      $request["fields"][] = $fieldName;
   }
   if ($recordID != "0") {
      $request["filters"]["recordID"] = $recordID;
   }
   if (!$_SESSION["isAdmin"]) {
      if ($modelName === "school") {
         $request["filters"]["accessUserID"] = $_SESSION["userID"];
      } else if ($modelName === "group") {
         if (isset($extraFilters["schoolID"])) {
            $request["filters"]["schoolID"] = $extraFilters["schoolID"];
         }
         if (isset($extraFilters["groupID"])) {
            $request["filters"]["recordID"] = $extraFilters["groupID"];
         }
         if (isset($extraFilters["contestID"])) {
            $request["filters"]["contestID"] = $extraFilters["contestID"];
         }
         $request["filters"]["checkAccessUserID"] = $_SESSION["userID"];
         $request["filters"]["checkSchoolUserID"] = $_SESSION["userID"];
      } else if ($modelName === "contest") {
         $request["filters"]["statusNotHidden"] = true;
      } else if ($modelName === "colleagues") {
         $request["filters"]["userID"] = $_SESSION["userID"];
      } else if ($modelName === "school_user") {
         $request["filters"]["userID"] = $_SESSION["userID"];
      } else if ($modelName === "school_year") {
         $request["filters"]["userID"] = $_SESSION["userID"];
      } else if ($modelName === "school_search") {
      } else if ($modelName === "team_view") {
         if (isset($extraFilters["groupField"])) {
            $request["filters"]["groupField"] = $extraFilters["groupField"];
         }
      } else if ($modelName === "contestant") {
         if (isset($extraFilters["contestID"])) {
            $request["filters"]["contestID"] = $extraFilters["contestID"];
         }
         if (isset($extraFilters["schoolID"])) {
            $request["filters"]["schoolID"] = $extraFilters["schoolID"];
         }
         if (isset($extraFilters["teamID"])) {
            $request["filters"]["teamID"] = $extraFilters["teamID"];
         }
         if (isset($extraFilters["groupID"])) {
            $request["filters"]["groupID"] = $extraFilters["groupID"];
         }
         if (isset($extraFilters["official"])) {
            $request["filters"]["official"] = true;
         }
         $request["filters"]["userID"] = $_SESSION["userID"];
      } else if ($modelName === "user") {
         $request["filters"]["recordID"] = $_SESSION["userID"];
      } else if ($modelName === "award_threshold") {
         if (isset($extraFilters["contestID"])) {
            $request["filters"]["contestID"] = $extraFilters["contestID"];
         }
      } else if ($modelName === "algorea_registration") {
         $request["filters"]["userID"] = $_SESSION["userID"];
         $request["filters"]["hasScore"] = 1;
      } else {
         echo json_encode(array("success" => false, "message" => 'unknown model name: '.$modelName));
         return;
      }
   }
   $result = selectRows($db, $request);
   echo json_encode(array("success" => true, "items" => $result["items"]));
}

function selectRecordsForJQGrid($db, $modelName, $params, $roles) {
   $format = "xml";
   if (isset($params["format"])) {
      $format = $params["format"];
   }
   if ($modelName == 'contestant' && $format == 'csv') {
      $modelName = 'contestantCSV';
   }
   
   $model = getViewModel($modelName);
   $request = array(
      "modelName" => $modelName,
      "model" => $model,
      "filters" => array()
   );
   foreach($model["fields"] as $fieldName => $field) {
      $request["fields"][] = $fieldName;
   }
   if (!$_SESSION["isAdmin"]) {
      if ($modelName == "school") {
         $params["accessUserID"] = $_SESSION["userID"];
      } else if ($modelName === "group") {
         $params["checkAccessUserID"] = $_SESSION["userID"];
         $params["checkSchoolUserID"] = $_SESSION["userID"];
      } else {
         $params["userID"] = $_SESSION["userID"];
      }
   }

   $filters = array();

   foreach ($params as $name => $value) {
      if ((isset($model["filters"][$name]) || isset($model["fields"][$name])) && ($value !== "_NOF_")) {
         if (isset($model["fields"][$name])) {
            if (getFieldType($model, $name) == "string") {
               $value = "%".$value."%";
            }
         }
         $filters[$name] = $value;
      }
   }
   $request["filters"] = $filters;

   if ($modelName == 'group') {
      $request["filters"]["checkNoChild"] = true;
   }

   if ($modelName == 'award1') {
      $request['filters']['awarded'] = true;
      $request['filters']['showable'] = true;
      $request['orders'] = $model['orders'];
   }

   if (isset($params["sidx"]) && ($params["sidx"] != "")) {
      if ($modelName == 'team_view' && $params['sidx'] == 'groupField') {
         $params['sidx'] = 'groupName';
      }
      $order = array("field" => $params["sidx"], "dir" => $params["sord"]);
      $request["orders"] = array($order);
   }
   $request["page"] = $params["page"];
   $request["rowsPerPage"] = $params["rows"];
   if ($format == 'csv' && $modelName !== 'contestantCSV') {
      explicitCSVRequest($request);
   }
   $result = selectRows($db, $request);

   // TODO: document
   if (function_exists('customJqGridDataFilter')) {
      customJqGridDataFilter($result, $request);
   }

   $limits = $result["limits"];
   if ($format === "xml") {
      displayRowsAsXml($result["items"], $model, $limits["page"], $limits["nbPages"], $result["nbTotalItems"]);
   } else {
      displayRowsAsCsv($modelName, $result["items"], $request['model']);
   }
}

// replaces a key by another, keeping same order.
// from http://stackoverflow.com/a/10182739/2560906
function replace_key_function($array, $key1, $key2)
{
    $keys = array_keys($array);
    $index = array_search($key1, $keys);

    if ($index !== false) {
        $keys[$index] = $key2;
        $array = array_combine($keys, $array);
    }

    return $array;
}

// request for csv export, replacing IDs with names mostly
function explicitCSVRequest(&$request) {
   // replacing schoolID with schoolName
   if (isset($request['model']['fields']['schoolID'])) {
      if(($key = array_search('schoolID', $request['fields'])) !== false) {
          $request['fields'][$key] = 'schoolName';
      }
      $request['model']['fields'] = replace_key_function($request['model']['fields'], 'schoolID', 'schoolName');
      $request['model']['fields']['schoolName'] = array('sql' => '`school`.`name`', 'tableName' => 'school');
      if ($request['model']['mainTable'] !== 'school' && !isset($request['model']['joins']['school'])) {
         $request['model']['joins']['school'] = array(
            'srcTable' => 'group',
            'dstTable' => 'school',
            'dstField' => 'ID',
            'srcField' => 'schoolID'
         );
      }
   }
   // explicit gender
   if (isset($request['model']['fields']['genre'])) {
      $request['model']['fields']['genre'] = array('sql' => "IF(`contestant`.`genre` = 1, 'F', 'M')", 'tableName' => 'contestant');
   }
   // replacing contestID with contestName
   if (isset($request['model']['fields']['contestID']) && $request['model']['mainTable'] != 'team') {
      if(($key = array_search('contestID', $request['fields'])) !== false) {
          $request['fields'][$key] = 'contestName';
      }
      $request['model']['fields'] = replace_key_function($request['model']['fields'], 'contestID', 'contestName');
      $request['model']['fields']['contestName'] = array('sql' => '`contest`.`name`', 'tableName' => 'contest');
      if ($request['model']['mainTable'] !== 'contest' && !isset($request['model']['joins']['contest'])) {
         $request['model']['joins']['contest'] = array(
            'srcTable' => $request['model']['mainTable'],
            'dstTable' => 'contest',
            'dstField' => 'ID',
            'srcField' => 'contestID'
         );
      }
      if (isset($request['orders'])) {
         foreach ($request['orders'] as $i => $order) {
            if ($order['field'] == 'contestID') {
               unset($request['orders'][$i]);
            }
         }
      }
   }
   // removing userID
   if (isset($request['model']['fields']['userID']) && $request['model']['mainTable'] !== 'school') {
      unset($request['model']['fields']['userID']);
      unset($request['fields']['userID']);
      $toDelete = -1;
      foreach ($request['fields'] as $ID => $fieldName) {
         if ($fieldName == "userID") {
            $toDelete = $ID;
            break;
         }
      }
      if ($toDelete >= 0) {
         array_splice($request['fields'], $ID, 1);
      }
   }
   if (!$_SESSION["isAdmin"] && isset($request['model']['fields']['groupField'])) {
      $request['model']['fields']['groupField'] = array("tableName" => "group", "fieldName" => "name");
   }
}

$roles = getRoles();

if (!isset($_REQUEST["tableName"])) {
   error_log("Requête invalide (tableName manquant) : ".json_encode($_REQUEST));
   unset($db);
   exit;
}
$modelName = $_REQUEST["tableName"];
if (!isset($_SESSION["userID"]) && !(($modelName === "user") && ($_REQUEST["oper"] === "insert"))) {
   error_log("Invalid request for non-connected user. session : ".json_encode($_SESSION)." request : ".json_encode($_REQUEST));
   http_response_code(500);
   header("Status: 500 Server Error Invalid Request");
   echo translate("session_expired");
   unset($db);
   exit;
}

if ($config->maintenanceUntil) {
   echo json_encode(['success' => false, "message" => sprintf(translate("site_under_maintainance"), $config->maintenanceUntil)]);
   exit();
}

if (isset($_REQUEST["oper"])) {
   $oper = $_REQUEST['oper'];
   if (isset($_REQUEST["record"])) {
      $record = $_REQUEST["record"];
   } else {
      $record = $_REQUEST;
   }
   if (isset($record["id"])) {
      $record["ID"] = $record["id"];
   }

   if (($oper === "edit") || ($oper === "update")) {
      updateRecord($db, $modelName, $record, $roles);
   } else if ($oper === 'insert') {
      insertRecord($db, $modelName, $record, $roles);
   } else if ($oper === 'del') {
      deleteRecord($db, $modelName, $record, $roles);
   } else if ($oper === "select") {
      selectRecords($db, $modelName, "0", $roles, $_REQUEST);
   } else if ($oper === "selectOne") {
      if (($_SESSION["isAdmin"])|| ($modelName == "team_view")) {
         selectRecords($db, $modelName, $_REQUEST["recordID"], $roles);
      }
   }
} else {
   selectRecordsForJQGrid($db, $modelName, $_REQUEST, $roles);
}
unset($db);


?>

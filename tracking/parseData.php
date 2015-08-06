<?php

require_once("connect.php");

/*
Cleanup : 

DELETE FROM answer;
DELETE from checkbox;
DELETE from clickitem;
DELETE FROM selectquestion;
delete from textinput;
update rawdata set extracted= 0;
*/

function prepareQuery($db, $tableName) {
   $fields = array("rawdataID", "serverTime", "clientTime", "teamID", "questionKey");
   if ($tableName === "tracking_selectQuestion") {
      $fields[] = "clicked";
   } else if ($tableName === "tracking_answer") {
      $fields[] = "answer";
   } else if ($tableName === "tracking_checkbox") {
      $fields[] = "choice";
      $fields[] = "checked";
   } else if ($tableName === "tracking_textinput") {
      $fields[] = "inputId";
      $fields[] = "value";
   } else if ($tableName === "tracking_clickitem") {
      $fields[] = "item";
   } 
   $marks = array();
   foreach ($fields as $fieldName) {
      if ($fieldName === "clientTime") {
         $marks[] = "FROM_UNIXTIME(?)";
      } else {
         $marks[] = "?";
      }
   }
   $query = "INSERT INTO `".strtolower($tableName)."` (`".implode("` , `", $fields)."`) VALUES (".implode(", ", $marks).")";
   $stmt = $db->prepare($query);
   return array("stmt" => $stmt, "fields" => $fields);
}

$db = connect();

$queries = array(
   "selectQuestion" => prepareQuery($db, "tracking_selectQuestion"),
   "answer" => prepareQuery($db, "tracking_answer"),
   "checkbox" => prepareQuery($db, "tracking_checkbox"),
   "textinput" => prepareQuery($db, "tracking_textinput"),
   "clickitem" => prepareQuery($db, "tracking_clickitem"),
   "nonSavedAnswer" => prepareQuery($db, "tracking_nonSavedAnswer")
);

$query = "SELECT * FROM `tracking_rawdata` where extracted = 0 LIMIT 0, 200";
$stmt = $db->prepare($query);
$stmt->execute(array());

$nbRecords = 0;
$nbValid = 0;
$nbErrors = 0;
$nbQueries = 0;

$queryUpdate = "UPDATE `tracking_rawdata` set `extracted` = 1 WHERE ID = ?";
$stmtUpdate = $db->prepare($queryUpdate);
while ($row = $stmt->fetchObject()) {
   $data = json_decode($row->data);
   if (is_array($data)) {
      foreach ($data as $record) {
         if ((isset($record->clientTime)) && isset($record->dataType)) {
            $record->rawdataID = $row->ID;
            $record->serverTime = $row->serverTime;
            $query = $queries[$record->dataType];
            $values = array();
            foreach ($query["fields"] as $fieldName) {
               $value = $record->$fieldName;
               $values[] = $value;
            }
            $query["stmt"]->execute($values);
            $nbQueries++;
         } else {
            $nbErrors++;
         }
      }
      $nbValid++;
   }
   $stmtUpdate->execute(array($row->ID));
   $nbRecords++;
}

$query = "SELECT count(*) as `nbRemaining` FROM `tracking_rawdata` where extracted = 0";
$stmt = $db->prepare($query);
$stmt->execute(array());
$row = $stmt->fetchObject();

echo json_encode(array("success" => true, "result" => $nbValid."/".$nbRecords." parsed, ".$nbQueries." queries, ".$nbErrors." errors, ".$row->nbRemaining." remaining."));

unset($db);

?>
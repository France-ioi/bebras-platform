<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

function generateAlgoreaCodes($db, $contestID) {
   // retrieving awarded contestants through "award1" model
   $modelName = 'award1';
   $model = getViewModel($modelName);
   $request = array(
      "modelName" => $modelName,
      "model" => $model,
      "filters" => array()
   );
   foreach($model["fields"] as $fieldName => $field) {
      $request["fields"][] = $fieldName;
   }
   $request["filters"] = array('awarded' => true, 'contestID' => $contestID);
   if (!$_SESSION["isAdmin"]) {
      $request["filters"]["userID"] = $_SESSION["userID"];
   }

   $result = selectRows($db, $request);
   $awarded = $result['items'];

   if (!count($awarded)) {
      return;
   }
   // we hope that there will be no collision in a serie of generated codes
   $query = "INSERT ignore INTO `contestant` (`ID`, `algoreaCode`) values ";
   $first = true;
   foreach($awarded as $contestant) {
      if (!$first) {
         $query = $query.', ';
      }
      $first = false;
      $query = $query.'('.$contestant->ID.', \''.genAccessCode($db).'\')';
   }
   $query = $query.' on duplicate key update `algoreaCode` = values(`algoreaCode`)';
   $db->exec($query);
}

if ((!isset($_SESSION["isAdmin"])) || (!$_SESSION["isAdmin"])) {
   echo json_encode((object)array("success" => false, "message" => "Seul un admin peut calculer les classements"));
   exit;
}

$contestID = $_REQUEST["contestID"];

generateAlgoreaCodes($db, $contestID);
unset($db);

echo json_encode(array("success" => true));

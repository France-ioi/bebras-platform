<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

function computeRanks($db, $contestID)
{  
   $query = "
      UPDATE `contestant` as `c1`, 
      (
         SELECT 
            `contestant2`.`ID`,
            @curRank := IF(@prevVal=`contestant2`.`score`, @curRank, @studentNumber) AS rank,
            @studentNumber := @studentNumber + 1 as studentNumber,
            @prevVal:=score
         FROM 
         (
            SELECT 
               `contestant`.`ID`, 
               `contestant`.`firstName`,
               `contestant`.`lastName`, 
               `team`.`score`
            FROM `contestant` 
            LEFT JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`) 
            LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
            WHERE 
               `team`.`participationType` = 'Official' AND 
               `group`.`contestID` = :contestID
            ORDER BY
            `team`.`score` DESC
         ) `contestant2`, 
         (
            SELECT 
               @curRank :=0, 
               @prevVal:=null, 
               @studentNumber:=1
         ) r
      ) AS `c2` 
      SET `c1`.`rank` = `c2`.`rank` 
      WHERE `c1`.`ID` = `c2`.`ID`
   ";

   $stmt = $db->prepare($query);
   $stmt->execute(array(':contestID' => $contestID)); 
}

function computeRanksSchool($db, $contestID)
{
   $query = "
    UPDATE `contestant` as `c1`,
    (
       SELECT 
           `contestant2`.`ID`,
            @curRank := IF(@prevSchool=`contestant2`.`schoolID`, IF(@prevScore=`contestant2`.`score`, @curRank, @studentNumber + 1), 1) AS schoolRank, 
            @studentNumber := IF(@prevSchool=`contestant2`.`schoolID`, @studentNumber + 1, 1) as studentNumber, 
            @prevScore:=score,
            @prevSchool:=`contestant2`.`schoolID`
    FROM 
    (
       SELECT 
          `contestant`.`ID`,
          `contestant`.`firstName`,
          `contestant`.`lastName`,
          `team`.`score`,
          `group`.`schoolID`
      FROM `contestant`
            LEFT JOIN `team` ON (`contestant`.`teamID` = `team`.`ID`)
            LEFT JOIN `group` ON (`team`.`groupID` = `group`.`ID`)
      WHERE 
          `team`.`participationType` = 'Official' AND 
          `group`.`contestID` = :contestID
      ORDER BY `group`.`schoolID`, `team`.`score` DESC
   ) `contestant2`,
   (
       SELECT 
          @curRank :=0, 
          @prevScore:=null, 
          @studentNumber:=0, 
          @prevSchool:=null
         ) r
    ) as `c2`
    SET `c1`.`schoolRank` = `c2`.`schoolRank` 
    WHERE `c1`.`ID` = `c2`.`ID`
";   $stmt = $db->prepare($query);
   $stmt->execute(array(':contestID' => $contestID)); 
}

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
   $request["filters"] = array('awarded' => null, 'contestID' => $contestID);
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

computeRanks($db, $contestID);
computeRanksSchool($db, $contestID);
generateAlgoreaCodes($db, $contestID);
unset($db);

echo json_encode(array("success" => true));

?>

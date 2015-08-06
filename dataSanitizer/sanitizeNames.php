<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");

/*
These functions should usually only be used once to correct a database
where the data was not sanitized before the insertion.
*/

function checkAndCorrectContestants($doRequests = false)
{
   global $db;
   $query = "
      SELECT 
         `contestant`.ID, 
         `contestant`.lastName, 
         `contestant`.firstName 
      FROM `contestant`
      WHERE 
         `contestant`.`orig_firstName` IS NULL
      LIMIT 0, 10000
      ";
   $stmt = $db->prepare($query);
   $stmt->execute();
   $all = array(); 
   while($row = $stmt->fetchObject())
   {      
      $all[] = $row;
   }
   $updateQuery = "UPDATE contestant SET orig_firstName = firstName, orig_lastName = lastName, firstName = :firstName, lastName = :lastName, saniValid = :saniValid WHERE ID = :ID";
   $updateStmt = $db->prepare($updateQuery);
   foreach ($all as $row)
   {
      list($first, $last, $saniValid, $msg) = 
         DataSanitizer::formatUserNames($row->firstName, $row->lastName);
      if ($doRequests)          
      {
         $updateStmt->execute(array(':ID' => $row->ID, ':firstName' => $first, ':lastName' => $last, ':saniValid' => $saniValid));
      } else {
         if ($saniValid == 1)
         {
            if ($first != $row->firstName || $last != $row->lastName)
            {
               echo "DIFF : {$row->firstName} {$row->lastName} => $first $last\n";
            }
            else
            {
               echo "GOOD : {$row->firstName} {$row->lastName}\n";
            }
         }
         else
         {
            echo "ERROR : (id={$row->ID}) {$row->firstName} {$row->lastName} ($first $last): => $msg\n";
         }
      }
   }
   echo "Updated contestants : ".count($all)."<br/>";
}
/*
Can take up to 2 minutes, without queries, 4 with.
Should be done in command line to inspect data : 
$ time $(php BatchTasks.php > LOG)

Compter le nom de chaque type :
$ grep -c "" LOG # Total
$ grep -c "GOOD" LOG # Already good
$ grep -c "DIFF" LOG # The script has corrected something (but easy case)
$ grep -c "ERROR" LOG # Impossible to correct it / bad format

Regarder : 
$ grep "ERROR" LOG |less

*/



function checkAndCorrectUsers($doRequests = false)
{
   global $db;
   $query = "
      SELECT 
         `user`.ID, 
         `user`.lastName, 
         `user`.firstName 
      FROM `user`
      WHERE
         `user`.`orig_lastName` IS NULL
      LIMIT 0, 10000
      ";

   $stmt = $db->prepare($query);
   $stmt->execute();
   $all = array(); 
   while($row = $stmt->fetchObject())
   {      
      $all[] = $row;
   }
   $updateQuery = "UPDATE user SET orig_firstName = firstName, orig_lastName = lastName, firstName = :firstName, lastName = :lastName, saniValid = :saniValid WHERE ID = :ID";
   $updateStmt = $db->prepare($updateQuery);
   foreach ($all as $row)
   {
      list($first, $last, $saniValid, $msg) = 
         DataSanitizer::formatUserNames($row->firstName, $row->lastName);
      if ($doRequests)          
      {
         $updateStmt->execute(array(':ID' => $row->ID, ':firstName' => $first, ':lastName' => $last, ':saniValid' => $saniValid));
      } else {
         if ($saniValid == 1)
         {
            if ($first != $row->firstName || $last != $row->lastName)
            {
               echo "DIFF : {$row->firstName} {$row->lastName} => $first $last\n";
               $row->firstName = $first;
               $row->lastName = $last;
            }
            else
            {
               echo "GOOD : {$row->firstName} {$row->lastName}\n";
            }
         }
         else
         {
            echo "ERROR : (id={$row->ID}) {$row->firstName} {$row->lastName} => $msg\n";
         }
      }
   }
   echo "Updated users : ".count($all)."<br/>";
}

function checkAndCorrectSchoolField($field, $execRequests = false)
{
   global $db;
   if (!in_array($field, array("city", "country", "name")))
   {
      echo "INVALID FIELD $field"; 
      return;
   }
   /*
   if ($field == "city")
   {
      $query = "UPDATE school SET saniMsg = ''";
      $db->prepare($query)->execute();
   }
   */


   $query = "
      SELECT 
         `school`.ID, 
         `school`.$field
      FROM `school`
      WHERE
          `school`.`orig_".$field."` IS NULL      
      ";
   $stmt = $db->prepare($query);
   $stmt->execute();
   $all = array(); 
   while($row = $stmt->fetchObject())
   {      
      $all[] = $row;
   }
   //$all[] = (object)array('ID'=>-1, 'city' => 'Aude', "$field"=>"Bidule de Truc Machin de la marne d'arras de l'eau");
   
   $updateQuery = "UPDATE school SET `orig_".$field."` = `".$field."`, `".$field."` = :value, `saniValid` = :saniValid, saniMsg = CONCAT(saniMsg, :saniMsg) WHERE ID = :ID";
   $updateStmt = $db->prepare($updateQuery);
   foreach ($all as $row)
   {
      $newVal = $row->$field;
      $saniValid = 1;
      $msg = "";
      try 
      {
         $newVal = DataSanitizer::formatNameComplex($row->$field);
         if ($field == 'name')
            $newVal = DataSanitizer::postFormatSchoolName($newVal);
         if ($newVal != $row->$field)
         {
            echo "DIFF : {$row->$field} => $newVal<br/>\n";
         }
         else
         {
            echo "GOOD : {$row->$field}<br/>\n";
         }            
      }
      catch (Exception $e) 
      {
         $saniValid = 0;
         $msg = $e->getMessage().";";
         echo "ERROR : (id={$row->ID}) {$row->$field} => ".$e->getMessage()."<br/>\n";
      }
      if ($execRequests)
      {
         $updateStmt->execute(array(':ID' => $row->ID, ':value' => $newVal, ':saniValid' => $saniValid, ":saniMsg" => $msg));
      }
   }
   echo "Updated schools (".$field.") : ".count($all)."<br/>";
}

$doRequest = true;
//checkAndCorrectContestants($doRequests);

// Only a few users with an error in the current base
checkAndCorrectUsers($doRequests);

// Only a few invalids
checkAndCorrectSchoolField("city", $doRequest);

// Only a few invalids
checkAndCorrectSchoolField("country", $doRequest);

// Arround 100 invalids but a lot are easy to fix
checkAndCorrectSchoolField("name", $doRequest);

?>
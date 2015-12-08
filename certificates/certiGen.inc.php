<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

// Directories definitions
define('CERTIGEN_MAINDIR', 'pdf/');
define('CERTIGEN_EXPORTDIR', 'export/');
define('CERTIGEN_SECRET', 'Ba77H55V3kt7GuL');
define('CERTIGEN_CONTESTS', '56');
// 2013: define('CERTIGEN_CONTESTS', '27, 30, 28, 29');
// 2012: define('CERTIGEN_CONTESTS', '6, 7, 8, 9')

// REMOVE
//@include('../connect.php');

// Sanitize a string to be used safely in a file name
function sanitize($string)
{
   $s = $string;
   // Remove accents
   $s = strtr(utf8_decode($s), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
   $s = str_replace(array(" ", "°"), array("_", "o"), $s);
   $s = preg_replace('/[^a-zA-Z_0-9-\.]+/', "", $s);
   return mb_strtolower($s);
}

class CertiGen
{
   ///// Pdf locations /////
   static function getSchoolOutput($schoolID, $conf)
   {
      return $schoolID."/school-".$schoolID."-".md5($schoolID."-".CERTIGEN_SECRET);
   }

   static function getSchoolOutputURL($schoolID, $conf)
   {
      global $config;
      return $config->certificates->webServiceUrl . "/" . CERTIGEN_EXPORTDIR . '/'.$conf['folder'].'/' . self::getSchoolOutput($schoolID) . ".pdf";
   }

   static function getGroupOutput($groupID, $schoolID, $conf)
   {
      return $schoolID."/school-".$schoolID."-group-".$groupID."-".md5($schoolID."-".$groupID."-".CERTIGEN_SECRET);
   }

   static function getGroupOutputURL($groupID, $schoolID, $conf)
   {
      global $config;
      return $config->certificates->webServiceUrl . "/" . CERTIGEN_EXPORTDIR . '/'.$conf['folder'].'/' . self::getGroupOutput($groupID, $schoolID, $conf) . ".pdf";
   }
 
   ///// Generation queue ///// 
   static public $STATE_WAITING  = 'WAITING';
   static public $STATE_RUNNING  = 'RUNNING';
   static public $STATE_CANCELED = 'CANCELED';
   static public $STATE_STOPPED  = 'STOPPED';
   static public $STATE_FINISHED = 'FINISHED';

   static private function getArrayStates($query)
   {
      $all = array();
      foreach (get_class_vars("CertiGen") as $name => $value)
      {
         $param = ':state'.ucfirst(strtolower($value));
         if (preg_match("/STATE_/", $name) && preg_match("/$param/", $query))
            $all[$param] = $value;
      }
      return $all;
   }

   // Add a new request for the school in the queue
   // Will cancel every existing request
   static public function queueAdd($schoolID, $conf)
   {      
      global $db;
      // Cancel any current request
      self::queueCancel($schoolID);

      // Number of partipants
      $query = "
      SELECT
         COUNT(*) AS count
      FROM `contestant`, `team`,  `group`
      WHERE 
         `contestant`.teamID = `team`.ID AND
         `team`.groupID = `group`.ID AND
         `group`.schoolID = :schoolID AND
         `team`.`participationType` = 'Official' AND
         `group`.contestID IN (".implode(', ', $conf['contestIDs']).")
      ";
      $stmt = $db->prepare($query);
      $stmt->execute(array(':schoolID' => $schoolID));
      $nbStudents = $stmt->fetchObject()->count;

      if ($nbStudents == 0)
         return;

      $query = "
         INSERT INTO `certi_queue` (`schoolID`,`nbStudents`,`requestDate`,`state`)
         VALUES (:schoolID, :nbStudents, NOW(), :stateWaiting)
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(array_merge(array(':schoolID' => $schoolID, ':nbStudents' => $nbStudents), self::getArrayStates($query)));  
   }
   // Cancel the current request for the queue
   static public function queueCancel($schoolID)
   {
      global $db;
      // We only cancel a request if it hasn't already been processed
      $query = "
         UPDATE `certi_queue` 
         SET state = :stateCanceled
         WHERE
           schoolID = :schoolID AND
           (state = :stateWaiting OR state = :stateRunning)
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(array_merge(array(':schoolID' => $schoolID), self::getArrayStates($query))); 
   }


   // Get infos about the state of this school in the queue
   //
   // Returns 'false' if the school is not in the queue
   static public function queueState($schoolID)
   {
      global $db;

      // Get data about the last request
      $query = "
      SELECT 
         `certi_queue`.*
      FROM `certi_queue`
      WHERE
         schoolID = :schoolID
      ORDER BY requestDate DESC
      LIMIT 0, 1
      ";
      $stmt = $db->prepare($query);
      $stmt->execute(array_merge(array(':schoolID' => $schoolID), self::getArrayStates($query)));  
      $res = $stmt->fetchObject();
      if (!$res)
         return false;

      // Get time estimation
      $query = "
         SELECT
         (
            SELECT AVG(TIME_TO_SEC(TIMEDIFF(`endDate`, `startDate`)) / `nbStudents`)
            FROM `certi_queue`  
            WHERE `state` = :stateFinished
            ORDER BY endDate DESC
            LIMIT 0, 10
         ) 
         AS avgTimePerStudent,
         (
            SELECT SUM(`nbStudents`)
            FROM `certi_queue`
            WHERE
                (`state` = :stateWaiting OR `state` = :stateRunning) AND
                requestDate <= :requestDate             
         ) AS nbWaiting
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(array_merge(array(':requestDate' => $res->requestDate), self::getArrayStates($query)));  
      $row = $stmt->fetchObject();
      $res->avgTimePerStudent = $row->avgTimePerStudent;
      $res->nbWaiting = $row->nbWaiting;
      if ($res->nbWaiting == "")
         $res->nbWaiting = 0;
      
      return $res;
   }


   static public function queueGetNext()
   {
      global $db;
      self::queueClean();

      // Get the oldest waiting request
      $query = "
         SELECT 
            `certi_queue`.*
         FROM `certi_queue`
         WHERE
            state = :stateWaiting
         ORDER BY requestDate ASC, nbStudents ASC
        LIMIT 0, 1
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(array_merge(array(), self::getArrayStates($query)));
      $row = $stmt->fetchObject();

      return $row;
   }


   // Inform the queue the 'WAITING' request for the school is being processed
   static public function queueStarted($requestID)
   {
      global $db;
      // We only start a request if it was still waiting
      $query = "
         UPDATE `certi_queue` 
         SET
            state = :stateRunning,
            startDate = NOW()
         WHERE
           ID = :requestID AND
           state = :stateWaiting
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(array_merge(array(':requestID' => $requestID), self::getArrayStates($query)));

      // Exactly one row should have been modified
      return ($stmt->rowCount() == 1);
   }

   // Inform the queue the 'RUNNING' request for the school has been processed
   static public function queueFinished($requestID)
   {
      global $db;
      // We only finish a request if it was still running
      $query = "
         UPDATE `certi_queue` 
         SET
            state = :stateFinished,
            endDate = NOW()
         WHERE
           ID = :requestID AND
           state = :stateRunning
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(array_merge(array(':requestID' => $requestID), self::getArrayStates($query))); 

      // Exactly one row should have been modified
      return ($stmt->rowCount() == 1);
   }

   // Inform the queue the 'RUNNING' request for the school has been stopped (error)
   static public function queueStopped($requestID)
   {
      global $db;
      // We only stopped a request if it was still running
      $query = "
         UPDATE `certi_queue` 
         SET
            state = :stateStopped
         WHERE
           ID = :requestID AND
           state = :stateRunning
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(array_merge(array(':requestID' => $requestID), self::getArrayStates($query))); 

      // Exactly one row should have been modified
      return ($stmt->rowCount() == 1);
   }


   // Cancel all requsts running for more that one hour
   static public function queueClean()
   {
      global $db;
      // We only stopped a request if it was still running
      $query = "
         UPDATE `certi_queue`
         SET state = :stateStopped
         WHERE
            state = :stateRunning AND
            TIME_TO_SEC(TIMEDIFF(NOW(), startDate)) > 3600
         ";
      $stmt = $db->prepare($query);
      $stmt->execute(self::getArrayStates($query));
   }

}

// Useful functions : to be better integrated
// Retrieve schools / groups about the current user
// We use a limit on the number of school
// Mostly for admin : we won't show everything !!
function getGroupsData($conf)
{
   global $db;
   $query = "
      SELECT 
         `school`.`ID` AS `schoolID`,
         `school`.`name` AS `schoolName`,
         `group`.`ID` AS `groupID`, 
         `group`.`name` AS `groupName`,
         CONCAT(`user`.firstName, ' ', `user`.lastName) AS userName
      FROM `team`, `group`, `user`, `school`, `school_user`
      WHERE 
         `team`.groupID = `group`.ID AND
         `group`.`userID` = `user`.`ID` AND
         `group`.`schoolID` = `school`.`ID`  AND
         `school`.`ID` = `school_user`.schoolID AND
         `school_user`.userID = :userID AND
         `team`.`participationType` = 'Official' AND
         `group`.`contestID` IN (".implode(', ', $conf['contestIDs']).")
      GROUP BY `group`.ID
      ORDER BY schoolName ASC, groupName ASC

   ";
   $stmt = $db->prepare($query);
   $stmt->execute(array('userID' => $_SESSION["userID"]));

   $aSchools = array();
   while ($row = $stmt->fetchObject())
   {
      if (!isset($aSchools[$row->schoolID]))
         $aSchools[$row->schoolID] = (object)array(
            'id' => $row->schoolID,
            'name' => $row->schoolName,
            'url' => CertiGen::getSchoolOutputURL($row->schoolID, $conf),
            'groups' => array()
            );
      $aSchools[$row->schoolID]->groups[] = (object)array(
         'id' => $row->groupID,
         'name' => $row->groupName,                            
         'userName' => $row->userName,                            
         'url' => CertiGen::getGroupOutputURL($row->groupID, $row->schoolID, $conf),
         );
   }
   return $aSchools;
}
function getStates($aSchools)
{
   foreach ($aSchools as &$school)
   {
      $state = CertiGen::queueState($school->id);
      if (!$state)
         continue;
//      $state->state = "FINISHED";
      $school->nbGroups = count($school->groups);
      $school->nbStudents = $state->nbStudents;
      $school->state = $state->state;
      $school->requestDate = $state->requestDate;
      $school->startDate = $state->startDate;
      $school->endDate = $state->endDate;
      $school->waitTime = round((intval($state->nbWaiting) * floatval($state->avgTimePerStudent)) / 60);
      $school->nbWaiting = $state->nbWaiting;
      $school->avgTimePerStudent = $state->avgTimePerStudent;

      // The list of groups is not needed
      if ($school->state != CertiGen::$STATE_FINISHED)
         unset($school->groups);
   }
   return $aSchools;
}







/*
if(true)
{
for($i=30;$i<=70;$i++)
   CertiGen::queueAdd($i);
}
*/
?>

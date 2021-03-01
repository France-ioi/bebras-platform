<?php

function showError($message) {
   echo "<script>alert('".$message."')</script><div style='background-color:#F88;font-weight:bold;padding:10px'>".$message."</div>";
}
   
function generateCode($schoolID, $userID, $groupID, $lastName, $firstName, $grade) {
   global $db, $config;

   // Sanitize names
   list($firstName, $lastName, $saniValid, $trash) = DataSanitizer::formatUserNames($firstName, $lastName);

   $query = "SELECT `code` FROM algorea_registration WHERE schoolID = :schoolID AND userID = :userID AND firstName = :firstName AND lastName = :lastName AND grade = :grade";
   $stmt = $db->prepare($query);
   $stmt->execute(['userID' => $userID,
      'schoolID' => $schoolID,
      'firstName' => $firstName,
      'lastName' => $lastName,
      'grade' => $grade,
      ]);
   if ($row = $stmt->fetchObject()) {
      showError(sprintf(translate("codes_participant_exists"), $firstName, $lastName, $row->code));
      return null;
   }
   $category = "";
   if (isset($config->defaultCategory)) {
      $category = $config->defaultCategory;
   }
   if (($grade == -1) && isset($config->defaultTeacherCategory)) {
      $category = $config->defaultTeacherCategory;
   }
   $code = generateRandomCode();
   $query = "INSERT INTO algorea_registration (`firstName`, `lastName`, `genre`, `email`, `studentID`, `phoneNumber`, `zipCode`, `code`, `grade`, `schoolID`, `userID`, `groupID`, `category`) ".
      "VALUES (:firstName, :lastName, 0, '', '', '', '', :code, :grade, :schoolID, :userID, :groupID, '".$category."') ";
   $stmt = $db->prepare($query);
   $stmt->execute(['userID' => $userID,
      'schoolID' => $schoolID,
      'groupID' => $groupID,
      'firstName' => $firstName,
      'lastName' => $lastName,
      'grade' => $grade,
      'code' => $code         
      ]);
   return $code;
}

function generateRandomCode() {
   global $db;
   srand(time() + rand());
   $charsAllowed = "0123456789";
   $base = 'g';
   $query = "SELECT ID as nb FROM algorea_registration WHERE code = :code;";
   $stmt = $db->prepare($query);
   while(true) {
      $code = $base;
      for ($pos = 0; $pos < 14; $pos++) {
         $iChar = rand(0, strlen($charsAllowed) - 1);
         $code .= substr($charsAllowed, $iChar, 1);
      }
      $stmt->execute(array('code' => $code));
      $row = $stmt->fetchObject();
      if (!$row) {
         return $code;
      }
      error_log("Error, code ".$code." is already used");
   }
}

function getGroupInfo($groupID, $userID) {
   global $db;
   $query = "SELECT `group`.name as groupName, `contest`.name as contestName, `group`.userID, `group`.grade, `group`.schoolID, contest.allowFromHome ".
   "FROM `group` ".
   "JOIN contest ON `group`.contestID = contest.ID ".
   "WHERE `group`.ID = :groupID";

   $stmt = $db->prepare($query);
   $stmt->execute(['groupID' => $groupID]);

   $rowGroup = $stmt->fetchObject();
   if ($rowGroup == null) {
      echo "Erreur : identifiant de groupe invalide.";
      exit;
   }

   if ($rowGroup->userID != $userID) {
      echo "Erreur : vous n'êtes pas le propriétaire de ce groupe.";
      exit;
   }
   return $rowGroup;
}

?>

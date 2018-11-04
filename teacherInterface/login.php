<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

function saveLoginDate($db, $userID) {
   $query = "UPDATE `user` SET `lastLoginDate` = UTC_TIMESTAMP() WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($userID));
}

function loadSchoolUsers($db) {
  $query = "SELECT DISTINCT `user`.`firstName`, `user`.`lastName` FROM `user_user` ".
     " INNER JOIN `user` ON `user`.`ID` = `user_user`.`userID`".
     " WHERE `user_user`.`targetUserID` = :userID";
  $stmt = $db->prepare($query);
  $stmt->execute(array("userID" => $_SESSION["userID"]));
  $users = array();
  while ($row = $stmt->fetchObject()) {
     $users[] = $row;
  }
  return $users;
}

function logout() {
   restartSession();
   echo json_encode(array("success" => true));
}

function jsonUser($db, $row) {
   return json_encode(array("success" => true,
      "user" => array(
         "ID" => $row->ID,
         "isAdmin" => $row->isAdmin,
         "allowMultipleSchools" => $row->allowMultipleSchools,
         "gender" => $row->gender,
         "firstName" => $row->firstName,
         "lastName" => $row->lastName,
         "officialEmail" => $row->officialEmail,
         "alternativeEmail" => $row->alternativeEmail,
         "officialEmailValidated" => $row->officialEmailValidated,
         "alternativeEmailValidated" => $row->alternativeEmailValidated,
         "awardPrintingDate" => $row->awardPrintingDate,
         ),
      "alternativeEmailValidated" => $row->alternativeEmailValidated,
      "schoolUsers" => loadSchoolUsers($db)
   ));
}

function isLogged($db) {
   if (!isset($_SESSION["userID"])) {
      echo json_encode(array("success" => false));
   } else {
      $query = "SELECT * FROM `user` WHERE `ID` = ?";
      $stmt = $db->prepare($query);
      $stmt->execute(array($_SESSION["userID"]));
      $row = $stmt->fetchObject();
      if (!$row) {
         echo json_encode(array("success" => false, "message" => translate("login_invalid_identifier")));
         return;
      }
      saveLoginDate($db, $row->ID);
      echo jsonUser($db, $row);
   }
}

function login($db, $email, $password) {
   global $config;
   restartSession();

   $query = "SELECT * FROM `user` WHERE (`officialEmail` = ? OR `alternativeEmail` = ?)";
   $stmt = $db->prepare($query);
   $stmt->execute(array($email, $email));
   while ($row = $stmt->fetchObject()) {
      $passwordMd5 = computePasswordMD5($password, $row->salt);
      $genericMd5 = computePasswordMD5($password, "");
      if (($passwordMd5 === $row->passwordMd5) || ($genericMd5 == $config->teacherInterface->genericPasswordMd5)) {
         if (($row->validated === "1") ||
              (isset($config->teacherInterface->acceptNonValidatedUsers) &&
               $config->teacherInterface->acceptNonValidatedUsers &&
               ($row->officialEmailValidated === "1"))) {
            saveLoginDate($db, $row->ID);
            $_SESSION["userID"] = $row->ID;
            $_SESSION["isAdmin"] = $row->isAdmin;
            if ($row->isAdmin) {
               $_SESSION["userType"] = "admin";
            } else {
               $_SESSION["userType"] = "user";
            }

            echo jsonUser($db, $row);
            return;
         } else if ($row->officialEmailValidated === "1") {
            $message = "<p>".sprintf(translate("login_user_not_validated"), $config->email->sInfoAddress)." ".
               sprintf(translate("login_manual_validation_required"), $config->email->sInfoAddress)."</p>";
         } else if ($row->officialEmail != "") {
            $message = "<p>".sprintf(translate("login_email_not_validated"), $row->officialEmail, $config->email->sInfoAddress)."</p>";
         } else {
            $message = "<p>".translate("login_no_official_email")." ".
               sprintf(translate("login_manual_validation_required"), $config->email->sInfoAddress)."</p>";"</p>";
         }
         echo json_encode(array("success" => false, "message" => $message));
         return;
      }   
   }
   echo json_encode(array("success" => false));
}

if (isset($_REQUEST["logout"])) {
   logout();
} else if (isset($_REQUEST["isLogged"])) {
   isLogged($db);
} else if (isset($_REQUEST["email"])) {
   login($db, $_REQUEST["email"], $_REQUEST["password"]);
} else {
   error_log("Invalid login request. ".json_encode($_REQUEST));
}
unset($db);

?>

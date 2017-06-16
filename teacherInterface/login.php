<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("login_module/auth.php");

function saveLoginDate($db, $userID) {
   $query = "UPDATE `user` SET `lastLoginDate` = UTC_TIMESTAMP() WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($userID));
}

function logout() {
   restartSession();
   echo json_encode(array("success" => true));
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
         echo json_encode(array("success" => false, "message" => "Identifiant invalide"));
         return;
      }
      saveLoginDate($db, $row->ID);
      echo  json_encode(jsonUser($db, $row, ['success'=> true]));
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
         if ((($row->officialEmail === $email) && ($row->officialEmailValidated === "1")) ||
             ($row->validated === "1")) {
            saveLoginDate($db, $row->ID);
            setUserSession($row);
            echo json_encode(jsonUser($db, $row, ['success'=> true]));
            return;
         } else {
            $message = "<p>Vos identifiants sont valides mais votre adresse email académique n'a pas encore été validée. Vous avez dû recevoir un mail après votre inscription avec un lien de validation, vérifiez éventuellement dans les courriers indésirables de votre boîte mail. Si vous n'avez rien reçu, ou si vous n'avez pas d'adresse académique qui fonctionne, contactez nous : ".$config->email->sInfoAddress."</p>";
            echo json_encode(array("success" => false, "message" => $message));
            return;
         }
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

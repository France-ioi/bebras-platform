<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */
/*
A simple API for certificates generation queue.
*/
require_once("../shared/common.php");
require_once("commonAdmin.php");
require_once("../certificates/certiGen.inc.php");

if (!isset($_SESSION["userID"])) {
   echo "Votre session a expiré, veuillez vous reconnecter.";
   exit;
}
if (!isset($_REQUEST["action"])) {
   echo "Erreur d'appel de la page.";
   exit;
}
header("Content-Type: application/json; charset=utf-8");

$aSchools = getGroupsData($config->certificates_confs[0]);
if ($_REQUEST["action"] != "state") {
   if (!(isset($_REQUEST["schoolID"]) && in_array($_REQUEST["schoolID"], array_keys($aSchools)))) {
      echo "Etablissement invalide";
      exit;
   }
   if ($_REQUEST["action"] == "add")
      CertiGen::queueAdd($_REQUEST["schoolID"], $config->certificates_confs[0]);
   if ($_REQUEST["action"] == "cancel")
      CertiGen::queueCancel($_REQUEST["schoolID"]);
   // When the queue is modified we want to immediately return the state
   // This allows us to have one less request and an immediate update of the page
   $_REQUEST["action"] = "state";
}

// We only want to know the current state of each school
if ($_REQUEST["action"] == "state") {
   $aSchools = getStates($aSchools); 
   echo json_encode($aSchools); 
   exit;
}
?>